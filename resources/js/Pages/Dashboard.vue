<script setup lang="ts">
import DashboardChart from '@/Components/UI/DashboardChart.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import SidebarIcon from '@/Components/UI/SidebarIcon.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type KpiIcon = 'clients' | 'events' | 'live' | 'setup' | 'revenue' | 'collected' | 'outstanding' | 'rsvp';
type BreakdownItem = { label: string; count: number; percentage: number };
type Analytics = {
    kpis: Record<'clients' | 'events' | 'live' | 'setup' | 'revenue' | 'collected' | 'outstanding' | 'rsvp_rate', number>;
    revenue_trend: { labels: string[]; revenue: number[]; collected: number[] };
    event_status: BreakdownItem[];
    rsvp_breakdown: { response_rate: number; items: BreakdownItem[] };
    events_by_type: { label: string; count: number }[];
    upcoming: { id: number; title: string; client: string | null; template_name: string | null; date: string | null; type: string | null; capacity: number | null; status: string }[];
    attention: { id: number; title: string; message: string }[];
};

const props = defineProps<{ events: any[]; isAdmin: boolean; analytics?: Analytics }>();
const money = (value: number) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0);
const kpiCards = computed(() => {
    if (!props.analytics) return [];

    const { kpis } = props.analytics;
    return [
        { key: 'clients', label: 'Clients', value: kpis.clients, icon: 'clients' as KpiIcon },
        { key: 'events', label: 'Events', value: kpis.events, icon: 'events' as KpiIcon },
        { key: 'live', label: 'Live', value: kpis.live, icon: 'live' as KpiIcon },
        { key: 'setup', label: 'Setup', value: kpis.setup, icon: 'setup' as KpiIcon },
        { key: 'revenue', label: 'Revenue', value: money(kpis.revenue), icon: 'revenue' as KpiIcon },
        { key: 'collected', label: 'Collected', value: money(kpis.collected), detail: 'Confirmed payments only', icon: 'collected' as KpiIcon },
        { key: 'outstanding', label: 'Outstanding', value: money(kpis.outstanding), icon: 'outstanding' as KpiIcon },
        { key: 'rsvp_rate', label: 'RSVP Rate', value: `${kpis.rsvp_rate}%`, icon: 'rsvp' as KpiIcon },
    ];
});
const revenueDatasets = computed(() => props.analytics ? [
    { label: 'Revenue', data: props.analytics.revenue_trend.revenue, backgroundColor: '#2f6b4f', borderColor: '#2f6b4f' },
    { label: 'Collected', data: props.analytics.revenue_trend.collected, backgroundColor: '#d4967d', borderColor: '#d4967d' },
] : []);
const eventStatusColors = ['#2f6b4f', '#d4967d', '#817a72'];
const rsvpColors = ['#2f6b4f', '#a5403d', '#d4967d'];
const eventStatusDatasets = computed(() => props.analytics ? [{ label: 'Events', data: props.analytics.event_status.map((item) => item.count), backgroundColor: eventStatusColors }] : []);
const rsvpDatasets = computed(() => props.analytics ? [{ label: 'Invitation parties', data: props.analytics.rsvp_breakdown.items.map((item) => item.count), backgroundColor: rsvpColors }] : []);
const eventTypeDatasets = computed(() => props.analytics ? [{ label: 'Events', data: props.analytics.events_by_type.map((item) => item.count), backgroundColor: '#d4967d' }] : []);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Dashboard" subtitle="Overview of your FormalInvites business.">
                <template #actions><Link v-if="isAdmin" :href="route('admin.customers.index')" class="fe-btn fe-btn-primary">+ Add Client</Link></template>
            </PageHeader>
        </template>

        <div v-if="isAdmin && analytics" class="fe-page fe-page-wide">
            <section aria-label="Key performance indicators" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="card in kpiCards" :key="card.key" class="fe-card min-w-0 p-5">
                    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-[color:var(--fe-text-muted)]">{{ card.label }}</p><p class="mt-3 text-2xl font-semibold sm:text-3xl">{{ card.value }}</p></div><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#f9ece7] text-[color:var(--fe-accent)]" aria-hidden="true"><SidebarIcon :name="card.icon" /></span></div>
                    <p v-if="card.detail" class="mt-2 text-xs text-[color:var(--fe-text-muted)]">{{ card.detail }}</p>
                </article>
            </section>

            <section class="mt-6 grid items-start gap-6 xl:grid-cols-3">
                <article class="fe-card min-w-0 p-5 xl:col-span-2"><h2 class="fe-section-title">Revenue Analytics</h2><p class="fe-section-copy">Final obligations by financial-record month and confirmed transactions by transaction date, over the last 12 months.</p><div class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm text-[color:var(--fe-text-secondary)]"><span>Total revenue: <strong class="text-[color:var(--fe-text)]">{{ money(analytics.kpis.revenue) }}</strong></span><span>Collected: <strong class="text-[color:var(--fe-text)]">{{ money(analytics.kpis.collected) }}</strong></span></div><div class="mt-4"><DashboardChart type="line" :labels="analytics.revenue_trend.labels" :datasets="revenueDatasets" description="Revenue and collected money for the last twelve months" value-format="currency" /></div></article>
                <article class="fe-card min-w-0 p-5"><h2 class="fe-section-title">Event Status</h2><p class="fe-section-copy">A business-facing view of your event portfolio.</p><div class="mt-4"><DashboardChart type="doughnut" :labels="analytics.event_status.map((item) => item.label)" :datasets="eventStatusDatasets" description="Event status breakdown" /></div><ul class="mt-4 grid gap-2 text-sm"><li v-for="(item, index) in analytics.event_status" :key="item.label" class="flex items-center justify-between gap-2"><span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: eventStatusColors[index] }" />{{ item.label }}</span><strong>{{ item.count }} <span class="font-normal text-[color:var(--fe-text-muted)]">({{ item.percentage }}%)</span></strong></li></ul></article>
            </section>

            <section class="mt-6 grid items-start gap-6 lg:grid-cols-2">
                <article class="fe-card min-w-0 p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="fe-section-title">RSVP Analytics</h2><p class="fe-section-copy">Active invitation parties only; declines count as responses.</p></div><div class="rounded bg-[#f9ece7] px-3 py-2 text-right"><p class="text-xs font-bold uppercase tracking-wider text-[color:var(--fe-text-muted)]">Response rate</p><p class="text-2xl font-semibold text-[color:var(--fe-text)]">{{ analytics.rsvp_breakdown.response_rate }}%</p></div></div><div class="mt-4"><DashboardChart type="doughnut" :labels="analytics.rsvp_breakdown.items.map((item) => item.label)" :datasets="rsvpDatasets" description="RSVP response breakdown" /></div><ul class="mt-4 grid gap-2 text-sm"><li v-for="(item, index) in analytics.rsvp_breakdown.items" :key="item.label" class="flex items-center justify-between gap-2"><span class="flex items-center gap-2"><i class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: rsvpColors[index] }" />{{ item.label }}</span><strong>{{ item.count }} <span class="font-normal text-[color:var(--fe-text-muted)]">({{ item.percentage }}%)</span></strong></li></ul></article>
                <article class="fe-card min-w-0 p-5"><h2 class="fe-section-title">Events by Type</h2><p class="fe-section-copy">Only event types with recorded events are shown.</p><div class="mt-4"><DashboardChart type="bar" :labels="analytics.events_by_type.map((item) => item.label)" :datasets="eventTypeDatasets" description="Events grouped by type" /></div><p v-if="analytics.events_by_type.length" class="mt-4 text-sm text-[color:var(--fe-text-secondary)]">{{ analytics.events_by_type.map((item) => `${item.label}: ${item.count}`).join(' · ') }}</p></article>
            </section>

            <section class="mt-6 grid items-start gap-6 lg:grid-cols-2">
                <article class="fe-card min-w-0 p-5"><div class="flex items-center justify-between gap-3"><h2 class="fe-section-title">Upcoming Events</h2><Link :href="route('events.index')" class="text-sm underline">View events</Link></div><div v-if="analytics.upcoming.length" class="mt-4 space-y-3"><article v-for="event in analytics.upcoming" :key="event.id" class="rounded border border-[color:var(--fe-border)] p-3"><div class="flex flex-wrap justify-between gap-2"><div><p class="font-semibold">{{ event.title }}</p><p class="text-sm text-[color:var(--fe-text-secondary)]">{{ event.date }} · {{ event.client || 'Client not set' }}</p><p class="text-sm text-[color:var(--fe-text-muted)]">{{ event.type }} · {{ event.template_name || 'No template selected' }} · {{ event.capacity ?? '—' }} guests · {{ event.status }}</p></div><Link :href="route('events.show', event.id)" class="fe-btn fe-btn-secondary">Manage</Link></div></article></div><p v-else class="mt-4 text-sm text-[color:var(--fe-text-muted)]">No upcoming events.</p></article>
                <article class="fe-card min-w-0 p-5"><h2 class="fe-section-title">Needs Attention</h2><div v-if="analytics.attention.length" class="mt-4 space-y-3"><article v-for="item in analytics.attention" :key="item.id" class="rounded border border-[color:var(--fe-border)] p-3"><p class="font-semibold">{{ item.title }}</p><p class="text-sm text-[color:var(--fe-text-secondary)]">{{ item.message }}</p><Link :href="route('events.show', item.id)" class="mt-2 inline-block text-sm underline">Review event</Link></article></div><p v-else class="mt-4 text-sm text-[color:var(--fe-text-muted)]">Nothing needs attention right now.</p></article>
            </section>

            <nav aria-label="Quick actions" class="mt-6 flex flex-wrap gap-2"><Link :href="route('events.index')" class="fe-btn fe-btn-secondary">View Events</Link><Link :href="route('admin.payments.index')" class="fe-btn fe-btn-secondary">View Payments</Link></nav>
        </div>

        <div v-else class="fe-page fe-page-standard"><section class="fe-card p-5"><p class="fe-section-copy">Your accessible events.</p><div v-if="events.length" class="mt-4 space-y-3"><article v-for="event in events" :key="event.id" class="rounded border border-[color:var(--fe-border)] p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold">{{ event.title || 'Invitation setup' }}</p><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">{{ event.main_date || 'Date to be confirmed' }} · {{ event.invitation_status.replaceAll('_', ' ') }}<span v-if="event.live_version"> · Version {{ event.live_version }}</span><span v-if="event.unpublished_changes" class="ml-1 text-[color:var(--fe-warning)]">· Unpublished changes</span></p></div><div class="flex flex-wrap gap-2"><Link :href="route('events.show', event.id)" class="fe-btn fe-btn-secondary">Manage</Link><Link :href="route('events.setup', event.id)" class="fe-btn fe-btn-secondary">{{ event.live_version ? 'Edit Invitation' : 'Continue Setup' }}</Link><Link v-if="event.live_version" :href="route('events.invitation.live-preview', event.id)" class="fe-btn fe-btn-primary">View Invitation</Link></div></div></article></div><p v-else class="mt-4 text-sm text-[color:var(--fe-text-muted)]">No events yet.</p></section></div>
    </AuthenticatedLayout>
</template>
