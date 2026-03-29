<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Business;
use App\Category;
use App\Contact;
use App\Product;
use App\ProductJobOrder;
use App\Restaurant\Booking;
use App\Transaction;
use App\TransactionSellLine;
use App\PurchaseLine;
use App\TransactionSellLinesPurchaseLines;
use App\TransactionPayment;
use App\Unit;
use App\VariationLocationDetails;
use App\BusinessLocation;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Repair\Entities\ContactDevice;
use Modules\Repair\Entities\DeviceModel;
use Modules\Repair\Entities\JobSheet;

class RepairOrderImportController extends ApiController
{
    protected $transactionUtil;
    protected $productUtil;
    protected $commonUtil;
    protected $brandCache = [];
    protected $modelCache = [];
    protected $contactCache = [];
    protected $productCache = [];

    public function __construct(
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        Util $commonUtil
    ) {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->commonUtil = $commonUtil;
        parent::__construct();
    }

    /**
     * Import repair orders from Excel file.
     * 
     * POST connector/api/import/repair-orders
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importRepairOrders(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls',
                'location_id' => 'required|integer|exists:business_locations,id',
                'dry_run' => 'nullable|boolean',
            ])->validate();

            $user = Auth::user();
            $results = $this->runImport(
                $request->file('file'),
                (int) $validated['location_id'],
                (bool) ($validated['dry_run'] ?? false),
                $user
            );

            return response()->json($results);
        } catch (\Exception $e) {
            Log::error('Repair order import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showImportForm()
    {
        $user = Auth::user();
        $locations = BusinessLocation::forDropdown($user->business_id, false, false, true, true);

        return view('connector::import.repair_orders', compact('locations'));
    }

    public function handleImportForm(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:xlsx,xls',
            'location_id' => 'required|integer|exists:business_locations,id',
            'dry_run' => 'nullable|boolean',
        ])->validate();

        try {
            $user = Auth::user();
            $locations = BusinessLocation::forDropdown($user->business_id, false, false, true, true);

            $allResults = [
                'total_entries' => 0,
                'created' => [],
                'errors' => [],
                'succeeded_entries' => 0,
                'failed_entries' => 0,
                'summary' => [
                    'contacts' => ['success' => 0, 'failed' => 0],
                    'brands' => ['success' => 0, 'failed' => 0],
                    'models' => ['success' => 0, 'failed' => 0],
                    'devices' => ['success' => 0, 'failed' => 0],
                    'bookings' => ['success' => 0, 'failed' => 0],
                    'job_sheets' => ['success' => 0, 'failed' => 0],
                    'transactions' => ['success' => 0, 'failed' => 0],
                    'products' => ['success' => 0, 'failed' => 0],
                    'product_joborder' => ['success' => 0, 'failed' => 0],
                ],
                'files_processed' => [],
            ];

            foreach ($request->file('files') as $file) {
                try {
                    $results = $this->runImport(
                        $file,
                        (int) $validated['location_id'],
                        $request->boolean('dry_run', false),
                        $user
                    );

                    $allResults['total_entries'] += $results['total_entries'];
                    $allResults['succeeded_entries'] += $results['succeeded_entries'];
                    $allResults['failed_entries'] += $results['failed_entries'];
                    $allResults['created'] = array_merge($allResults['created'], $results['created']);
                    $allResults['errors'] = array_merge($allResults['errors'], $results['errors']);

                    foreach ($allResults['summary'] as $key => $val) {
                        $allResults['summary'][$key]['success'] += $results['summary'][$key]['success'];
                        $allResults['summary'][$key]['failed'] += $results['summary'][$key]['failed'];
                    }

                    $allResults['files_processed'][] = [
                        'name' => $file->getClientOriginalName(),
                        'entries' => $results['total_entries'],
                        'succeeded' => $results['succeeded_entries'],
                        'failed' => $results['failed_entries'],
                    ];
                } catch (\Exception $e) {
                    $allResults['files_processed'][] = [
                        'name' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $allResults['message'] = empty($allResults['errors'])
                ? 'All files imported successfully.'
                : 'Import finished with some errors. Successful entries were saved; failed entries were skipped.';

            $results = $allResults;
            return view('connector::import.repair_orders', compact('locations', 'results'));
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('import_error', $e->getMessage());
        }
    }

    private function runImport($file, int $location_id, bool $dry_run, $user): array
    {
        $business_id = $user->business_id;
        $user_id = $user->id;
        
        $allSheets = Excel::toArray([], $file);
        
        if (empty($allSheets)) {
            throw new \Exception('Excel file is empty');
        }

        // Global date tracking for carry-forward across all entries
        $globalVisitDate = null;
        $globalReceiptDate = null;
        $globalDeliveryDate = null;
        $this->contactCache = [];
        $this->productCache = [];

        $results = [
            'total_entries' => 0,
            'created' => [],
            'errors' => [],
            'succeeded_entries' => 0,
            'failed_entries' => 0,
            'summary' => [
                'contacts' => ['success' => 0, 'failed' => 0],
                'brands' => ['success' => 0, 'failed' => 0],
                'models' => ['success' => 0, 'failed' => 0],
                'devices' => ['success' => 0, 'failed' => 0],
                'bookings' => ['success' => 0, 'failed' => 0],
                'job_sheets' => ['success' => 0, 'failed' => 0],
                'transactions' => ['success' => 0, 'failed' => 0],
                'products' => ['success' => 0, 'failed' => 0],
                'product_joborder' => ['success' => 0, 'failed' => 0],
            ],
        ];

        // Process all sheets in the Excel file
        foreach ($allSheets as $sheetIndex => $rows) {
            if (count($rows) < 2) {
                continue; // Skip empty sheets
            }

            // Reset global dates for each new sheet
            $globalVisitDate = null;
            $globalReceiptDate = null;
            $globalDeliveryDate = null;

            $headers = $this->parseHeaders($rows[0]);
            array_shift($rows);
            
            $entries = $this->groupRowsByEntry($rows, $headers);
            $results['total_entries'] += count($entries);

            if ($dry_run) {
                $results['dry_run'] = true;
                $results['message'] = 'Dry run completed. No records were created.';
                if (!isset($results['entries_preview'])) {
                    $results['entries_preview'] = [];
                }
                foreach ($entries as $entryNum => $entryRows) {
                    $preview = $this->parseEntryData($entryRows, $headers, $globalVisitDate, $globalReceiptDate, $globalDeliveryDate);
                    $results['entries_preview'][] = [
                        'entry' => $entryNum,
                        'owner_name' => $preview['owner_name'],
                        'phone' => $preview['phone'],
                        'car_type' => $preview['car_type'],
                        'visit_date' => $preview['visit_date'],
                        'spare_parts_count' => count($preview['spare_parts']),
                        'spare_parts' => $preview['spare_parts'],
                    ];
                }
                continue;
            }

            foreach ($entries as $entryNum => $entryRows) {
            $localStats = [
                'contacts' => ['success' => 0, 'failed' => 0],
                'brands' => ['success' => 0, 'failed' => 0],
                'models' => ['success' => 0, 'failed' => 0],
                'devices' => ['success' => 0, 'failed' => 0],
                'bookings' => ['success' => 0, 'failed' => 0],
                'job_sheets' => ['success' => 0, 'failed' => 0],
                'transactions' => ['success' => 0, 'failed' => 0],
                'products' => ['success' => 0, 'failed' => 0],
                'product_joborder' => ['success' => 0, 'failed' => 0],
            ];

            try {
                DB::beginTransaction();
                $entryData = $this->parseEntryData($entryRows, $headers, $globalVisitDate, $globalReceiptDate, $globalDeliveryDate);
                $result = $this->processEntry($entryData, $business_id, $location_id, (int) $user->id, $localStats);
                DB::commit();

                $results['succeeded_entries']++;

                foreach ($localStats as $k => $v) {
                    $results['summary'][$k]['success'] += $v['success'];
                    $results['summary'][$k]['failed'] += $v['failed'];
                }

                $results['created'][] = [
                    'entry' => $entryNum,
                    'contact_id' => $result['contact_id'],
                    'device_id' => $result['device_id'],
                    'booking_id' => $result['booking_id'],
                    'job_sheet_id' => $result['job_sheet_id'],
                    'transaction_id' => $result['transaction_id'],
                    'products_created' => $result['products_created'],
                ];
            } catch (\Exception $e) {
                DB::rollBack();

                $results['failed_entries']++;

                $results['errors'][] = [
                    'entry' => $entryNum,
                    'error' => $e->getMessage(),
                ];
                Log::error('Import entry error', [
                    'entry' => $entryNum,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            }
        }

        $results['message'] = empty($results['errors'])
            ? 'Import completed successfully.'
            : 'Import finished with some errors. Successful entries were saved; failed entries were skipped.';

        return $results;
    }

    /**
     * Parse header row to map column names to indices
     */
    private function parseHeaders(array $headerRow): array
    {
        $mapping = [];
        $columnMap = [
            // Entry/Item number
            'البند' => 'entry',
            'item' => 'entry',
            'entry' => 'entry',
            
            // Visit date
            'تاريخ الزيارة' => 'visit_date',
            'visit date' => 'visit_date',
            
            // Receipt date (car received)
            'تاريخ استلام السياره' => 'receipt_date',
            'car receipt date' => 'receipt_date',
            
            // Delivery date (car returned) - 2025
            'تاريخ تسليم السياره' => 'delivery_date',
            
            // Owner name
            'اسم المالك' => 'owner_name',
            'owner name' => 'owner_name',
            
            // Sales rep / Technician - 2025
            'المندوب' => 'tech',
            'tech' => 'tech',
            
            // Phone
            'رقم التليفون' => 'phone',
            'phone' => 'phone',
            'phone number' => 'phone',
            
            // Car type/brand
            'نوع السيارة' => 'car_type',
            'car type' => 'car_type',
            
            // Plate number (multiple variations)
            'رقم اللوحة' => 'plate_number',
            'رقم السياره' => 'plate_number',
            'plate number' => 'plate_number',
            
            // Chassis (multiple variations)
            'شاسية' => 'chassis',
            'الشاسيه' => 'chassis',
            'ش' => 'chassis',
            '[' => 'chassis',  // 2023 garbage character for chassis
            'chassis' => 'chassis',
            
            // Engine
            'موتور' => 'engine',
            'موتور ' => 'engine',  // with trailing space
            'engine' => 'engine',
            
            // Model
            'موديل' => 'model',
            'model' => 'model',
            
            // Kilometers
            'km' => 'km',
            
            // Spare parts
            'قطع الغيار' => 'spare_parts',
            'قطع غيار' => 'spare_parts',
            'spare parts' => 'spare_parts',
            
            // Part number - 2024/2025
            'رقم القطعه' => 'part_number',
            'part number' => 'part_number',
            
            // Quantity
            'الكميه' => 'quantity',
            'quantity' => 'quantity',
            
            // Retail price
            'retail price' => 'retail_price',
            'سعر التجزئة' => 'retail_price',
            'total price' => 'retail_price',
            'total  price' => 'retail_price',
            
            // Labor
            'مصنعيات' => 'labor',
            'labor' => 'labor',
            
            // Discount - 2024/2025
            'خصم' => 'discount',
            'discount' => 'discount',
            
            // Total (multiple spellings)
            'الاجمالى' => 'total',
            'الاجمالي' => 'total',
            'الصافى' => 'total',
            'الاجمالى / الصافى / الاجمالي' => 'total',
            'total' => 'total',
            
            // Supplier - 2025
            'supplier' => 'supplier',
            
            // Parts cost / Cost
            'parts cost' => 'parts_cost',
            'cost' => 'parts_cost',
            
            // Gross profit
            'gp' => 'gp',
            'gross profit' => 'gp',
            'total gp' => 'total_gp',
            
            // Notes
            'notes' => 'notes',
            
            // Prices
            'الاسعار' => 'prices',
            'prices' => 'prices',
        ];

        foreach ($headerRow as $index => $header) {
            if ($header === null) continue;
            $normalized = strtolower(trim((string) $header));
            if (isset($columnMap[$normalized])) {
                $mapping[$columnMap[$normalized]] = $index;
            }
        }

        // Fallback: if spare_parts not found but we have km and quantity, infer spare_parts is between them
        if (!isset($mapping['spare_parts']) && isset($mapping['km']) && isset($mapping['quantity'])) {
            $kmIndex = $mapping['km'];
            $qtyIndex = $mapping['quantity'];
            // spare_parts should be the column between km and quantity (or right after km)
            if ($qtyIndex > $kmIndex + 1) {
                $mapping['spare_parts'] = $kmIndex + 1;
            }
        }

        // Fallback: if spare_parts still not found, look for column with whitespace/garbage header between known columns
        if (!isset($mapping['spare_parts'])) {
            foreach ($headerRow as $index => $header) {
                $trimmed = trim((string) $header);
                // Check if it's whitespace, garbage chars, or empty-ish
                if (empty($trimmed) || preg_match('/^[\s\[\]\\\\0-9]+$/', $trimmed)) {
                    // Check if this column has data that looks like part names (Arabic text)
                    // For now, just assign if it's after km column
                    if (isset($mapping['km']) && $index > $mapping['km'] && $index < ($mapping['quantity'] ?? PHP_INT_MAX)) {
                        $mapping['spare_parts'] = $index;
                        break;
                    }
                }
            }
        }

        // Log detected headers for debugging
        Log::info('Parsed headers', [
            'raw_headers' => $headerRow,
            'mapping' => $mapping,
        ]);

        return $mapping;
    }

    /**
     * Group rows by entry number (numeric entry starts new group, '-' continues previous)
     * For files without entry column, group by owner_name changes
     * For files with entry column but null values, group by owner_name changes
     */
    private function groupRowsByEntry(array $rows, array $headers): array
    {
        $entries = [];
        $currentEntry = null;
        $hasEntryColumn = isset($headers['entry']);
        $entryIndex = $headers['entry'] ?? 0;
        $ownerIndex = $headers['owner_name'] ?? null;
        $visitDateIndex = $headers['visit_date'] ?? null;
        $autoEntryCounter = 1;
        $lastOwnerName = null;
        $lastVisitDate = null;
        $useOwnerGrouping = false;

        foreach ($rows as $row) {
            // Skip completely empty rows
            if ($this->isEmptyRow($row)) {
                continue;
            }

            if ($hasEntryColumn && !$useOwnerGrouping) {
                // Standard mode: use entry column
                $entryValue = trim((string) ($row[$entryIndex] ?? ''));
                
                if (is_numeric($entryValue)) {
                    $currentEntry = (int) $entryValue;
                    $entries[$currentEntry] = [$row];
                } elseif ($currentEntry !== null && ($entryValue === '-' || $entryValue === '')) {
                    // Continuation row for spare parts
                    $entries[$currentEntry][] = $row;
                } elseif (empty($entryValue) || $entryValue === 'null') {
                    // Entry column is null/empty - switch to owner grouping
                    $useOwnerGrouping = true;
                    // Fall through to owner grouping logic
                }
            }

            if ($useOwnerGrouping || !$hasEntryColumn) {
                // Group by owner_name AND visit_date changes
                $currentOwnerName = $ownerIndex !== null ? trim((string) ($row[$ownerIndex] ?? '')) : '';
                $currentVisitDate = $visitDateIndex !== null ? trim((string) ($row[$visitDateIndex] ?? '')) : '';
                
                // New entry if owner name is present and different from last, OR if visit date changed
                // Each occurrence of same contact on same date = separate transaction
                if (!empty($currentOwnerName) && $currentOwnerName !== '-' && 
                    ($currentOwnerName !== $lastOwnerName || $currentVisitDate !== $lastVisitDate)) {
                    $currentEntry = $autoEntryCounter++;
                    $entries[$currentEntry] = [$row];
                    $lastOwnerName = $currentOwnerName;
                    $lastVisitDate = $currentVisitDate;
                } elseif ($currentEntry !== null && !empty($currentOwnerName) && $currentOwnerName !== '-') {
                    // Check if this is a continuation row (spare part) or new occurrence
                    // If owner name and date are same, it's a spare part continuation
                    if ($currentOwnerName === $lastOwnerName && $currentVisitDate === $lastVisitDate) {
                        $entries[$currentEntry][] = $row;
                    } else {
                        // Different owner or date = new entry
                        $currentEntry = $autoEntryCounter++;
                        $entries[$currentEntry] = [$row];
                        $lastOwnerName = $currentOwnerName;
                        $lastVisitDate = $currentVisitDate;
                    }
                } elseif ($currentEntry !== null) {
                    // Continuation row (same owner and date = spare part row)
                    $entries[$currentEntry][] = $row;
                }
            }
        }

        return $entries;
    }

    /**
     * Check if a row is empty
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '' && trim((string) $cell) !== '-') {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse entry data from grouped rows
     */
    private function parseEntryData(array $rows, array $headers, &$globalVisitDate = null, &$globalReceiptDate = null, &$globalDeliveryDate = null): array
    {
        $firstRow = $rows[0];
        
        // Parse dates from first row, use global carry-forward if empty
        $parsedVisitDate = $this->parseDate($this->getCellValue($firstRow, $headers, 'visit_date'));
        $parsedReceiptDate = $this->parseDate($this->getCellValue($firstRow, $headers, 'receipt_date'));
        $parsedDeliveryDate = $this->parseDate($this->getCellValue($firstRow, $headers, 'delivery_date'));
        
        // Use global dates if parsed dates are null, otherwise update global dates
        $visitDate = $parsedVisitDate ?: $globalVisitDate;
        $receiptDate = $parsedReceiptDate ?: $globalReceiptDate;
        $deliveryDate = $parsedDeliveryDate ?: $globalDeliveryDate;
        
        // Update global dates if we found new non-null dates
        if ($parsedVisitDate) $globalVisitDate = $parsedVisitDate;
        if ($parsedReceiptDate) $globalReceiptDate = $parsedReceiptDate;
        if ($parsedDeliveryDate) $globalDeliveryDate = $parsedDeliveryDate;
        
        // Debug: Log parsed dates
        Log::info('Parsed dates for entry', [
            'owner_name' => $this->getCellValue($firstRow, $headers, 'owner_name'),
            'raw_visit_date' => $this->getCellValue($firstRow, $headers, 'visit_date'),
            'raw_receipt_date' => $this->getCellValue($firstRow, $headers, 'receipt_date'),
            'parsed_visit_date' => $visitDate,
            'parsed_receipt_date' => $receiptDate,
            'parsed_delivery_date' => $deliveryDate,
            'headers_mapping' => $headers,
            'receipt_date_index' => $headers['receipt_date'] ?? 'NOT_FOUND',
        ]);
        
        $data = [
            'entry' => $this->getCellValue($firstRow, $headers, 'entry'),
            'visit_date' => $visitDate,
            'receipt_date' => $receiptDate,
            'delivery_date' => $deliveryDate,
            'owner_name' => $this->getCellValue($firstRow, $headers, 'owner_name'),
            'tech' => $this->getCellValue($firstRow, $headers, 'tech'),
            'phone' => $this->normalizePhone($this->getCellValue($firstRow, $headers, 'phone')),
            'car_type' => $this->getCellValue($firstRow, $headers, 'car_type'),
            'plate_number' => $this->getCellValue($firstRow, $headers, 'plate_number'),
            'chassis' => $this->getCellValue($firstRow, $headers, 'chassis'),
            'engine' => $this->getCellValue($firstRow, $headers, 'engine'),
            'model' => $this->getCellValue($firstRow, $headers, 'model'),
            'km' => $this->getCellValue($firstRow, $headers, 'km'),
            'notes' => $this->getCellValue($firstRow, $headers, 'notes'),
            'discount' => $this->parseNumber($this->getCellValue($firstRow, $headers, 'discount')),
            'spare_parts' => [],
        ];

        // Parse spare parts from all rows, carrying forward dates if empty
        foreach ($rows as $row) {
            $partName = $this->getCellValue($row, $headers, 'spare_parts');
            if ($partName && $partName !== '-') {
                // Carry forward dates if not found in this row
                $rowVisitDate = $this->parseDate($this->getCellValue($row, $headers, 'visit_date'));
                $rowReceiptDate = $this->parseDate($this->getCellValue($row, $headers, 'receipt_date'));
                $rowDeliveryDate = $this->parseDate($this->getCellValue($row, $headers, 'delivery_date'));
                
                if (!$rowVisitDate) $rowVisitDate = $visitDate;
                if (!$rowReceiptDate) $rowReceiptDate = $receiptDate;
                if (!$rowDeliveryDate) $rowDeliveryDate = $deliveryDate;
                
                // Update carried forward dates for next iteration
                if ($rowVisitDate) $visitDate = $rowVisitDate;
                if ($rowReceiptDate) $receiptDate = $rowReceiptDate;
                if ($rowDeliveryDate) $deliveryDate = $rowDeliveryDate;
                
                $quantity = $this->parseQuantity($this->getCellValue($row, $headers, 'quantity'));
                
                // If quantity is more than 50, set it to 1
                if ($quantity > 50) {
                    $quantity = 1;
                }
                $retailPrice = $this->parseNumber($this->getCellValue($row, $headers, 'retail_price'));
                $labor = $this->parseNumber($this->getCellValue($row, $headers, 'labor'));
                $partsCost = $this->parseNumber($this->getCellValue($row, $headers, 'parts_cost'));
                $partNumber = $this->getCellValue($row, $headers, 'part_number');
                $supplier = $this->getCellValue($row, $headers, 'supplier');
                $rowTotal = $this->parseNumber($this->getCellValue($row, $headers, 'total'));

                $data['spare_parts'][] = [
                    'name' => $partName,
                    'quantity' => $quantity > 0 ? $quantity : 1,
                    'retail_price' => $retailPrice,
                    'labor' => $labor,
                    'parts_cost' => $partsCost,
                    'part_number' => $partNumber,
                    'supplier' => $supplier,
                    'row_total' => $rowTotal,
                ];
            }
        }
        
        // Update data with final carried forward dates
        $data['visit_date'] = $visitDate;
        $data['receipt_date'] = $receiptDate;
        $data['delivery_date'] = $deliveryDate;

        // Check for labor-only entries (no spare parts but has labor cost)
        if (empty($data['spare_parts'])) {
            $labor = $this->parseNumber($this->getCellValue($firstRow, $headers, 'labor'));
            if ($labor > 0) {
                $data['spare_parts'][] = [
                    'name' => 'مصنعيات - ' . ($data['owner_name'] ?: 'Labor'),
                    'quantity' => 1,
                    'retail_price' => $labor,
                    'labor' => $labor,
                    'parts_cost' => 0,
                    'part_number' => null,
                    'supplier' => null,
                ];
            }
        }

        return $data;
    }

    /**
     * Get cell value by header key
     */
    private function getCellValue(array $row, array $headers, string $key)
    {
        if (!isset($headers[$key])) {
            return null;
        }
        $value = $row[$headers[$key]] ?? null;
        if ($value === null || trim((string) $value) === '-') {
            return null;
        }
        return trim((string) $value);
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($value): ?string
    {
        if (!$value || $value === '-') {
            return null;
        }

        // Handle Excel numeric date
        if (is_numeric($value)) {
            try {
                // Excel for Windows uses 1900 date system, Excel for Mac uses 1904
                // Try Windows first (1900 system)
                $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $excelDate->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                    return null;
            }
        }

        // Try common date formats
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Normalize phone number
     */
    private function normalizePhone($value): ?string
    {
        if (!$value || $value === '-') {
            return null;
        }
        // Remove non-numeric characters except leading +
        $phone = preg_replace('/[^0-9+]/', '', (string) $value);
        return $phone ?: null;
    }

    /**
     * Parse quantity (handles "X لتر" format)
     */
    private function parseQuantity($value): float
    {
        if (!$value) return 1;
        // Extract first number from string like "6 لتر"
        if (preg_match('/(\d+\.?\d*)/', (string) $value, $matches)) {
            return (float) $matches[1];
        }
        return 1;
    }

    /**
     * Parse number from cell
     */
    private function parseNumber($value): float
    {
        if (!$value || $value === '-') return 0;
        return (float) preg_replace('/[^0-9.]/', '', (string) $value);
    }

    /**
     * Process a single entry and create all records
     */
    private function processEntry(array $data, int $business_id, int $location_id, int $user_id, array &$stats): array
    {
        $result = [
            'contact_id' => null,
            'device_id' => null,
            'booking_id' => null,
            'job_sheet_id' => null,
            'transaction_id' => null,
            'products_created' => [],
        ];

        // 1. Find or create contact
        try {
            $contact = $this->findOrCreateContact($data, $business_id, $user_id, $stats);
        } catch (\Exception $e) {
            $stats['contacts']['failed']++;
            throw $e;
        }
        $result['contact_id'] = $contact->id;

        // 2. Find or create car brand and model
        try {
            $brandId = $this->findOrCreateBrand($data['car_type'], $business_id, $user_id, $stats);
        } catch (\Exception $e) {
            $stats['brands']['failed']++;
            throw $e;
        }
        try {
            $modelId = $this->findOrCreateModel($data['model'], $brandId, $business_id, $user_id, $stats);
        } catch (\Exception $e) {
            $stats['models']['failed']++;
            throw $e;
        }

        // 3. Find or create car (contact_device)
        try {
            $device = $this->findOrCreateDevice($data, $contact->id, $brandId, $modelId, $stats);
        } catch (\Exception $e) {
            $stats['devices']['failed']++;
            throw $e;
        }
        $result['device_id'] = $device->id;

        // 4. Create booking
        try {
            $booking = $this->createBooking($data, $contact->id, $device->id, $business_id, $location_id, $user_id, $stats);
        } catch (\Exception $e) {
            $stats['bookings']['failed']++;
            throw $e;
        }
        $result['booking_id'] = $booking->id;

        // 5. Create job sheet
        try {
            $jobSheet = $this->createJobSheet($data, $booking, $contact->id, $business_id, $location_id, $user_id, $stats);
        } catch (\Exception $e) {
            $stats['job_sheets']['failed']++;
            throw $e;
        }
        $result['job_sheet_id'] = $jobSheet->id;

        // 6. Create transaction
        $transactionDate = $data['visit_date'] ?: ($data['receipt_date'] ?: Carbon::now()->toDateTimeString());
        
        // Calculate final total: use total from first row if available, otherwise sum retail prices
        $partsTotal = 0;
        $laborTotal = 0;

        foreach ($data['spare_parts'] as $part) {
            $name = (string) ($part['name'] ?? '');
            $qty = isset($part['quantity']) ? (float) $part['quantity'] : 1;
            $price = isset($part['retail_price']) ? (float) $part['retail_price'] : 0;
            $labor = isset($part['labor']) ? (float) $part['labor'] : 0;

            $laborTotal += $labor;

            if (! $this->isServiceKeyword($name)) {
                $partsTotal += ($qty * $price);
            }
        }

        // Apply discount if present
        $discount = isset($data['discount']) ? (float) $data['discount'] : 0;
        $finalTotal = max(0, ($partsTotal + $laborTotal) - $discount);
        
        // Skip transaction if total exceeds 500000 (likely data error)
        if ($finalTotal > 500000) {
            Log::warning('Transaction total exceeds 500000, skipping', [
                'contact' => $data['owner_name'],
                'total' => $finalTotal,
                'parts_total' => $partsTotal,
                'labor_total' => $laborTotal,
            ]);
            $finalTotal = 0;
            $partsTotal = 0;
            $laborTotal = 0;
        }
        
        try {
            $transaction = $this->createTransaction(
                $jobSheet,
                $device,
                $contact->id,
                $business_id,
                $location_id,
                $user_id,
                $stats,
                $transactionDate,
                (float) $finalTotal,
                (float) $discount
            );
        } catch (\Exception $e) {
            $stats['transactions']['failed']++;
            throw $e;
        }
        $result['transaction_id'] = $transaction->id;

        // 7. Create products and product_joborder entries
        $purchaseLineItems = [];
        foreach ($data['spare_parts'] as $partData) {
            if ($this->isServiceKeyword((string) ($partData['name'] ?? ''))) {
                continue;
            }

            try {
                $product = $this->findOrCreateProduct($partData, $business_id, $user_id, $location_id, $stats);
            } catch (\Exception $e) {
                $stats['products']['failed']++;
                throw $e;
            }

            // Adjust quantity: if over 100, set to 1
            $adjustedQuantity = (float) $partData['quantity'];
            if ($adjustedQuantity > 100) {
                $adjustedQuantity = 1;
            }

            $retailPrice = isset($partData['retail_price']) ? (float) $partData['retail_price'] : 0;
            $sellUnitPrice = $retailPrice;
            $sellQty = $adjustedQuantity;

            // Create transaction sell line (so parts appear in transaction overview/invoice)
            try {
                $this->createTransactionSellLine($transaction, $product->id, $sellQty, (float) $sellUnitPrice);
            } catch (\Exception $e) {
                throw $e;
            }

            // Collect purchase line items (skip services)
            if (!$this->isServiceKeyword($partData['name'])) {
                $purchaseLineItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $adjustedQuantity,
                    'retail_price' => (float) $partData['retail_price'],
                ];
            }

            $result['products_created'][] = [
                'product_id' => $product->id,
                'name' => $partData['name'],
            ];

            // Create product_joborder entry (use adjusted quantity)
            try {
                DB::table('product_joborder')->insert([
                    'job_order_id' => $jobSheet->id,
                    'product_id' => $product->id,
                    'quantity' => $sellQty,
                    'price' => $sellUnitPrice,
                    'delivered_status' => 1,
                    'out_for_deliver' => 1,
                    'client_approval' => 1,
                    'product_status' => 'black',
                ]);
                $stats['product_joborder']['success']++;
            } catch (\Exception $e) {
                $stats['product_joborder']['failed']++;
                throw $e;
            }
        }

        if ($laborTotal > 0) {
            $laborProductData = [
                'name' => 'مصنعيات',
                'quantity' => 1,
                'retail_price' => $laborTotal,
                'labor' => $laborTotal,
                'parts_cost' => 0,
                'part_number' => null,
                'supplier' => null,
            ];

            try {
                $laborProduct = $this->findOrCreateProduct($laborProductData, $business_id, $user_id, $location_id, $stats);
                $this->createTransactionSellLine($transaction, $laborProduct->id, 1, (float) $laborTotal);

                DB::table('product_joborder')->insert([
                    'job_order_id' => $jobSheet->id,
                    'product_id' => $laborProduct->id,
                    'quantity' => 1,
                    'price' => $laborTotal,
                    'delivered_status' => 1,
                    'out_for_deliver' => 1,
                    'client_approval' => 1,
                    'product_status' => 'black',
                ]);
                $stats['product_joborder']['success']++;

                $result['products_created'][] = [
                    'product_id' => $laborProduct->id,
                    'name' => 'مصنعيات',
                ];
            } catch (\Exception $e) {
                throw $e;
            }
        }

        // Create purchase transaction with all spare parts (for inventory deduction)
        // One purchase transaction per job sheet, not per spare part
        // Skip if transaction was zeroed out due to exceeding 500000 limit
        if (!empty($purchaseLineItems) && $finalTotal > 0) {
            $this->createPurchaseTransactionWithLines(
                $transaction,
                $purchaseLineItems,
                $partsTotal,
                $business_id,
                $location_id,
                $user_id,
                $transactionDate
            );
            $stats['transactions']['success']++;
        } elseif (!empty($purchaseLineItems) && $finalTotal == 0) {
            Log::warning('Skipping purchase transaction - sell transaction was zeroed (exceeded 500000 limit)', [
                'contact' => $data['owner_name'],
                'spare_parts_count' => count($purchaseLineItems),
            ]);
        }

        return $result;
    }

    private function createTransactionSellLine(Transaction $transaction, int $product_id, float $quantity, float $unit_price): void
    {
        $variation = DB::table('variations')
            ->where('product_id', $product_id)
            ->orderBy('id')
            ->first();

        if (! $variation) {
            throw new \Exception('Product has no variation for sell line (product_id: ' . $product_id . ')');
        }

        $qty = $quantity > 0 ? $quantity : 1;
        // Use provided unit_price, or fall back to variation's selling price if zero
        $price = $unit_price > 0 ? $unit_price : (float) ($variation->sell_price_inc_tax ?? 0);

        TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product_id,
            'variation_id' => $variation->id,
            'quantity' => $qty,
            'unit_price' => $price,
            'unit_price_inc_tax' => $price,
            'item_tax' => 0,
            'tax_id' => null,
        ]);
    }

    /**
     * Find or create contact by phone or name
     */
    private function findOrCreateContact(array $data, int $business_id, int $user_id, array &$stats): Contact
    {
        $phone = $data['phone'];
        $name = $data['owner_name'] ?: 'Unknown Customer';

        // Check cache first
        if (! empty($phone) && isset($this->contactCache[$phone])) {
            return $this->contactCache[$phone];
        }

        // If phone exists and already used, reuse the same contact.
        if (! empty($phone)) {
            $existing = Contact::where('business_id', $business_id)
                ->where('mobile', $phone)
                ->whereNull('deleted_at')
                ->first();
            if ($existing) {
                $this->contactCache[$phone] = $existing;
                return $existing;
            }
        }

        // If phone missing, generate a unique random mobile.
        $mobileToUse = ! empty($phone) ? $phone : $this->generateUniqueMobile($business_id);

        $contact = Contact::create([
            'business_id' => $business_id,
            'name' => $name,
            'mobile' => $mobileToUse,
            'type' => 'customer',
            'contact_type' => 'individual',
            'created_by' => $user_id,
        ]);

        $stats['contacts']['success']++;
        if (! empty($phone)) {
            $this->contactCache[$phone] = $contact;
        }
        return $contact;
    }

    /**
     * Check if a product name is a service keyword
     */
    private function isServiceKeyword(string $name): bool
    {
        $serviceKeywords = [
            'مصنعيات',      // Labor
        ];

        $nameLower = strtolower(trim($name));
        foreach ($serviceKeywords as $keyword) {
            if (strpos($nameLower, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }

    private function generateUniqueMobile(int $business_id): string
    {
        for ($i = 0; $i < 30; $i++) {
            $candidate = '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $exists = Contact::where('business_id', $business_id)
                ->where('mobile', $candidate)
                ->whereNull('deleted_at')
                ->exists();
            if (!$exists) {
                return $candidate;
            }
        }

        // Fallback: include timestamp (still numeric)
        return '9' . substr((string) time(), -9);
    }

    /**
     * Find or create car brand (category with type 'device')
     * Always use Mercedes as the brand category
     */
    private function findOrCreateBrand(?string $carType, int $business_id, int $user_id, array &$stats): int
    {
        // Always use Mercedes as the brand
        $brandName = 'Mercedes';
        $cacheKey = strtolower($brandName);

        // Check cache first
        if (isset($this->brandCache[$cacheKey])) {
            return $this->brandCache[$cacheKey];
        }

        // Try to find existing Mercedes brand (case-insensitive)
        $brand = Category::where('business_id', $business_id)
            ->where('category_type', 'device')
            ->where('parent_id', 0)
            ->whereRaw('LOWER(name) = ?', [strtolower($brandName)])
            ->first();

        if ($brand) {
            $this->brandCache[$cacheKey] = $brand->id;
            return $brand->id;
        }

        // Create Mercedes brand
        $brand = Category::create([
            'business_id' => $business_id,
            'name' => $brandName,
            'category_type' => 'device',
            'parent_id' => 0,
            'created_by' => $user_id,
        ]);

        $stats['brands']['success']++;
        $this->brandCache[$cacheKey] = $brand->id;

        return $brand->id;
    }

    /**
     * Find or create car model
     */
    private function findOrCreateModel(?string $modelName, int $brandId, int $business_id, int $user_id, array &$stats): int
    {
        if (!$modelName) {
            $modelName = 'Other';
        }

        $cacheKey = $brandId . '_' . strtolower($modelName);

        // Check cache first
        if (isset($this->modelCache[$cacheKey])) {
            return $this->modelCache[$cacheKey];
        }

        // Try to find existing model
        $model = DeviceModel::where('business_id', $business_id)
            ->where('device_id', $brandId)
            ->whereRaw('LOWER(name) = ?', [strtolower($modelName)])
            ->first();

        if ($model) {
            $this->modelCache[$cacheKey] = $model->id;
            return $model->id;
        }

        // Create new model
        $model = DeviceModel::create([
            'business_id' => $business_id,
            'name' => $modelName,
            'device_id' => $brandId,
            'created_by' => $user_id,
        ]);

        $stats['models']['success']++;
        $this->modelCache[$cacheKey] = $model->id;

        return $model->id;
    }

    /**
     * Find or create contact device (car)
     */
    private function findOrCreateDevice(array $data, int $contact_id, int $brandId, int $modelId, array &$stats): ContactDevice
    {
        // Try to find by chassis number first (if provided)
        if (!empty($data['chassis'])) {
            $device = ContactDevice::where('contact_id', $contact_id)
                ->where('chassis_number', $data['chassis'])
                ->first();
            
            if ($device) {
                return $device;
            }
        }

        // Try to find by plate number
        if (!empty($data['plate_number'])) {
            $device = ContactDevice::where('contact_id', $contact_id)
                ->where('plate_number', $data['plate_number'])
                ->first();
            
            if ($device) {
                return $device;
            }
        }

        // Extract year from model field if numeric
        $year = null;
        if (!empty($data['model']) && is_numeric($data['model'])) {
            $year = (int) $data['model'];
        }

        // manufacturing_year cannot be null
        if (empty($year)) {
            $year = (int) Carbon::now()->format('Y');
        }

        // Create new device
        $device = ContactDevice::create([
            'contact_id' => $contact_id,
            'device_id' => $brandId,
            'models_id' => $modelId,
            'plate_number' => $data['plate_number'],
            'chassis_number' => $data['chassis'],
            'manufacturing_year' => $year,
            'car_type' => $data['car_type'],
        ]);

        $stats['devices']['success']++;
        return $device;
    }

    /**
     * Create booking
     */
    private function createBooking(array $data, int $contact_id, int $device_id, int $business_id, int $location_id, int $user_id, array &$stats): Booking
    {
        $bookingDate = $data['visit_date'] ?: ($data['receipt_date'] ?: Carbon::now()->format('Y-m-d H:i:s'));
        $bookingName = ($data['owner_name'] ?: 'Import') . ' - ' . ($data['car_type'] ?: 'Unknown') . ' - ' . $bookingDate;
        
        // Debug: Log what date is being used for booking
        Log::info('Creating booking with date', [
            'owner_name' => $data['owner_name'],
            'visit_date' => $data['visit_date'],
            'receipt_date' => $data['receipt_date'],
            'final_booking_date' => $bookingDate,
        ]);

        $booking = new Booking();
        $booking->contact_id = $contact_id;
        $booking->device_id = $device_id;
        $booking->business_id = $business_id;
        $booking->location_id = $location_id;
        $booking->booking_start = $bookingDate;
        $booking->booking_end = $bookingDate;
        $booking->booking_name = $bookingName;
        $booking->booking_status = 'booked';
        $booking->booking_note = $data['notes'] ?: 'Imported from Excel';
        $booking->created_by = $user_id;
        $booking->save();

        $stats['bookings']['success']++;

        return $booking;
    }

    /**
     * Create job sheet
     */
    private function createJobSheet(array $data, Booking $booking, int $contact_id, int $business_id, int $location_id, int $user_id, array &$stats): JobSheet
    {
        // Get repair settings for job sheet prefix
        $business = Business::find($business_id);
        $repair_settings = json_decode($business->repair_settings ?? '{}', true);
        $job_sheet_prefix = $repair_settings['job_sheet_prefix'] ?? '';

        // Generate reference number
        $ref_count = $this->commonUtil->setAndGetReferenceCount('job_sheet', $business_id);
        $job_sheet_no = $this->commonUtil->generateReferenceNumber('job_sheet', $ref_count, null, $job_sheet_prefix);

        // Get first status
        $status = DB::table('repair_statuses')->where('status_category', 'status')->first();

        $jobSheet = JobSheet::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'contact_id' => $contact_id,
            'booking_id' => $booking->id,
            'job_sheet_no' => $job_sheet_no,
            'status_id' => $status->id ?? null,
            'entry_date' => $data['visit_date'] ?: Carbon::now()->format('Y-m-d H:i:s'),
            'km' => $data['km'] ? (int) preg_replace('/[^0-9]/', '', $data['km']) : null,
            'created_by' => $user_id,
        ]);

        $stats['job_sheets']['success']++;

        return $jobSheet;
    }

    /**
     * Create transaction for the job sheet
     */
    private function createTransaction(JobSheet $jobSheet, ContactDevice $device, int $contact_id, int $business_id, int $location_id, int $user_id, array &$stats, ?string $transaction_date = null, float $final_total = 0, float $discount_amount = 0): Transaction
    {
        $transactionDate = $transaction_date ?: Carbon::now()->toDateTimeString();

        $input = [
            'location_id' => $location_id,
            'status' => 'final',
            'type' => 'sell',
            'total_before_tax' => $final_total ,
            'tax' => 0,
            'final_total' => $final_total,
            'contact_id' => $contact_id,
            'transaction_date' => $transactionDate,
            'discount_amount' => $discount_amount,
            'sub_type' => 'repair',
            'repair_brand_id' => $device->device_id,
            'repair_status_id' => $jobSheet->status_id,
            'repair_model_id' => $device->models_id,
            'repair_job_sheet_id' => $jobSheet->id,
        ];

        $transaction = $this->transactionUtil->createSellTransaction(
            $business_id,
            $input,
            ['total_before_tax' => $final_total, 'tax' => 0],
            $user_id
        );

        $stats['transactions']['success']++;

        // If no payment lines exist, add full cash payment & mark paid.
        // (Some systems might create payments elsewhere; we avoid duplicates.)
        $has_payments = $transaction->payment_lines()->count() > 0;
        if (! $has_payments && $final_total > 0) {
            $payments = [[
                'amount' => $final_total,
                'method' => 'cash',
                'paid_on' => $transactionDate,
            ]];
            $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments, $business_id, $user_id, false);
            $this->transactionUtil->updatePaymentStatus($transaction->id, $final_total);
        }

        return $transaction;
    }

    /**
     * Find or create product (spare part)
     */
    private function findOrCreateProduct(array $partData, int $business_id, int $user_id, int $location_id, array &$stats): Product
    {
        $name = $partData['name'];
        $cacheKey = strtolower($name);

        // Check cache first
        if (isset($this->productCache[$cacheKey])) {
            return $this->productCache[$cacheKey];
        }

        // Try to find existing product by name
        $product = Product::where('business_id', $business_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($product) {
            $this->ensureProductHasLocationAndVld($product, $location_id);
            $this->productCache[$cacheKey] = $product;
            return $product;
        }

        // Get default unit
        $unitId = Unit::where('business_id', $business_id)->value('id');
        if (!$unitId) {
            $unitId = Unit::whereNull('business_id')->value('id');
        }

        // Get default category for products
        $categoryId = Category::where('business_id', $business_id)
            ->where('category_type', 'product')
            ->where('parent_id', 0)
            ->value('id');

        if (!$categoryId) {
            // Create a default category
            $category = Category::create([
                'business_id' => $business_id,
                'name' => 'Spare Parts',
                'category_type' => 'product',
                'parent_id' => 0,
                'created_by' => $user_id,
            ]);
            $categoryId = $category->id;
        }

        // Detect if this is a service (labor/maintenance) or a spare part
        $isService = $this->isServiceKeyword($name);
        $enableStock = $isService ? 0 : 1;

        // Create product
        // Use part_number as SKU if available and not empty, otherwise generate UUID
        $partNumber = $partData['part_number'] ?? null;
        $sku = (!empty($partNumber) && trim($partNumber) !== '') 
            ? trim($partNumber) 
            : 'IMP-' . $business_id . '-' . Str::uuid();
        $price = $partData['retail_price'] ?: 0;

        $product = Product::create([
            'name' => $name,
            'business_id' => $business_id,
            'type' => 'single',
            'unit_id' => $unitId,
            'category_id' => $categoryId,
            'tax' => null,
            'tax_type' => 'exclusive',
            'enable_stock' => $enableStock,
            'alert_quantity' => 0,
            'sku' => $sku,
            'barcode_type' => 'C128',
            'created_by' => $user_id,
            'not_for_selling' => 0,
        ]);

        // Generate proper SKU
        $generatedSku = $this->productUtil->generateProductSku($product->id);
        $product->sku = $generatedSku;
        $product->save();

        // Create variation
        $this->productUtil->createSingleProductVariation(
            $product->id,
            $product->sku,
            0,      // purchase_price
            0,      // dpp_inc_tax
            0,      // profit_percent
            $price, // selling_price
            $price  // selling_price_inc_tax
        );

        // Attach product to location without detaching other locations
        $product->product_locations()->syncWithoutDetaching([$location_id]);

        // Ensure VLD exists for the variation at this location
        $this->ensureProductHasLocationAndVld($product, $location_id);

        $stats['products']['success']++;
        $this->productCache[$cacheKey] = $product;

        return $product;
    }

    private function ensureProductHasLocationAndVld(Product $product, int $location_id): void
    {
        $product->product_locations()->syncWithoutDetaching([$location_id]);

        $variation = DB::table('variations')
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->first();

        if (!$variation) {
            // Product exists but has no variation. Create one so it can be sold.
            $price = 0;
            $existingVariation = DB::table('variations')
                ->where('product_id', $product->id)
                ->orderByDesc('id')
                ->first();
            if ($existingVariation && isset($existingVariation->sell_price_inc_tax)) {
                $price = (float) $existingVariation->sell_price_inc_tax;
            }

            $this->productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                0,
                0,
                0,
                $price,
                $price
            );

            $variation = DB::table('variations')
                ->where('product_id', $product->id)
                ->orderBy('id')
                ->first();

            if (!$variation) {
                return;
            }
        }

        $exists = VariationLocationDetails::where('variation_id', $variation->id)
            ->where('location_id', $location_id)
            ->exists();

        if ($exists) {
            return;
        }

        VariationLocationDetails::create([
            'product_id' => $product->id,
            'product_variation_id' => $variation->product_variation_id,
            'variation_id' => $variation->id,
            'location_id' => $location_id,
            'qty_available' => 0,
        ]);
    }

    /**
     * Create purchase transaction with all spare parts and their quantities
     * One purchase transaction per job sheet with all spare parts
     */
    private function createPurchaseTransactionWithLines(
        Transaction $sellTransaction,
        array $purchaseLineItems,
        float $partsTotal,
        int $business_id,
        int $location_id,
        int $user_id,
        string $transactionDate
    ): Transaction {
        // Find or create default supplier
        $supplier = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->first();

        if (!$supplier) {
            $supplier = Contact::create([
                'business_id' => $business_id,
                'name' => 'Default Supplier',
                'type' => 'supplier',
                'contact_type' => 'individual',
                'created_by' => $user_id,
            ]);
        }

        // Calculate total from purchase line items to ensure accuracy
        $calculatedTotal = 0;
        foreach ($purchaseLineItems as $item) {
            $calculatedTotal += ($item['quantity'] * $item['retail_price']);
        }

        // Use calculated total (more accurate than passed partsTotal)
        $purchaseTotal = $calculatedTotal > 0 ? $calculatedTotal : $partsTotal;

        // Generate invoice/reference number for purchase transaction
        $ref_count = $this->commonUtil->setAndGetReferenceCount('purchase', $business_id);
        $invoice_no = $this->commonUtil->generateReferenceNumber('purchase', $ref_count);

        // Create single purchase transaction with all spare parts
        $purchaseTransaction = Transaction::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'purchase',
            'status' => 'received',
            'contact_id' => $supplier->id,
            'transaction_date' => $transactionDate,
            'total_before_tax' => $purchaseTotal,
            'final_total' => $purchaseTotal,
            'ref_number' => $invoice_no . '-' . $sellTransaction->id,
            'created_by' => $user_id,
        ]);

        // Create purchase lines for each spare part
        foreach ($purchaseLineItems as $item) {
            $variation = DB::table('variations')
                ->where('product_id', $item['product_id'])
                ->orderBy('id')
                ->first();

            if ($variation) {
                $purchaseLine = PurchaseLine::create([
                    'transaction_id' => $purchaseTransaction->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $variation->id,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['retail_price'],
                    'purchase_price_inc_tax' => $item['retail_price'],
                    'item_tax' => 0,
                    'tax_id' => null,
                ]);

                // Link sell line to purchase line for stock history tracking
                $sellLine = TransactionSellLine::where('transaction_id', $sellTransaction->id)
                    ->where('product_id', $item['product_id'])
                    ->orderByDesc('id')
                    ->first();

                if ($sellLine) {
                    TransactionSellLinesPurchaseLines::create([
                        'sell_line_id' => $sellLine->id,
                        'purchase_line_id' => $purchaseLine->id,
                        'quantity' => $item['quantity'],
                    ]);
                }

                // Adjust inventory: deduct qty from variation_location_details
                $vld = VariationLocationDetails::where('variation_id', $variation->id)
                    ->where('location_id', $location_id)
                    ->first();

                if ($vld) {
                    $vld->qty_available = max(0, $vld->qty_available - $item['quantity']);
                    $vld->save();
                } else {
                    // Create new variation_location_details if it doesn't exist
                    VariationLocationDetails::create([
                        'product_id' => $item['product_id'],
                        'product_variation_id' => $variation->product_variation_id,
                        'variation_id' => $variation->id,
                        'location_id' => $location_id,
                        'qty_available' => max(0, -$item['quantity']),
                    ]);
                }
            }
        }

        // Mark purchase as paid with cash payment
        TransactionPayment::create([
            'transaction_id' => $purchaseTransaction->id,
            'amount' => $purchaseTotal,
            'method' => 'cash',
            'paid_on' => $transactionDate,
            'created_by' => $user_id,
        ]);

        // Update transaction payment_status to 'paid'
        $purchaseTransaction->update([
            'payment_status' => 'paid',
        ]);

        return $purchaseTransaction;
    }
}
