<?php

namespace Modules\Connector\Transformers;

use App\Utils\Util;
use Illuminate\Http\Resources\Json\JsonResource;

class SellResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $commonUtil = new Util;
        
        // Essential transaction fields only
        $transaction = [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => $this->type,
            'status' => $this->status,
            'sub_status' => $this->when($this->sub_status, $this->sub_status),
            'is_quotation' => $this->is_quotation,
            'payment_status' => $this->payment_status,
            'contact_id' => $this->contact_id,
            'invoice_no' => $this->invoice_no,
            'ref_no' => $this->when($this->ref_no, $this->ref_no),
            'source' => $this->when($this->source, $this->source),
            'transaction_date' => $this->transaction_date,
            'total_before_tax' => $this->total_before_tax,
            'tax_amount' => $this->tax_amount,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'final_total' => $this->final_total,
            'round_off_amount' => $this->round_off_amount,
            'rp_redeemed' => $this->rp_redeemed,
            'rp_redeemed_amount' => $this->rp_redeemed_amount,
            'shipping_details' => $this->when($this->shipping_details, $this->shipping_details),
            'shipping_address' => $this->when($this->shipping_address, $this->shipping_address),
            'shipping_status' => $this->when($this->shipping_status, $this->shipping_status),
            'delivered_to' => $this->when($this->delivered_to, $this->delivered_to),
            'shipping_charges' => $this->shipping_charges,
            'packing_charge' => $this->packing_charge,
            'commission_agent' => $this->when($this->commission_agent, $this->commission_agent),
            'is_direct_sale' => $this->is_direct_sale,
            'is_suspend' => $this->is_suspend,
            'exchange_rate' => $this->exchange_rate,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'types_of_service_id' => $this->when($this->types_of_service_id, $this->types_of_service_id),
            'service_custom_field_1' => $this->when($this->service_custom_field_1, $this->service_custom_field_1),
            'custom_field_1' => $this->when($this->custom_field_1, $this->custom_field_1),
            'custom_field_2' => $this->when($this->custom_field_2, $this->custom_field_2),
            'custom_field_3' => $this->when($this->custom_field_3, $this->custom_field_3),
            'custom_field_4' => $this->when($this->custom_field_4, $this->custom_field_4),
            'invoice_token' => $this->invoice_token,
            'additional_notes' => $this->when($this->additional_notes, $this->additional_notes),
            'staff_note' => $this->when($this->staff_note, $this->staff_note),
            'document' => $this->when($this->document, $this->document),
        ];

        // Only include sell_lines if they exist
        if ($this->sell_lines && $this->sell_lines->count() > 0) {
            $transaction['sell_lines'] = $this->sell_lines->map(function ($line) {
                return [
                    'id' => $line->id,
                    'transaction_id' => $line->transaction_id,
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'quantity' => $line->quantity,
                    'quantity_returned' => $line->quantity_returned,
                    'unit_price' => $line->unit_price,
                    'unit_price_before_discount' => $line->unit_price_before_discount,
                    'unit_price_inc_tax' => $line->unit_price_inc_tax,
                    'item_tax' => $line->item_tax,
                    'line_discount_type' => $line->line_discount_type,
                    'line_discount_amount' => $line->line_discount_amount,
                    'tax_id' => $this->when($line->tax_id, $line->tax_id),
                    'sell_line_note' => $this->when($line->sell_line_note, $line->sell_line_note),
                    'created_at' => $line->created_at,
                    'updated_at' => $line->updated_at,
                    'purchase_price' => $this->getPurchasePrice($line),
                ];
            });
        }

        // Only include payment_lines if they exist
        if ($this->payment_lines && $this->payment_lines->count() > 0) {
            $transaction['payment_lines'] = $this->payment_lines->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount,
                    'method' => $payment->method,
                    'status' => $this->when($payment->status, $payment->status),
                    'paid_on' => $payment->paid_on,
                    'payment_ref_no' => $this->when($payment->payment_ref_no, $payment->payment_ref_no),
                    'note' => $this->when($payment->note, $payment->note),
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ];
            });
        }

        // Contact info - essential fields only
        if ($this->contact) {
            $transaction['contact'] = [
                'id' => $this->contact->id,
                'business_id' => $this->contact->business_id,
                'type' => $this->contact->type,
                'name' => $this->contact->name,
                'mobile' => $this->when($this->contact->mobile, $this->contact->mobile),
                'email' => $this->when($this->contact->email, $this->contact->email),
                'address_line_1' => $this->when($this->contact->address_line_1, $this->contact->address_line_1),
                'address_line_2' => $this->when($this->contact->address_line_2, $this->contact->address_line_2),
                'city' => $this->when($this->contact->city, $this->contact->city),
                'state' => $this->when($this->contact->state, $this->contact->state),
                'country' => $this->when($this->contact->country, $this->contact->country),
                'balance' => $this->contact->balance,
                'credit_limit' => $this->when($this->contact->credit_limit, $this->contact->credit_limit),
                'total_rp' => $this->contact->total_rp,
            ];
        }

        // Maintenance notes from job_sheet
        $transaction['maintenance_notes'] = [];
        if (!empty($this->job_sheet) && !empty($this->job_sheet->maintenance_notes)) {
            foreach ($this->job_sheet->maintenance_notes as $note) {
                $creator_name = '';
                if (!empty($note->creator)) {
                    $creator_name = trim(implode(' ', array_filter([
                        $note->creator->surname ?? '',
                        $note->creator->first_name ?? '',
                        $note->creator->last_name ?? '',
                    ])));
                }

                $transaction['maintenance_notes'][] = [
                    'id' => $note->id,
                    'note' => $note->note,
                    'category_status' => $note->category_status,
                    'created_at' => $note->created_at,
                    'creator_name' => $creator_name,
                    'status_name' => $note->repairStatus->name ?? null,
                    'status_color' => $note->repairStatus->color ?? null,
                ];
            }
        }

        // Add invoice URLs
        $transaction['invoice_url'] = $commonUtil->getInvoiceUrl($this->id, $this->business_id);
        $transaction['payment_link'] = $commonUtil->getInvoicePaymentLink($this->id, $this->business_id);

        return $transaction;
    }

    /**
     * Get purchase price from sell line purchase lines
     */
    private function getPurchasePrice($line)
    {
        if (empty($line->sell_line_purchase_lines)) {
            return [];
        }

        $purchase_prices = [];
        foreach ($line->sell_line_purchase_lines as $sell_line_purchase_line) {
            if (isset($sell_line_purchase_line->purchase_line)) {
                $purchase_prices[] = [
                    'purchase_price' => $sell_line_purchase_line->purchase_line->purchase_price,
                    'pp_inc_tax' => $sell_line_purchase_line->purchase_line->purchase_price_inc_tax,
                    'lot_number' => $sell_line_purchase_line->purchase_line->lot_number,
                ];
            }
        }

        return $purchase_prices;
    }
}
