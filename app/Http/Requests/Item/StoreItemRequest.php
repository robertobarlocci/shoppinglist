<?php

declare(strict_types=1);

namespace App\Http\Requests\Item;

use App\Enums\ListType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isParent() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:1'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'list_type' => [
                'required',
                Rule::enum(ListType::class)->only(ListType::active()),
            ],
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
            'list_type.required' => 'Der Listentyp ist erforderlich.',
            'list_type.enum' => 'Ungültiger Listentyp.',
            'category_id.exists' => 'Die ausgewählte Kategorie existiert nicht.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Artikelname',
            'quantity' => 'Menge',
            'category_id' => 'Kategorie',
            'list_type' => 'Listentyp',
        ];
    }

    /**
     * Get the validated list type as enum.
     */
    public function listType(): ListType
    {
        return ListType::from($this->validated('list_type'));
    }
}
