<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class InventoryDeliveryReturnController extends Controller
{
    public function index()
    {
        return view('inventory_delivery_returns.index');
    }

    public function datatable(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        /** @var \App\User $user */
        $user = auth()->user();

        $query = DB::table('product_joborder as pjo')
            ->join('repair_job_sheets as rjs', 'rjs.id', '=', 'pjo.job_order_id')
            ->leftJoin('products as p', 'p.id', '=', 'pjo.product_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'rjs.contact_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'rjs.location_id')
            ->where('pjo.inventory_delivery', 1)
            ->where('rjs.business_id', (int) $business_id);

        $permitted_locations = $user->permitted_locations($business_id);
        if ($permitted_locations !== 'all') {
            $query->whereIn('rjs.location_id', $permitted_locations);
        }

        $query->select(
            'pjo.id',
            'pjo.job_order_id',
            'pjo.product_id',
            'pjo.quantity',
            'pjo.price',
            'pjo.inventory_delivery',
            'rjs.job_sheet_no',
            'rjs.location_id',
            DB::raw('COALESCE(p.name, "") as product_name'),
            DB::raw('COALESCE(c.name, "") as customer_name'),
            DB::raw('COALESCE(bl.name, "") as location_name')
        );

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $url = action([\App\Http\Controllers\InventoryDeliveryReturnController::class, 'returnToInventory'], [$row->id]);

                return '<button type="button" class="btn btn-xs btn-danger js-return-inventory-delivery" data-href="' . e($url) . '">'
                    . __('inventory_delivery_returns.actions.return')
                    . '</button>';
            })
            ->editColumn('job_sheet_no', function ($row) {
                return $row->job_sheet_no ?? '';
            })
            ->editColumn('product_name', function ($row) {
                return $row->product_name ?? '';
            })
            ->editColumn('customer_name', function ($row) {
                return $row->customer_name ?? '';
            })
            ->editColumn('location_name', function ($row) {
                return $row->location_name ?? '';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function returnToInventory($id, Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');

            /** @var \App\User $user */
            $user = auth()->user();

            $record = DB::table('product_joborder as pjo')
                ->join('repair_job_sheets as rjs', 'rjs.id', '=', 'pjo.job_order_id')
                ->where('pjo.id', (int) $id)
                ->where('rjs.business_id', (int) $business_id)
                ->select('pjo.id', 'rjs.location_id')
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'msg' => __('Record not found'),
                ]);
            }

            $permitted_locations = $user->permitted_locations($business_id);
            if ($permitted_locations !== 'all' && (!is_array($permitted_locations) || !in_array((int) $record->location_id, $permitted_locations, true))) {
                return response()->json([
                    'success' => false,
                    'msg' => __('Unauthorized action.'),
                ]);
            }

            DB::table('product_joborder')
                ->where('id', (int) $id)
                ->update([
                    'inventory_delivery' => 0,
                    'out_for_deliver' => 0,
                ]);

            return response()->json([
                'success' => true,
                'msg' => __('Updated successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
