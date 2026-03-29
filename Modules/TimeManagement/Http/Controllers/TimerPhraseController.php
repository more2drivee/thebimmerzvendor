<?php

namespace Modules\TimeManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TimerPhraseController extends Controller
{
    public function index()
    {
        return view('timemanagement::time_control.phrases');
    }

    public function list(Request $request)
    {
        $business_id = Auth::user()->business_id ?? null;

        $query = DB::table('timer_pre_phrases')
            ->when($business_id, function ($q) use ($business_id) {
                $q->where(function ($w) use ($business_id) {
                    $w->whereNull('business_id')
                      ->orWhere('business_id', $business_id);
                });
            });

        if (($active = $request->input('is_active')) !== null && $active !== '') {
            $query->where('is_active', (bool)$active);
        }

        if ($search = $request->input('search')) {
            $query->where('body', 'like', "%{$search}%");
        }

        $phrases = $query->orderByDesc('id')->get();

        return response()->json(['data' => $phrases]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason_type' => 'required|in:record_reason,finishtimer,ignore',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $business_id = Auth::user()->business_id ?? null;

        $now = now();
        $id = DB::table('timer_pre_phrases')->insertGetId([
            'business_id' => $business_id,
            'reason_type' => $data['reason_type'],
            'body' => $data['body'],
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $phrase = DB::table('timer_pre_phrases')->where('id', $id)->first();

        return response()->json(['success' => true, 'data' => $phrase]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason_type' => 'required|in:record_reason,finishtimer,ignore',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $exists = DB::table('timer_pre_phrases')->where('id', $id)->exists();
        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Phrase not found'], 404);
        }

        $payload = [
            'reason_type' => $data['reason_type'],
            'body' => $data['body'],
            'updated_at' => now(),
        ];

        if (isset($data['is_active'])) {
            $payload['is_active'] = (bool)$data['is_active'];
        }

        DB::table('timer_pre_phrases')->where('id', $id)->update($payload);

        $phrase = DB::table('timer_pre_phrases')->where('id', $id)->first();

        return response()->json(['success' => true, 'data' => $phrase]);
    }

    public function destroy($id)
    {
        $exists = DB::table('timer_pre_phrases')->where('id', $id)->exists();
        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Phrase not found'], 404);
        }

        DB::table('timer_pre_phrases')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }
}
