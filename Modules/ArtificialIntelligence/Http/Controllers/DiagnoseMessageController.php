<?php

namespace Modules\ArtificialIntelligence\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\Repair\Entities\JobSheet;
use Modules\Repair\Entities\DeviceModel;
use Modules\ArtificialIntelligence\Services\ArtificialIntelligenceService;

class DiagnoseMessageController extends Controller
{
    protected $aiService;

    public function __construct(ArtificialIntelligenceService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function edit()
    {
        $message = DB::table('diagnose_message')->value('message');

        return view('artificialintelligence::diagnose_message.create', compact('message'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        DB::table('diagnose_message')->update(['message' => $request->message]);

        return redirect()->back()->with('success', '✅ تم تحديث الرسالة بنجاح!');
    }

    public function BrandImport()
    {
        // $aiProviders = DB::table('ai_providers')
        //     ->distinct()
        //     ->pluck('provider')
        //     ->toArray();
        
        return view('artificialintelligence::diagnose_message.import_model_brand_obd');
    }

    public function handle_jobsheet_obds_ai(Request $request, $id) 
    {
        
            // Use path parameter as jobsheet id
            $jobSheetId = (int) $id;
            
            if (!JobSheet::where('id', $jobSheetId)->exists()) {
                return response()->json(['error' => 'Job sheet not found'], 404);
            }

            // Build query with explicit where clause
            $job_order = JobSheet::leftJoin('bookings', 'bookings.id', '=', 'repair_job_sheets.booking_id')
                ->leftJoin('contact_device', 'bookings.device_id', '=', 'contact_device.id')
                ->leftJoin('repair_device_models AS rdm', 'rdm.id', '=', 'contact_device.models_id')
                ->leftJoin('categories AS cat', 'contact_device.device_id', '=', 'cat.id')
                ->select([
                    'repair_job_sheets.obd_id',
                    'contact_device.chassis_number',
                    'contact_device.manufacturing_year',
                    'cat.name AS car_category',
                    'rdm.name AS car_model',
                    'bookings.booking_note',
                    'repair_job_sheets.km'
                ])
                ->where('repair_job_sheets.id', $jobSheetId)
                ->first();

            if (!$job_order) {
                return response()->json(['error' => 'Job sheet not found'], 404);
            }
        
        // Decode JSON OBD IDs
        $obdIds = json_decode($job_order->obd_id, true);
        $obdCodes = [];

        if (!empty($obdIds) && is_array($obdIds)) {
            $obdCodes = DB::table('obd_codes')
                ->whereIn('id', $obdIds)
                ->pluck('code')
                ->toArray();
        }

        // Extract details
        $carModel = $job_order->car_model ?? 'غير معروف';
        $carBrand = $job_order->car_category ?? 'غير معروف';
        $manufacturingYear = $job_order->manufacturing_year ?? 'غير متوفر';
        $bookingNote = $job_order->booking_note ?? 'لا يوجد ملاحظات على الحجز';
        $kmText = $job_order->km ?? 'غير معروف';

        // Fetch message template with fallback
        $messageTemplate = DB::table('diagnose_message')->value('message');
        if (!$messageTemplate) {
            // Fallback template if no template exists in database
            $messageTemplate = "🔧 تحليل سيارة - {car_brand} {car_model} ({manufacturing_year}) - {km} كم

⚠️ أكواد OBD: {obd_codes}
📝 ملاحظات: {booking_notes}

يرجى تقديم:
1. 🎯 تشخيص المشكلة الرئيسية
2. 🔍 الأسباب المحتملة  
3. 🛠️ قطع الغيار المطلوبة
4. 🛡️ توصيات وقائية لتجنب تكرار الأعطال";
        }

        // Determine OBD codes text and booking notes text
        $obdCodesText = !empty($obdCodes) ? implode(', ', $obdCodes) : 'غير معروف';
        $bookingNoteText = !empty($job_order->booking_note) ? $bookingNote : 'غير معروف';

        // Replace placeholders with actual values
        $message = str_replace(
            ['{car_model}', '{car_brand}', '{manufacturing_year}', '{obd_codes}', '{booking_notes}', '{km}'],
            [$carModel, $carBrand, $manufacturingYear, $obdCodesText, $bookingNoteText, $kmText],
            $messageTemplate
        );
        // return response()->json($message);

        // Remove any HTML tags if they exist
        $message = strip_tags($message);
        // Send message to OpenAI for analysis
        return $this->askGPT($message);
    }

public function askGPT($userMessage)
{
    try {
        // Prepare payload for OpenAI request
        $messages = [
            [
                'role' => 'system',
                'content' => 'أنت مساعد ذكي يساعد في تحليل تقارير تشخيص المركبات.',
            ],
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
        ];

        $options = [
            'model' => 'gpt-4.1',
            'max_tokens' => 3000
        ];

        // Use the service instead of direct API call
        $data = $this->aiService->chatGPT($messages, $options);
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            return response()->json(['error' => 'No response from AI'], 502);
        }

        // Clean JSON response if wrapped in ```json ... ```
        $content = preg_replace('/```json\s*([\s\S]+?)\s*```/', '$1', $content);

        // Decode JSON if possible then ensure string output for markdown rendering
        $parsedData = json_decode($content, true);
        $responseString = is_array($parsedData) ? json_encode($parsedData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string)$content;

        return response()->json([
            'response' => $responseString
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'prompt' => $userMessage,
            'error' => 'Exception: ' . $e->getMessage()
        ], 500);
    }
}

public function getModels(Request $request)
{
    $provider = $request->input('provider', 'gemini');
    
    $models = DB::table('ai_providers')
        ->where('provider', $provider)
        ->select('model_name', 'status')
        ->get()
        ->map(function($model) {
            $icon = $model->status === 'free' ? '🆓' : '💰';
            $model->display_name = $model->model_name . ' ' . $icon;
            return $model;
        });
    
    return response()->json(['models' => $models]);
}
}
