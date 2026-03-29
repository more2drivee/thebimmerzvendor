<?php

namespace App\Http\Controllers;

use App\GenericSparePart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class GenericSparePartController extends Controller
{
    public function index()
    {
        return view('generic_spare_parts.index');
    }

    public function datatable(Request $request)
    {
        $query = GenericSparePart::with(['business', 'creator'])
            ->where('business_id', session('user.business_id'));

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return [
                    'edit_id' => $row->id,
                    'delete_id' => $row->id,
                ];
            })
            ->editColumn('created_by', function ($row) {
                return $row->creator ? $row->creator->name : '-';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d H:i:s');
            })
            ->toJson();
    }

    public function create()
    {
        $html = view('generic_spare_parts.partials.form', [
            'genericSparePart' => null,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['business_id'] = session('user.business_id');
        $data['created_by'] = Auth::id();

        GenericSparePart::create($data);

        return response()->json([
            'success' => true,
            'msg' => __('generic_spare_parts.created_successfully'),
        ]);
    }

    public function edit($id)
    {
        $genericSparePart = GenericSparePart::find($id);
        if (!$genericSparePart) {
            return response()->json([
                'success' => false,
                'msg' => __('generic_spare_parts.not_found'),
            ]);
        }

        $html = view('generic_spare_parts.partials.form', [
            'genericSparePart' => $genericSparePart,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function update(Request $request, $id)
    {
        $genericSparePart = GenericSparePart::find($id);
        if (!$genericSparePart) {
            return response()->json([
                'success' => false,
                'msg' => __('generic_spare_parts.not_found'),
            ]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $data['updated_by'] = Auth::id();

        $genericSparePart->update($data);

        return response()->json([
            'success' => true,
            'msg' => __('generic_spare_parts.updated_successfully'),
        ]);
    }

    public function destroy($id)
    {
        $genericSparePart = GenericSparePart::find($id);
        if (!$genericSparePart) {
            return response()->json([
                'success' => false,
                'msg' => __('generic_spare_parts.not_found'),
            ]);
        }

        $genericSparePart->delete();

        return response()->json([
            'success' => true,
            'msg' => __('generic_spare_parts.deleted_successfully'),
        ]);
    }
}
