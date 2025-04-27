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

    protected function prepareForValidation()
    {
        if (!$this->route('event')) {
            throw new HttpResponseException(
                ApiResponse::error(null, 'Event not found', 404)
            );
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            try {
                $event = $this->route('event');
                $user = $this->user();

                if (!$event->hasAvailableSpots()) {
                    $validator->errors()->add('event', 'This event has reached its maximum participant limit.');
                    return;
                }

                if ($user->hasOverlappingEvents($event->start_time, $event->end_time, $event->id)) {
                    $validator->errors()->add('event', 'You have another event scheduled during this time period.');
                    return;
                }

                // Check if user is already registered
                if ($event->registrations()->where('user_id', $user->id)->where('status', 'registered')->exists()) {
                    $validator->errors()->add('event', 'You are already registered for this event.');
                }
            } catch (\Exception $e) {
                $validator->errors()->add('event', 'An error occurred while validating the registration.');
            }
        });
    }
}
