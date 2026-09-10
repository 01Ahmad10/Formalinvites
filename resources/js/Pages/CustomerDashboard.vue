<script setup lang="ts">
import Badge from '@/Components/UI/Badge.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { invitationStatus, statusTone } from '@/Support/AdminPresentation';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

type Invitation = {
    id: number;
    title: string;
    template_name: string | null;
    status: 'setup' | 'live' | 'disabled' | 'archived';
    date: string | null;
    guest_capacity: number | null;
    allocated_capacity: number;
    families_invited: number;
    responded_families: number;
    confirmed_attendees: number;
    response_rate: number;
    invitation_url: string;
    manage_url: string;
    preview_url: string;
    view_url: string | null;
    guests_url: string;
    rsvps_url: string;
    meals_url: string;
    schedule_url: string;
    next_action: { title: string;
    template_name: string | null; description: string; label: string; url: string };
};

type Allowance = { allowed_events: number; used_events: number; remaining_events: number; can_create_event: boolean };
type Completion = { event_id: number; guests_url: string; view_url: string } | null;
const props = defineProps<{ invitations: Invitation[]; allowance: Allowance; completion: Completion }>();
const user = usePage().props.auth.user;
const singleInvitation = props.invitations.length === 1 ? props.invitations[0] : null;
const greeting = () => user.name.split(' ')[0] || 'there';
const startingInvitation = ref(false);
const startInvitation = () => {
    if (startingInvitation.value || !props.allowance.can_create_event) return;
    startingInvitation.value = true;
    router.get(route('events.start'), {}, { onFinish: () => { startingInvitation.value = false; } });
};
</script>

<template>
    <Head title="My Invitation" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="`Welcome, ${greeting()}`" eyebrow="My Invitation" :subtitle="singleInvitation ? 'Everything you need for your invitation, guests, and responses.' : 'Create and manage invitations for your celebrations.'"><template #actions><button v-if="invitations.length && allowance.can_create_event" type="button" :disabled="startingInvitation" class="fe-btn fe-btn-primary" @click="startInvitation">{{ startingInvitation ? 'Opening...' : 'Create Invitation' }}</button></template></PageHeader>
        </template>

        <main class="fe-page fe-page-wide space-y-6">
            <section class="fe-card fe-card-body flex flex-wrap items-center justify-between gap-4" aria-label="Invitation allowance"><div><p class="fe-page-eyebrow">Invitations</p><p class="mt-1 font-semibold">{{ allowance.used_events }} of {{ allowance.allowed_events }} used</p><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">{{ allowance.remaining_events }} {{ allowance.remaining_events === 1 ? 'invitation' : 'invitations' }} remaining</p></div><p v-if="!allowance.can_create_event" class="max-w-md text-sm text-[color:var(--fe-text-secondary)]">You have used all available invitations. Contact FormalEvites to add another invitation.</p></section>

            <section v-if="completion" class="fe-card border-[color:var(--fe-success)] p-6"><p class="fe-page-eyebrow">Invitation complete</p><h2 class="fe-section-title mt-2">Your invitation is live.</h2><p class="fe-section-copy mt-2">Your design and event details are ready. Next, add families and guests so each family can receive their personal invitation link.</p><div class="mt-5 flex flex-wrap gap-3"><Link :href="completion.guests_url" class="fe-btn fe-btn-primary">Add Families &amp; Guests</Link><Link :href="completion.view_url" class="fe-btn fe-btn-secondary">View Invitation</Link></div></section>

            <EmptyState v-if="!invitations.length" title="You haven't created your invitation yet." message="Start by choosing your event type and design."><template #action><button v-if="allowance.can_create_event" type="button" :disabled="startingInvitation" class="fe-btn fe-btn-primary" @click="startInvitation">{{ startingInvitation ? 'Opening...' : 'Create Invitation' }}</button></template></EmptyState>

            <template v-else-if="singleInvitation">
                <Card>
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3"><p class="fe-page-eyebrow">Your invitation</p><Badge :tone="statusTone(singleInvitation.status)">{{ invitationStatus(singleInvitation.status) }}</Badge></div>
                            <h2 class="fe-display-heading mt-2 text-3xl sm:text-4xl">{{ singleInvitation.title }}</h2>
                            <p class="mt-3 text-sm text-[color:var(--fe-text-secondary)]">Template: {{ singleInvitation.template_name || 'Not selected' }} · {{ singleInvitation.date || 'Date to be confirmed' }}</p>
                        </div>
                        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row lg:shrink-0"><Link :href="singleInvitation.status === 'archived' ? singleInvitation.manage_url : singleInvitation.invitation_url" class="fe-btn fe-btn-primary justify-center">{{ singleInvitation.status === 'setup' ? 'Continue Invitation' : singleInvitation.status === 'archived' ? 'View Invitation' : 'Edit Invitation' }}</Link><Link v-if="singleInvitation.status !== 'archived'" :href="singleInvitation.view_url || singleInvitation.preview_url" class="fe-btn fe-btn-secondary justify-center">{{ singleInvitation.view_url ? 'Preview Invitation' : 'Preview Invitation' }}</Link></div>
                    </div>
                </Card>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Invitation progress">
                    <Card><p class="text-sm text-[color:var(--fe-text-muted)]">Guest Capacity</p><p class="mt-2 text-2xl font-semibold">{{ singleInvitation.guest_capacity ?? '—' }}</p></Card>
                    <Card><p class="text-sm text-[color:var(--fe-text-muted)]">Allocated Capacity</p><p class="mt-2 text-2xl font-semibold">{{ singleInvitation.allocated_capacity }}</p></Card>
                    <Card><p class="text-sm text-[color:var(--fe-text-muted)]">Confirmed Attendees</p><p class="mt-2 text-2xl font-semibold">{{ singleInvitation.confirmed_attendees }}</p></Card>
                    <Card><p class="text-sm text-[color:var(--fe-text-muted)]">RSVP Progress</p><p class="mt-2 text-2xl font-semibold">{{ singleInvitation.response_rate }}%</p><p class="mt-1 text-xs text-[color:var(--fe-text-muted)]">{{ singleInvitation.responded_families }} of {{ singleInvitation.families_invited }} families responded</p></Card>
                </section>

                <section class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,.8fr)]">
                    <Card><p class="fe-page-eyebrow">Next action</p><h2 class="fe-section-title mt-2">{{ singleInvitation.next_action.title }}</h2><p class="fe-section-copy mt-2">{{ singleInvitation.next_action.description }}</p><Link :href="singleInvitation.next_action.url" class="fe-btn fe-btn-primary mt-5">{{ singleInvitation.next_action.label }}</Link></Card>
                    <Card muted><p class="fe-page-eyebrow">Invitation status</p><p class="fe-section-title mt-2">{{ invitationStatus(singleInvitation.status) }}</p><p class="fe-section-copy mt-2">{{ singleInvitation.status === 'setup' ? 'Continue the three-step invitation journey when you are ready.' : singleInvitation.status === 'live' ? 'Your invitation is ready to share and guest responses are being tracked.' : singleInvitation.status === 'disabled' ? 'This invitation is temporarily unavailable to guests. Contact FormalEvites if this was unexpected.' : 'This invitation is archived and cannot be changed.' }}</p></Card>
                </section>

                <section v-if="singleInvitation.status !== 'archived'" aria-label="Invitation quick links">
                    <h2 class="fe-section-title">Quick links</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <Link :href="singleInvitation.guests_url" class="fe-card fe-card-body transition hover:border-[color:var(--fe-accent)]"><p class="font-semibold">Families &amp; Guests</p><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">{{ singleInvitation.families_invited }} families invited</p></Link>
                        <Link :href="singleInvitation.rsvps_url" class="fe-card fe-card-body transition hover:border-[color:var(--fe-accent)]"><p class="font-semibold">RSVP Responses</p><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">{{ singleInvitation.responded_families }} families responded</p></Link>
                        <Link :href="singleInvitation.meals_url" class="fe-card fe-card-body transition hover:border-[color:var(--fe-accent)]"><p class="font-semibold">Meal Options</p><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">Manage guest meal choices</p></Link>
                        <Link :href="singleInvitation.schedule_url" class="fe-card fe-card-body transition hover:border-[color:var(--fe-accent)]"><p class="font-semibold">Schedule</p><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">Plan the flow of your day</p></Link>
                    </div>
                </section>
            </template>

            <section v-else>
                <p class="fe-section-copy">You have more than one invitation. Choose one to manage its details, guests, and responses.</p>
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <Card v-for="invitation in invitations" :key="invitation.id"><div class="flex flex-wrap items-start justify-between gap-3"><div><div class="flex items-center gap-2"><p class="fe-section-title">{{ invitation.title }}</p><Badge :tone="statusTone(invitation.status)">{{ invitationStatus(invitation.status) }}</Badge></div><p class="mt-2 text-sm text-[color:var(--fe-text-secondary)]">Template: {{ invitation.template_name || 'Not selected' }} · {{ invitation.date || 'Date to be confirmed' }}</p></div><Link :href="invitation.manage_url" class="fe-btn fe-btn-secondary">Open Invitation</Link></div><dl class="mt-5 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-[color:var(--fe-text-muted)]">Guest Capacity</dt><dd class="mt-1 font-semibold">{{ invitation.guest_capacity ?? '—' }}</dd></div><div><dt class="text-[color:var(--fe-text-muted)]">RSVP Progress</dt><dd class="mt-1 font-semibold">{{ invitation.response_rate }}%</dd></div></dl></Card>
                </div>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
