<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ event: any; party: any }>();
</script>

<template>
    <Head :title="`${party.name} RSVP`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between"><h2 class="text-xl font-semibold">{{ party.name }} RSVP</h2><Link :href="route('events.rsvps.index', event.id)" class="rounded border px-3 py-2 text-sm">Back to RSVPs</Link></div>
        </template>
        <div class="mx-auto max-w-5xl space-y-5 p-6">
            <section class="rounded bg-white p-5 shadow">
                <h3 class="mb-3 text-lg font-semibold">Response</h3>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500">Status</dt><dd class="capitalize">{{ party.rsvp?.status?.replaceAll('_', ' ') || 'Pending' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Maximum party size</dt><dd>{{ party.maximum_party_size }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Submitted</dt><dd>{{ party.rsvp?.submitted_at || 'Not submitted' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Updated</dt><dd>{{ party.rsvp?.last_updated_at || (party.rsvp?.submitted_at ? 'Not updated since submission' : 'Not submitted') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Guest message</dt><dd>{{ party.rsvp?.guest_message || 'None' }}</dd></div>
                </dl>
            </section>
            <section class="overflow-x-auto rounded bg-white shadow">
                <div class="p-5"><h3 class="text-lg font-semibold">People</h3></div>
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="p-3">Name</th><th class="p-3">Type</th><th class="p-3">Attending</th><th class="p-3">Meal</th><th class="p-3">Dietary note</th><th class="p-3">Source</th></tr></thead>
                    <tbody><tr v-for="person in party.rsvp?.person_responses || []" :key="person.id" class="border-t"><td class="p-3">{{ person.first_name }} {{ person.last_name }}</td><td class="p-3 capitalize">{{ person.member_type }}</td><td class="p-3">{{ person.is_attending ? 'Attending' : 'Not attending' }}</td><td class="p-3">{{ person.meal_option?.name || 'None' }}</td><td class="p-3">{{ person.dietary_note || 'None' }}</td><td class="p-3">{{ person.is_original_party_member ? 'Party member' : 'Additional guest' }}</td></tr></tbody>
                </table>
                <p v-if="!(party.rsvp?.person_responses || []).length" class="p-5 text-gray-600">No person responses submitted yet.</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
