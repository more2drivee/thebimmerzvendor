<?php

namespace Modules\VinManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\VinManagement\Services\VinImportService;
use Modules\VinManagement\Repositories\VinNumberRepository;
use Modules\VinManagement\Entities\VinNumber;
use Modules\VinManagement\Entities\VinGroup;
use App\Category;
use Modules\Repair\Entities\DeviceModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VinManagementController extends Controller
{
    public function dashboard()
    {
        return view('vinmanagement::dashboard');
    }

    public function import()
    {
        return view('vinmanagement::pages.import');
    }

    /**
     * Get all brands (device categories) for dropdown.
     */
    public function brands(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $brands = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('category_type', 'device')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($brands);
    }

    /**
     * Get models for a specific brand for dropdown.
     */
    public function modelsByBrand($brandId)
    {
        $models = DB::table('repair_device_models')
            ->where('device_id', (int)$brandId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($models);
    }

    /**
     * Store a single VIN record from manual form.
     */
    public function storeSingleVin(Request $request)
    {
        $data = $request->validate([
            'vin_number'   => 'required|string|max:255|unique:vin_numbers,vin_number',
            'car_brand'    => 'required|integer|exists:categories,id',
            'car_model'    => 'required|integer|exists:repair_device_models,id',
            'color'        => 'nullable|string|max:255',
            'year'         => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'manufacturer' => 'required|string|max:255',
            'car_type'     => 'required|string|max:255',
            'transmission' => 'required|string|max:255',
        ]);

        $vin = VinNumber::create($data);

        return response()->json([
            'success' => true,
            'message' => 'VIN added successfully.',
            'data'    => $vin,
        ]);
    }

    public function uploadImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $service = new VinImportService();
        $result = $service->parse($request->file('file'));

        if (!empty($result['errors'])) {
            return response()->json([
                'status' => 'error',
                'errors' => $result['errors'],
            ], 422);
        }

        // Filter out rows with row_errors
        $validRows = [];
        $rowErrors = $result['row_errors'] ?? [];
        $businessId = request()->session()->get('user.business_id');
        foreach ($result['rows'] as $idx => $row) {
            $excelRowNum = $idx + 2; // account for header
            if (isset($rowErrors[$excelRowNum])) {
                continue;
            }

            // Resolve brand (car_brand) to categories.id (category_type = 'device')
            $brandId = null;
            $brandInput = trim((string)($row['car_brand'] ?? $row['manufacturer'] ?? ''));
            if ($brandInput !== '') {
                if (is_numeric($brandInput)) {
                    $cat = Category::where('id', (int)$brandInput)
                        ->where('category_type', 'device')
                        ->first();
                    if ($cat) {
                        $brandId = $cat->id;
                    } else {
                        $rowErrors[$excelRowNum][] = "Unknown brand ID: {$brandInput}";
                        continue; // skip this row to avoid FK violation
                    }
                } else {
                    // Case-insensitive search
                    $cat = Category::where('business_id', $businessId)
                        ->where('category_type', 'device')
                        ->where(DB::raw('LOWER(name)'), strtolower($brandInput))
                        ->first();

                    if ($cat) {
                        $brandId = $cat->id;
                    } else {
                        // Auto-create brand
                        try {
                            $newBrand = Category::create([
                                'business_id' => $businessId,
                                'name' => ucfirst(strtolower($brandInput)), // Title case
                                'category_type' => 'device',
                                'created_by' => request()->session()->get('user.id'),
                                'parent_id' => 0,
                                'slug' => Str::slug($brandInput) . '-' . uniqid(),
                            ]);
                            $brandId = $newBrand->id;
                        } catch (\Exception $e) {
                            $rowErrors[$excelRowNum][] = "Failed to create brand: {$brandInput}";
                            continue;
                        }
                    }
                }
            }

            // Resolve model (car_model) to repair_device_models.id
            $modelId = null;
            if (isset($row['car_model']) && $row['car_model'] !== '' && $row['car_model'] !== null) {
                $modelInput = trim((string)$row['car_model']);
                if (is_numeric($modelInput)) {
                    $dm = DeviceModel::where('id', (int)$modelInput)->first();
                    if ($dm) {
                        $modelId = $dm->id;
                    } else {
                        $rowErrors[$excelRowNum][] = "Unknown model ID: {$modelInput}";
                        continue; // skip row
                    }
                } else {
                    // Prefer lookup within resolved brand if available; fallback to name-only within business
                    $dmQuery = DeviceModel::where('business_id', $businessId)
                        ->where(DB::raw('LOWER(name)'), strtolower($modelInput));

                    if ($brandId) {
                        $dmQuery->where('device_id', $brandId);
                    }

                    $dm = $dmQuery->first();

                    if ($dm) {
                        $modelId = $dm->id;
                    } else {
                        // Auto-create model if brand is known
                        if ($brandId) {
                            try {
                                $newModel = DeviceModel::create([
                                    'business_id' => $businessId,
                                    'name' => ucfirst(strtolower($modelInput)),
                                    'device_id' => $brandId, // Link to brand
                                    'created_by' => request()->session()->get('user.id'),
                                ]);
                                $modelId = $newModel->id;
                            } catch (\Exception $e) {
                                $rowErrors[$excelRowNum][] = "Failed to create model: {$modelInput}";
                                continue;
                            }
                        } else {
                            $rowErrors[$excelRowNum][] = "Cannot create model '{$modelInput}' without a valid brand.";
                            continue;
                        }
                    }
                }
            }

            // Prepare normalized row with foreign keys resolved
            if ($brandId) {
                $row['car_brand'] = $brandId;
            }
            if ($modelId) {
                $row['car_model'] = $modelId;
            }

            $validRows[] = $row;
        }

        $repo = new VinNumberRepository();
        $repo->bulkStoreAddOnly($validRows);

        return response()->json([
            'status' => 'success',
            'inserted' => count($validRows),
            'row_errors' => $rowErrors,
        ]);
    }

    /**
     * Finalize import: add-only behavior; existing records are preserved.
     */
    public function submitImport(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $token = $request->input('token');
        $tmpDir = public_path('uploads/temp');
        $fullPath = $tmpDir . DIRECTORY_SEPARATOR . $token;
        if (!file_exists($fullPath)) {
            return response()->json(['status' => 'error', 'message' => 'Upload session expired or file missing.'], 422);
        }

        $service = new VinImportService();
        $fakeFile = new \Illuminate\Http\UploadedFile($fullPath, $token, null, null, true);
        $result = $service->parse($fakeFile);
        if (!empty($result['errors'])) {
            return response()->json(['status' => 'error', 'errors' => $result['errors']], 422);
        }

        // Filter out rows with row_errors
        $validRows = [];
        foreach ($result['rows'] as $idx => $row) {
            $excelRowNum = $idx + 2; // account for header
            if (isset($result['row_errors'][$excelRowNum])) {
                continue;
            }
            $validRows[] = $row;
        }

        $repo = new VinNumberRepository();
        $repo->bulkStoreAddOnly($validRows);

        return response()->json(['status' => 'success', 'inserted' => count($validRows)]);
    }

    /**
     * Server-side listing for DataTables.
     */
    public function list(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = $request->input('search.value');

        $query = VinNumber::query();

        if ($manufacturer = $request->input('manufacturer')) {
            $query->where('manufacturer', 'LIKE', "%" . $manufacturer . "%");
        }
        if ($carType = $request->input('car_type')) {
            $query->where('car_type', $carType);
        }
        if ($trans = $request->input('transmission')) {
            $query->where('transmission', $trans);
        }
        if ($year = $request->input('year')) {
            $query->where('year', (int) $year);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('vin_number', 'LIKE', "%$search%")
                    ->orWhere('manufacturer', 'LIKE', "%$search%")
                    ->orWhere('car_type', 'LIKE', "%$search%")
                    ->orWhere('transmission', 'LIKE', "%$search%")
                    ->orWhere('color', 'LIKE', "%$search%")
                    ->orWhere('year', 'LIKE', "%$search%");
            });
        }

        $recordsTotal = VinNumber::count();
        $recordsFiltered = (clone $query)->count();

        $data = $query
            ->leftJoin('categories as brands', 'vin_numbers.car_brand', '=', 'brands.id')
            ->leftJoin('repair_device_models as models', 'vin_numbers.car_model', '=', 'models.id')
            ->orderBy('vin_numbers.id', 'desc')
            ->skip($start)
            ->take($length)
            ->get([
                'vin_numbers.id',
                'vin_numbers.vin_number',
                'vin_numbers.color',
                'vin_numbers.year',
                'vin_numbers.manufacturer',
                'vin_numbers.car_type',
                'vin_numbers.transmission',
                DB::raw('COALESCE(brands.name, "") as car_brand_name'),
                DB::raw('COALESCE(models.name, "") as car_model_name')
            ]);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Export all VINs as CSV including new fields.
     */
    public function export(Request $request)
    {
        $filename = 'vin_numbers_export_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['vin_number', 'car_brand', 'car_model', 'color', 'year', 'manufacturer', 'car_type', 'transmission'];

        $callback = function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            VinNumber::orderBy('id')
                ->chunk(500, function ($chunk) use ($out, $columns) {
                    foreach ($chunk as $row) {
                        $line = [];
                        foreach ($columns as $col) {
                            $line[] = $row->{$col};
                        }
                        fputcsv($out, $line);
                    }
                });
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete a VIN record.
     */
    public function destroy($id)
    {
        $vin = VinNumber::findOrFail((int)$id);

        // Detach from any groups (pivot) before deleting
        try {
            $vin->groups()->detach();
        } catch (\Exception $e) {
            // ignore if relation not set up or pivot missing
        }

        $vin->delete();

        return response()->json([
            'success' => true,
            'message' => 'VIN deleted successfully.',
        ]);
    }

    /**
     * Download a CSV template for VIN import with dummy data.
     */
    public function template(Request $request)
    {
        // Use CSV template (Excel can open CSV directly) to keep upload validation simple
        $filename = 'vin_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['car_brand', 'car_model', 'color', 'vin_number', 'year', 'manufacturer', 'car_type', 'transmission'];

        $callback = function () use ($columns) {
            $out = fopen('php://output', 'w');
            // header
            fputcsv($out, $columns);
            // dummy example row
            fputcsv($out, [
                'TOYOTA',
                'Corolla',
                'White',
                'JTDBR32E720123456',
                '2020',
                'Toyota Motor Corporation',
                'Sedan',
                'Automatic',
            ]);
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Manufacturer autocomplete suggestions.
     */
    public function manufacturerSuggestions(Request $request)
    {
        $term = $request->input('term');
        $query = VinNumber::select('manufacturer')->distinct()->whereNotNull('manufacturer');
        if ($term) {
            $query->where('manufacturer', 'LIKE', "%" . $term . "%");
        }
        $items = $query->orderBy('manufacturer')->limit(20)->pluck('manufacturer');
        return response()->json($items);
    }

    public function groups()
    {
        return view('vinmanagement::pages.groups');
    }

    public function campaigns()
    {
        return view('vinmanagement::pages.campaigns');
    }

    public function automation()
    {
        return view('vinmanagement::pages.automation');
    }

    /**
     * Groups: JSON listing
     */
    public function groupList(Request $request)
    {
        $businessId = request()->session()->get('user.business_id');
        $query = VinGroup::query();
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        $groups = $query->orderBy('id', 'desc')->get(['id', 'name', 'color', 'text']);
        return response()->json($groups);
    }

    /**
     * Groups: create
     */
    public function groupStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'text' => 'nullable|string',
        ]);
        $validated['business_id'] = request()->session()->get('user.business_id');
        $group = VinGroup::create($validated);
        return response()->json(['success' => true, 'group' => $group]);
    }

    /**
     * Groups: update
     */
    public function groupUpdate($id, Request $request)
    {
        $group = VinGroup::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
            'text' => 'nullable|string',
        ]);
        $group->update($validated);
        return response()->json(['success' => true, 'group' => $group]);
    }

    /**
     * Groups: delete
     */
    public function groupDelete($id)
    {
        $group = VinGroup::findOrFail($id);
        // Ensure all pivot assignments are removed (defensive even if DB has ON DELETE CASCADE)
        $group->vinNumbers()->detach();
        $group->delete();
        return response()->json(['success' => true]);
    }

    /**
     * List VINs assigned to a specific group
     */
    public function groupVins($id)
    {
        $group = VinGroup::findOrFail($id);
        $vins = $group->vinNumbers()
            ->select(['vin_numbers.id', 'vin_numbers.vin_number', 'vin_numbers.manufacturer', 'vin_numbers.year', 'vin_numbers.car_type', 'vin_numbers.transmission'])
            ->orderBy('vin_numbers.id', 'desc')
            ->get();
        return response()->json($vins);
    }

    /**
     * Assign a VIN number to a group
     */
    public function assignVinToGroup(Request $request)
    {
        $validated = $request->validate([
            'vin_id' => 'required|integer|exists:vin_numbers,id',
            'group_id' => 'required|integer|exists:vin_groups,id',
        ]);
        $vin = VinNumber::findOrFail($validated['vin_id']);
        $vin->groups()->syncWithoutDetaching([$validated['group_id']]);
        return response()->json(['success' => true]);
    }

    /**
     * Unassign a VIN number from a group
     */
    public function unassignVinFromGroup(Request $request)
    {
        $validated = $request->validate([
            'vin_id' => 'required|integer|exists:vin_numbers,id',
            'group_id' => 'required|integer|exists:vin_groups,id',
        ]);
        $vin = VinNumber::findOrFail($validated['vin_id']);
        $vin->groups()->detach([$validated['group_id']]);
        return response()->json(['success' => true]);
    }

    /**
     * Lookup groups for a given VIN string (17-char VIN).
     * Returns groups with id, name, color, and text.
     */
    public function vinGroupsByNumber(Request $request)
    {
        $vinStr = trim((string)$request->query('vin', ''));
        if ($vinStr === '' || strlen($vinStr) < 17) {
            return response()->json([]);
        }

        $vin = VinNumber::where('vin_number', $vinStr)->first();
        if (!$vin) {
            return response()->json([]);
        }

        $groups = $vin->groups()
            ->select(['vin_groups.id', 'vin_groups.name', 'vin_groups.color', 'vin_groups.text'])
            ->orderBy('vin_groups.id', 'desc')
            ->get();

        return response()->json($groups);
    }
}
