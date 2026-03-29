<?php

namespace Modules\Sms\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sms\Entities\SmsMessage;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Modules\Sms\Entities\SmsLog;
use Illuminate\Support\Facades\DB;
use App\Contact;

class SmsMessageController extends Controller
{
    /**
     * Display a listing of the SMS messages.
     */
    public function index(): Renderable
    {
        return view('sms::sms_messages.index');
    }

    /**
     * Dashboard view showing messages and SMS logs
     */
    public function dashboard(\Modules\Sms\Services\EPushService $smsService): Renderable
    {
        $totalMessages = SmsMessage::count();
        $totalSent = SmsLog::where('status', 'sent')->count();
        $totalFailed = SmsLog::where('status', 'failed')->count();

        // Prefer latest provider_balance from sms_logs; fallback to service balance
        $latestBalance = SmsLog::whereNotNull('provider_balance')
            ->orderByDesc('id')
            ->value('provider_balance');

        if ($latestBalance !== null) {
            $balance = $latestBalance;
        } else {
            // Try to get balance, default to 0.00 if failed
            $balance = $smsService->getBalance();
        }

        return view('sms::sms_messages.dashboard', compact('totalMessages', 'totalSent', 'totalFailed', 'balance'));
    }

    /**
     * Get SMS messages for DataTable
     */
    public function getData(Request $request)
    {
        $messages = SmsMessage::select('sms_messages.*');

        return DataTables::of($messages)
            ->addColumn('action', function ($row) {
                return view('sms::sms_messages.actions', compact('row'))->render();
            })
            ->addColumn('roles', function ($row) {
                $roleNames = [];
                $roleIds = is_array($row->roles) ? $row->roles : [];

                if (!empty($roleIds)) {
                    $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->toArray();
                }

                return !empty($roleNames)
                    ? e(implode(', ', $roleNames))
                    : '<span class="badge badge-secondary">No roles assigned</span>';
            })
            ->addColumn('status', function ($row) {
                $badge = $row->status ? 'badge-success' : 'badge-danger';
                $text = $row->status ? 'Active' : 'Inactive';
                return "<span class='badge {$badge}'>{$text}</span>";
            })
            ->rawColumns(['action', 'roles', 'status'])
            ->make(true);
    }

    /**
     * Show the form for creating a new SMS message.
     */
    public function create(): Renderable
    {
        $roles = Role::all();
        return view('sms::sms_messages.create', compact('roles'));
    }

    /**
     * Store a newly created SMS message in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:sms_messages,name',
            'message_template' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
            'roles' => 'array|nullable',
            'roles.*' => 'exists:roles,id',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = $request->boolean('status', true);

        $message = SmsMessage::create($validated);

        if ($request->has('roles')) {
            $message->roles = $request->roles ?? [];
            $message->save();
        }

        return redirect()->route('sms.messages.index')
            ->with('success', 'SMS message created successfully');
    }

    /**
     * Show the specified SMS message.
     */
    public function show($id): Renderable
    {
        // roles are stored as JSON array of role IDs, no Eloquent relationship
        $message = SmsMessage::findOrFail($id);

        $roleIds = is_array($message->roles) ? $message->roles : [];
        $roles = !empty($roleIds)
            ? Role::whereIn('id', $roleIds)->get()
            : collect();

        return view('sms::sms_messages.show', compact('message', 'roles'));
    }

    /**
     * Show the form for editing the specified SMS message.
     */
    public function edit($id): Renderable
    {
        // roles are stored as JSON array of role IDs, no Eloquent relationship
        $message = SmsMessage::findOrFail($id);
        $roles = Role::all();
        $assignedRoles = is_array($message->roles) ? $message->roles : [];

        return view('sms::sms_messages.edit', compact('message', 'roles', 'assignedRoles'));
    }

    /**
     * Update the specified SMS message in storage.
     */
    public function update(Request $request, $id)
    {
        $message = SmsMessage::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:sms_messages,name,' . $id,
            'message_template' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
            'roles' => 'array|nullable',
            'roles.*' => 'exists:roles,id',
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['status'] = $request->boolean('status', true);

        $message->update($validated);

        if ($request->has('roles')) {
            $message->roles = $request->roles ?? [];
            $message->save();
        }

        return redirect()->route('sms.messages.index')
            ->with('success', 'SMS message updated successfully');
    }

    /**
     * Remove the specified SMS message from storage.
     */
    public function destroy($id)
    {
        $message = SmsMessage::findOrFail($id);
        $message->delete();

        return redirect()->route('sms.messages.index')
            ->with('success', 'SMS message deleted successfully');
    }

    /**
     * Assign roles to SMS message via AJAX
     */
    public function assignRoles(Request $request, $id)
    {
        $message = SmsMessage::findOrFail($id);

        $validated = $request->validate([
            'roles' => 'array|required',
            'roles.*' => 'exists:roles,id',
        ]);

        // Store role IDs in JSON column via model helper
        $message->syncRoles($validated['roles']);

        // Resolve role names from Spatie Role model for response
        $roleNames = Role::whereIn('id', $validated['roles'])->pluck('name');

        return response()->json([
            'success' => true,
            'message' => 'Roles assigned successfully',
            'roles' => $roleNames,
        ]);
    }

    /**
     * Get SMS logs for DataTable
     */
    public function logsData(Request $request)
    {
        $query = DB::table('sms_logs')
            ->leftJoin('sms_messages', 'sms_logs.sms_message_id', '=', 'sms_messages.id')
            ->leftJoin('contacts', 'sms_logs.contact_id', '=', 'contacts.id')
            ->select('sms_logs.*', 'sms_messages.name as message_name', 'contacts.name as contact_name', 'contacts.mobile as contact_mobile');

        return DataTables::of($query)
            ->addColumn('message_name', function ($row) {
                return $row->message_name ?? '<span class="text-muted">-</span>';
            })
            ->addColumn('message_content', function ($row) {
                if (empty($row->message_content)) {
                    return '<span class="text-muted">-</span>';
                }
                // Return a button to show the full message in a modal
                return '<button class="btn btn-xs btn-primary btn-show-message" 
                            data-message="' . e($row->message_content) . '"
                            data-id="' . $row->id . '">
                            <i class="fas fa-eye"></i> ' . __('sms::lang.show') . '
                        </button>';
            })
            ->addColumn('contact_name', function ($row) {
                return $row->contact_name ?? '<span class="text-muted">-</span>';
            })
            ->addColumn('contact_mobile', function ($row) {
                return $row->contact_mobile ?? $row->mobile ?? '<span class="text-muted">-</span>';
            })
            ->addColumn('status', function ($row) {
                $badge = $row->status === 'sent' ? 'badge-success' : 'badge-danger';
                return "<span class='badge {$badge}'>" . ucfirst($row->status) . "</span>";
            })
            ->editColumn('sent_at', function ($row) {
                return $row->sent_at ? date('Y-m-d H:i:s', strtotime($row->sent_at)) : '-';
            })
            ->rawColumns(['message_name', 'message_content', 'contact_name', 'contact_mobile', 'status'])
            ->make(true);
    }
}
