<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { date, invitationStatus, statusTone } from '@/Support/AdminPresentation';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps<{ events: any[]; pagination?: any; filters: any; types: string[]; statuses: string[]; customers: any[]; isAdmin: boolean }>();
const filters = reactive({ ...props.filters });
const apply = () => router.get(route('events.index'), filters, { preserveState: true, replace: true });
const reset = () => { filters.search = ''; filters.event_type = ''; filters.status = ''; filters.customer_id = ''; apply(); };
const typeLabel = (type?: string) => type ? type.replaceAll('_', ' ') : 'Not set';
</script>

<template>
    <Head title="Events" />
    <AuthenticatedLayout>
        <template #header><PageHeader title="Events" subtitle="Manage client invitations, event details, and readiness."><template #actions><Link v-if="isAdmin" :href="route('admin.customers.index')" class="fe-btn fe-btn-primary">+ Add Client</Link></template></PageHeader></template>
        <div class="fe-page fe-page-wide space-y-5">
            <form class="fe-card fe-card-muted flex flex-wrap gap-2 p-3" @submit.prevent="apply"><input v-model="filters.search" placeholder="Search events" class="w-full min-w-0 sm:w-auto sm:min-w-56" /><select v-model="filters.event_type" class="w-full min-w-0 sm:w-auto"><option value="">All types</option><option v-for="type in types" :key="type" :value="type">{{ typeLabel(type) }}</option></select><select v-if="isAdmin" v-model="filters.status" class="w-full min-w-0 sm:w-auto"><option value="">All invitation states</option><option v-for="item in statuses" :key="item" :value="item">{{ invitationStatus(item) }}</option></select><select v-if="isAdmin" v-model="filters.customer_id" class="w-full min-w-0 sm:w-auto"><option value="">All clients</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select><Button type="submit">Apply</Button><Button type="button" variant="ghost" @click="reset">Reset</Button></form>
            <div v-if="events.length" class="fe-table-wrap"><table class="fe-table min-w-[52rem]"><thead><tr><th>Event</th><th>Client</th><th>Type</th><th>Event date</th><th>Guest capacity</th><th>Invitation</th><th class="text-right">Action</th></tr></thead><tbody><tr v-for="event in events" :key="event.id"><td class="font-semibold">{{ event.title || 'Invitation setup' }}</td><td>{{ event.customer?.name || 'Not assigned' }}</td><td class="capitalize">{{ typeLabel(event.event_type) }}</td><td>{{ event.main_date ? date(event.main_date) : 'Not set' }}</td><td>{{ event.guest_capacity ? `${event.guest_capacity} guests` : 'Not assigned' }}</td><td><Badge :tone="statusTone(event.invitation_status)">{{ invitationStatus(event.invitation_status) }}</Badge></td><td class="text-right"><Link :href="route('events.show', event.id)" class="fe-btn fe-btn-secondary">Manage</Link></td></tr></tbody></table></div><EmptyState v-else title="No events match these filters." message="Try a different client, type, or invitation state." />
            <Pagination :pagination="pagination" />
        </div>
    </AuthenticatedLayout>
</template>
