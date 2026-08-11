<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ party: any; rsvp: any; meals: any[]; closed: boolean; confirmation: string | null }>();
const hasSubmittedResponse = computed(() => ['attending', 'not_attending'].includes(props.rsvp?.status));
const isEditing = ref(false);
const previous = (id: number) => props.rsvp?.person_responses?.find((person: any) => person.party_member_id === id);
const form = useForm({
    status: props.rsvp?.status === 'pending' ? '' : props.rsvp?.status || '',
    guest_message: props.rsvp?.guest_message || '',
    members: props.party.members.map((member: any) => {
        const response = previous(member.id);
        return { id: member.id, is_attending: response?.is_attending ?? false, event_meal_option_id: response?.event_meal_option_id || '', dietary_note: response?.dietary_note || '' };
    }),
    additional_guests: (props.rsvp?.person_responses || []).filter((person: any) => !person.is_original_party_member).map((person: any) => ({ first_name: person.first_name, last_name: person.last_name || '', member_type: person.member_type, event_meal_option_id: person.event_meal_option_id || '', dietary_note: person.dietary_note || '' })),
});
const availableAdditionalSlots = computed(() => Math.max(
    Number(props.party.maximum_party_size || 0) - Number(props.party.listed_member_count || 0),
    0,
));
const addGuest = () => {
    if (form.additional_guests.length < availableAdditionalSlots.value) form.additional_guests.push({ first_name: '', last_name: '', member_type: 'adult', event_meal_option_id: '', dietary_note: '' });
};
const submit = () => form.post(route('public.rsvp.submit', route().params.token), { onSuccess: () => { isEditing.value = false; } });
</script>

<template>
    <Head :title="`RSVP for ${party.event.title}`" />
    <main class="min-h-screen bg-gray-100 p-4 sm:p-8">
        <div class="mx-auto max-w-2xl space-y-5">
            <section class="rounded bg-white p-6 shadow">
                <h1 class="text-2xl font-semibold">{{ party.event.title }}</h1>
                <p v-if="party.event.event_type" class="mt-1 capitalize text-gray-500">{{ party.event.event_type }}</p>
                <p class="mt-2 text-gray-600">{{ party.event.host_name }} <span v-if="party.event.second_host_name">&amp; {{ party.event.second_host_name }}</span></p>
                <p class="mt-3">Invitation for <strong>{{ party.name }}</strong></p>
                <dl class="mt-4 grid gap-3 border-t pt-4 text-sm sm:grid-cols-2">
                    <div v-if="party.event.main_date"><dt class="text-gray-500">Event date</dt><dd>{{ party.event.main_date }}</dd></div>
                    <div v-if="party.event.start_time || party.event.end_time"><dt class="text-gray-500">Event time</dt><dd>{{ party.event.start_time || 'Time to be confirmed' }}<span v-if="party.event.end_time"> – {{ party.event.end_time }}</span></dd></div>
                    <div v-if="party.event.venue"><dt class="text-gray-500">Venue</dt><dd>{{ party.event.venue }}</dd></div>
                    <div v-if="party.event.address"><dt class="text-gray-500">Address</dt><dd>{{ party.event.address }}</dd></div>
                    <div v-if="party.event.location_url"><dt class="text-gray-500">Location</dt><dd><a :href="party.event.location_url" target="_blank" rel="noopener noreferrer" class="text-indigo-600 underline">Open location</a></dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">RSVP deadline</dt><dd>{{ party.event.rsvp_deadline || 'No RSVP deadline' }}</dd></div>
                </dl>
            </section>

            <section v-if="hasSubmittedResponse && !isEditing" class="rounded bg-white p-6 shadow">
                <p v-if="confirmation" class="mb-3 rounded border border-green-200 bg-green-50 p-3 text-green-800">{{ confirmation }}</p>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Your submitted RSVP</h2>
                        <p class="mt-1 text-gray-600">Response: <strong>{{ rsvp.status === 'attending' ? 'Attending' : 'Not attending' }}</strong></p>
                        <p class="text-sm text-gray-500">Last updated: {{ rsvp.last_updated_at || rsvp.submitted_at }}</p>
                    </div>
                    <button v-if="!closed" type="button" class="rounded border border-gray-300 px-3 py-2 text-sm" @click="isEditing = true">Edit RSVP</button>
                </div>

                <div v-if="rsvp.status === 'attending' && rsvp.person_responses?.length" class="mt-4 border-t pt-4">
                    <h3 class="font-medium">Guest responses</h3>
                    <div class="mt-2 space-y-2 text-sm">
                        <div v-for="(person, index) in rsvp.person_responses" :key="person.party_member_id || `additional-${index}`" class="rounded bg-gray-50 p-3">
                            <strong>{{ person.first_name }} {{ person.last_name }}</strong>
                            <dl class="mt-2 space-y-1 text-gray-700">
                                <div><dt class="inline font-medium">Status:</dt> <dd class="inline">{{ person.is_attending ? 'Attending' : 'Not attending' }}</dd></div>
                                <div v-if="person.is_attending && person.meal_option"><dt class="inline font-medium">Meal:</dt> <dd class="inline">{{ person.meal_option.name }}</dd></div>
                                <div v-if="person.is_attending && person.dietary_note"><dt class="inline font-medium">Dietary note:</dt> <dd class="inline">{{ person.dietary_note }}</dd></div>
                            </dl>
                        </div>
                    </div>
                </div>
                <div v-if="rsvp.guest_message" class="mt-4 border-t pt-4"><h3 class="font-medium">Message to host</h3><p class="mt-1 whitespace-pre-line text-gray-700">{{ rsvp.guest_message }}</p></div>
            </section>

            <section v-if="closed" class="rounded bg-white p-6 shadow">
                <h2 class="text-lg font-semibold">RSVP closed</h2>
                <p class="mt-2 text-gray-600">The RSVP deadline has passed. Please contact the host if you need assistance.</p>
            </section>

            <form v-if="!closed && (!hasSubmittedResponse || isEditing)" class="space-y-5 rounded bg-white p-6 shadow" @submit.prevent="submit">
                <div>
                    <h2 class="font-semibold">Will you attend?</h2>
                    <label class="mr-4"><input v-model="form.status" type="radio" value="attending" required> Attending</label>
                    <label><input v-model="form.status" type="radio" value="not_attending"> Not attending</label>
                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                </div>

                <div v-if="form.status === 'attending'" class="space-y-4">
                    <div v-for="(member, index) in party.members" :key="member.id" class="rounded border p-3">
                        <div class="flex justify-between"><strong>{{ member.first_name }} {{ member.last_name }}</strong><label><input v-model="form.members[index].is_attending" type="checkbox"> Attending</label></div>
                        <div v-if="form.members[index].is_attending" class="mt-3 grid gap-2 sm:grid-cols-2">
                            <select v-if="meals.length" v-model="form.members[index].event_meal_option_id" class="rounded border-gray-300"><option value="">No meal selected</option><option v-for="meal in meals" :key="meal.id" :value="meal.id">{{ meal.name }}</option></select>
                            <input v-model="form.members[index].dietary_note" placeholder="Dietary / allergy note (optional)" class="rounded border-gray-300">
                        </div>
                    </div>

                    <section v-if="availableAdditionalSlots > 0" class="space-y-3 rounded border border-dashed p-4">
                        <div>
                            <h3 class="font-semibold">Additional guests</h3>
                            <p class="text-sm text-gray-600">You may add up to {{ availableAdditionalSlots }} additional {{ availableAdditionalSlots === 1 ? 'guest' : 'guests' }}.</p>
                        </div>
                        <div v-for="(guest, index) in form.additional_guests" :key="index" class="rounded border p-3">
                            <div class="flex justify-between"><strong>Additional guest {{ index + 1 }}</strong><button type="button" class="text-red-600" @click="form.additional_guests.splice(index, 1)">Remove</button></div>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div><label class="mb-1 block text-sm">First name</label><input v-model="guest.first_name" required class="w-full rounded border-gray-300"></div>
                                <div><label class="mb-1 block text-sm">Last name</label><input v-model="guest.last_name" class="w-full rounded border-gray-300"></div>
                                <div><label class="mb-1 block text-sm">Adult / Child</label><select v-model="guest.member_type" class="w-full rounded border-gray-300"><option value="adult">Adult</option><option value="child">Child</option></select></div>
                                <div v-if="meals.length"><label class="mb-1 block text-sm">Meal</label><select v-model="guest.event_meal_option_id" class="w-full rounded border-gray-300"><option value="">No meal selected</option><option v-for="meal in meals" :key="meal.id" :value="meal.id">{{ meal.name }}</option></select></div>
                                <div class="sm:col-span-2"><label class="mb-1 block text-sm">Dietary / allergy note (optional)</label><input v-model="guest.dietary_note" class="w-full rounded border-gray-300"></div>
                            </div>
                        </div>
                        <button v-if="form.additional_guests.length < availableAdditionalSlots" type="button" class="w-fit rounded border border-indigo-600 px-3 py-2 text-indigo-600" @click="addGuest">+ Add Guest</button>
                        <p v-else class="text-sm text-gray-600">Maximum additional guest capacity reached.</p>
                    </section>
                </div>

                <div><label class="mb-1 block font-semibold">Message to host (optional)</label><textarea v-model="form.guest_message" class="w-full rounded border-gray-300" /></div>
                <p v-if="Object.keys(form.errors).length" class="text-sm text-red-600">Please correct the highlighted RSVP details.</p>
                <div class="flex gap-3"><button :disabled="form.processing" class="rounded bg-indigo-600 px-4 py-2 text-white">{{ hasSubmittedResponse ? 'Update RSVP' : 'Submit RSVP' }}</button><button v-if="hasSubmittedResponse" type="button" class="text-gray-600" @click="isEditing = false">Cancel</button></div>
            </form>
        </div>
    </main>
</template>
