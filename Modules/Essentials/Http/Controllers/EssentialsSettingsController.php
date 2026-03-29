<?php

namespace Modules\Essentials\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\Shift;

class EssentialsSettingsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            ) &&
            !$is_admin
        ) {
            abort(403, 'Unauthorized action.');
        }

        $settings = request()->session()->get('business.essentials_settings');
        $settings = !empty($settings) ? json_decode($settings, true) : [];

        $locations = BusinessLocation::forDropdown($business_id);

        // Load shifts for the import-template defaults section
        $shifts = Shift::where('business_id', $business_id)->pluck('name', 'id');

        if ($is_admin) {
            return view('essentials::settings.add')->with(
                compact('settings', 'is_admin', 'locations', 'shifts'),
            );
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (
            !(
                auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')
            ) &&
            !$is_admin
        ) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only([
                'leave_ref_no_prefix',
                'leave_instructions',
                'payroll_ref_no_prefix',
                'essentials_todos_prefix',
                'grace_before_checkin',
                'grace_after_checkin',
                'grace_before_checkout',
                'grace_after_checkout',
                // Import template defaults
                'default_import_shift_id',
                'default_import_location_id',
                'default_import_dept_id',
                'default_clock_in_time',
                'default_clock_out_time',
            ]);
            $input['is_location_required'] = !empty($request->input('is_location_required'))
                ? 1
                : 0;
            $input['calculate_sales_target_commission_without_tax'] = !empty(
                $request->input('calculate_sales_target_commission_without_tax')
            )
                ? 1
                : 0;

            // Ensure empty selectors are stored as null, not empty string
            foreach (
                ['default_import_shift_id', 'default_import_location_id', 'default_import_dept_id']
                as $key
            ) {
                if (isset($input[$key]) && $input[$key] === '') {
                    $input[$key] = null;
                }
            }

            $business = Business::find($business_id);
            $business->essentials_settings = json_encode($input);
            $business->save();

            $request->session()->put('business', $business);

            $output = ['success' => 1, 'msg' => trans('lang_v1.updated_succesfully')];
        } catch (\Exception $e) {
            \Log::emergency(
                'File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage(),
            );

            $output = ['success' => 0, 'msg' => trans('messages.something_went_wrong')];
        }

        return redirect()
            ->back()
            ->with(['status' => $output]);
    }
}
