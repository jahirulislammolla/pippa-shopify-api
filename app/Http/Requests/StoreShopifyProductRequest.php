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
            'body_html' => ['nullable', 'string'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],

            // এখন options ফ্রি-শেপ: string[] অথবা {name, values[]}[]
            'options' => ['required', 'array', 'min:1'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'variants.*.inventory_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.option_values' => ['required', 'array', 'min:1'],
            'variants.*.option_values.*' => ['string', 'max:255'],
            'variants.*.image.src' => ['nullable', 'url'],
            'variants.*.image.alt' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Normalize "options" to names array and keep provided values (if any)
     */
    protected function prepareForValidation(): void
    {
        $opts = $this->input('options', []);

        // case A: options = ["Size","Color"]
        if (is_array($opts) && (empty($opts) || is_string(reset($opts)))) {
            // nothing to do
            return;
        }

        // case B: options = [{ name:"Size", values:[{name:"S"}] }, ...]
        if (is_array($opts) && is_array(reset($opts))) {
            $names = [];
            $valuesByIndex = [];

            foreach ($opts as $i => $opt) {
                $name = $opt['name'] ?? null;
                if ($name) {
                    $names[] = $name;
                } else {
                    $names[] = "Option".($i+1);
                }

                $vals = [];
                if (!empty($opt['values']) && is_array($opt['values'])) {
                    foreach ($opt['values'] as $v) {
                        // support both {name:"S"} or plain "S"
                        $vals[] = is_array($v) ? (string)($v['name'] ?? '') : (string)$v;
                    }
                }
                $valuesByIndex[$i] = array_values(array_unique(array_filter($vals)));
            }

            // replace options with names[], keep provided values in hidden key
            $this->merge([
                'options' => $names,
                '_option_values_from_options' => $valuesByIndex,
            ]);
        }
    }

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
