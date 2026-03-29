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
        
        // Build clean response with only useful fields
        $array = [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => $this->type,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'contact_id' => $this->contact_id,
            'invoice_no' => $this->invoice_no,
            'transaction_date' => $this->transaction_date,
            'total_before_tax' => $this->total_before_tax,
            'tax_amount' => $this->tax_amount,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'final_total' => $this->final_total,
            'round_off_amount' => $this->round_off_amount,
            
            'is_direct_sale' => $this->is_direct_sale,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'invoice_token' => $this->invoice_token,
        ];

        // Add optional fields only if they have values
        $optionalFields = [
            'sub_status', 'source', 'ref_no', 'shipping_details', 'shipping_address',
            'shipping_status', 'delivered_to', 'commission_agent', 'types_of_service_id',
            'service_custom_field_1', 'custom_field_1', 'custom_field_2', 'custom_field_3', 'custom_field_4',
            'additional_notes', 'staff_note', 'document'
        ];
        foreach ($optionalFields as $field) {
            if (!empty($this->{$field})) {
                $array[$field] = $this->{$field};
            }
        }

        // Clean sell_lines
        if (!empty($this->sell_lines)) {
            $array['sell_lines'] = [];
            foreach ($this->sell_lines as $line) {
                $sellLine = [
                    'id' => $line->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product->name ?? '',
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'unit_price_inc_tax' => $line->unit_price_inc_tax,
                    'line_discount_type' => $line->line_discount_type,
                    'line_discount_amount' => $line->line_discount_amount,
                ];
                // Add optional sell line fields
                if (!empty($line->sell_line_note)) $sellLine['sell_line_note'] = $line->sell_line_note;
                if (!empty($line->tax_id)) $sellLine['tax_id'] = $line->tax_id;
                if (!empty($line->purchase_price)) $sellLine['purchase_price'] = $line->purchase_price;
                
                $array['sell_lines'][] = $sellLine;
            }
        }

        // Clean payment_lines
        if (!empty($this->payment_lines)) {
            $array['payment_lines'] = [];
            foreach ($this->payment_lines as $payment) {
                $paymentLine = [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'method' => $payment->method,
                    'paid_on' => $payment->paid_on,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ];
                if (!empty($payment->payment_ref_no)) $paymentLine['payment_ref_no'] = $payment->payment_ref_no;
                if (!empty($payment->note)) $paymentLine['note'] = $payment->note;
                
                $array['payment_lines'][] = $paymentLine;
            }
        }

        // Clean contact
        if (!empty($this->contact)) {
            $array['contact'] = [
                'id' => $this->contact->id,
                'name' => $this->contact->name,
                'mobile' => $this->contact->mobile,
                'balance' => $this->contact->balance,
            ];
            if (!empty($this->contact->email)) $array['contact']['email'] = $this->contact->email;
        }

        // Maintenance notes
        $array['maintenance_notes'] = [];
        if (!empty($this->job_sheet) && !empty($this->job_sheet->maintenance_notes)) {
            foreach ($this->job_sheet->maintenance_notes as $note) {
                $creator_name = '';
                if (!empty($note->creator)) {
                    $creator_name = trim(($note->creator->surname ?? '') . ' ' . ($note->creator->first_name ?? '') . ' ' . ($note->creator->last_name ?? ''));
                }
                $array['maintenance_notes'][] = [
                    'id' => $note->id,
                    'note' => $note->note,
                    'creator_name' => $creator_name,
                    'status_name' => $note->repairStatus->name ?? null,
                    'created_at' => $note->created_at,
                ];
            }
        }

        $array['invoice_url'] = $commonUtil->getInvoiceUrl($this->id, $this->business_id);
        $array['payment_link'] = $commonUtil->getInvoicePaymentLink($this->id, $this->business_id);

        return $array;
    }
}
