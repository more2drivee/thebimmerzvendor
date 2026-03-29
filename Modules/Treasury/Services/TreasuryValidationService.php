<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Treasury Validation Service
 * 
 * Handles all validation logic for Treasury module
 * Centralizes validation rules and error handling
 */
class TreasuryValidationService
{
    /**
     * Validate internal transfer submission data
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateInternalTransferSubmission(array $data): array
    {
        // Determine if this is a branch transfer or payment method transfer
        $isBranchTransfer = !empty($data['from_location_id']) && !empty($data['to_location_id']);
        
        // Base rules that apply to both types
        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required_without:date|date',
            'date' => 'required_without:transfer_date|date',
            'notes' => 'nullable|string|max:500'
        ];
        
        $messages = [
            'amount.required' => __('treasury::lang.amount_required'),
            'amount.numeric' => __('treasury::lang.amount_must_be_numeric'),
            'amount.min' => __('treasury::lang.amount_must_be_positive'),
            'transfer_date.required_without' => __('treasury::lang.transfer_date_required'),
            'transfer_date.date' => __('treasury::lang.transfer_date_invalid'),
            'date.required_without' => __('treasury::lang.date_required'),
            'date.date' => __('treasury::lang.date_invalid'),
            'notes.max' => __('treasury::lang.notes_max_length')
        ];
        
        if ($isBranchTransfer) {
            // Branch transfer: different locations, same payment method
            $rules['from_location_id'] = 'required|integer|different:to_location_id';
            $rules['to_location_id'] = 'required|integer|different:from_location_id';
            $rules['payment_method'] = 'required'; // Single payment method for branch transfers
            
            $messages['from_location_id.required'] = __('treasury::lang.from_branch_required');
            $messages['from_location_id.different'] = __('treasury::lang.branches_must_be_different');
            $messages['to_location_id.required'] = __('treasury::lang.to_branch_required');
            $messages['to_location_id.different'] = __('treasury::lang.branches_must_be_different');
            $messages['payment_method.required'] = __('treasury::lang.payment_method_required');
        } else {
            // Payment method transfer: different payment methods, same location (or no location specified)
            $rules['from_payment_method'] = 'required|different:to_payment_method';
            $rules['to_payment_method'] = 'required|different:from_payment_method';
            
            $messages['from_payment_method.required'] = __('treasury::lang.from_payment_method_required');
            $messages['from_payment_method.different'] = __('treasury::lang.payment_methods_must_be_different');
            $messages['to_payment_method.required'] = __('treasury::lang.to_payment_method_required');
            $messages['to_payment_method.different'] = __('treasury::lang.payment_methods_must_be_different');
        }

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        
        // Additional business logic validation
        if ($isBranchTransfer) {
            // Ensure branches are different
            if ($validated['from_location_id'] == $validated['to_location_id']) {
                throw ValidationException::withMessages([
                    'to_location_id' => [__('treasury::lang.branches_must_be_different')]
                ]);
            }
        } else {
            // Ensure payment methods are different
            if ($validated['from_payment_method'] === $validated['to_payment_method']) {
                throw ValidationException::withMessages([
                    'to_payment_method' => [__('treasury::lang.payment_methods_must_be_different')]
                ]);
            }
        }

        return $validated;
    }

    /**
     * Validate internal transfer update data
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function validateInternalTransferUpdate(array $data): array
    {
        $rules = [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'from_payment_method' => 'required',
            'to_payment_method' => 'required|different:from_payment_method',
            'notes' => 'nullable|string|max:500'
        ];

        $messages = [
            'date.required' => __('treasury::lang.date_required'),
            'date.date' => __('treasury::lang.date_invalid'),
            'amount.required' => __('treasury::lang.amount_required'),
            'amount.numeric' => __('treasury::lang.amount_must_be_numeric'),
            'amount.min' => __('treasury::lang.amount_must_be_positive'),
            'from_payment_method.required' => __('treasury::lang.from_payment_method_required'),
            'to_payment_method.required' => __('treasury::lang.to_payment_method_required'),
            'to_payment_method.different' => __('treasury::lang.payment_methods_must_be_different'),
            'notes.max' => __('treasury::lang.notes_max_length')
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate treasury transaction filters
     *
     * @param array $data
     * @return array
     */
    public function validateTransactionFilters(array $data): array
    {
        $rules = [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location_id' => 'nullable|integer|min:1',
            'transaction_type' => 'nullable|string|in:expense,sell,purchase,opening_balance,sell_return,purchase_return,payroll',
            'payment_status' => 'nullable|string|in:paid,partial,due'
        ];

        $messages = [
            'start_date.date' => __('treasury::lang.start_date_invalid'),
            'end_date.date' => __('treasury::lang.end_date_invalid'),
            'end_date.after_or_equal' => __('treasury::lang.end_date_must_be_after_start_date'),
            'location_id.integer' => __('treasury::lang.location_id_must_be_integer'),
            'location_id.min' => __('treasury::lang.location_id_must_be_positive'),
            'transaction_type.in' => __('treasury::lang.invalid_transaction_type'),
            'payment_status.in' => __('treasury::lang.invalid_payment_status')
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray()
            ];
        }

        return [
            'success' => true,
            'data' => $validator->validated()
        ];
    }

    /**
     * Validate dashboard date range
     *
     * @param array $data
     * @return array
     */
    public function validateDashboardDateRange(array $data): array
    {
        $rules = [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'location_id' => 'nullable|integer|min:1'
        ];

        $messages = [
            'start_date.date' => __('treasury::lang.start_date_invalid'),
            'end_date.date' => __('treasury::lang.end_date_invalid'),
            'end_date.after_or_equal' => __('treasury::lang.end_date_must_be_after_start_date'),
            'location_id.integer' => __('treasury::lang.location_id_must_be_integer'),
            'location_id.min' => __('treasury::lang.location_id_must_be_positive')
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray()
            ];
        }

        return [
            'success' => true,
            'data' => $validator->validated()
        ];
    }

    /**
     * Validate internal transfer filters
     *
     * @param array $data
     * @return array
     */
    public function validateInternalTransferFilters(array $data): array
    {
        $rules = [
            'date_filter' => 'nullable|string',
            'payment_method_filter' => 'nullable|string',
            'amount_filter' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        // Additional validation for date_filter format
        if (!empty($data['date_filter']) && strpos($data['date_filter'], ' to ') !== false) {
            $dates = explode(' to ', $data['date_filter']);
            if (count($dates) !== 2) {
                return [
                    'success' => false,
                    'errors' => ['date_filter' => [__('treasury::lang.invalid_date_range_format')]]
                ];
            }

            foreach ($dates as $date) {
                if (!\Carbon\Carbon::createFromFormat('m/d/Y', trim($date))) {
                    return [
                        'success' => false,
                        'errors' => ['date_filter' => [__('treasury::lang.invalid_date_format')]]
                    ];
                }
            }
        }

        // Additional validation for amount_filter format
        if (!empty($data['amount_filter']) && strpos($data['amount_filter'], '-') !== false) {
            $amounts = explode('-', $data['amount_filter']);
            if (count($amounts) !== 2) {
                return [
                    'success' => false,
                    'errors' => ['amount_filter' => [__('treasury::lang.invalid_amount_range_format')]]
                ];
            }

            foreach ($amounts as $amount) {
                if (!is_numeric(trim($amount))) {
                    return [
                        'success' => false,
                        'errors' => ['amount_filter' => [__('treasury::lang.invalid_amount_format')]]
                    ];
                }
            }
        }

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray()
            ];
        }

        return [
            'success' => true,
            'data' => $validator->validated()
        ];
    }

    /**
     * Validate business access permissions
     *
     * @param int $business_id
     * @param int $user_business_id
     * @return bool
     */
    public function validateBusinessAccess(int $business_id, int $user_business_id): bool
    {
        return $business_id === $user_business_id;
    }

    /**
     * Validate transaction access permissions
     *
     * @param string $action
     * @param string $permission_prefix
     * @return bool
     */
    public function validateTransactionPermission(string $action, string $permission_prefix = 'treasury'): bool
    {
        $permission_map = [
            'view' => "{$permission_prefix}.view",
            'create' => "{$permission_prefix}.create",
            'edit' => "{$permission_prefix}.edit",
            'delete' => "{$permission_prefix}.delete"
        ];

        $permission = $permission_map[$action] ?? null;
        
        if (!$permission) {
            return false;
        }

        return auth()->user()->can($permission);
    }

    /**
     * Validate amount format and constraints
     *
     * @param mixed $amount
     * @param float $min_amount
     * @param float|null $max_amount
     * @return array
     */
    public function validateAmount($amount, float $min_amount = 0.01, ?float $max_amount = null): array
    {
        if (!is_numeric($amount)) {
            return [
                'success' => false,
                'message' => __('treasury::lang.amount_must_be_numeric')
            ];
        }

        $amount = (float) $amount;

        if ($amount < $min_amount) {
            return [
                'success' => false,
                'message' => __('treasury::lang.amount_must_be_at_least', ['amount' => $min_amount])
            ];
        }

        if ($max_amount !== null && $amount > $max_amount) {
            return [
                'success' => false,
                'message' => __('treasury::lang.amount_must_not_exceed', ['amount' => $max_amount])
            ];
        }

        return [
            'success' => true,
            'amount' => $amount
        ];
    }

    /**
     * Validate date range
     *
     * @param string|null $start_date
     * @param string|null $end_date
     * @param int $max_days
     * @return array
     */
    public function validateDateRange(?string $start_date, ?string $end_date, int $max_days = 365): array
    {
        if (!$start_date && !$end_date) {
            return ['success' => true];
        }

        if ($start_date && !$end_date) {
            return [
                'success' => false,
                'message' => __('treasury::lang.end_date_required_when_start_date_provided')
            ];
        }

        if (!$start_date && $end_date) {
            return [
                'success' => false,
                'message' => __('treasury::lang.start_date_required_when_end_date_provided')
            ];
        }

        try {
            $start = \Carbon\Carbon::parse($start_date);
            $end = \Carbon\Carbon::parse($end_date);

            if ($end->lt($start)) {
                return [
                    'success' => false,
                    'message' => __('treasury::lang.end_date_must_be_after_start_date')
                ];
            }

            if ($start->diffInDays($end) > $max_days) {
                return [
                    'success' => false,
                    'message' => __('treasury::lang.date_range_too_large', ['days' => $max_days])
                ];
            }

            return [
                'success' => true,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('treasury::lang.invalid_date_format')
            ];
        }
    }

    /**
     * Validate payment method existence
     *
     * @param string $payment_method
     * @param array $available_methods
     * @return array
     */
    public function validatePaymentMethod(string $payment_method, array $available_methods): array
    {
        if (!array_key_exists($payment_method, $available_methods)) {
            return [
                'success' => false,
                'message' => __('treasury::lang.invalid_payment_method')
            ];
        }

        return [
            'success' => true,
            'method_name' => $available_methods[$payment_method]
        ];
    }

    /**
     * Sanitize and validate notes input
     *
     * @param string|null $notes
     * @param int $max_length
     * @return array
     */
    public function validateNotes(?string $notes, int $max_length = 500): array
    {
        if ($notes === null) {
            return [
                'success' => true,
                'notes' => ''
            ];
        }

        $notes = trim(strip_tags($notes));

        if (strlen($notes) > $max_length) {
            return [
                'success' => false,
                'message' => __('treasury::lang.notes_max_length', ['length' => $max_length])
            ];
        }

        return [
            'success' => true,
            'notes' => $notes
        ];
    }
}