<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A claimed entitlement needs a real package. Stop before changing the
        // schema if a historical Event cannot be represented without guessing.
        if (DB::table('events')->whereNull('event_package_id')->exists()) {
            throw new \RuntimeException('Invitation entitlement migration stopped: every existing Event must have an event_package_id before it can be backfilled safely.');
        }

        if (! Schema::hasTable('invitation_entitlements')) {
            Schema::create('invitation_entitlements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('customer_id')->constrained()->restrictOnDelete();
                $table->foreignId('event_package_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('exact_guest_capacity');
                $table->string('status', 20)->default('available');
                $table->foreignId('claimed_event_id')->nullable()->unique()->constrained('events')->restrictOnDelete();
                $table->timestampTz('claimed_at')->nullable();
                $table->timestamps();
                $table->index(['customer_id', 'status']);
            });
        }

        $packages = DB::table('event_packages')->pluck('maximum_guests', 'id');
        $now = now();

        DB::table('events')->orderBy('id')->each(function (object $event) use ($packages, $now): void {
            $capacity = $event->guest_capacity ?? $packages[$event->event_package_id] ?? null;

            if ($capacity === null) {
                throw new \RuntimeException("Invitation entitlement migration stopped: Event {$event->id} has no exact guest capacity and its Package maximum is unavailable.");
            }

            // claimed_event_id is unique, making this safe if a deployment must
            // retry after a recoverable interruption.
            DB::table('invitation_entitlements')->updateOrInsert(
                ['claimed_event_id' => $event->id],
                [
                    'customer_id' => $event->customer_id,
                    'event_package_id' => $event->event_package_id,
                    'exact_guest_capacity' => $capacity,
                    'status' => 'claimed',
                    'claimed_at' => $event->created_at ?? $now,
                    'updated_at' => $now,
                    'created_at' => $event->created_at ?? $now,
                ],
            );
        });

        // The old effective capacity for a legacy Event was its Package maximum.
        // Persist that deterministic value so later Package edits cannot rewrite
        // historical invitation capacity.
        DB::table('events')->whereNull('guest_capacity')->orderBy('id')->each(function (object $event) use ($packages): void {
            DB::table('events')->where('id', $event->id)->update([
                'guest_capacity' => $packages[$event->event_package_id],
            ]);
        });

        // All current customer logins receive every historical Event. Existing
        // owner/editor rows remain untouched.
        DB::table('events')->orderBy('id')->each(function (object $event) use ($now): void {
            $userIds = DB::table('users')
                ->where('customer_id', $event->customer_id)
                ->where('role', 'customer')
                ->pluck('id');

            foreach ($userIds as $userId) {
                DB::table('event_user')->insertOrIgnore([
                    'event_id' => $event->id,
                    'user_id' => $userId,
                    'role' => 'editor',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        // Unused legacy allowed_events cannot safely become available slots: the
        // historic scalar contains neither a Package nor an exact capacity. They
        // remain visible only as legacy data until an Admin provisions explicit
        // entitlements.
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_entitlements');
    }
};
