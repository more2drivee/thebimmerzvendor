<?php

namespace Modules\Vendors\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductByVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|required|integer',
            'Vendor_id' => 'sometimes|required|integer',
            'Product_price' => 'sometimes|required|numeric',
            'warranty_id' => 'nullable|integer',
            'shipping_information' => 'nullable|string',
            'Return_policy' => 'nullable|string',
            'Country_of_Origin' => 'nullable|string',
        ];
    }
}
