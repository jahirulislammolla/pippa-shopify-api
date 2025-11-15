<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopifyProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],

            'options' => ['required', 'array', 'min:1'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*' => ['string', 'max:255'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'variants.*.inventory_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.option_values' => ['required', 'array', 'min:1'],
            'variants.*.option_values.*' => ['string', 'max:255'],

            'images' => ['nullable', 'array'],
            'images.*.src' => ['nullable', 'url'],
            'images.*.alt' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Normalize "options" to names array and keep provided values (if any)
     */

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $options = $this->input('options', []);
            $optCount = is_array($options) ? count($options) : 0;

            foreach ((array) $this->input('variants', []) as $i => $variant) {
                $values = $variant['option_values'] ?? [];
                if ($optCount > 0 && count($values) !== $optCount) {
                    $v->errors()->add("variants.$i.option_values",
                        "option_values count must match 'options' count ($optCount).");
                }
            }
        });
    }
}
