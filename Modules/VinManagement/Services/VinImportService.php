<?php

namespace Modules\VinManagement\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class VinImportService
{
    /**
     * Store the uploaded file temporarily and parse into structured rows with validation.
     * Supports .xlsx and .csv, validates expected headers, and returns a token for preview/submit.
     */
    public function parse(UploadedFile $file): array
    {
        $tmpDir = public_path('uploads/temp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
            return [
                'errors' => ['Unsupported file format. Allowed: .xlsx, .xls, .csv'],
            ];
        }

        $filename = $this->storeTemp($file, $tmpDir);
        $filePath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        try {
            // Use PhpSpreadsheet directly to avoid Laravel Excel's temp file/caching issues
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            // Wrap in array to match previous structure (array of sheets)
            $data = [$sheetData];
        } catch (\Exception $e) {
            return [
                'errors' => ['Failed to read file: ' . $e->getMessage()],
            ];
        }

        $sheet = $data[0] ?? [];
        if (empty($sheet)) {
            return [
                'token' => $filename,
                'headers' => [],
                'rows' => [],
                'errors' => ['Uploaded file contains no rows.'],
            ];
        }

        // Extract headers and normalize
        $headersRaw = array_filter($sheet[0] ?? []);
        $headers = [];
        foreach ($headersRaw as $h) {
            // normalize to snake_case to align with backend keys
            $headers[] = Str::snake(trim((string)$h));
        }

        $expected = [
            'car_brand',
            'car_model',
            'color',
            'vin_number',
            'year',
            'manufacturer',
            'car_type',
            'transmission'
        ];
        $missing = array_values(array_diff($expected, $headers));
        $unknown = array_values(array_diff($headers, $expected));
        $errors = [];
        if (!empty($missing)) {
            $errors[] = 'Missing required columns: ' . implode(', ', $missing);
        }
        if (!empty($unknown)) {
            $errors[] = 'Unknown columns present: ' . implode(', ', $unknown);
        }

        // Remove header row
        unset($sheet[0]);

        $rows = [];
        $rowErrors = [];
        $currentYear = (int) date('Y') + 1;
        $allowedTypes = ['Sedan', 'SUV', 'Truck', 'Hatchback', 'Coupe', 'Convertible', 'Van', 'Other'];
        $allowedTrans = ['Automatic', 'Manual', 'CVT', 'Dual-Clutch', 'Other'];
        foreach ($sheet as $index => $row) {
            $mapped = [];
            foreach ($row as $k => $v) {
                if (array_key_exists($k, $headers)) {
                    $key = $headers[$k];
                    $mapped[$key] = is_string($v) ? trim($v) : $v;
                }
            }

            // Only include rows with a vin_number
            if (!empty($mapped['vin_number'])) {
                // Normalize enums
                if (isset($mapped['car_type'])) {
                    $mapped['car_type'] = $this->normalizeEnum($mapped['car_type'], $allowedTypes);
                }
                if (isset($mapped['transmission'])) {
                    $mapped['transmission'] = $this->normalizeEnum($mapped['transmission'], $allowedTrans);
                }

                // Validate required fields
                $errs = [];
                foreach (['vin_number', 'year', 'manufacturer', 'car_type', 'transmission'] as $f) {
                    if (empty($mapped[$f])) {
                        $errs[] = "$f is required";
                    }
                }
                // Year range
                if (!empty($mapped['year'])) {
                    $yearVal = (int) $mapped['year'];
                    if ($yearVal < 1900 || $yearVal > $currentYear) {
                        $errs[] = "year must be between 1900 and $currentYear";
                    }
                }
                // Cross-field rule
                if (!empty($mapped['car_type']) && !empty($mapped['transmission'])) {
                    if (in_array($mapped['car_type'], ['Truck', 'Van']) && $mapped['transmission'] === 'Dual-Clutch') {
                        $errs[] = 'Dual-Clutch is not available for Truck/Van';
                    }
                }

                if (!empty($errs)) {
                    $rowErrors[$index + 2] = $errs; // excel row number (1-based) incl header
                }

                $rows[] = $mapped;
            }
        }

        return [
            'token' => $filename,
            'headers' => $headers,
            'rows' => $rows,
            'errors' => $errors,
            'row_errors' => $rowErrors,
        ];
    }

    protected function storeTemp(UploadedFile $file, string $dir): string
    {
        $ext = $file->getClientOriginalExtension();
        $name = 'vin_import_' . time() . '_' . Str::random(8) . '.' . $ext;
        $file->move($dir, $name);
        return $name;
    }

    private function normalizeEnum($value, array $allowed): ?string
    {
        if ($value === null || $value === '') return null;
        $val = trim((string)$value);
        foreach ($allowed as $opt) {
            if (strcasecmp($val, $opt) === 0) {
                return $opt;
            }
        }
        $titled = Str::title(strtolower($val));
        foreach ($allowed as $opt) {
            if ($titled === $opt) {
                return $opt;
            }
        }
        return $val;
    }
}
