<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ party: any; rsvp: any; meals: any[]; closed: boolean; confirmation: string | null; romantic?: boolean; editorial?: boolean }>();
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
const availableAdditionalSlots = computed(() => Math.max(Number(props.party.maximum_party_size || 0) - Number(props.party.listed_member_count || 0), 0));
const addGuest = () => { if (form.additional_guests.length < availableAdditionalSlots.value) form.additional_guests.push({ first_name: '', last_name: '', member_type: 'adult', event_meal_option_id: '', dietary_note: '' }); };
const submit = () => form.post(route('public.rsvp.submit', route().params.token), { onSuccess: () => { isEditing.value = false; } });
</script>

<template>
    <div :class="{ 'romantic-rsvp': romantic, 'editorial-rsvp': editorial }">
    <section v-if="hasSubmittedResponse && !isEditing" class="rounded-2xl border border-white/60 bg-white/90 p-6 shadow-sm backdrop-blur">
        <p v-if="confirmation" class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">{{ confirmation }}</p>
        <div class="flex items-start justify-between gap-4"><div><h2 class="font-serif text-2xl font-semibold">Your RSVP</h2><p class="mt-1 text-stone-700">Response: <strong>{{ rsvp.status === 'attending' ? 'Attending' : 'Not attending' }}</strong></p><p class="text-sm text-stone-500">Last updated: {{ rsvp.last_updated_at || rsvp.submitted_at }}</p></div><button v-if="!closed" type="button" class="rounded-full border border-stone-400 px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-rose-500" @click="isEditing = true">Edit RSVP</button></div>
        <div v-if="rsvp.status === 'attending' && rsvp.person_responses?.length" class="mt-5 border-t border-stone-200 pt-4"><h3 class="font-medium">Guest responses</h3><div class="mt-3 space-y-2 text-sm"><div v-for="(person, index) in rsvp.person_responses" :key="person.party_member_id || `additional-${index}`" class="rounded-xl bg-rose-50/60 p-3"><strong>{{ person.first_name }} {{ person.last_name }}</strong><dl class="mt-2 space-y-1 text-stone-700"><div><dt class="inline font-medium">Status:</dt> <dd class="inline">{{ person.is_attending ? 'Attending' : 'Not attending' }}</dd></div><div v-if="!person.is_original_party_member"><dt class="inline font-medium">Guest type:</dt> <dd class="inline">{{ person.member_type === 'child' ? 'Child' : 'Adult' }}</dd></div><div v-if="person.is_attending && person.meal_option"><dt class="inline font-medium">Meal:</dt> <dd class="inline">{{ person.meal_option.name }}</dd></div><div v-if="person.is_attending && person.dietary_note"><dt class="inline font-medium">Dietary note:</dt> <dd class="inline">{{ person.dietary_note }}</dd></div></dl></div></div></div>
        <div v-if="rsvp.guest_message" class="mt-4 border-t border-stone-200 pt-4"><h3 class="font-medium">Message to host</h3><p class="mt-1 whitespace-pre-line text-stone-700">{{ rsvp.guest_message }}</p></div>
    </section>

    <section v-if="closed" class="rounded-2xl border border-white/60 bg-white/90 p-6 shadow-sm"><h2 class="font-serif text-2xl font-semibold">RSVP closed</h2><p class="mt-2 text-stone-700">The RSVP deadline has passed. Please contact the host if you need assistance.</p></section>

    <form v-if="!closed && (!hasSubmittedResponse || isEditing)" class="space-y-5 rounded-2xl border border-white/60 bg-white/90 p-6 shadow-sm backdrop-blur" @submit.prevent="submit">
        <div><h2 class="font-serif text-2xl font-semibold">RSVP</h2><p class="mt-1 text-stone-600">Will you attend?</p><label class="mr-4 mt-3 inline-flex items-center gap-2"><input v-model="form.status" type="radio" value="attending" required> Attending</label><label class="inline-flex items-center gap-2"><input v-model="form.status" type="radio" value="not_attending"> Not attending</label><p v-if="form.errors.status" class="mt-1 text-sm text-red-700">{{ form.errors.status }}</p></div>
        <div v-if="form.status === 'attending'" class="space-y-4"><div v-for="(member, index) in party.members" :key="member.id" class="rounded-xl border border-stone-200 p-4"><div class="flex justify-between gap-3"><strong>{{ member.first_name }} {{ member.last_name }}</strong><label class="inline-flex items-center gap-2"><input v-model="form.members[index].is_attending" type="checkbox"> Attending</label></div><div v-if="form.members[index].is_attending" class="mt-3 grid gap-2 sm:grid-cols-2"><select v-if="meals.length" v-model="form.members[index].event_meal_option_id" class="rounded border-stone-300"><option value="">No meal selected</option><option v-for="meal in meals" :key="meal.id" :value="meal.id">{{ meal.name }}</option></select><input v-model="form.members[index].dietary_note" placeholder="Dietary / allergy note (optional)" class="rounded border-stone-300"></div></div>
            <section v-if="availableAdditionalSlots > 0" class="space-y-3 rounded-xl border border-dashed border-rose-300 p-4"><div><h3 class="font-semibold">Additional guests</h3><p class="text-sm text-stone-600">You may add up to {{ availableAdditionalSlots }} additional {{ availableAdditionalSlots === 1 ? 'guest' : 'guests' }}.</p></div><div v-for="(guest, index) in form.additional_guests" :key="index" class="rounded-xl border border-stone-200 p-4"><div class="flex justify-between"><strong>Additional guest {{ index + 1 }}</strong><button type="button" class="text-rose-700 underline" @click="form.additional_guests.splice(index, 1)">Remove</button></div><div class="mt-3 grid gap-3 sm:grid-cols-2"><div><label class="mb-1 block text-sm">First name</label><input v-model="guest.first_name" required class="w-full rounded border-stone-300"></div><div><label class="mb-1 block text-sm">Last name</label><input v-model="guest.last_name" class="w-full rounded border-stone-300"></div><div><label class="mb-1 block text-sm">Adult / Child</label><select v-model="guest.member_type" class="w-full rounded border-stone-300"><option value="adult">Adult</option><option value="child">Child</option></select></div><div v-if="meals.length"><label class="mb-1 block text-sm">Meal</label><select v-model="guest.event_meal_option_id" class="w-full rounded border-stone-300"><option value="">No meal selected</option><option v-for="meal in meals" :key="meal.id" :value="meal.id">{{ meal.name }}</option></select></div><div class="sm:col-span-2"><label class="mb-1 block text-sm">Dietary / allergy note (optional)</label><input v-model="guest.dietary_note" class="w-full rounded border-stone-300"></div></div></div><button v-if="form.additional_guests.length < availableAdditionalSlots" type="button" class="rounded-full border border-rose-700 px-4 py-2 text-sm font-medium text-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-500" @click="addGuest">+ Add Guest</button><p v-else class="text-sm text-stone-600">Maximum additional guest capacity reached.</p></section>
        </div>
        <div><label class="mb-1 block font-medium">Message to host (optional)</label><textarea v-model="form.guest_message" class="w-full rounded border-stone-300" /></div><p v-if="Object.keys(form.errors).length" class="text-sm text-red-700">Please correct the highlighted RSVP details.</p><div class="flex gap-3"><button :disabled="form.processing" class="rounded-full bg-rose-800 px-5 py-2.5 font-medium text-white focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:opacity-60">{{ hasSubmittedResponse ? 'Update RSVP' : 'Submit RSVP' }}</button><button v-if="hasSubmittedResponse" type="button" class="text-stone-700 underline" @click="isEditing = false">Cancel</button></div>
    </form>
    </div>
</template>

<style scoped>
.romantic-rsvp :is(section, form) { border: 1px solid rgba(194, 138, 145, 0.38); border-radius: 1.35rem; background: linear-gradient(145deg, rgba(255, 252, 248, 0.96), rgba(248, 234, 228, 0.9)); box-shadow: 0 1rem 2.8rem rgba(91, 38, 50, 0.1); }
.romantic-rsvp h2, .romantic-rsvp h3 { color: #4a1723; font-family: Georgia, serif; font-weight: 400; }
.romantic-rsvp :is(input, select, textarea) { min-height: 2.7rem; border: 1px solid rgba(172, 112, 124, 0.45); border-radius: 0.7rem; background: rgba(255, 255, 255, 0.84); color: #3e3030; }
.romantic-rsvp input[type='radio'], .romantic-rsvp input[type='checkbox'] { min-height: auto; accent-color: #7b3041; }
.romantic-rsvp :is(input, select, textarea):focus { outline: 3px solid rgba(146, 54, 73, 0.2); outline-offset: 1px; }
.romantic-rsvp :is(.rounded-xl.border-stone-200, .rounded-xl.border-dashed) { border-color: rgba(194, 138, 145, 0.42); background: rgba(255, 252, 248, 0.75); }
.romantic-rsvp button[type='submit'] { background: #6f2334; box-shadow: 0 0.6rem 1.2rem rgba(91, 38, 50, 0.2); }
.romantic-rsvp button[type='submit']:hover { background: #581727; }
.romantic-rsvp button[type='button'] { color: #6f2334; }
.editorial-rsvp :is(section, form) { border: 1px solid color-mix(in srgb, currentColor 20%, transparent); border-radius: 0; background: transparent; box-shadow: none; }
.editorial-rsvp h2, .editorial-rsvp h3 { font-family: Georgia, serif; font-weight: 400; }
.editorial-rsvp :is(input, select, textarea) { min-height: 2.85rem; border: 1px solid color-mix(in srgb, currentColor 28%, transparent); border-radius: 0; background: rgba(255, 255, 255, 0.46); color: inherit; }
.editorial-rsvp input[type='radio'], .editorial-rsvp input[type='checkbox'] { min-height: auto; accent-color: #24211d; }
.editorial-rsvp :is(input, select, textarea):focus { outline: 3px solid rgba(168, 135, 74, 0.3); outline-offset: 2px; }
.editorial-rsvp :is(.rounded-xl.border-stone-200, .rounded-xl.border-dashed) { border-color: color-mix(in srgb, currentColor 20%, transparent); border-radius: 0; background: transparent; }
.editorial-rsvp button[type='submit'] { border-radius: 0; background: #1f1d1a; box-shadow: none; letter-spacing: 0.09em; text-transform: uppercase; }
.editorial-rsvp button[type='submit']:hover { background: #3a3630; }
.editorial-rsvp button[type='button'] { color: inherit; }
</style>
