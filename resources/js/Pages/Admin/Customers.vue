<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import ValidationSummary from '@/Components/ValidationSummary.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{ customers: any[]; packages: any[]; filters: any }>();
const filters = reactive({ ...props.filters });
const showCreate = ref(false);
const showSecondLogin = ref(false);
const form = useForm({ name: '', phone: '', guest_capacity: '', primary_name: '', primary_email: '', primary_password: '', secondary_name: '', secondary_email: '', secondary_password: '' });
const packagePreview = computed(() => props.packages.filter((item: any) => Number(form.guest_capacity) >= item.minimum_guests && Number(form.guest_capacity) <= item.maximum_guests));
const apply = () => router.get(route('admin.customers.index'), filters, { preserveState: true, replace: true });
const reset = () => { filters.search = ''; apply(); };
const closeCreate = () => { showCreate.value = false; form.clearErrors(); };
const removeSecondLogin = () => { showSecondLogin.value = false; form.secondary_name = ''; form.secondary_email = ''; form.secondary_password = ''; };
const submit = () => form.post(route('admin.clients.store'), { onSuccess: () => { form.reset(); removeSecondLogin(); showCreate.value = false; } });
const statusTone = (status?: string) => status?.includes('live') || status?.includes('paid') || status === 'active' ? 'success' : status?.includes('incomplete') || status?.includes('unpaid') ? 'warning' : 'neutral';
</script>

<template>
    <Head title="Clients" />
    <AuthenticatedLayout>
        <template #header><PageHeader title="Clients" subtitle="Manage customer accounts and invitation access."><template #actions><Button @click="showCreate = true">+ Add Client</Button></template></PageHeader></template>

        <div class="fe-page fe-page-wide space-y-5">
            <form class="fe-card fe-card-muted flex flex-wrap items-center gap-2 p-3" @submit.prevent="apply"><input v-model="filters.search" placeholder="Search name, email, phone" class="w-full min-w-0 sm:w-auto sm:min-w-56"><Button type="submit">Search</Button><Button type="button" variant="ghost" @click="reset">Reset</Button></form>
            <div v-if="customers.length" class="fe-table-wrap"><table class="fe-table"><thead><tr><th>Client</th><th>Primary login</th><th>Second login</th><th>Event</th><th>Guest capacity</th><th>Invitation status</th><th>Payment status</th><th class="text-right"><span class="sr-only">Actions</span></th></tr></thead><tbody><tr v-for="customer in customers" :key="customer.id"><td class="font-semibold">{{ customer.name }}</td><td><template v-if="customer.primary_login"><span class="block">{{ customer.primary_login.name }}</span><span class="text-xs text-[color:var(--fe-text-muted)]">{{ customer.primary_login.email }}</span></template><span v-else>—</span></td><td><template v-if="customer.second_login"><span class="block">{{ customer.second_login.name }}</span><span class="text-xs text-[color:var(--fe-text-muted)]">{{ customer.second_login.email }}</span></template><span v-else>—</span></td><template v-if="customer.event"><td>{{ customer.event.title || 'Invitation setup not completed' }}</td><td>{{ customer.event.guest_capacity ? `${customer.event.guest_capacity} guests` : '—' }}</td><td><Badge :tone="statusTone(customer.event.invitation_status)">{{ customer.event.invitation_status }}</Badge></td><td><Badge :tone="statusTone(customer.event.payment_status)">{{ customer.event.payment_status }}</Badge></td></template><template v-else><td colspan="4">{{ customer.event_count ? `${customer.event_count} Events` : 'No Events' }}</td></template><td class="text-right"><Link :href="route('admin.customers.show', customer.id)" class="font-semibold text-[color:var(--fe-primary)] underline decoration-[color:var(--fe-accent)] underline-offset-4">View Client</Link></td></tr></tbody></table></div>
            <EmptyState v-else title="No clients match your search." message="Try a different name, email address, or phone number." />
        </div>

        <Modal :show="showCreate" max-width="2xl" @close="closeCreate"><form class="space-y-5 p-5 sm:p-6" @submit.prevent="submit"><div class="flex flex-wrap items-start justify-between gap-4"><div class="min-w-0"><h3 class="text-lg font-semibold text-[color:var(--fe-text)]">Add Client</h3><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">Create the client login and invitation setup shell. The client completes invitation details later.</p></div><button type="button" class="rounded p-1 text-xl text-[color:var(--fe-text-muted)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--fe-accent)]" aria-label="Close" @click="closeCreate">×</button></div><section class="space-y-3"><h4 class="fe-section-title">Client</h4><div class="grid gap-3 sm:grid-cols-2"><label>Client name<input v-model="form.name" required class="mt-1 w-full" /></label><label>Phone <span class="fe-form-optional">(optional)</span><input v-model="form.phone" class="mt-1 w-full" /></label><label>Guest capacity<input v-model="form.guest_capacity" type="number" min="1" required class="mt-1 w-full" /><span v-if="packagePreview.length === 1" class="mt-1 block text-sm text-[color:var(--fe-text-secondary)]">Package: {{ packagePreview[0].name }} · Price: {{ packagePreview[0].price ?? 'not set' }}</span><span v-else-if="form.guest_capacity" class="mt-1 block text-sm text-[color:var(--fe-warning)]">{{ packagePreview.length ? 'Package configuration is ambiguous.' : 'No active package covers this capacity.' }}</span></label></div></section><section class="space-y-3 border-t border-[color:var(--fe-border)] pt-4"><h4 class="fe-section-title">Primary login <span class="text-sm font-medium text-[color:var(--fe-error)]">required</span></h4><div class="grid gap-3 sm:grid-cols-3"><label>Name<input v-model="form.primary_name" required class="mt-1 w-full" /></label><label>Login email<input v-model="form.primary_email" type="email" required class="mt-1 w-full" /></label><label>Temporary password<input v-model="form.primary_password" type="password" required class="mt-1 w-full" /></label></div></section><section class="border-t border-[color:var(--fe-border)] pt-4"><Button v-if="!showSecondLogin" type="button" variant="ghost" @click="showSecondLogin = true">+ Add second login</Button><template v-else><div class="flex flex-wrap items-center justify-between gap-2"><h4 class="fe-section-title">Second login <span class="fe-form-optional">(optional)</span></h4><Button type="button" variant="ghost" @click="removeSecondLogin">Remove</Button></div><div class="mt-3 grid gap-3 sm:grid-cols-3"><label>Name<input v-model="form.secondary_name" class="mt-1 w-full" /></label><label>Login email<input v-model="form.secondary_email" type="email" class="mt-1 w-full" /></label><label>Temporary password<input v-model="form.secondary_password" type="password" class="mt-1 w-full" /></label></div></template></section><ValidationSummary :errors="form.errors" /><div class="flex flex-wrap justify-end gap-3 border-t border-[color:var(--fe-border)] pt-4"><Button type="button" variant="secondary" @click="closeCreate">Cancel</Button><Button type="submit" :loading="form.processing">Create Client</Button></div></form></Modal>
    </AuthenticatedLayout>
</template>
