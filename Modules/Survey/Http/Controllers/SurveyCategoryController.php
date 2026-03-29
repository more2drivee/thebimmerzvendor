<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class SurveyCategoryController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('survey.view')) {
            abort(403, 'Unauthorized action.');
        }

        return view('survey::category.index');
    }

    public function data()
    {
        if (! auth()->user()->can('survey.view')) {
            abort(403, 'Unauthorized action.');
        }

        $categories = DB::table('survey_categories')
            ->select('id', 'name', 'active', 'created_at');

        return DataTables::of($categories)
            ->addColumn('status', function ($row) {
                return $row->active ? __('messages.active') : __('messages.inactive');
            })
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">'
                    . '<button type="button" class="btn btn-xs btn-primary edit-category" data-id="' . $row->id . '" data-name="' . e($row->name) . '" data-active="' . $row->active . '"><i class="fa fa-edit"></i> ' . __('messages.edit') . '</button>';

                if (auth()->user()->can('survey.delete')) {
                    $html .= '<button type="button" class="btn btn-xs btn-danger delete-category" data-id="' . $row->id . '"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</button>';
                }

                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('survey.update')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['nullable', Rule::in([0, 1, true, false])],
        ]);

        DB::table('survey_categories')->insert([
            'name' => $data['name'],
            'active' => (bool) ($data['active'] ?? true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => __('messages.added_success')]);
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('survey.update')) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'active' => ['nullable', Rule::in([0, 1, true, false])],
        ]);

        DB::table('survey_categories')
            ->where('id', $id)
            ->update([
                'name' => $data['name'],
                'active' => (bool) ($data['active'] ?? true),
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true, 'message' => __('messages.updated_success')]);
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('survey.delete')) {
            abort(403, 'Unauthorized action.');
        }

        DB::table('survey_categories')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => __('messages.deleted_success')]);
    }

    public function getActiveCategories()
    {
        $categories = DB::table('survey_categories')
            ->where('active', 1)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return response()->json(['categories' => $categories]);
    }
}
