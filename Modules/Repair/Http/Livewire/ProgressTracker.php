<?php

namespace Modules\Repair\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ProgressTracker extends Component
{
    public $jobOrderId;
    public $statuses;
    public $currentStatusId;

    protected $listeners = ['statusUpdated' => 'refreshStatus'];

    public function mount($jobOrderId)
    {
        $this->jobOrderId = $jobOrderId;
        $this->fetchStatuses();
    }

    public function fetchStatuses()
    {
        $this->statuses = DB::table('repair_statuses')
            ->select('name', 'id','color')
            ->where('status_category', 'status')
            ->get();

        $status = DB::table('repair_job_sheets')
            ->select('status_id')
            ->where('id', $this->jobOrderId)
            ->first();

        $this->currentStatusId = $status ? $status->status_id : null;
    }


    public function refreshStatus()
    {
        $this->fetchStatuses();
    }

    public function render()
    {
        return view('repair::Livewire.progress-tracker');
    }
}
