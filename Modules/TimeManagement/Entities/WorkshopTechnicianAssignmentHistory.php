<?php

namespace Modules\TimeManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkshopTechnicianAssignmentHistory extends Model
{
    use HasFactory;

    protected $table = 'workshop_assignments';
    protected $fillable = [
        'workshop_id',
        'user_id',
        'job_sheet_id',
        'assigned_by',
        'status',
        'notes',
        'assignment_type',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the workshop that the technician was assigned to
     */
    public function workshop()
    {
        return $this->belongsTo(\Modules\Repair\Entities\Workshop::class, 'workshop_id');
    }

    /**
     * Get the technician who was assigned
     */
    public function technician()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    /**
     * Get the user who made the assignment
     */
    public function assignedBy()
    {
        return $this->belongsTo(\App\User::class, 'assigned_by');
    }

    /**
     * Scope for active assignments (not unassigned yet)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'assigned');
    }

    /**
     * Scope for assignments created within a date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get assignments for a specific technician
     */
    public function scopeForTechnician($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for job sheet assignments.
     */
    public function scopeForJobSheet($query, $jobSheetId)
    {
        return $query->where('job_sheet_id', $jobSheetId);
    }

    /**
     * Scope for specific assignment type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('assignment_type', $type);
    }

    /**
     * Scope for assignments created on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    /**
     * Get current assignments for given user IDs and date.
     */
    public static function getCurrentAssignments($userIds, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return static::with('workshop')
            ->whereIn('user_id', $userIds)
            ->where('assignment_type', 'workshop')
            ->where('status', 'assigned')
            ->whereDate('created_at', $date)
            ->get()
            ->keyBy('user_id')
            ->map(function ($assignment) {
                return $assignment->workshop->name ?? null;
            });
    }

    /**
     * Get current workshop assignments for given job sheet IDs and date.
     */
    public static function getCurrentJobAssignments($jobSheetIds, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return static::with('workshop')
            ->whereIn('job_sheet_id', $jobSheetIds)
            ->where('assignment_type', 'job_sheet')
            ->where('status', 'assigned')
            ->whereDate('created_at', $date)
            ->get()
            ->groupBy('job_sheet_id')
            ->map(function ($assignments) {
                return $assignments->pluck('workshop.name')->filter()->values()->toArray();
            });
    }
}
