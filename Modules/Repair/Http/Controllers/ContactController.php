<?php

namespace Modules\Repair\Http\Controllers;

use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    protected $contactUtil;

    protected $moduleUtil;

    protected $commonUtil;

    public function __construct(ContactUtil $contactUtil, ModuleUtil $moduleUtil, Util $commonUtil)
    {
        $this->contactUtil = $contactUtil;
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
    }

    public function editBasic($id)
    {
        if (! auth()->user()->can('customer.update') && ! auth()->user()->can('supplier.update') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $contact = Contact::where('business_id', $business_id)->findOrFail($id);

        // Get assigned users dropdown
        $assigned_users = \App\User::forDropdown($business_id, false);

        // Get currently assigned users for this contact
        $contact_assigned_users = DB::table('user_contact_access')
            ->where('contact_id', $id)
            ->pluck('user_id')
            ->toArray();

        return view('repair::contact.edit_basic')->with(compact('contact', 'assigned_users', 'contact_assigned_users'));
    }

    public function updateBasic(Request $request, $id)
    {
        if (! auth()->user()->can('customer.update') && ! auth()->user()->can('supplier.update') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        if (! $request->ajax()) {
            abort(400, 'Invalid request.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            }

            $input = $request->only([
                'first_name',
                'middle_name',
                'last_name',
                'mobile',
            ]);

            $assigned_users = $request->input('assigned_users', []);

            $name_array = [];

            if (! empty($input['first_name'])) {
                $name_array[] = $input['first_name'];
            }
            if (! empty($input['middle_name'])) {
                $name_array[] = $input['middle_name'];
            }
            if (! empty($input['last_name'])) {
                $name_array[] = $input['last_name'];
            }

            $input['name'] = trim(implode(' ', $name_array));

            // Check for duplicate mobile in the same business
            if (! empty($input['mobile'])) {
                $duplicate_contact = Contact::where('business_id', $business_id)
                    ->where('mobile', $input['mobile'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($duplicate_contact) {
                    $current_contact = Contact::where('business_id', $business_id)->find($id);

                    return response()->json([
                        'success' => false,
                        'msg' => __('contact.mobile_already_exists'),
                        'duplicate_mobile' => true,
                        'current_contact_id' => $id,
                        'current_contact_name' => $current_contact ? $current_contact->name : '',
                        'duplicate_contact_id' => $duplicate_contact->id,
                        'duplicate_contact_name' => $duplicate_contact->name,
                        'mobile' => $input['mobile'],
                    ]);
                }
            }

            $output = $this->contactUtil->updateContact($input, $id, $business_id);

            // Update assigned users for this contact
            if (is_array($assigned_users)) {
                // Delete existing user_contact_access for this contact
                DB::table('user_contact_access')->where('contact_id', $id)->delete();

                // Insert new user_contact_access records
                foreach ($assigned_users as $user_id) {
                    DB::table('user_contact_access')->insert([
                        'contact_id' => $id,
                        'user_id' => $user_id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::emergency('Repair basic contact update failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return response()->json($output);
    }

    public function mergeContacts(Request $request)
    {
        if (! auth()->user()->can('customer.update') && ! auth()->user()->can('supplier.update') && ! auth()->user()->can('customer.view_own') && ! auth()->user()->can('supplier.view_own')) {
            abort(403, 'Unauthorized action.');
        }

        if (! $request->ajax()) {
            abort(400, 'Invalid request.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            }

            $keep_contact_id = $request->input('keep_contact_id');
            $merge_contact_id = $request->input('merge_contact_id');

            if (! $keep_contact_id || ! $merge_contact_id) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }

            if ($keep_contact_id == $merge_contact_id) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }

            $keep_contact = Contact::where('business_id', $business_id)->findOrFail($keep_contact_id);
            $merge_contact = Contact::where('business_id', $business_id)->findOrFail($merge_contact_id);

            DB::beginTransaction();

            // Update all transactions to point to the kept contact
            Transaction::where('business_id', $business_id)
                ->where('contact_id', $merge_contact_id)
                ->update(['contact_id' => $keep_contact_id]);

            // Update transaction payments (payment_for field)
            TransactionPayment::where('business_id', $business_id)
                ->where('payment_for', $merge_contact_id)
                ->update(['payment_for' => $keep_contact_id]);

            // Update contact devices using DB::table
            DB::table('contact_device')
                ->where('contact_id', $merge_contact_id)
                ->update(['contact_id' => $keep_contact_id]);

            // Update job estimators using DB::table
            DB::table('job_estimator')
                ->where('business_id', $business_id)
                ->where('contact_id', $merge_contact_id)
                ->update(['contact_id' => $keep_contact_id]);

            // Update bookings using DB::table
            DB::table('bookings')
                ->where('business_id', $business_id)
                ->where('contact_id', $merge_contact_id)
                ->update(['contact_id' => $keep_contact_id]);

            // Update job sheets using DB::table
            DB::table('repair_job_sheets')
                ->where('contact_id', $merge_contact_id)
                ->update(['contact_id' => $keep_contact_id]);

            // Update user contact access
            DB::table('user_contact_access')
                ->where('contact_id', $merge_contact_id)
                ->update(['contact_id' => $keep_contact_id]);

            // Force delete the merged contact
            Contact::where('id', $merge_contact_id)->forceDelete();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('contact.contacts_merged_successfully'),
                'kept_contact_id' => $keep_contact_id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::emergency('Repair contact merge failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return response()->json($output);
    }

    public function checkMobile(Request $request)
    {
        if (! $request->ajax()) {
            abort(400, 'Invalid request.');
        }

        $mobile = $request->input('mobile');
        $contact_id = $request->input('contact_id');

        if (empty($mobile)) {
            return response()->json([
                'valid' => true,
            ]);
        }

        $business_id = $request->session()->get('user.business_id');

        $query = Contact::where('business_id', $business_id)
            ->where('mobile', $mobile);

        if ($contact_id) {
            $query->where('id', '!=', $contact_id);
        }

        $duplicate = $query->first();

        if ($duplicate) {
            return response()->json([
                'valid' => false,
                'duplicate_mobile' => true,
                'duplicate_contact_id' => $duplicate->id,
                'duplicate_contact_name' => $duplicate->name,
            ]);
        }

        return response()->json([
            'valid' => true,
        ]);
    }
}