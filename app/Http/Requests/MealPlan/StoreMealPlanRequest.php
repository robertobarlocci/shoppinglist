<?php

declare(strict_types=1);

namespace App\Http\Requests\MealPlan;

use App\Enums\MealType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMealPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\MealPlan::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'meal_type' => [
                'required',
                Rule::enum(MealType::class),
            ],
            'title' => ['required', 'string', 'max:255', 'min:1'],
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
            'date.required' => 'Das Datum ist erforderlich.',
            'date.date' => 'Ungültiges Datum.',
            'meal_type.required' => 'Der Mahlzeittyp ist erforderlich.',
            'meal_type.enum' => 'Ungültiger Mahlzeittyp.',
            'title.required' => 'Der Titel ist erforderlich.',
            'title.max' => 'Der Titel darf maximal 255 Zeichen lang sein.',
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
            'date' => 'Datum',
            'meal_type' => 'Mahlzeittyp',
            'title' => 'Titel',
        ];
    }

    /**
     * Get the validated meal type as enum.
     */
    public function mealType(): MealType
    {
        return MealType::from($this->validated('meal_type'));
    }
}
