<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ValidationSummary from '@/Components/ValidationSummary.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { invitationStatus, money, paymentStatus, statusTone } from '@/Support/AdminPresentation';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{ customer: any; customerUsers: any[]; events: any[]; canAddSecondLogin: boolean }>();
const second = useForm({ name: '', email: '', password: '' });
const reset = useForm({ password: '' });
const resetPassword = (user: any) => { if (window.confirm(`Reset password for ${user.name}?`)) reset.put(route('admin.customer-users.password.update', user.id)); };
const eventPaymentStatus = (event: any) => !event.finance?.has_record ? 'unpaid' : event.finance.remaining_amount > 0 ? (event.finance.paid_amount > 0 ? 'partially_paid' : 'unpaid') : 'paid';
</script>

<template>
    <Head :title="customer.name" />
    <AuthenticatedLayout>
        <template #header><PageHeader :title="customer.name" subtitle="Client account, invitation access, and financial overview."><template #actions><Link :href="route('admin.customers.index')" class="fe-btn fe-btn-secondary">Back to Clients</Link></template></PageHeader></template>

        <div class="fe-page fe-page-standard space-y-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-1"><h2 class="fe-section-title">Client Information</h2><dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-[color:var(--fe-text-muted)]">Primary contact</dt><dd class="mt-1 font-medium">{{ customer.contact_name || 'Not provided' }}</dd></div><div><dt class="text-[color:var(--fe-text-muted)]">Email</dt><dd class="mt-1 break-words">{{ customer.email || 'No email' }}</dd></div><div><dt class="text-[color:var(--fe-text-muted)]">Phone</dt><dd class="mt-1">{{ customer.phone || 'No phone' }}</dd></div></dl></Card>
                <Card class="lg:col-span-2"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="fe-section-title">Accounts</h2><p class="fe-section-copy">Primary and optional secondary invitation access.</p></div><Badge tone="neutral">{{ customerUsers.length }} of 2 accounts</Badge></div><div v-if="customerUsers.length" class="mt-4 divide-y divide-[color:var(--fe-border)]"><div v-for="user in customerUsers" :key="user.id" class="flex flex-wrap items-end justify-between gap-3 py-4 first:pt-0"><div class="min-w-0"><p class="font-semibold">{{ user.name }} <span class="font-normal text-[color:var(--fe-text-muted)]">· {{ user.customer_account_role === 'primary' ? 'Primary' : 'Secondary' }}</span></p><p class="break-words text-sm text-[color:var(--fe-text-secondary)]">{{ user.email }}</p></div><form class="flex flex-wrap items-end gap-2" @submit.prevent="resetPassword(user)"><label class="text-sm">New temporary password<input v-model="reset.password" type="password" required class="mt-1 w-full sm:w-56" /></label><Button type="submit" variant="secondary">Reset password</Button></form></div></div><EmptyState v-else class="mt-4" title="No client accounts yet." />
                    <form v-if="canAddSecondLogin" class="mt-5 grid gap-3 border-t border-[color:var(--fe-border)] pt-5 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="second.post(route('admin.customers.second-login.store', customer.id))"><label>Name<input v-model="second.name" required class="mt-1 w-full" /></label><label>Email<input v-model="second.email" type="email" required class="mt-1 w-full" /></label><label>Temporary password<input v-model="second.password" type="password" required class="mt-1 w-full" /></label><div class="flex items-end"><Button type="submit" :loading="second.processing">Add secondary login</Button></div><div class="sm:col-span-2 lg:col-span-4"><ValidationSummary :errors="second.errors" /></div></form><p v-else class="mt-5 border-t border-[color:var(--fe-border)] pt-5 text-sm text-[color:var(--fe-text-secondary)]">This client already has the maximum two login accounts.</p></Card>
            </div>

            <Card><div><h2 class="fe-section-title">Event &amp; Invitation</h2><p class="fe-section-copy">Guest capacity and finance for this client’s events.</p></div><div v-if="events.length" class="mt-4 divide-y divide-[color:var(--fe-border)]"><article v-for="event in events" :key="event.id" class="py-4 first:pt-0"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0"><h3 class="font-semibold">{{ event.title || 'Invitation setup' }}</h3><div class="mt-2 flex flex-wrap gap-2"><Badge :tone="statusTone(event.invitation_status)">{{ invitationStatus(event.invitation_status) }}</Badge><Badge :tone="statusTone(eventPaymentStatus(event))">{{ paymentStatus(eventPaymentStatus(event)) }}</Badge></div></div><Link :href="route('events.show', event.id)" class="fe-btn fe-btn-secondary">Manage</Link></div><dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4"><div><dt class="text-[color:var(--fe-text-muted)]">Guest capacity</dt><dd class="mt-1 font-medium">{{ event.guest_capacity ? `${event.guest_capacity} guests` : 'Not assigned' }}</dd></div><div><dt class="text-[color:var(--fe-text-muted)]">Package</dt><dd class="mt-1">{{ event.package?.name || 'Not assigned' }}</dd></div><div><dt class="text-[color:var(--fe-text-muted)]">Final amount</dt><dd class="mt-1">{{ event.finance?.has_record ? money(event.finance.final_amount) : 'No record' }}</dd></div><div><dt class="text-[color:var(--fe-text-muted)]">Remaining</dt><dd class="mt-1">{{ event.finance?.has_record ? money(event.finance.remaining_amount) : '—' }}</dd></div></dl></article></div><EmptyState v-else class="mt-4" title="No events yet." message="The client can complete invitation details after setup begins." /></Card>
        </div>
    </AuthenticatedLayout>
</template>
