<?php

namespace App;

use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TransactionPayment extends Model
{
    use SoftDeletes;
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the phone record associated with the user.
     */
    public function payment_account()
    {
        return $this->belongsTo(\App\Account::class, 'account_id');
    }

    /**
     * Get the transaction related to this payment.
     */
    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    /**
     * Get the user.
     */
    public function created_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Get child payments
     */
    public function child_payments()
    {
        return $this->hasMany(\App\TransactionPayment::class, 'parent_id');
    }

    /**
     * Retrieves documents path if exists
     */
    public function getDocumentPathAttribute()
    {
        if (empty($this->document)) {
            return null;
        }
        // If already a full URL, return as-is
        if (preg_match('/^https?:\/\//i', $this->document)) {
            return $this->document;
        }
        // Use storage URL (supports values like 'documents/filename.ext')
        return Storage::disk('public')->url($this->document);

    }

    /**
     * Removes timestamp from document name
     */
    public function getDocumentNameAttribute()
    {
        if (empty($this->document)) {
            return null;
        }
        $basename = basename($this->document);
        $parts = explode('_', $basename, 2);
        return !empty($parts[1]) ? $parts[1] : $basename;
    }

    public static function deletePayment($payment)
    {
        //Update parent payment if exists
        if (! empty($payment->parent_id)) {
            $parent_payment = TransactionPayment::find($payment->parent_id);
            $parent_payment->amount -= $payment->amount;

            if ($parent_payment->amount <= 0) {
                $parent_payment->forceDelete();
                event(new TransactionPaymentDeleted($parent_payment));
            } else {
                $parent_payment->save();
                //Add event to update parent payment account transaction
                event(new TransactionPaymentUpdated($parent_payment, null));
            }
        }

        $payment->forceDelete();

        $transactionUtil = new \App\Utils\TransactionUtil();

        if (! empty($payment->transaction_id)) {
            //update payment status
            $transaction = $payment->load('transaction')->transaction;
            $transaction_before = $transaction->replicate();

            $payment_status = $transactionUtil->updatePaymentStatus($payment->transaction_id);

            $transaction->payment_status = $payment_status;

            $transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
        }

        $log_properities = [
            'id' => $payment->id,
            'ref_no' => $payment->payment_ref_no,
        ];
        $transactionUtil->activityLog($payment, 'payment_deleted', null, $log_properities);

        //Add event to delete account transaction
        event(new TransactionPaymentDeleted($payment));
    }

    public function denominations()
    {
        return $this->morphMany(\App\CashDenomination::class, 'model');
    }
}
