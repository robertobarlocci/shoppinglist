<?php

declare(strict_types=1);

namespace App\Http\Requests\MealPlan;

use App\Models\MealPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMealPlanRequest extends FormRequest
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
            'date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'meal_type' => [
                'sometimes',
                'required',
                Rule::in(['breakfast', 'lunch', 'zvieri', 'dinner']),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255', 'min:1'],
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
            'date.date' => 'Ungültiges Datum.',
            'meal_type.in' => 'Ungültiger Mahlzeittyp.',
            'title.max' => 'Der Titel darf maximal 255 Zeichen lang sein.',
        ];
    }
}
