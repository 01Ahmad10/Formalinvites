<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ event: any; customer: any; paymentSummary: any; canEdit: boolean; isAdmin: boolean }>();
const value = (item: string | null | undefined) => item || 'Not provided';
const date = (item: string | null | undefined) => item ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(`${item.slice(0, 10)}T00:00:00`)) : 'Not provided';
const money = (item: string | number | null | undefined) => item === null || item === undefined ? 'Not provided' : `$${Number(item).toFixed(2)}`;
const label = (item: string) => item.replaceAll('_', ' ');
</script>

<template>
    <Head :title="event.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div><h2 class="text-xl font-semibold">{{ event.title }}</h2><p class="text-sm text-gray-600">{{ label(event.status) }}</p></div>
                <div class="flex gap-2"><Link :href="route('events.index')" class="rounded border border-gray-300 px-3 py-2 text-sm">Back to Events</Link><Link v-if="canEdit" :href="route('events.edit', event.id)" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">Edit Event</Link></div>
            </div>
        </template>

        <div class="mx-auto max-w-5xl space-y-6 p-6">
            <section class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">Basic Information</h3><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-gray-500">Event title</dt><dd>{{ event.title }}</dd></div><div><dt class="text-sm text-gray-500">Event type</dt><dd class="capitalize">{{ label(event.event_type) }}</dd></div><div><dt class="text-sm text-gray-500">Host name</dt><dd>{{ value(event.host_name) }}</dd></div><div><dt class="text-sm text-gray-500">Second host / partner</dt><dd>{{ value(event.second_host_name) }}</dd></div><div class="sm:col-span-2"><dt class="text-sm text-gray-500">Description</dt><dd class="whitespace-pre-line">{{ value(event.description) }}</dd></div><div><dt class="text-sm text-gray-500">Current status</dt><dd class="capitalize">{{ label(event.status) }}</dd></div></dl></section>

            <section class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">Date & Time</h3><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-gray-500">Main date</dt><dd>{{ date(event.main_date) }}</dd></div><div><dt class="text-sm text-gray-500">RSVP deadline</dt><dd>{{ date(event.rsvp_deadline) }}</dd></div><div><dt class="text-sm text-gray-500">Start time</dt><dd>{{ value(event.start_time) }}</dd></div><div><dt class="text-sm text-gray-500">End time</dt><dd>{{ value(event.end_time) }}</dd></div></dl></section>

            <section class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">Location</h3><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-gray-500">Venue</dt><dd>{{ value(event.venue) }}</dd></div><div><dt class="text-sm text-gray-500">Address</dt><dd>{{ value(event.address) }}</dd></div><div class="sm:col-span-2"><dt class="text-sm text-gray-500">Location URL</dt><dd><a v-if="event.location_url" :href="event.location_url" target="_blank" rel="noopener noreferrer" class="text-indigo-600 underline">Open Location</a><span v-else>Not provided</span></dd></div></dl></section>

            <section v-if="isAdmin && customer" class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">Customer</h3><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-gray-500">Customer name</dt><dd>{{ customer.name }}</dd></div><div><dt class="text-sm text-gray-500">Contact name</dt><dd>{{ value(customer.contact_name) }}</dd></div><div><dt class="text-sm text-gray-500">Email</dt><dd>{{ value(customer.email) }}</dd></div><div><dt class="text-sm text-gray-500">Phone</dt><dd>{{ value(customer.phone) }}</dd></div></dl></section>

            <section class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">Package</h3><dl v-if="event.package" class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-gray-500">Package name</dt><dd>{{ event.package.name }}</dd></div><div><dt class="text-sm text-gray-500">State</dt><dd>{{ event.package.is_active ? 'Active' : 'Inactive' }}</dd></div><div><dt class="text-sm text-gray-500">Guest capacity</dt><dd>{{ event.package.minimum_guests }}–{{ event.package.maximum_guests }} guests</dd></div><div><dt class="text-sm text-gray-500">Price</dt><dd>{{ money(event.package.price) }}</dd></div></dl><p v-else class="text-gray-600">Not provided</p></section>

            <section class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">Payment Summary</h3><dl v-if="paymentSummary" class="grid gap-4 sm:grid-cols-3"><div><dt class="text-sm text-gray-500">Package price</dt><dd>{{ money(paymentSummary.package_price) }}</dd></div><div><dt class="text-sm text-gray-500">Original amount</dt><dd>{{ money(paymentSummary.original_amount) }}</dd></div><div><dt class="text-sm text-gray-500">Discount</dt><dd>{{ money(paymentSummary.discount) }}</dd></div><div><dt class="text-sm text-gray-500">Final amount</dt><dd>{{ money(paymentSummary.final_amount) }}</dd></div><div><dt class="text-sm text-gray-500">Paid amount</dt><dd>{{ money(paymentSummary.paid_amount) }}</dd></div><div><dt class="text-sm text-gray-500">Remaining balance</dt><dd>{{ money(paymentSummary.remaining_amount) }}</dd></div><div><dt class="text-sm text-gray-500">Payment status</dt><dd class="capitalize">{{ label(paymentSummary.status) }}</dd></div></dl><p v-else class="text-gray-600">No payment recorded yet.</p></section>

            <section class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">Event Members</h3><div v-if="event.members.length" class="divide-y"><div v-for="member in event.members" :key="member.id" class="flex justify-between py-3"><span>{{ member.name }}</span><span class="capitalize text-gray-600">{{ member.pivot.role }}</span></div></div><p v-else class="text-gray-600">No members are assigned.</p></section>

            <section class="rounded bg-white p-6 shadow sm:rounded-lg"><h3 class="mb-4 text-lg font-semibold">System Information</h3><dl class="grid gap-4 sm:grid-cols-2"><div><dt class="text-sm text-gray-500">Created date</dt><dd>{{ date(event.created_at) }}</dd></div><div><dt class="text-sm text-gray-500">Last updated</dt><dd>{{ date(event.updated_at) }}</dd></div></dl></section>
        </div>
    </AuthenticatedLayout>
</template>
