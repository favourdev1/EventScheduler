<?php

namespace App\Http\Requests;

use Illuminate\Support\Carbon;

class EventStoreRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isOrganizer();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'max_participants' => ['required', 'integer', 'min:1'],
            'is_private' => ['boolean'],
            'category_id' => ['nullable', 'exists:event_categories,id'],
            'timezone' => ['required', 'string', 'timezone']
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->timezone) {
            $this->merge([
                'start_time' => Carbon::parse($this->start_time, $this->timezone)->setTimezone('UTC'),
                'end_time' => Carbon::parse($this->end_time, $this->timezone)->setTimezone('UTC'),
            ]);
        }
    }
}
