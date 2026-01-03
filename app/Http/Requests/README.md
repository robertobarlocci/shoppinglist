# Form Request Validation

This directory should contain Form Request classes for validation.

## Example Usage

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'quantity' => 'nullable|string|max:100',
            'category_id' => 'nullable|exists:categories,id',
            'list_type' => 'required|in:quick_buy,to_buy,inventory',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Artikelname ist erforderlich.',
            'name.max' => 'Der Artikelname darf maximal 255 Zeichen haben.',
        ];
    }
}
```

## Generate Form Requests

```bash
php artisan make:request StoreItemRequest
php artisan make:request UpdateItemRequest
php artisan make:request StoreCategoryRequest
```
