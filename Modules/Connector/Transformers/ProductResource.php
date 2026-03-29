<?php

namespace Modules\Connector\Transformers;

use App\PurchaseLine;
use App\Utils\ProductUtil;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $array = parent::toArray($request);

        $array['brand'] = $this->brand;
        $array['unit'] = $this->unit;

        $array['category'] = $this->category ?? null;
    
        $array['sub_category'] = $this->sub_category ?? null;
        $array['product_tax'] = $this->product_tax;

        // Ensure qty_available and default_sell_price are included
        $array['qty_available'] = $this->qty_available ?? "0.0";
        $array['default_sell_price'] = $this->default_sell_price ?? "0.0";

        // Add compatibility information
        $array['compatibility'] = $this->formatCompatibility();

        $send_lot_detail = ! empty(request()->input('send_lot_detail')) && request()->input('send_lot_detail') == 1 ? true : false;

        $productUtil = new ProductUtil;
        foreach ($array['product_variations'] as $key => $value) {
            foreach ($value['variations'] as $k => $v) {

                //set lot details in each variation_location_details
                if ($send_lot_detail && ! empty($v['variation_location_details'])) {
                    foreach ($v['variation_location_details'] as $u => $w) {
                        $lot_details = [];
                        $purchase_lines = PurchaseLine::where('variation_id', $w['variation_id'])
                                                    ->leftJoin('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                                                    ->where('t.location_id', $w['location_id'])
                                                    ->where('t.status', 'received')
                                                    ->get();

                        foreach ($purchase_lines as $pl) {
                            if ($pl->quantity_remaining > 0) {
                                $lot_details[] = [
                                    'lot_number' => $pl->lot_number,
                                    'qty_available' => $pl->quantity_remaining,
                                    'default_purchase_price' => $pl->purchase_price,
                                    'dpp_inc_tax' => $pl->purchase_price_inc_tax,
                                ];
                            }
                        }

                        $array['product_variations'][$key]['variations'][$k]['variation_location_details'][$u]['lot_details'] = $lot_details;
                    }
                }
                
                if (isset($v['group_prices'])) {
                    $array['product_variations'][$key]['variations'][$k]['selling_price_group'] = $v['group_prices'];
                    unset($array['product_variations'][$key]['variations'][$k]['group_prices']);
                }
                //get discounts for each location
                $discounts = [];
                foreach ($array['product_locations'] as $pl) {
                    $selling_price_group = $pl['selling_price_group_id'];
                    $location_discount = $productUtil->getProductDiscount($this, $array['business_id'], $pl['id'], false, $selling_price_group, $v['id']);
                    if (! empty($location_discount)) {
                        $discounts[] = $location_discount;
                    }
                }

                $array['product_variations'][$key]['variations'][$k]['discounts'] = $discounts;
            }
        }

        return array_diff_key($array, array_flip($this->__excludeFields()));
    }

    private function __excludeFields()
{
    return [
        'created_at',
        'updated_at',
        'brand_id',
        'unit_id',
        'category_id',
        'sub_category_id',
        'tax',
        'type',
        'tax_type',
        'secondary_unit_id',
        'sub_unit_ids',
        'barcode_type',

        // 'enable_stock',
        'image',
        'product_description',
        'created_by',
        'preparation_time_in_minutes',
        'warranty_id',
       // ✅ Unused in Flutter
        'manufacturing_year',
        'brand_category',
        'repair_model_id',

        'expiry_period',
        'expiry_period_type',
        'enable_sr_no',
        'weight',
        'product_custom_field1',
        'product_custom_field2',
        'product_custom_field3',
        'product_custom_field4',
        'product_custom_field5',
        'product_custom_field6',
        'product_custom_field7',
        'product_custom_field8',
        'product_custom_field9',
        'product_custom_field10',
        'product_custom_field11',
        'product_custom_field12',
        'product_custom_field13',
        'product_custom_field14',
        'product_custom_field15',
        'product_custom_field16',
        'product_custom_field17',
        'product_custom_field18',
        'product_custom_field19',
        'product_custom_field20',
        'product_tax',

        'virtual_product',
        'image_url',
        'ai_flag',
        // 'brand',
        // 'category',
        'product_locations',
        'product_variations',
        'is_inactive',
        'business_id',
        'not_for_selling',
        // Make sure these fields are not excluded
        // 'qty_available',
        // 'default_sell_price',
    ];
}

/**
 * Format compatibility information as a list of objects
 */
private function formatCompatibility()
{
    if (!$this->compatibility || $this->compatibility->isEmpty()) {
        return [];
    }

    return $this->compatibility->map(function ($compat) {
        $parts = [];

        if ($compat->brand_category_id && $compat->brandCategory) {
            $parts[] = $compat->brandCategory->name;
        }

        if ($compat->model_id && $compat->deviceModel) {
            $parts[] = $compat->deviceModel->name;
        }

        if (!empty($compat->motor_cc)) {
            $parts[] = $compat->motor_cc;
        }

        if ($compat->from_year && $compat->to_year) {
            if ($compat->from_year == $compat->to_year) {
                $parts[] = $compat->from_year;
            } else {
                $parts[] = $compat->from_year . '-' . $compat->to_year;
            }
        } elseif ($compat->from_year) {
            $parts[] = $compat->from_year . '+';
        } elseif ($compat->to_year) {
            $parts[] = $compat->to_year . '-';
        }

        return [
            'brand' => $compat->brandCategory->name ?? null,
            'model' => $compat->deviceModel->name ?? null,
            'motor_cc' => $compat->motor_cc ?? null,
            'from_year' => $compat->from_year,
            'to_year' => $compat->to_year,
            'label' => !empty($parts) ? implode(' ', $parts) : null
        ];
    })->values()->all();
}

}
