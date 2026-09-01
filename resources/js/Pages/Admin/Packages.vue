<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import InputError from '@/Components/InputError.vue';
import ValidationSummary from '@/Components/ValidationSummary.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { money } from '@/Support/AdminPresentation';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps<{ packages: any[]; filters: any }>();
const filters = reactive({ ...props.filters });
const form = useForm({ name: '', minimum_guests: 1, maximum_guests: 50, price: '', is_active: true });
const pendingPackage = ref<any>(null);
const apply = () => router.get(route('admin.packages.index'), filters, { preserveState: true, replace: true });
const reset = () => { filters.search = ''; filters.active = ''; apply(); };
const updatePackage = (item: any) => router.put(route('admin.packages.update', item.id), { ...item, is_active: !item.is_active }, { onFinish: () => { pendingPackage.value = null; } });
const toggle = (item: any) => item.is_active ? updatePackage(item) : (pendingPackage.value = item);
</script>

<template>
    <Head title="Packages" />
    <AuthenticatedLayout>
        <template #header><PageHeader title="Packages" subtitle="Manage pricing tiers and guest-capacity packages." /></template>
        <div class="fe-page fe-page-standard space-y-6">
            <Card><form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="form.post(route('admin.packages.store'))"><div class="sm:col-span-2 lg:col-span-5"><h2 class="fe-section-title">Add Package</h2><p class="fe-section-copy">Set a guest range and price for new client invitations.</p></div><label>Name<input v-model="form.name" required class="mt-1 w-full" /><InputError :message="form.errors.name" class="mt-1" /></label><label>Minimum guests<input v-model="form.minimum_guests" type="number" min="1" class="mt-1 w-full" /><InputError :message="form.errors.minimum_guests" class="mt-1" /></label><label>Maximum guests<input v-model="form.maximum_guests" type="number" min="1" class="mt-1 w-full" /><InputError :message="form.errors.maximum_guests" class="mt-1" /></label><label>Price<input v-model="form.price" type="number" min="0" step="0.01" class="mt-1 w-full" /><InputError :message="form.errors.price" class="mt-1" /></label><div class="flex items-end"><Button type="submit" :loading="form.processing">Add Package</Button></div><div class="sm:col-span-2 lg:col-span-5"><ValidationSummary :errors="form.errors" /></div></form></Card>
            <form class="fe-card fe-card-muted flex flex-wrap gap-2 p-3" @submit.prevent="apply"><input v-model="filters.search" placeholder="Search packages" class="w-full min-w-0 sm:w-auto sm:min-w-56" /><select v-model="filters.active" class="w-full min-w-0 sm:w-auto"><option value="">All package states</option><option value="active">Active</option><option value="inactive">Inactive</option></select><Button type="submit">Apply</Button><Button type="button" variant="ghost" @click="reset">Reset</Button></form>
            <div v-if="packages.length" class="fe-table-wrap"><table class="fe-table min-w-[42rem]"><thead><tr><th>Package</th><th>Guest range</th><th class="text-right">Price</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody><tr v-for="packageItem in packages" :key="packageItem.id"><td class="font-semibold">{{ packageItem.name }}</td><td>{{ packageItem.minimum_guests }}–{{ packageItem.maximum_guests }} guests</td><td class="text-right">{{ packageItem.price === null ? 'Not set' : money(packageItem.price) }}</td><td><Badge :tone="packageItem.is_active ? 'success' : 'neutral'">{{ packageItem.is_active ? 'Active' : 'Inactive' }}</Badge></td><td class="text-right"><Button type="button" variant="ghost" @click="toggle(packageItem)">{{ packageItem.is_active ? 'Deactivate' : 'Activate' }}</Button></td></tr></tbody></table></div><EmptyState v-else title="No packages match these filters." message="Add a package before creating a client invitation." />
        </div>
        <ConfirmationModal :show="!!pendingPackage" title="Activate package?" message="This package will be available for new client invitations." confirm-label="Activate package" @close="pendingPackage = null" @confirm="updatePackage(pendingPackage)" />
    </AuthenticatedLayout>
</template>
