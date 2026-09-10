<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Event;
use App\Models\InvitationEntitlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvitationEntitlementClaimService
{
    public function claim(Customer $customer, ?int $entitlementId = null): Event
    {
        return DB::transaction(function () use ($customer, $entitlementId): Event {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $entitlements = $customer->invitationEntitlements()
                ->where('status', InvitationEntitlement::AVAILABLE)
                ->whereNull('claimed_event_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($entitlementId === null && $entitlements->count() > 1) {
                throw ValidationException::withMessages([
                    'entitlement_id' => 'Choose which available invitation entitlement to start.',
                ]);
            }

            $entitlement = $entitlementId === null
                ? $entitlements->first()
                : $entitlements->firstWhere('id', $entitlementId);

            if (! $entitlement) {
                throw ValidationException::withMessages([
                    'entitlement_id' => 'This Client has no available invitation entitlement.',
                ]);
            }

            $package = $entitlement->package()->lockForUpdate()->firstOrFail();
            if ($entitlement->exact_guest_capacity < $package->minimum_guests || $entitlement->exact_guest_capacity > $package->maximum_guests) {
                throw ValidationException::withMessages([
                    'entitlement_id' => 'The selected invitation entitlement has a capacity outside its Package range.',
                ]);
            }

            $event = Event::create([
                'customer_id' => $customer->id,
                'event_package_id' => $package->id,
                'guest_capacity' => $entitlement->exact_guest_capacity,
                'event_timezone' => config('app.timezone'),
                'status' => 'draft',
            ]);

            $customerUserIds = $customer->users()
                ->where('role', 'customer')
                ->lockForUpdate()
                ->pluck('id');

            $event->members()->syncWithoutDetaching(
                $customerUserIds->mapWithKeys(fn (int $id) => [$id => ['role' => 'editor']])->all(),
            );

            $entitlement->update([
                'status' => InvitationEntitlement::CLAIMED,
                'claimed_event_id' => $event->id,
                'claimed_at' => now(),
            ]);

            return $event;
        });
    }
}
