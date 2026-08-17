<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{ event: any; customers: any[]; packages: any[]; types: string[]; statuses: string[]; timezones: string[]; defaultTimezone: string; isAdmin: boolean }>();
const dateValue = (value: string | null | undefined) => value ? value.slice(0, 10) : '';
const timeValue = (value: string | null | undefined) => value ? value.slice(0, 5) : '';
const form = useForm({
    customer_id: props.event?.customer_id || '', event_package_id: props.event?.event_package_id || '', title: props.event?.title || '', event_type: props.event?.event_type || props.types[0], host_name: props.event?.host_name || '', second_host_name: props.event?.second_host_name || '', description: props.event?.description || '', main_date: dateValue(props.event?.main_date), start_time: timeValue(props.event?.start_time), end_time: timeValue(props.event?.end_time), venue: props.event?.venue || '', address: props.event?.address || '', location_url: props.event?.location_url || '', rsvp_deadline: dateValue(props.event?.rsvp_deadline), event_timezone: props.event?.event_timezone || props.defaultTimezone, dress_code: props.event?.dress_code || '', parking_information: props.event?.parking_information || '', transportation_information: props.event?.transportation_information || '', accommodation_information: props.event?.accommodation_information || '', guest_information: props.event?.guest_information || '', status: props.event?.status || 'draft',
});
const submit = () => props.event ? form.put(route('events.update', props.event.id)) : form.post(route('events.store'));
</script>

<template>
    <Head :title="event ? 'Edit event' : 'Create event'" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold">{{ event ? 'Edit event' : 'Create event' }}</h2></template>
        <form class="mx-auto max-w-4xl space-y-6 p-6" @submit.prevent="submit">
            <section class="grid gap-4 bg-white p-6 shadow sm:grid-cols-2 sm:rounded-lg">
                <div v-if="isAdmin"><label for="customer_id" class="block text-sm font-medium">Customer</label><select id="customer_id" v-model="form.customer_id" required class="mt-1 block w-full rounded border-gray-300"><option value="">Select customer</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select><InputError :message="form.errors.customer_id" class="mt-1" /></div>
                <div><label for="title" class="block text-sm font-medium">Event title</label><input id="title" v-model="form.title" required class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.title" class="mt-1" /></div>
                <div><label for="event_type" class="block text-sm font-medium">Event type</label><select id="event_type" v-model="form.event_type" class="mt-1 block w-full rounded border-gray-300"><option v-for="type in types" :key="type" :value="type">{{ type.replaceAll('_', ' ') }}</option></select><InputError :message="form.errors.event_type" class="mt-1" /></div>
                <div><label for="host_name" class="block text-sm font-medium">Host name</label><input id="host_name" v-model="form.host_name" required class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.host_name" class="mt-1" /></div>
                <div><label for="second_host_name" class="block text-sm font-medium">Second host / partner name</label><input id="second_host_name" v-model="form.second_host_name" class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.second_host_name" class="mt-1" /></div>
                <div class="sm:col-span-2"><label for="description" class="block text-sm font-medium">Description</label><textarea id="description" v-model="form.description" rows="4" class="mt-1 block w-full rounded border-gray-300" /></div>
            </section>

            <section class="grid gap-4 bg-white p-6 shadow sm:grid-cols-2 sm:rounded-lg">
                <h3 class="sm:col-span-2 text-lg font-semibold">Date & Time</h3>
                <div class="sm:col-span-2"><label for="event_timezone" class="block text-sm font-medium">Event timezone</label><select id="event_timezone" v-model="form.event_timezone" required class="mt-1 block w-full rounded border-gray-300"><option disabled value="">Select timezone</option><option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone }}</option></select><p class="mt-1 text-xs text-gray-500">RSVP deadlines and schedule activities use this Event timezone.</p><InputError :message="form.errors.event_timezone" class="mt-1" /></div>
                <div><label for="main_date" class="block text-sm font-medium">Main date</label><input id="main_date" v-model="form.main_date" type="date" class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.main_date" class="mt-1" /></div>
                <div><label for="rsvp_deadline" class="block text-sm font-medium">RSVP deadline</label><input id="rsvp_deadline" v-model="form.rsvp_deadline" type="date" class="mt-1 block w-full rounded border-gray-300"><p class="mt-1 text-xs text-gray-500">RSVPs remain open through the end of the selected Event timezone day.</p><InputError :message="form.errors.rsvp_deadline" class="mt-1" /></div>
                <div><label for="start_time" class="block text-sm font-medium">Start time</label><input id="start_time" v-model="form.start_time" type="time" class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.start_time" class="mt-1" /></div>
                <div><label for="end_time" class="block text-sm font-medium">End time</label><input id="end_time" v-model="form.end_time" type="time" class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.end_time" class="mt-1" /></div>
            </section>

            <section class="grid gap-4 bg-white p-6 shadow sm:grid-cols-2 sm:rounded-lg"><h3 class="sm:col-span-2 text-lg font-semibold">Location</h3><div><label for="venue" class="block text-sm font-medium">Venue</label><input id="venue" v-model="form.venue" class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.venue" class="mt-1" /></div><div><label for="address" class="block text-sm font-medium">Address</label><input id="address" v-model="form.address" class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.address" class="mt-1" /></div><div class="sm:col-span-2"><label for="location_url" class="block text-sm font-medium">Location URL</label><input id="location_url" v-model="form.location_url" type="url" class="mt-1 block w-full rounded border-gray-300"><InputError :message="form.errors.location_url" class="mt-1" /></div></section>

            <section class="grid gap-4 bg-white p-6 shadow sm:grid-cols-2 sm:rounded-lg"><h3 class="sm:col-span-2 text-lg font-semibold">Guest Information</h3><div><label for="dress_code" class="block text-sm font-medium">Dress code</label><textarea id="dress_code" v-model="form.dress_code" class="mt-1 block w-full rounded border-gray-300" /></div><div><label for="parking_information" class="block text-sm font-medium">Parking information</label><textarea id="parking_information" v-model="form.parking_information" class="mt-1 block w-full rounded border-gray-300" /></div><div><label for="transportation_information" class="block text-sm font-medium">Transportation information</label><textarea id="transportation_information" v-model="form.transportation_information" class="mt-1 block w-full rounded border-gray-300" /></div><div><label for="accommodation_information" class="block text-sm font-medium">Accommodation / hotel information</label><textarea id="accommodation_information" v-model="form.accommodation_information" class="mt-1 block w-full rounded border-gray-300" /></div><div class="sm:col-span-2"><label for="guest_information" class="block text-sm font-medium">Additional guest information</label><textarea id="guest_information" v-model="form.guest_information" class="mt-1 block w-full rounded border-gray-300" /></div></section>

            <section class="grid gap-4 bg-white p-6 shadow sm:grid-cols-2 sm:rounded-lg">
                <h3 class="sm:col-span-2 text-lg font-semibold">Package & Status</h3>
                <div v-if="isAdmin"><label for="event_package_id" class="block text-sm font-medium">Package</label><select id="event_package_id" v-model="form.event_package_id" class="mt-1 block w-full rounded border-gray-300"><option value="">No package assigned</option><option v-for="packageItem in packages" :key="packageItem.id" :value="packageItem.id">{{ packageItem.name }}</option></select><InputError :message="form.errors.event_package_id" class="mt-1" /></div>
                <div v-else><p class="text-sm font-medium">Package</p><p class="mt-1 text-gray-600">{{ event?.package?.name || 'Not assigned — package assignment is managed by an administrator.' }}</p></div>
                <div><p class="text-sm font-medium">Status</p><p class="mt-1 capitalize text-gray-600">{{ form.status.replaceAll('_', ' ') }}</p><p class="mt-1 text-sm text-gray-500">Use the Publishing section on Event Details to submit, review, approve, publish, or archive this Event.</p></div>
            </section>
            <button :disabled="form.processing" class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">{{ event ? 'Save changes' : 'Create event' }}</button>
        </form>
    </AuthenticatedLayout>
</template>
