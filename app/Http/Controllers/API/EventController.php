<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Requests\EventRegistrationRequest;
use App\Http\Responses\ApiResponse;
use App\Mail\EventRegistrationConfirmation;
use App\Mail\NewParticipantRegistered;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EventController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Event::query()
                ->with(['category', 'organizer'])
                ->when(!$request->user()->isAdmin(), function ($query) {
                    $query->where('is_private', false)
                          ->orWhere('organizer_id', auth()->id());
                })
                ->when($request->filled('status'), function ($query) use ($request) {
                    $query->where('status', $request->status);
                })
                ->when($request->filled('category'), function ($query) use ($request) {
                    $query->whereHas('category', function ($q) use ($request) {
                        $q->where('slug', $request->category);
                    });
                });

            $events = $query->latest()->paginate(15);

            return ApiResponse::success($events, 'Events retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve events');
        }
    }

    public function store(EventStoreRequest $request)
    {
        try {
            $event = Event::create([
                ...$request->validated(),
                'organizer_id' => $request->user()->id
            ]);

            return ApiResponse::successCreated(
                $event->load(['category', 'organizer']),
                'Event created successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to create event');
        }
    }

    public function show(Event $event, Request $request)
    {
        try {
            if ($event->is_private && !$request->user()->isAdmin() && $event->organizer_id !== $request->user()->id) {
                return ApiResponse::unauthorized('This event is private');
            }

            return ApiResponse::success(
                $event->load(['category', 'organizer']),
                'Event details retrieved successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve event details');
        }
    }

    public function update(EventUpdateRequest $request, Event $event)
    {
        try {
            $event->update($request->validated());

            if ($request->status === 'cancelled') {
                $event->registrations()->where('status', 'registered')
                      ->update(['status' => 'cancelled']);
            }

            return ApiResponse::success(
                $event->load(['category', 'organizer']),
                'Event updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to update event');
        }
    }

    public function destroy(Event $event, Request $request)
    {
        try {
            if (!$request->user()->isAdmin()) {
                return ApiResponse::unauthorized('Only administrators can delete events');
            }

            $event->delete();
            return ApiResponse::success(null, 'Event deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to delete event');
        }
    }

    public function register(Event $event, EventRegistrationRequest $request)
    {
        try {
            DB::beginTransaction();

            $event = Event::lockForUpdate()->findOrFail($event->id);

            if (!$event->hasAvailableSpots()) {
                DB::rollBack();
                return ApiResponse::validationError(['event' => ['This event has reached its maximum participant limit.']]);
            }

            if ($request->user()->hasOverlappingEvents($event->start_time, $event->end_time, $event->id)) {
                DB::rollBack();
                return ApiResponse::validationError(['event' => ['You have another event scheduled during this time period.']]);
            }

            if ($event->registrations()->where('user_id', $request->user()->id)->where('status', 'registered')->exists()) {
                DB::rollBack();
                return ApiResponse::validationError(['event' => ['You are already registered for this event.']]);
            }

            $registration = $event->registrations()->create([
                'user_id' => $request->user()->id,
                'status' => 'registered'
            ]);

            // Send confirmation email to participant
            Mail::to($request->user())->queue(
                new EventRegistrationConfirmation($registration, $event)
            );

            // Send notification to event organizer
            Mail::to($event->organizer)->queue(
                new NewParticipantRegistered($event, $request->user(), $registration)
            );

            DB::commit();

            return ApiResponse::successCreated([
                'registration' => $registration
            ], 'Successfully registered for the event');

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error(null, $e->getMessage());
        }
    }

    public function cancelRegistration(Event $event, Request $request)
    {
        try {
            $registration = $event->registrations()
                ->where('user_id', $request->user()->id)
                ->where('status', 'registered')
                ->firstOrFail();

            $registration->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->reason
            ]);

            return ApiResponse::success(null, 'Registration cancelled successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Registration not found');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to cancel registration');
        }
    }

    public function participants(Event $event, Request $request)
    {
        try {
            if (!$request->user()->isAdmin() && $event->organizer_id !== $request->user()->id) {
                return ApiResponse::unauthorized('Unauthorized to view participants');
            }

            $participants = $event->registrations()
                ->with('user')
                ->paginate(15);

            return ApiResponse::success($participants, 'Participants retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error(null, 'Failed to retrieve participants');
        }
    }
}
