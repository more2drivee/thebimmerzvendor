<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Restaurant\JobEstimator;
use App\Transaction;
use App\TransactionPayment;
use App\Contact;
use Modules\Repair\Entities\MaintenanceNote;
use Modules\Sms\Entities\SmsLog;
use App\Utils\SmsUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobEstimatorApiController extends ApiController
{
    /**
     * Store a newly created job estimator in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer',
            'device_id' => 'required|integer',
            'location_id' => 'required|integer',
            'service_type_id' => 'nullable|integer',
            'vehicle_details' => 'nullable|string|max:1000',
            'amount' => 'nullable|numeric|min:0',
            'send_notification_value' => 'nullable|integer|in:0,1',
        ]);

        try {
            $businessId = Auth::user()->business_id;
            $userId = Auth::id();

            $data = [
                'contact_id' => $validated['contact_id'],
                'device_id' => $validated['device_id'],
                'business_id' => $businessId,
                'location_id' => $validated['location_id'],
                'created_by' => $userId,
                'service_type_id' => $validated['service_type_id'] ?? null,
                'vehicle_details' => $validated['vehicle_details'] ?? null,
                'amount' => $validated['amount'] ?? null,
                'send_sms' => isset($validated['send_notification_value']) && $validated['send_notification_value'] == 1,
                'estimator_status' => 'pending',
            ];

            $data['estimate_no'] = $this->generateEstimateNo($businessId);

            $estimator = JobEstimator::create($data);

            $amount = isset($validated['amount']) ? (float) $validated['amount'] : 0.0;
            if ($amount > 0) {
                try {
                    $this->createEstimatorAdvancePayment(
                        $validated['contact_id'],
                        $amount,
                        $estimator,
                        $validated['location_id'],
                        $businessId,
                        $userId
                    );
                } catch (\Throwable $txEx) {
                    Log::warning('Failed to record estimator advance payment', [
                        'estimator_id' => $estimator->id,
                        'contact_id' => $validated['contact_id'],
                        'error' => $txEx->getMessage(),
                    ]);
                }
            }

            try {
                MaintenanceNote::updateOrCreate(
                    [
                        'job_estimator_id' => $estimator->id,
                        'category_status' => 'purchase_req',
                    ],
                    [
                        'job_sheet_id' => null,
                        'created_by' => $userId,
                        'device_id' => $validated['device_id'],
                        'status' => 'awaiting_reply',
                        'content' => $validated['vehicle_details'] ?? null,
                    ]
                );
            } catch (\Throwable $ex) {
                Log::warning('Failed to create maintenance note on estimator create', [
                    'estimator_id' => $estimator->id,
                    'error' => $ex->getMessage(),
                ]);
            }

            if (isset($validated['send_notification_value']) && $validated['send_notification_value'] == 1) {
                $contact = DB::table('contacts')->where('id', $validated['contact_id'])->select('id', 'mobile', 'name')->first();
                if ($contact && $contact->mobile) {
                    $location_name = DB::table('business_locations')->where('id', $validated['location_id'])->value('name');
                    $message = 'اهلا ا/' . $contact->name . ' تم ارسال مقايسة صيانة لمركبتك من ' . $location_name;

                    $smsResult = SmsUtil::sendEpusheg($contact->mobile, $message);
                    $smsSent = is_array($smsResult) ? $smsResult['success'] : $smsResult;

                    SmsLog::create([
                        'contact_id' => $contact->id,
                        'transaction_id' => null,
                        'job_sheet_id' => null,
                        'mobile' => $contact->mobile,
                        'message_content' => $message,
                        'status' => $smsSent ? 'sent' : 'failed',
                        'error_message' => $smsSent ? null : 'Failed to send SMS',
                        'provider_balance' => is_array($smsResult) ? $smsResult['balance'] : SmsUtil::getLastNetBalance(),
                        'sent_at' => $smsSent ? now() : null,
                    ]);

                    $estimator->sent_to_customer_at = now();
                    $estimator->save();
                }
            }

            return response()->json([
                'success' => true,
                'msg' => trans('lang_v1.added_success'),
                'send_notification' => isset($validated['send_notification_value']) && $validated['send_notification_value'] == 1,
                'data' => [
                    'id' => $estimator->id,
                    'estimate_no' => $estimator->estimate_no,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Error in store job estimator: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * Generate a unique estimate number starting with ES.
     */
    private function generateEstimateNo(int $businessId): string
    {
        $ref_count = DB::table('reference_counts')
            ->where('ref_type', 'job_estimator')
            ->where('business_id', $businessId)
            ->value('ref_count') ?? 0;

        $ref_count += 1;

        DB::table('reference_counts')
            ->updateOrInsert(
                ['ref_type' => 'job_estimator', 'business_id' => $businessId],
                ['ref_count' => $ref_count]
            );

        $year = date('Y');
        $estimate_prefix = 'ES' . $year;
        return $estimate_prefix . '/' . str_pad($ref_count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create estimator advance payment.
     */
    private function createEstimatorAdvancePayment(int $contactId, float $amount, JobEstimator $estimator, int $locationId, int $businessId, int $userId): void
    {
        $paymentRef = $this->generateAdvancePaymentRef($businessId);

        $draftTransaction = Transaction::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'contact_id' => $contactId,
            'type' => 'sell',
            'status' => 'draft',
            'payment_status' => 'due',
            'transaction_date' => now(),
            'created_by' => $userId,
            'total_before_tax' => $amount,
            'tax_amount' => 0,
            'final_total' => $amount,
            'ref_no' => $paymentRef,
        ]);

        TransactionPayment::create([
            'transaction_id' => $draftTransaction->id,
            'amount' => $amount,
            'method' => 'advance',
            'payment_for' => $contactId,
            'is_advance' => 1,
            'payment_ref_no' => $paymentRef,
            'note' => 'Estimator #' . ($estimator->estimate_no ?? $estimator->id),
            'paid_on' => null,
            'created_by' => $userId,
            'business_id' => $businessId,
            'payment_type' => null,
            'status' => 'due',
        ]);

        Contact::where('id', $contactId)->increment('balance', $amount);
    }

    /**
     * Generate advance payment reference.
     */
    private function generateAdvancePaymentRef(int $businessId): string
    {
        $prefix = 'ADV-' . $businessId;

        do {
            $reference = $prefix . '-' . strtoupper(Str::random(6));
        } while (TransactionPayment::where('payment_ref_no', $reference)->exists());

        return $reference;
    }
}
