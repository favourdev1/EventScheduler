<?php

namespace App\Http\Requests;

use App\Models\Event;

class EventRegistrationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');
        return $this->user()->is_active &&
               $event->status === 'upcoming' &&
               !$event->trashed();
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $event = $this->route('event');
            $user = $this->user();

            if (!$event->hasAvailableSpots()) {
                $validator->errors()->add('event', 'This event has reached its maximum participant limit.');
            }

            if ($user->hasOverlappingEvents($event->start_time, $event->end_time, $event->id)) {
                $validator->errors()->add('event', 'You have another event scheduled during this time period.');
            }
        });
    }
}
