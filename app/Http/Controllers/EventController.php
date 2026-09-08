<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Event;
use App\Models\EventPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use DateTimeZone;
use App\Support\InvitationPublicationSnapshotBuilder;
use App\Support\EventPublicationService;
use App\Support\EventTiming;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    private const ADMIN_ONLY_STATUSES = ['published', 'disabled', 'archived'];
    public function index(Request $request, InvitationPublicationSnapshotBuilder $snapshots): Response { $user = $request->user(); abort_unless($user->can('viewAny', Event::class), 403); $search = $request->string('search')->trim()->toString(); $type = $request->string('event_type')->toString(); $status = $user->isAdmin() ? $request->string('status')->toString() : ''; $customerId = $user->isAdmin() ? $request->integer('customer_id') : null; $events = ($user->isAdmin() ? Event::query() : $user->managedEvents())->with(['customer','package','publications'])->when($search, fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('host_name', 'like', "%{$search}%")->orWhere('second_host_name', 'like', "%{$search}%")->orWhere('venue', 'like', "%{$search}%")->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))))->when($type, fn ($query) => $query->where('event_type', $type))->when($status, fn ($query) => $query->where('status', $status))->when($customerId, fn ($query) => $query->where('customer_id', $customerId))->latest('events.created_at')->paginate(25)->withQueryString(); $events->each(function (Event $event) use ($snapshots): void { $live = $event->publications->sortByDesc('version')->first(); $dirty = $live && ! hash_equals($live->snapshot_hash, $snapshots->hash($event)); $event->setAttribute('live_version', $live?->version); $event->setAttribute('invitation_status', $event->invitationStatus()); $event->setAttribute('unpublished_changes', (bool) $dirty); $event->unsetRelation('publications'); }); return Inertia::render('Events/Index', ['events' => $events->items(), 'pagination' => ['current_page' => $events->currentPage(), 'last_page' => $events->lastPage(), 'total' => $events->total(), 'prev_page_url' => $events->previousPageUrl(), 'next_page_url' => $events->nextPageUrl()], 'filters' => ['search' => $search, 'event_type' => $type, 'status' => $status, 'customer_id' => $customerId ?: ''], 'types' => Event::TYPES, 'statuses' => Event::STATUSES, 'customers' => $user->isAdmin() ? Customer::where('is_active', true)->get(['id', 'name']) : [], 'isAdmin' => $user->isAdmin()]); }
    public function create(): Response { $user = request()->user(); abort_unless($user->can('create', Event::class), 403); return Inertia::render('Events/Form', ['event' => null, 'customers' => $user->isAdmin() ? Customer::where('is_active', true)->get() : [], 'packages' => $user->isAdmin() ? EventPackage::where('is_active', true)->capacityOrder()->get() : [], 'types' => Event::TYPES, 'statuses' => $this->statusesFor($user->isAdmin()), 'timezones' => DateTimeZone::listIdentifiers(), 'defaultTimezone' => config('app.timezone'), 'isAdmin' => $user->isAdmin()]); }
    public function store(Request $request): RedirectResponse { abort_unless($request->user()->can('create', Event::class), 403); $data = $this->validated($request); if ($request->user()->isAdmin()) { $request->validate(['customer_id' => ['required', 'exists:customers,id']]); $data['customer_id'] = $request->integer('customer_id'); } else { $data['customer_id'] = $request->user()->customer_id; unset($data['event_package_id'], $data['guest_capacity']); } $event = DB::transaction(function () use ($request, $data): Event { $customer = Customer::query()->lockForUpdate()->findOrFail($data['customer_id']); if (! $customer->canCreateEvent()) throw ValidationException::withMessages(['customer_id' => 'This Customer has reached their allowed invitation limit. Increase the allowance before creating another Event.']); if ($request->user()->isAdmin()) $this->assertCapacityCompatible(isset($data['event_package_id']) ? EventPackage::find($data['event_package_id']) : null, $data['guest_capacity'] ?? null, 0); $data['status'] = 'draft'; $event = Event::create($data); $event->members()->syncWithoutDetaching([$request->user()->id => ['role' => 'owner']]); return $event; }); return to_route('events.show', $event)->with('success', 'Event created.'); }
    public function show(Event $event, InvitationPublicationSnapshotBuilder $snapshots): Response
    {
        $user = request()->user();
        abort_unless($user->can('view', $event), 403);

        $relations = ['package', 'activities' => fn ($query) => $query->orderBy('display_order')->orderBy('starts_at'), 'payments' => fn ($query) => $query->with(['transactions' => fn ($transactions) => $transactions->orderBy('payment_date')->orderBy('id')])->latest()];
        if ($user->isAdmin()) $relations[] = 'members:id,name';
        $event->load([...$relations, 'template:id,name,component_key']);
        $payment = $event->payments->first();
        $activities = $event->activities
            ->map(fn ($activity) => $this->activityForPresentation($activity, $event))
            ->values();
        $event->unsetRelation('payments');
        $event->unsetRelation('activities');
        $currentHash = $snapshots->hash($event);
        $livePublication = $event->publications()->orderByDesc('version')->first();
        $isArchived = $event->isArchived();
        $hasLiveVersion = $livePublication !== null;
        $hasUnpublishedChanges = $hasLiveVersion && ! hash_equals($livePublication->snapshot_hash, $currentHash);
        $invitationStatus = $event->invitationStatus();
        $eventForPresentation = $user->isAdmin() ? $event : [
            'id' => $event->id, 'title' => $event->title, 'template' => $event->template ? ['name' => $event->template->name] : null, 'event_type' => $event->event_type, 'host_name' => $event->host_name,
            'second_host_name' => $event->second_host_name, 'description' => $event->description, 'main_date' => $event->main_date,
            'end_date' => $event->end_date, 'start_time' => $event->start_time, 'end_time' => $event->end_time,
            'rsvp_deadline' => $event->rsvp_deadline, 'event_timezone' => $event->event_timezone, 'venue' => $event->venue,
            'address' => $event->address, 'location_url' => $event->location_url, 'guest_capacity' => $event->guest_capacity,
            'package' => $event->package ? ['maximum_guests' => $event->package->maximum_guests] : null,
        ];

        return Inertia::render('Events/Show', [
            'event' => $eventForPresentation,
            'eventTiming' => [
                'main_date' => EventTiming::date($event->getRawOriginal('main_date'), $event->event_timezone),
                'end_date' => EventTiming::date($event->getRawOriginal('end_date'), $event->event_timezone),
                'start_time' => EventTiming::time($event->start_time, $event->event_timezone),
                'end_time' => EventTiming::time($event->end_time, $event->event_timezone),
            ],
            'activities' => $activities,
            'guestInfo' => ['dress_code' => $event->dress_code, 'parking_information' => $event->parking_information, 'transportation_information' => $event->transportation_information, 'accommodation_information' => $event->accommodation_information, 'guest_information' => $event->guest_information],
            'customer' => $user->isAdmin() && $event->customer ? ['id' => $event->customer->id, 'name' => $event->customer->name, 'contact_name' => $event->customer->contact_name, 'email' => $event->customer->email, 'phone' => $event->customer->phone] : null,
            'paymentSummary' => $payment ? [
                'package_price' => $event->package?->price,
                'original_amount' => $payment->original_amount,
                'discount' => $payment->discount,
                'discount_type' => $payment->discount_type,
                'discount_value' => $payment->discount_value,
                'coupon_code' => $payment->coupon_code,
                'status' => $payment->status,
                'final_amount' => $payment->final_amount,
                'paid_amount' => $payment->paid_amount,
                'remaining_amount' => $payment->remainingAmount(),
                'transactions' => $payment->transactions->map(fn ($transaction) => ['id' => $transaction->id, 'amount' => $transaction->amount, 'payment_method' => $transaction->payment_method, 'reference' => $transaction->reference, 'payment_date' => $transaction->payment_date]),
            ] : null,
            'canEdit' => $user->can('update', $event),
            'canViewGuests' => $user->can('view', $event),
            'canManageGuests' => $user->can('update', $event),
            'canViewRsvps' => $user->can('view', $event),
            'canManageRsvps' => $user->can('update', $event),
            'isAdmin' => $user->isAdmin(),
            'workflow' => [
                'invitation_status' => $invitationStatus,
                'live_version' => $livePublication?->version,
                'unpublished_changes' => $hasUnpublishedChanges,
                'can_publish' => $user->can('update', $event) && ! $isArchived && ! $event->isDisabled() && (! $hasLiveVersion || $hasUnpublishedChanges),
                'can_archive' => $user->isAdmin() && ! $isArchived,
                'can_disable' => $user->isAdmin() && $event->isLive(),
                'can_enable' => $user->isAdmin() && $event->isDisabled() && $hasLiveVersion,
            ],
        ]);
    }
    public function edit(Event $event): Response { $user = request()->user(); abort_unless($user->can('update', $event), 403); return Inertia::render('Events/Form', ['event' => $event->load('package'), 'customers' => $user->isAdmin() ? Customer::where('is_active', true)->get() : [], 'packages' => $user->isAdmin() ? EventPackage::where('is_active', true)->capacityOrder()->get() : [], 'types' => Event::TYPES, 'statuses' => $this->statusesFor($user->isAdmin()), 'timezones' => DateTimeZone::listIdentifiers(), 'defaultTimezone' => config('app.timezone'), 'isAdmin' => $user->isAdmin()]); }
    public function update(Request $request, Event $event, EventPublicationService $publications): RedirectResponse { abort_unless($request->user()->can('update', $event), 403); $data = $this->validated($request); if (($data['status'] ?? $event->status) !== $event->status) throw ValidationException::withMessages(['status' => 'Use the Event publishing workflow to change status.']); unset($data['status']); if (!$request->user()->isAdmin()) { unset($data['customer_id'], $data['event_package_id'], $data['guest_capacity']); } else { $package = array_key_exists('event_package_id', $data) ? EventPackage::find($data['event_package_id']) : $event->package; $capacity = array_key_exists('guest_capacity', $data) ? $data['guest_capacity'] : $event->guest_capacity; $this->assertCapacityCompatible($package, $capacity, $event->allocatedGuestCapacity()); } DB::transaction(function () use ($event, $data, $request, $publications): void { $locked = Event::query()->lockForUpdate()->findOrFail($event->id); abort_if($locked->isArchived(), 422, 'Archived Events cannot be changed.'); $locked->update($data); $publications->publishIfActiveLocked($locked, $request->user()); }); return to_route('events.show', $event)->with('success', 'Event updated.'); }
    private function validated(Request $request): array
    {
        $existingEvent = $request->route('event');

        if (! $request->filled('event_timezone')) {
            $request->merge(['event_timezone' => $existingEvent?->event_timezone ?: config('app.timezone')]);
        }

        $statusRules = ['required', Rule::in([...Event::STATUSES, ...Event::LEGACY_STATUSES])];
        $endTimeRules = ['nullable', 'date_format:H:i'];
        $rsvpDeadlineRules = ['nullable', 'date'];

        if (!$request->user()->isAdmin()) $statusRules[] = Rule::notIn(self::ADMIN_ONLY_STATUSES);
        if ($request->filled('main_date')) $rsvpDeadlineRules[] = 'before_or_equal:main_date';

        $data = $request->validate(['customer_id' => ['nullable','exists:customers,id'], 'event_package_id' => ['nullable','exists:event_packages,id'], 'guest_capacity' => ['nullable','integer','min:1'], 'title' => ['required','string','max:255'], 'event_type' => ['required', Rule::in(Event::TYPES)], 'host_name' => ['required','string','max:255'], 'second_host_name' => ['nullable','string','max:255'], 'description' => ['nullable','string'], 'main_date' => ['nullable','date'], 'start_time' => ['nullable','date_format:H:i'], 'end_time' => $endTimeRules, 'venue' => ['nullable','string','max:255'], 'address' => ['nullable','string','max:255'], 'location_url' => ['nullable','url','max:2048'], 'rsvp_deadline' => $rsvpDeadlineRules, 'event_timezone' => ['required', 'timezone:all'], 'dress_code' => ['nullable', 'string'], 'parking_information' => ['nullable', 'string'], 'transportation_information' => ['nullable', 'string'], 'accommodation_information' => ['nullable', 'string'], 'guest_information' => ['nullable', 'string'], 'status' => $statusRules]);

        return EventTiming::deriveEndDate($data, $data['event_timezone']);
    }
    private function assertCapacityCompatible(?EventPackage $package, ?int $capacity, int $allocated): void { $effective = $capacity ?? $package?->maximum_guests; if ($capacity !== null && (! $package || $capacity < $package->minimum_guests || $capacity > $package->maximum_guests)) throw ValidationException::withMessages(['guest_capacity' => 'Guest capacity must be within the assigned Package range.']); if ($effective !== null && $allocated > $effective) throw ValidationException::withMessages([$capacity === null ? 'event_package_id' : 'guest_capacity' => 'Guest capacity cannot be lower than the capacity already allocated to invitation parties.']); }
    private function statusesFor(bool $isAdmin): array { return $isAdmin ? Event::STATUSES : array_values(array_diff(Event::STATUSES, self::ADMIN_ONLY_STATUSES)); }
    private function activityForPresentation($activity, Event $event): array { $start = $activity->starts_at->setTimezone($event->event_timezone); $end = $activity->ends_at?->setTimezone($event->event_timezone); return ['id' => $activity->id, 'title' => $activity->title, 'activity_type' => $activity->activity_type, 'date' => $start->format('F j, Y'), 'start_time' => $start->format('g:i A'), 'end_date' => $end?->format('F j, Y'), 'end_time' => $end?->format('g:i A'), 'venue' => $activity->venue, 'address' => $activity->address, 'location_url' => $activity->location_url, 'is_active' => $activity->is_active, 'display_order' => $activity->display_order]; }
}
