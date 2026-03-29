<?php

namespace Modules\TimeManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TimerStopReasonAdminController extends Controller
{
    public function index()
    {
        return view('timemanagement::time_control.stop_reasons');
    }

    public function list(Request $request)
    {
        $query = DB::table('timer_stop_reasons')
            ->leftJoin('timer_pre_phrases', 'timer_stop_reasons.phrase_id', '=', 'timer_pre_phrases.id')
            ->leftJoin('timer_tracking', 'timer_stop_reasons.timer_id', '=', 'timer_tracking.id')
            ->leftJoin('repair_job_sheets as rjs', 'timer_tracking.job_sheet_id', '=', 'rjs.id')
            ->leftJoin('users', 'timer_tracking.user_id', '=', 'users.id')
            ->select(
                'timer_stop_reasons.*',
                'timer_pre_phrases.reason_type as phrase_reason_type',
                'timer_pre_phrases.body as phrase_body',
                'timer_tracking.job_sheet_id',
                'rjs.job_sheet_no',
                'users.first_name',
                'users.last_name'
            );

        if ($timerId = $request->input('timer_id')) {
            $query->where('timer_id', $timerId);
        }

        if (($active = $request->input('is_active')) !== null && $active !== '') {
            $query->where('is_active', (bool)$active);
        }

        if ($request->filled('has_end')) {
            if ($request->input('has_end') === '1') {
                $query->whereNotNull('pause_end');
            } elseif ($request->input('has_end') === '0') {
                $query->whereNull('pause_end');
            }
        }

        if ($search = $request->input('search')) {
            $query->where('body', 'like', "%{$search}%");
        }

        $reasons = $query->orderByDesc('created_at')->get();

        return response()->json(['data' => $reasons]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_sheet_id' => 'required|integer|exists:repair_job_sheets,id',
            'phrase_id' => 'nullable|integer|exists:timer_pre_phrases,id',
            'body' => 'nullable|string',
            'pause_start' => 'nullable|date',
            'pause_end' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Resolve timer_id from selected job sheet (must have active or paused timer)
        $timerId = DB::table('timer_tracking')
            ->where('job_sheet_id', $data['job_sheet_id'])
            ->whereIn('status', ['active', 'paused'])
            ->orderByDesc('id')
            ->value('id');

        if (!$timerId) {
            return response()->json([
                'success' => false,
                'message' => 'No active or paused timer found for the selected job sheet.',
            ], 422);
        }

        // Derive reason_type from linked phrase, if provided
        $reasonType = null;
        if (!empty($data['phrase_id'])) {
            $phrase = DB::table('timer_pre_phrases')->where('id', $data['phrase_id'])->first();
            $reasonType = $phrase->reason_type ?? null;
        }

        $now = now();
        $id = DB::table('timer_stop_reasons')->insertGetId([
            'timer_id' => $timerId,
            'phrase_id' => $data['phrase_id'] ?? null,
            'reason_type' => $reasonType,
            'body' => $data['body'] ?? null,
            'pause_start' => $data['pause_start'] ?? $now,
            'pause_end' => $data['pause_end'] ?? null,
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $reason = DB::table('timer_stop_reasons')->where('id', $id)->first();

        return response()->json(['success' => true, 'data' => $reason]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'job_sheet_id' => 'nullable|integer|exists:repair_job_sheets,id',
            'phrase_id' => 'nullable|integer|exists:timer_pre_phrases,id',
            'body' => 'nullable|string',
            'pause_start' => 'nullable|date',
            'pause_end' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $exists = DB::table('timer_stop_reasons')->where('id', $id)->exists();
        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Stop reason not found'], 404);
        }

        $payload = ['updated_at' => now()];

        if (array_key_exists('job_sheet_id', $data)) {
            // Re-resolve timer_id from job sheet
            $timerId = DB::table('timer_tracking')
                ->where('job_sheet_id', $data['job_sheet_id'])
                ->whereIn('status', ['active', 'paused'])
                ->orderByDesc('id')
                ->value('id');

            if (!$timerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active or paused timer found for the selected job sheet.',
                ], 422);
            }

            $payload['timer_id'] = $timerId;
        }
        if (array_key_exists('phrase_id', $data)) {
            $payload['phrase_id'] = $data['phrase_id'];

            // Re-derive reason_type when phrase_id changes
            $reasonType = null;
            if (!empty($data['phrase_id'])) {
                $phrase = DB::table('timer_pre_phrases')->where('id', $data['phrase_id'])->first();
                $reasonType = $phrase->reason_type ?? null;
            }
            $payload['reason_type'] = $reasonType;
        }
        if (array_key_exists('body', $data)) {
            $payload['body'] = $data['body'];
        }
        if (array_key_exists('pause_start', $data)) {
            $payload['pause_start'] = $data['pause_start'];
        }
        if (array_key_exists('pause_end', $data)) {
            $payload['pause_end'] = $data['pause_end'];
        }
        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool)$data['is_active'];
        }

        DB::table('timer_stop_reasons')->where('id', $id)->update($payload);

        $reason = DB::table('timer_stop_reasons')->where('id', $id)->first();

        return response()->json(['success' => true, 'data' => $reason]);
    }

    public function destroy($id)
    {
        $exists = DB::table('timer_stop_reasons')->where('id', $id)->exists();
        if (! $exists) {
            return response()->json(['success' => false, 'message' => 'Stop reason not found'], 404);
        }

        DB::table('timer_stop_reasons')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * List ongoing job sheets (repair transactions under processing) for current user's location.
     * Returns minimal data for dropdown: id, job_sheet_no.
     */
    public function ongoingJobSheets()
    {
        $user = auth()->user();

        $jobSheets = DB::table('repair_job_sheets as rjs')
            ->join('transactions as t', 't.repair_job_sheet_id', '=', 'rjs.id')
            ->where('rjs.location_id', $user->location_id)
            ->where('t.type', 'sell')
            ->where('t.sub_type', 'repair')
            ->where('t.status', 'under processing')
            ->select('rjs.id', 'rjs.job_sheet_no')
            ->orderByDesc('rjs.id')
            ->get();

        return response()->json(['data' => $jobSheets]);
    }
}
