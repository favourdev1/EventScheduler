<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventUpdateRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');
        return $this->user()->isAdmin() ||
               ($this->user()->isOrganizer() && $event->organizer_id === $this->user()->id);
    }

    public function rules(): array
    {
        $event = $this->route('event');

        $rules = [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_private' => ['sometimes', 'boolean'],
            'category_id' => ['sometimes', 'nullable', 'exists:event_categories,id'],
            'timezone' => ['sometimes', 'required', 'string', 'timezone']
        ];

        if ($event->isUpcoming()) {
            $rules['start_time'] = ['sometimes', 'required', 'date', 'after:now'];
            $rules['end_time'] = ['sometimes', 'required', 'date', 'after:start_time'];
            $rules['max_participants'] = [
                'sometimes',
                'required',
                'integer',
                'min:' . $event->active_participants_count
            ];
        }

        if ($this->has('status')) {
            $rules['status'] = [
                'required',
                Rule::in(['cancelled', 'archived']),
                function ($attribute, $value, $fail) use ($event) {
                    if ($value === 'archived' && !$event->isCompleted()) {
                        $fail('Only completed events can be archived.');
                    }
                }
            ];
            $rules['cancellation_reason'] = [
                Rule::requiredIf($this->status === 'cancelled'),
                'nullable',
                'string'
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->timezone && ($this->has('start_time') || $this->has('end_time'))) {
            $data = [];

            if ($this->has('start_time')) {
                $data['start_time'] = Carbon::parse($this->start_time, $this->timezone)->setTimezone('UTC');
            }

            if ($this->has('end_time')) {
                $data['end_time'] = Carbon::parse($this->end_time, $this->timezone)->setTimezone('UTC');
            }

            $this->merge($data);
        }
    }
}
