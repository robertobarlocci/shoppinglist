<?php

declare(strict_types=1);

namespace App\Http\Requests\Item;

use App\Enums\ListType;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class MoveItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && $this->user()?->can('move', $item);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to_list' => [
                'required',
                Rule::enum(ListType::class),
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
            'to_list.required' => 'Das Ziel ist erforderlich.',
            'to_list.enum' => 'Ungültiges Ziel.',
        ];
    }

    /**
     * Get the validated destination list type.
     */
    public function toList(): ListType
    {
        return ListType::from($this->validated('to_list'));
    }
}
