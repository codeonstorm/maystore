<?php

namespace Zoyica\ZoyicaApi\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price'        => ['sometimes', 'required', 'numeric', 'min:0'],
            'special_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'lt:price'],
            'inventories'  => ['sometimes', 'required', 'array'],
            'inventories.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (! $this->has('price') && ! $this->has('inventories')) {
                $v->errors()->add('request', 'At least one of price or inventories must be provided.');
            }
        });
    }
}
