<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Merge form-data / x-www-form-urlencoded into the request
        // so validation works regardless of Content-Type
        if (!$this->isJson()) {
            $this->merge($this->all());
        }
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name'        => 'required|string|max:255|unique:products,name,' . $productId,
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'enabled'     => 'boolean',
        ];
    }
}
