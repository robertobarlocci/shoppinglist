<?php

declare(strict_types=1);

namespace App\Http\Requests\MealPlan;

use App\Models\MealPlan;
use Illuminate\Foundation\Http\FormRequest;

final class AddIngredientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $mealPlan = $this->route('mealPlan');

        return $mealPlan instanceof MealPlan && $this->user()?->can('update', $mealPlan);
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
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
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
            'name.required' => 'Der Zutatenname ist erforderlich.',
            'name.max' => 'Der Zutatenname darf maximal 255 Zeichen lang sein.',
            'item_id.exists' => 'Der verknüpfte Artikel existiert nicht.',
        ];
    }
}
