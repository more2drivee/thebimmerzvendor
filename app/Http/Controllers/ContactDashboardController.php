<?php

namespace App\Http\Controllers;

use App\Contact;
use App\CustomerGroup;
use App\LoyaltyDiscountRequest;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;

class ContactDashboardController extends Controller
{
    protected $contactUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    public function __construct(
        ContactUtil $contactUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $counters = [
            __('contact.suppliers') => [
                'data' => Contact::where('business_id', $business_id)
                    ->where('type', 'supplier')
                    ->count(),
                'icon' => 'fa fa-truck'
            ],
            __('contact.customers') => [
                'data' => Contact::where('business_id', $business_id)
                    ->where('type', 'customer')
                    ->count(),
                'icon' => 'fa fa-users'
            ],
            __('lang_v1.customer_groups') => [
                'data' => CustomerGroup::where('business_id', $business_id)
                    ->count(),
                'icon' => 'fa fa-sitemap'
            ],
            __('lang_v1.loyalty_requests') => [
                'data' => LoyaltyDiscountRequest::forBusiness($business_id)
                    ->where('status', 'pending')
                    ->count(),
                'icon' => 'fa fa-clock-o'
            ]
        ];

        $recent_suppliers = Contact::where('business_id', $business_id)
            ->where('type', 'supplier')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recent_customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recent_loyalty_requests = LoyaltyDiscountRequest::forBusiness($business_id)
            ->with(['contact'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('contact.dashboard.main', compact(
            'counters',
            'recent_suppliers',
            'recent_customers',
            'recent_loyalty_requests'
        ));
    }
}
