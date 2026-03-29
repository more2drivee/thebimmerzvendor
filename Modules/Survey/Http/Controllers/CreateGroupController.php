<?php

namespace Modules\Survey\Http\Controllers;

use AWS\CRT\HTTP\Request as HTTPRequest;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;


class CreateGroupController extends Controller
{
    public function index()
    {
        $contacts = DB::table('contacts')->select('name', 'email', 'id')->get();
        return view('survey::creategroup.index', compact('contacts'));
    }

    public function store(Request $request)
    {
        DB::table('groups')->insert([
            'name' => $request->title
        ]);
        $group_id = DB::table('groups')->select('id')->where('name', $request->title)->first();
        for ($i = 0; $i < count($request->contact_id); $i++) {
            DB::table('user_group')->insert([
                'user_id' => $request->contact_id[$i],
                'group_id' => $group_id->id
            ]);
        }
        return view('survey::survey.index');
    }

    public function indexService()
    {
        $services = DB::table('types_of_services')->select('name', 'id')->get();
        return view('survey::creategroup.service', compact('services'));
    }

    public function storeService(Request $request)
    {
        // dd($request);
        DB::table('groups')->insert([
            'name' => $request->title
        ]);
        $group_id = DB::table('groups')->select('id')->where('name', $request->title)->first();
        for ($i = 0; $i < count($request->contact_id); $i++) {
            $contact_services = DB::table('repear')->select('contact_id')->where('type_service_id', $request->contact_id[$i])->get();
            foreach ($contact_services as $contact) {
                DB::table('user_group')->insert([
                    'user_id' => $contact->contact_id,
                    'group_id' => $group_id->id
                ]);
            }
        }
        return view('survey::survey.index');
    }

    public function showGroups()
    {
        $groups = DB::table('groups')->select('name', 'id')->get();

        // dd($groups);
        $contacts = DB::table('contacts')->select('name', 'id')->get();
        return view('survey::groupsurvey.show', compact('groups', 'contacts'));
    }



    public function getGroupsData()
    {
        $groups = DB::table('groups')
            ->select(
                'groups.name',
                'groups.id',
            )
            ->get();
        // dd($groups);
        return DataTables::of($groups)
            ->addColumn(
                'action',
                function ($group) {
                    $html = '<div class="btn-group">
                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    ' . __('messages.actions') . '
                    <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    // View Group
                    if (auth()->user()->can('survey.view')) {
                        $html .= '<li><a href="' . route('group.show', $group->id) . '" class="view-product"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                    }

                    // Edit Group
                    if (auth()->user()->can('survey.update')) {
                        $html .= '<li><a href="' . route('group.edit', $group->id) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                    }

                    // Delete Group
                    if (auth()->user()->can('survey.delete')) {
                        $html .= '<li><a href="' . route('group.delete', $group->id) . '" class="delete-product"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                }
            )
            ->rawColumns(['action'])
            ->make(true);
    }

    public function delete($id)
    {
        DB::table('groups')->where('id', $id)->delete();
        DB::table('user_group')->where('group_id', $id)->delete();
        return redirect('/group');
    }

    public function edit($id)
    {
        $groupName = DB::table('groups')->select('name', 'id')->where('id', $id)->first();
        $contactsID = DB::table('user_group')
            ->where('group_id', $id)
            ->join('contacts', 'user_group.user_id', '=', 'contacts.id')
            ->select('user_group.user_id', 'contacts.name')
            ->get();
        // dd($contactsID);
        $contacts = DB::table('contacts')->select('id', 'name')->get();
        return view('survey::groupsurvey.edit', compact('groupName', 'contactsID', 'contacts'));
    }

    public function update(Request $request)
    {
        // dd($request);
        DB::table('groups')
            ->where('id', $request->group_id)
            ->update([
                'name' => $request->title
            ]);
        DB::table('user_group')->where('group_id', $request->group_id)->delete();
        for ($i = 0; $i < count($request->contact_id); $i++) {
            DB::table('user_group')->insert([
                'user_id' => $request->contact_id[$i],
                'group_id' => $request->group_id
            ]);
        }
        return redirect('/group');
    }

    public function show($id)
    {
        $groupName = DB::table('groups')->select('name', 'id')->where('id', $id)->first();
        $contactsID = DB::table('user_group')
            ->where('group_id', $id)
            ->join('contacts', 'user_group.user_id', '=', 'contacts.id')
            ->select('user_group.user_id', 'contacts.name')
            ->get();
        // dd($contactsID);
        $contacts = DB::table('contacts')->select('id', 'name')->get();
        return view('survey::creategroup.show', compact('groupName', 'contactsID', 'contacts'));
    }
}
