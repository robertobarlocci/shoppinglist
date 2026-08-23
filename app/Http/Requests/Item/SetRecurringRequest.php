<?php

declare(strict_types=1);

namespace App\Http\Requests\Item;

use App\Enums\ListType;
use App\Models\Item;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SetRecurringRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('item');

        if (! $item instanceof Item) {
            return false;
        }

        return $this->user()?->can('update', $item) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'monday' => ['boolean'],
            'tuesday' => ['boolean'],
            'wednesday' => ['boolean'],
            'thursday' => ['boolean'],
            'friday' => ['boolean'],
            'saturday' => ['boolean'],
            'sunday' => ['boolean'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $item = $this->route('item');

            if ($item instanceof Item && $item->list_type !== ListType::INVENTORY) {
                $validator->errors()->add('item', 'Recurring schedules can only be set on inventory items.');
            }
        });
    }

    /**
     * Get the schedule data as an array.
     *
     * @return array<string, bool>
     */
    public function scheduleData(): array
    {
        return $this->only(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure all days default to false if not provided
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            if (! $this->has($day)) {
                $this->merge([$day => false]);
            }
        }
    }
}
