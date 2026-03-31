<?php

namespace Modules\Vendors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductByVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer',
            'Vendor_id' => 'required|integer',
            'Product_price' => 'required|numeric',
            'warranty_id' => 'nullable|integer',
            'shipping_information' => 'nullable|string',
            'Return_policy' => 'nullable|string',
            'Country_of_Origin' => 'nullable|string',
        ];
    }
}
