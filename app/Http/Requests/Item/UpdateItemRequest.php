<?php

declare(strict_types=1);

namespace App\Http\Requests\Item;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && $this->user()?->can('update', $item);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', 'min:1'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Der Artikelname ist erforderlich.',
            'name.max' => 'Der Artikelname darf maximal 255 Zeichen lang sein.',
            'category_id.exists' => 'Die ausgewählte Kategorie existiert nicht.',
        ];
    }
}
