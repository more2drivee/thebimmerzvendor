<?php

namespace Modules\Repair\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class PartsApproval extends Component
{
    public $jobOrderId;
    public $parts = [];
    public $totalCount = 0;
    public $totalPrice = 0;

    public function mount($jobOrderId)
    {
        $this->jobOrderId = $jobOrderId;
        $this->loadParts();
    }

    public function loadParts()
    {
        $this->parts = DB::table('job_orders')
            ->where('job_order_id', $this->jobOrderId)
            ->join('products', 'products.id', '=', 'job_orders.product_id')
            ->select('job_orders.id', 'products.name', 'job_orders.price', 'job_orders.quantity', 'job_orders.client_approval')
            ->get();
    }

    public function toggleApproval($partId)
    {
        DB::table('job_orders')
            ->where('id', $partId)
            ->update(['client_approval' => DB::raw('1 - client_approval')]);

        $this->loadParts();
        $this->updateTotals();
    }

    public function updateTotals()
    {
        $approvedParts = collect($this->parts)->where('client_approval', 1);

        $this->totalCount = $approvedParts->sum('quantity');
        $this->totalPrice = $approvedParts->sum(fn($part) => $part->price * $part->quantity);
    }

    public function render()
    {
        return view('repair::Livewire.parts-approval');
    }
}
