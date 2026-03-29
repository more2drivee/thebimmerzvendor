<?php

namespace Modules\ArtificialIntelligence\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ArtificialIntelligence\Services\ArtificialIntelligenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArtificialIntelligenceController extends Controller
{
    protected $aiService;

    public function __construct(ArtificialIntelligenceService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('artificialintelligence::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('artificialintelligence::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('artificialintelligence::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('artificialintelligence::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Process AI request
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        try {
            $type = $request->input('type');
            $data = $request->input('data');
            $options = $request->input('options', []);

            switch ($type) {
                case 'openai':
                    $result = $this->aiService->chatGPT($data, $options);
                    break;
                case 'openrouter':
                    $result = $this->aiService->openRouter($data, $options);
                    break;
                case 'groq':
                    $result = $this->aiService->groq($data, $options);
                    break;
                case 'qwen':
                    $result = $this->aiService->qwen($data, $options);
                    break;
                case 'gemini':
                    $result = $this->aiService->gemini($data, $options);
                    break;
                case 'huggingface':
                    $model = $request->input('model');
                    $result = $this->aiService->huggingFace($data, $model, $options);
                    break;
                default:
                    return response()->json(['error' => 'Invalid AI type'], 400);
            }

            return response()->json(['result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of AI providers.
     * @return Renderable
     */
    public function providers()
    {
        $providers = DB::table('ai_providers')
            ->select('id', 'provider', 'model_name', 'status')
            ->paginate(10);
        return view('artificialintelligence::providers.index', compact('providers'));
    }

    /**
     * Show the form for creating a new AI provider.
     * @return Renderable
     */
    public function createProvider()
    {
        return view('artificialintelligence::providers.create');
    }

    /**
     * Store a newly created AI provider in storage.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeProvider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|max:50',
            'model_name' => 'required|string|max:100',
            'status' => 'required|in:free,paid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::table('ai_providers')->insert([
            'provider' => $request->provider,
            'model_name' => $request->model_name,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI Provider created successfully'
        ]);
    }

    public function updateProvider(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|max:50',
            'model_name' => 'required|string|max:100',
            'status' => 'required|in:free,paid',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = DB::table('ai_providers')
            ->where('id', $id)
            ->update([
                'provider' => $request->provider,
                'model_name' => $request->model_name,
                'status' => $request->status,
            ]);
            
        if (!$updated) {
            return response()->json(['error' => 'AI Provider not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'AI Provider updated successfully'
        ]);
    }

    public function destroyProvider($id)
    {
        $deleted = DB::table('ai_providers')->where('id', $id)->delete();
        
        if (!$deleted) {
            return response()->json(['error' => 'AI Provider not found'], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'AI Provider deleted successfully'
        ]);
    }

    /**
     * Show the specified AI provider.
     * @param int $id
     * @return Renderable
     */
    public function showProvider($id)
    {
        $provider = DB::table('ai_providers')->where('id', $id)->first();
        
        if (!$provider) {
            if (request()->ajax()) {
                return response()->json(['error' => 'AI Provider not found'], 404);
            }
            return redirect()->route('artificialintelligence.providers')
                ->with('error', 'AI Provider not found');
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $provider
            ]);
        }
        
        return view('artificialintelligence::providers.show', compact('provider'));
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
   
    public function settings()
    {
        // Get all providers
        $aiProviders = DB::table('ai_providers')
            ->distinct()
            ->pluck('provider')
            ->toArray();

        // Get currently active provider and model
        $activeProvider = DB::table('ai_providers')
            ->where('is_active', 1)
            ->first();

        $defaultProvider = $activeProvider ? $activeProvider->provider : 'gemini';
        $defaultModel = $activeProvider ? $activeProvider->model_name : null;

        return view('artificialintelligence::settings.default_provider', compact(
            'aiProviders',
            'defaultProvider',
            'defaultModel'
        ));
    }

    /**
     * Update active AI provider and model
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActiveProvider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|exists:ai_providers,provider',
            'model_name' => 'required|string|exists:ai_providers,model_name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                // Set all providers to inactive
                DB::table('ai_providers')
                    ->update(['is_active' => 0]);

                // Set the selected provider and model to active
                DB::table('ai_providers')
                    ->where('provider', $request->provider)
                    ->where('model_name', $request->model_name)
                    ->update(['is_active' => 1]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Default AI provider updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating default AI provider'
            ], 500);
        }
    }
}
