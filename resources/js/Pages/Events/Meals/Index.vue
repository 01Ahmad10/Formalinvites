<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Meal = { id: number; name: string; description: string | null; display_order: number; is_active: boolean };
const props = defineProps<{ event: { id: number; title: string }; meals: Meal[]; canManage: boolean; fromSetup: boolean }>();
const form = useForm({ name: '', description: '', display_order: 0, from_setup: props.fromSetup });
const editing = ref<Meal | null>(null);
const editForm = useForm({ name: '', description: '', display_order: 0, from_setup: props.fromSetup });
const startEdit = (meal: Meal) => { editing.value = meal; editForm.name = meal.name; editForm.description = meal.description || ''; editForm.display_order = meal.display_order; editForm.clearErrors(); };
const saveEdit = () => editing.value && editForm.put(route('events.meals.update', [props.event.id, editing.value.id]), { onSuccess: () => editing.value = null });
const setActive = (meal: Meal) => router.patch(route('events.meals.active', [props.event.id, meal.id]), { is_active: !meal.is_active, from_setup: props.fromSetup });
</script>

<template>
    <Head :title="`${event.title} Meal Options`" />
    <AuthenticatedLayout>
        <template #header><div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-semibold">Meal Options</h2><p class="text-sm text-gray-600">Choose the meal choices guests can select.</p></div><Link :href="fromSetup ? route('events.setup', { event: event.id, step: 3 }) : route('events.rsvps.index', event.id)" class="rounded border px-3 py-2 text-sm">{{ fromSetup ? 'Back to Invitation Setup' : 'Back to RSVPs' }}</Link></div></template>
        <div class="mx-auto max-w-4xl space-y-5 p-6">
            <form v-if="canManage" class="flex flex-wrap items-start gap-3 rounded bg-white p-5 shadow" @submit.prevent="form.post(route('events.meals.store', event.id), { onSuccess: () => form.reset('name', 'description', 'display_order') })"><label class="flex-1">Meal name<input v-model="form.name" required class="mt-1 block w-full rounded border-gray-300" /><InputError :message="form.errors.name" /></label><label class="flex-1">Description <span class="text-gray-500">(optional)</span><input v-model="form.description" class="mt-1 block w-full rounded border-gray-300" /><InputError :message="form.errors.description" /></label><label class="w-24">Order<input v-model="form.display_order" type="number" min="0" class="mt-1 block w-full rounded border-gray-300" /></label><button class="mt-6 rounded bg-indigo-600 px-4 py-2 text-white">Add Meal Option</button></form>
            <section class="divide-y rounded bg-white shadow"><div v-for="meal in meals" :key="meal.id" class="flex flex-wrap items-center justify-between gap-3 p-5"><div><h3 class="font-medium">{{ meal.name }}</h3><p v-if="meal.description" class="text-sm text-gray-600">{{ meal.description }}</p><p class="mt-1 text-xs text-gray-500">{{ meal.is_active ? 'Active' : 'Inactive' }}</p></div><div v-if="canManage" class="flex gap-3 text-sm"><button class="text-indigo-600" @click="startEdit(meal)">Edit</button><button class="text-indigo-600" @click="setActive(meal)">{{ meal.is_active ? 'Deactivate' : 'Reactivate' }}</button></div></div><p v-if="!meals.length" class="p-5 text-gray-600">No meal options have been added.</p></section>
            <form v-if="editing" class="grid gap-3 rounded bg-white p-5 shadow sm:grid-cols-2" @submit.prevent="saveEdit"><label>Meal name<input v-model="editForm.name" required class="mt-1 block w-full rounded border-gray-300" /><InputError :message="editForm.errors.name" /></label><label>Description<input v-model="editForm.description" class="mt-1 block w-full rounded border-gray-300" /></label><label>Order<input v-model="editForm.display_order" type="number" min="0" class="mt-1 block w-full rounded border-gray-300" /></label><div class="flex items-end gap-3"><button class="rounded bg-indigo-600 px-4 py-2 text-white">Save Meal Option</button><button type="button" class="text-gray-600" @click="editing = null">Cancel</button></div></form>
        </div>
    </AuthenticatedLayout>
</template>
