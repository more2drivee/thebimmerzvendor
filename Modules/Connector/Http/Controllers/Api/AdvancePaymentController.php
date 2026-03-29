<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\TransactionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @group Advance Payments
 * @authenticated
 *
 * APIs for managing advance payments
 */
class AdvancePaymentController extends ApiController
{
    /**
     * Apply advance payment to contact
     *
     * Apply a pending advance payment (status=due, method=advance) to a contact.
     * This endpoint retrieves the matching advance payment record and updates it
     * with transaction details and document.
     *
     * @bodyParam contact_id integer required The contact ID. Example: 27
     * @bodyParam amount decimal required The amount to apply. Example: 3000
     * @bodyParam transaction_no string optional Transaction reference number. Example: TXN123
     * @bodyParam document file optional Document file to attach
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Advance payment applied successfully",
     *   "data": {
     *     "id": 9,
     *     "transaction_id": null,
     *     "amount": "3000.0000",
     *     "method": "advance",
     *     "payment_for": 27,
     *     "is_advance": 1,
     *     "payment_ref_no": "ADV-1-ABC123",
     *     "note": "Advance from estimator #ES001",
     *     "paid_on": null,
     *     "status": "due",
     *     "document": null,
     *     "created_by": 1,
     *     "business_id": 1
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "No matching advance payment found for this contact and amount"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation error",
     *   "errors": {
     *     "contact_id": ["The contact_id field is required."],
     *     "amount": ["The amount field is required."]
     *   }
     * }
     */
    public function applyAdvancePayment(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'transaction_no' => 'nullable|string|max:255',
            'document' => 'nullable|file|max:10240',
        ]);

        try {
            $contactId = $validated['contact_id'];
            $amount = (float) $validated['amount'];

            // Find matching advance payment: method=advance, status=due, amount matches
            $advancePayment = TransactionPayment::where('payment_for', $contactId)
                ->where('method', 'advance')
                ->where('status', 'due')
                ->where('amount', $amount)
                ->whereNull('transaction_id')
                ->first();

            if (!$advancePayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching advance payment found for this contact and amount',
                ], 404);
            }

            $updateData = [];

            // Add transaction_no if provided
            if (!empty($validated['transaction_no'])) {
                $updateData['transaction_no'] = $validated['transaction_no'];
            }

            // Handle document upload if provided
            if ($request->hasFile('document')) {
                $file = $request->file('document');
                // Store in storage/app/public/advance_payments with unique filename
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('advance_payments', $filename, 'public');
                $updateData['document'] = $path;
                $updateData['document_name'] = $file->getClientOriginalName();
               
            }

            // Update the advance payment record
            if (!empty($updateData)) {
                $advancePayment->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Advance payment applied successfully',
                'data' => $advancePayment->fresh(),
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error applying advance payment', [
                'error' => $e->getMessage(),
                'contact_id' => $validated['contact_id'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while applying the advance payment',
            ], 500);
        }
    }

    /**
     * Get advance payments for contact
     *
     * Retrieve all pending advance payments (status=due, method=advance) for a specific contact.
     *
     * @queryParam contact_id integer required The contact ID. Example: 27
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 9,
     *       "transaction_id": null,
     *       "amount": "3000.0000",
     *       "method": "advance",
     *       "payment_for": 27,
     *       "is_advance": 1,
     *       "payment_ref_no": "ADV-1-ABC123",
     *       "note": "Advance from estimator #ES001",
     *       "paid_on": null,
     *       "status": "due",
     *       "created_by": 1,
     *       "business_id": 1
     *     }
     *   ]
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation error"
     * }
     */
    public function getContactAdvancePayments(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer',
        ]);

        try {
            $advancePayments = TransactionPayment::where('payment_for', $validated['contact_id'])
                ->where('method', 'advance')
                ->where('status', 'due')
                ->whereNull('transaction_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $advancePayments,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching advance payments', [
                'error' => $e->getMessage(),
                'contact_id' => $validated['contact_id'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching advance payments',
            ], 500);
        }
    }
}
