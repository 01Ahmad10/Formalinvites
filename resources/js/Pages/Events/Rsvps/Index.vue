<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import ValidationSummary from '@/Components/ValidationSummary.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

type Meal = { id: number; name: string; description: string | null; display_order: number; is_active: boolean };

const props = defineProps<{ event: any; parties: any[]; summary: any; meals: Meal[]; filters: any; canManage: boolean; fromSetup: boolean; pagination?: any }>();
const filters = reactive({ ...props.filters });
const mealForm = useForm({ name: '', description: '', display_order: 0, from_setup: props.fromSetup });
const editingMeal = ref<Meal | null>(null);
const editMealForm = useForm({ name: '', description: '', display_order: 0, from_setup: props.fromSetup });

const apply = () => router.get(route('events.rsvps.index', props.event.id), { ...filters, from_setup: props.fromSetup ? 1 : undefined }, { preserveState: true, replace: true });
const reset = () => { filters.search = ''; filters.status = ''; apply(); };
const attendingCount = (party: any) => party.rsvp?.person_responses?.filter((person: any) => person.is_attending).length || 0;
const adultCount = (party: any) => party.rsvp?.person_responses?.filter((person: any) => person.is_attending && person.member_type === 'adult').length || 0;
const childCount = (party: any) => party.rsvp?.person_responses?.filter((person: any) => person.is_attending && person.member_type === 'child').length || 0;
const startEditingMeal = (meal: Meal) => {
    editingMeal.value = meal;
    editMealForm.name = meal.name;
    editMealForm.description = meal.description || '';
    editMealForm.display_order = meal.display_order;
    editMealForm.clearErrors();
};
const updateMeal = () => {
    if (!editingMeal.value) return;
    editMealForm.put(route('events.meals.update', [props.event.id, editingMeal.value.id]), {
        onSuccess: () => { editingMeal.value = null; },
    });
};
</script>

<template>
    <Head :title="`${event.title} RSVPs`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between">
                <h2 class="text-xl font-semibold">RSVP responses</h2>
                <Link :href="fromSetup ? route('events.setup', { event: event.id, step: 3 }) : route('events.show', event.id)" class="rounded border px-3 py-2 text-sm">{{ fromSetup ? 'Back to Invitation Setup' : 'Back to Invitation' }}</Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-5 p-6">
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(value, label) in {
                    'Invitation parties': summary.party_count,
                    Responded: summary.responded,
                    Pending: summary.pending,
                    'Attending parties': summary.attending_parties,
                    Declined: summary.declined_parties,
                    'Attending people': summary.attending_people,
                    Adults: summary.adults,
                    Children: summary.children,
                }" :key="label" class="rounded bg-white p-4 shadow">
                    <p class="text-sm text-gray-500">{{ label }}</p>
                    <p class="text-xl font-semibold">{{ value }}</p>
                </div>
            </section>

            <section class="bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold">Meal options</h3>
                <form v-if="canManage" class="flex flex-wrap gap-2" @submit.prevent="mealForm.post(route('events.meals.store', event.id), { onSuccess: () => mealForm.reset() })">
                    <input v-model="mealForm.name" required placeholder="Meal name" class="rounded border-gray-300">
                    <input v-model="mealForm.description" placeholder="Description" class="rounded border-gray-300">
                    <input v-model="mealForm.display_order" type="number" min="0" aria-label="Display order" class="w-20 rounded border-gray-300">
                    <button class="rounded bg-indigo-600 px-3 text-white">Add meal</button>
                    <ValidationSummary :errors="mealForm.errors" />
                </form>
                <form v-if="editingMeal" class="mt-3 flex flex-wrap gap-2 rounded border p-3" @submit.prevent="updateMeal">
                    <input v-model="editMealForm.name" required aria-label="Meal name" class="rounded border-gray-300">
                    <input v-model="editMealForm.description" aria-label="Meal description" class="rounded border-gray-300">
                    <input v-model="editMealForm.display_order" type="number" min="0" aria-label="Display order" class="w-20 rounded border-gray-300">
                    <button class="rounded bg-indigo-600 px-3 text-white">Save meal</button>
                    <button type="button" class="text-gray-600" @click="editingMeal = null">Cancel</button>
                    <InputError :message="editMealForm.errors.name || editMealForm.errors.description || editMealForm.errors.display_order" />
                </form>
                <div class="mt-3 divide-y">
                    <div v-for="meal in meals" :key="meal.id" class="flex items-center justify-between py-2">
                        <span>{{ meal.name }} <span class="text-sm text-gray-500">{{ meal.description }}</span></span>
                        <span class="flex items-center gap-3">
                            <button v-if="canManage" class="text-indigo-600" @click="startEditingMeal(meal)">Edit</button>
                            <button v-if="canManage" class="text-indigo-600" @click="router.patch(route('events.meals.active', [event.id, meal.id]), { is_active: !meal.is_active, from_setup: fromSetup })">{{ meal.is_active ? 'Deactivate' : 'Activate' }}</button>
                            <span v-else>{{ meal.is_active ? 'Active' : 'Inactive' }}</span>
                        </span>
                    </div>
                    <p v-if="!meals.length" class="py-2 text-gray-600">No meal options are configured.</p>
                </div>
            </section>

            <form class="flex flex-wrap gap-2" @submit.prevent="apply">
                <input v-model="filters.search" placeholder="Search party" class="rounded border-gray-300">
                <select v-model="filters.status" class="rounded border-gray-300">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="attending">Attending</option>
                    <option value="not_attending">Not attending</option>
                </select>
                <button class="rounded bg-gray-800 px-3 text-white">Search</button>
                <button type="button" class="text-gray-600" @click="reset">Reset</button>
            </form>

            <div class="overflow-x-auto bg-white shadow">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50"><tr><th class="p-3">Party</th><th class="p-3">Status</th><th class="p-3">Maximum size</th><th class="p-3">Attending</th><th class="p-3">Adults / Children</th><th class="p-3">Message</th><th class="p-3">Submitted / Updated</th><th class="p-3"></th></tr></thead>
                    <tbody>
                        <tr v-for="party in parties" :key="party.id" class="border-t">
                            <td class="p-3">{{ party.name }}</td>
                            <td class="p-3 capitalize">{{ party.rsvp?.status?.replaceAll('_', ' ') || 'pending' }}</td>
                            <td class="p-3">{{ party.maximum_party_size }}</td>
                            <td class="p-3">{{ attendingCount(party) }}</td>
                            <td class="p-3">{{ adultCount(party) }} / {{ childCount(party) }}</td>
                            <td class="p-3">{{ party.rsvp?.guest_message ? 'Yes' : 'No' }}</td>
                            <td class="p-3">
                                <template v-if="party.rsvp?.submitted_at">
                                    <div><span class="font-medium">Submitted:</span><br>{{ party.rsvp.submitted_at }}</div>
                                    <div v-if="party.rsvp.last_updated_at" class="mt-2 text-gray-600"><span class="font-medium">Updated:</span><br>{{ party.rsvp.last_updated_at }}</div>
                                </template>
                                <span v-else>Not submitted</span>
                            </td>
                            <td class="p-3"><Link :href="route('events.rsvps.show', [event.id, party.id])" class="text-indigo-600">View details</Link></td>
                        </tr>
                        <tr v-if="!parties.length"><td colspan="8" class="p-5 text-center text-gray-600">No RSVP responses match these filters.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :pagination="pagination" />
        </div>
    </AuthenticatedLayout>
</template>
