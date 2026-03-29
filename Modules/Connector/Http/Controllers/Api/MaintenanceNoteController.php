<?php

namespace Modules\Connector\Http\Controllers\Api;


use App\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


/**
 * @group Brand management
 * @authenticated
 *
 * APIs for managing brands
 */
class MaintenanceNoteController extends ApiController
{


    public function store(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|integer',
            'content' => 'required|string',
            'job_sheet_id' => 'required|integer',  
            'created_by' => 'nullable|integer|exists:users,id',
            'device_id' => 'nullable|integer',
            'category_status' => 'nullable|string|in:note,comment,purchase_req', 
        ]);
    
        // Handle validation failure
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Insert into database
        DB::table('maintenance_note')->insert([
    
            'title' => $request->title,
            'content' => $request->content,
            'job_sheet_id' => $request->job_sheet_id,
            'created_by' => Auth::id(),
            'device_id' => $request->device_id,
            'category_status' => $request->category_status, 
            'created_at' => now(),
        ]);

        $all_comments = DB::table('maintenance_note')
        ->leftJoin('users', 'maintenance_note.created_by', '=', 'users.id')
        ->leftJoin('repair_statuses', 'maintenance_note.title', '=', 'repair_statuses.id')
        ->where('job_sheet_id',$request->job_sheet_id)
        ->where('category_status','comment')
        ->select(
            'maintenance_note.*',
                    DB::raw("CONCAT(COALESCE(users.surname, ''),' ',COALESCE(users.first_name, ''),' ',COALESCE(users.last_name,'')) as user_name"),
            'repair_statuses.id as title_id',
            'repair_statuses.name as title_name',
            'repair_statuses.color as status_color'
        )

        ->limit(20)
        ->get();
    
        return response()->json([
            'message' => 'Maintenance Note created successfully',
            'all_comments' => $all_comments,
        ], 201);
    }

    /**
     * Update an existing Maintenance Note.
     */
    public function update(Request $request, $id)
    {
        // Check if the record exists
        $jobSheetNote = DB::table('maintenance_note')->where('id', $id)->first();
    
        if (!$jobSheetNote) {
            return response()->json(['message' => 'Maintenance Note not found'], 404);
        }
    
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'job_sheet_id' => 'nullable|integer',
            'device_id' => 'nullable|integer',
            'category_status' => 'nullable|string|in:note,comment,purchase_req', 
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Update the record
        DB::table('maintenance_note')->where('id', $id)->update([
            'content' => $request->content,
            'job_sheet_id' => $request->job_sheet_id,
            'user' => $request->user,
            'device_id' => $request->device_id,
            'category_status' => $request->category_status, 
            'updated_at' => now(), // Changed to updated_at for consistency
        ]);
    
        return response()->json([
            'message' => 'Maintenance Note updated successfully',
        ], 200);
    }

    /**
     * Delete a JobSheetNote.
     */
    public function destroy($id)
    {
        // Find the record to delete
        $jobSheetNote = DB::table('maintenance_note')->where('id', $id)->first();
    
        if (!$jobSheetNote) {
            return response()->json(['message' => 'Job Sheet Note not found'], 404);
        }
    
        // Perform the delete operation
        DB::table('maintenance_note')->where('id', $id)->delete();
    
        return response()->json(['message' => 'Job Sheet Note deleted successfully'], 200);
    }

    // -----------------------
    // New API endpoints (separate from existing ones)
    // -----------------------

    /**
     * Get all maintenance notes by job sheet ID along with purchase request status.
     * Route: GET /connector/api/maintenance-notes/by-job-sheet/{job_sheet_id}
     */
    public function getByJobSheet(Request $request, $job_sheet_id)
    {
        $validator = Validator::make(['job_sheet_id' => $job_sheet_id], [
            'job_sheet_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Return ONLY maintenance_note rows that are purchase requests for the given job sheet
        $purchase_request_notes = DB::table('maintenance_note')
            ->leftJoin('users', 'users.id', '=', 'maintenance_note.created_by')
            ->where('job_sheet_id', $job_sheet_id)
            ->where('category_status', 'purchase_req')
            ->orderBy('created_at', 'desc')
            ->select(
                'maintenance_note.id',
                'maintenance_note.title',
                'maintenance_note.content',
                'maintenance_note.job_sheet_id',
                'maintenance_note.created_by',
                'maintenance_note.device_id',
                'maintenance_note.created_at',
                'maintenance_note.category_status',
                'maintenance_note.status',
                DB::raw("TRIM(CONCAT(COALESCE(users.surname, ''), ' ', COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, ''))) AS created_by_name")
            )
            ->get();

        return response()->json([
            'success' => true,
            'job_sheet_id' => (int) $job_sheet_id,
            'notes' => $purchase_request_notes,
        ], 200);
    }

    /**
     * Store new maintenance note (separate endpoint).
     * Route: POST /connector/api/maintenance-notes/create
     */
    public function storeNewPurchase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|integer',
            'content' => 'required|string',
            'job_sheet_id' => 'required|integer|min:1',
            'device_id' => 'nullable|integer',
            'category_status' => 'required|string|in:note,comment,purchase_req',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = [
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'job_sheet_id' => $request->input('job_sheet_id'),
            'created_by' => Auth::id(),
            'device_id' => $request->input('device_id'),
            'category_status' => $request->input('category_status'),
            'created_at' => now(),
        ];

        DB::table('maintenance_note')->insert($payload);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance note created',
        ], 201);
    }

    /**
     * Update existing maintenance note (separate endpoint).
     * Route: PUT /connector/api/maintenance-notes/update/{id}
     */
    public function updateExisting(Request $request, $id)
    {
        $existing = DB::table('maintenance_note')->where('id', $id)->first();
        if (! $existing) {
            return response()->json(['message' => 'Maintenance Note not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|integer',
            'content' => 'required|string',
            'job_sheet_id' => 'nullable|integer|min:1',
            'device_id' => 'nullable|integer',
            'category_status' => 'nullable|string|in:note,comment,purchase_req',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $update = [
            'title' => $request->input('title', $existing->title),
            'content' => $request->input('content'),
            'job_sheet_id' => $request->input('job_sheet_id', $existing->job_sheet_id),
            'device_id' => $request->input('device_id', $existing->device_id),
            'category_status' => $request->input('category_status', $existing->category_status),
            'updated_at' => now(),
        ];

        DB::table('maintenance_note')->where('id', $id)->update($update);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance note updated',
        ], 200);
    }
}