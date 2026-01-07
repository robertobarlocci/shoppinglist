<?php

declare(strict_types=1);

namespace App\Http\Requests\Item;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

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

        // Only allow setting recurring on inventory items
        if ($item->list_type !== Item::LIST_TYPE_INVENTORY) {
            return false;
        }

        return $this->user()?->can('update', $item) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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

    /**
     * Get the schedule data as an array.
     *
     * @return array<string, bool>
     */
    public function scheduleData(): array
    {
        return $this->only(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
    }
}
