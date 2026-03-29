<?php

namespace Modules\Connector\Http\Controllers\Api;


use App\User;
use App\Business;
use App\Category;
use Carbon\Carbon;

use App\Utils\Util;

use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Utils\QwenService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Utils\CashRegisterUtil;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Modules\Repair\Utils\RepairUtil;
use Modules\Repair\Entities\JobSheet;
use Modules\Repair\Entities\DeviceModel;
use Illuminate\Support\Facades\Validator;
use Goutte\Client;

class OpenAIController extends Controller
{

  
   
  protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
    }




public function chatWithOpenAI(array $messages, $model = 'gpt-4o-mini')
{
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'Content-Type'  => 'application/json',
    ])->post('https://api.openai.com/v1/chat/completions', [
        'model'    => $model,
        'messages' => $messages,
    ]);

    if ($response->successful()) {
        return $response->json();
    }

    throw new \Exception('API request failed: ' . $response->body());
}

  




public function importBrandAndModels(Request $request)
{
    $user = auth()->user();
    if (!$user) {
        return response()->json(['error' => 'User not authenticated.'], 401);
    }

    $brandInput = trim($request->input('brand'));
    if (empty($brandInput)) {
        return response()->json(['error' => 'Brand name is required.'], 400);
    }

    // Define the prompt for OpenAI API
    $prompt = "Provide a comprehensive list of all known car models for {$brandInput}, including both current and discontinued models.
               Include a complete list of OBD codes commonly associated with {$brandInput} vehicles.
               Also, provide the VIN category code for {$brandInput} and VIN model codes for each model.
               The response should be in JSON format:
               {
                   \"brand\": \"{$brandInput}\",
                   \"vin_category_code\": \"VIN_CATEGORY_CODE\",
                   \"models\": [
                       {
                           \"name\": \"Model1\",
                           \"vin_model_code\": \"VIN_MODEL_CODE\"
                       },
                       ...
                   ],
                   \"obd_codes\": [
                       {
                           \"code\": \"P0001\",
                           \"problem_name\": \"Fuel Volume Regulator Control Circuit/Open\"
                       },
                       ...
                   ]
               }.";

    // Prepare messages payload for OpenAI API
    $messages = [
        ['role' => 'system', 'content' => 'You are an AI specialized in vehicle diagnostics and car models.'],
        ['role' => 'user', 'content' => $prompt]
    ];

    try {
        $response = $this->chatWithOpenAI($messages);

        if (!isset($response['choices'][0]['message']['content'])) {
            return response()->json(['error' => 'Empty or malformed API response.'], 502);
        }

        // **Fix: Extract clean JSON from the response**
        $content = $response['choices'][0]['message']['content'];

        // Remove markdown JSON tags if present
        $content = preg_replace('/```json\s*([\s\S]+?)\s*```/', '$1', $content);
        $content = trim($content);

        // Decode JSON response
        $result = json_decode($content, true);

        // **Fix: Check if JSON decoding failed**
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON in API response: ' . json_last_error_msg()], 502);
        }

        // Validate required fields
        if (!isset($result['brand'], $result['models'], $result['obd_codes'], $result['vin_category_code'])) {
            return response()->json(['error' => 'Missing required fields in JSON response.'], 400);
        }

        $brandName = trim($result['brand']);
        $vinCategoryCode = trim($result['vin_category_code']);
        $models = $result['models'];
        $obdCodes = $result['obd_codes'];

        // Check if brand exists
        $existingBrand = Category::where('name', $brandName)->first();
        if ($existingBrand) {
            $brand = $existingBrand;
            $brand->vin_category_code = $vinCategoryCode; // Update VIN category code
            $brand->save();
        } else {
            $brand = Category::create([
                'name'              => $brandName,
                'business_id'       => $user->business_id,
                'category_type'     => 'device',
                'vin_category_code' => $vinCategoryCode, // Save VIN category code
                'created_by'        => $user->id,
            ]);
        }

        $addedModels = 0;
        $skippedModels = 0;

        foreach ($models as $model) {
            $modelName = trim($model['name']);
            $vinModelCode = trim($model['vin_model_code'] ?? '');

            if (empty($modelName)) {
                continue;
            }

            if (DeviceModel::where('name', $modelName)->exists()) {
                $skippedModels++;
                continue;
            }

            DeviceModel::create([
                'name'           => $modelName,
                'device_id'      => $brand->id,
                'business_id'    => $user->business_id,
                'vin_model_code' => $vinModelCode, // Save VIN model code
                'created_by'     => $user->id,
            ]);

            $addedModels++;
        }

        // Import OBD Codes
        [$addedObdCodes, $skippedObdCodes] = $this->importObdCodes($brand, $obdCodes, $user);

        $message = "Imported brand '{$brandName}' with VIN category code '{$vinCategoryCode}'.
                    Added {$addedModels} new model(s) and {$addedObdCodes} new OBD code(s). 
                    Skipped {$skippedModels} duplicate model(s) and {$skippedObdCodes} duplicate OBD code(s).";

        return response()->json([
            'message'         => $message,
            'brand'           => $brandName,
            'vin_category_code'=> $vinCategoryCode,
            'added_models'    => $addedModels,
            'skipped_models'  => $skippedModels,
            'added_obd_codes' => $addedObdCodes,
            'skipped_obd_codes'=> $skippedObdCodes,
            'models'          => $models,
            'obd_codes'       => $obdCodes,
        ], 201);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Exception: ' . $e->getMessage()], 500);
    }
}



private function importObdCodes($brand, $obdCodes, $user)
{
    $addedObdCodes = 0;
    $skippedObdCodes = 0;

    $obdGroup = DB::table('obd_groups')
        ->whereJsonContains('brand_id', $brand->id)
        ->first();

    if (!$obdGroup) {
        $obdGroupId = DB::table('obd_groups')->insertGetId([
            'brand_id' => json_encode([$brand->id]),
            'name'     => "OBD Group for {$brand->name}",
        ]);
    } else {
        $obdGroupId = $obdGroup->id;
    }

    foreach ($obdCodes as $obdCode) {
        $code = trim($obdCode['code']);
        $problemName = trim($obdCode['problem_name']);

        if (empty($code) || empty($problemName)) {
            continue;
        }

        $codeExists = DB::table('obd_codes')
            ->where('obd_group_id', $obdGroupId)
            ->where('code', $code)
            ->exists();

        if ($codeExists) {
            $skippedObdCodes++;
            continue;
        }

        DB::table('obd_codes')->insert([
            'obd_group_id' => $obdGroupId,
            'code'         => $code,
            'problem_name' => $problemName,
        ]);

        $addedObdCodes++;
    }

    return [$addedObdCodes, $skippedObdCodes];
}



public function getObdProblem(Request $request)
{
    $validator = Validator::make($request->all(), [
        'brand_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }
    
    // Find OBD group with matching brand_id in JSON and name
    $obdGroup = DB::table('obd_groups')
    ->whereJsonContains('brand_id', (int)$request->brand_id) // Cast brand_id to integer
    ->first();
    
    
    if (!$obdGroup) {
        return response()->json(['error' => 'No OBD group found for the provided brand and name.'], 404);
    }
    $data=[];
    // Fetch OBD codes for the found OBD group
    $problems = DB::table('obd_codes')

    ->where('obd_group_id', $obdGroup->id)
    ->get();

    $data['problems']  = $problems;
    $data['name']  = $obdGroup->name;
    
    return response()->json($data);
    if ($problems->isEmpty()) {
        return response()->json(['error' => 'No OBD codes found for this group.'], 404);
    }

    return response()->json(['problems' => $problems], 200);
}
// public function getObdProblemPaginated(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'brand_id' => 'required|integer',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['errors' => $validator->errors()], 422);
//     }
    
//     // Find OBD group with matching brand_id in JSON and name
//     try {
//      // 1. Try specific match
//         $obdGroup = DB::table('obd_groups')
//         ->whereJsonContains('brand_id', $brandId)
//         ->when($modelName, fn($q) => $q->where('name', 'universal'))
//         ->first();

//     } catch (\Throwable $th) {
//         $obdGroup = DB::table('obd_groups')
//         ->whereJsonContains('brand_id', (int)$request->brand_id) // Cast brand_id to integer
        

//         ->first();
//         //throw $th;
//     }
    
    
//     if (!$obdGroup) {
//         return response()->json(['error' => 'No OBD group found for the provided brand and name.'], 404);
//     }
//     $data=[];
//     // Fetch OBD codes for the found OBD group
//     $problems = DB::table('obd_codes')

//     ->where('obd_group_id', $obdGroup->id)
//     ->get();

//     $data['problems']  = $problems;
//     $data['name']  = $obdGroup->name;
    
//     return response()->json($data);
//     if ($problems->isEmpty()) {
//         return response()->json(['error' => 'No OBD codes found for this group.'], 404);
//     }

//     return response()->json(['problems' => $problems], 200);
// }
public function getObdProblemPaginated(Request $request)
{
    $validator = Validator::make($request->all(), [
        'brand_id' => 'required|integer',
        'per_page' => 'sometimes|integer|min:1|max:100',
        'search' => 'sometimes|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $brandId = (int) $request->brand_id;
    $perPage = $request->input('per_page', 30); // default 10 per page
    $search = $request->input('search', '');

    // Find all OBD groups for the brand OR the universal group
    $obdGroups = DB::table('obd_groups')
        ->where(function ($q) use ($brandId) {
            $q->whereJsonContains('brand_id', $brandId)
              ->orWhere('name', 'universal');
        })
        ->get();

    if ($obdGroups->isEmpty()) {
        return response()->json(['error' => 'No OBD groups found for the provided brand and name.'], 404);
    }

    // Collect all matching group IDs (brand-specific + universal)
    $groupIds = $obdGroups->pluck('id');

    // Paginate OBD codes across all matching groups
    $problems = DB::table('obd_codes')
        ->whereIn('obd_group_id', $groupIds)
        ->orderBy('code')
        ->when($search, function ($query) use ($search) {
            $query->where('code', 'like', "%{$search}%");                
        })
        ->paginate($perPage);

    // If there are zero total results across all pages
    if (method_exists($problems, 'total') && $problems->total() === 0) {
        return response()->json(['error' => 'No OBD codes found for these groups.'], 404);
    }

    return response()->json([
        // Return groups so the client knows which groups were used (brand-specific and/or universal)
        'groups' => $obdGroups->map(function ($g) {
            return ['id' => $g->id, 'name' => $g->name];
        }),
        'problems' => $problems,
    ]);
}


}

