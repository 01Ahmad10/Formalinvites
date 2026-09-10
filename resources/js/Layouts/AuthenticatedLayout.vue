<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ToastNotifications from '@/Components/ToastNotifications.vue';
import SidebarIcon from '@/Components/UI/SidebarIcon.vue';
import formalInvitesLogo from '@/assets/brand/formalinvites-logo.webp';

const page = usePage<any>();
const mobileOpen = ref(false);
type SidebarIconName = 'dashboard' | 'events' | 'clients' | 'payments' | 'packages' | 'coupons' | 'templates' | 'families' | 'meals' | 'schedule' | 'rsvp';
type Item = { label: string; href: string; paths: string[]; icon: SidebarIconName; exact?: boolean };
const navigation = computed<Item[]>(() => {
    const items: Item[] = [{ label: 'Dashboard', href: route('dashboard'), paths: ['/dashboard'], icon: 'dashboard' }];
    if (page.props.auth.user.role === 'admin') items.push(
        { label: 'Events', href: route('events.index'), paths: ['/events'], icon: 'events' },
        { label: 'Clients', href: route('admin.customers.index'), paths: ['/admin/customers', '/admin/clients'], icon: 'clients' },
        { label: 'Payments', href: route('admin.payments.index'), paths: ['/admin/payments'], icon: 'payments' },
        { label: 'Packages', href: route('admin.packages.index'), paths: ['/admin/packages'], icon: 'packages' },
        { label: 'Coupons', href: route('admin.coupons.index'), paths: ['/admin/coupons'], icon: 'coupons' },
        { label: 'Templates', href: route('admin.templates.index'), paths: ['/admin/templates'], icon: 'templates' },
    );
    else {
        const events = page.props.auth.customerEvents || [];
        const event = currentInvitationScope.value;
        if (event) {
            items.push({ label: 'My Invitation', href: event.is_archived ? route('events.show', event.id) : (event.is_live ? route('events.builder', event.id) : route('events.setup', event.id)), paths: [`/events/${event.id}`, `/events/${event.id}/setup`, `/events/${event.id}/builder`], icon: 'events', exact: true });
            if (!event.is_archived) items.push(
                { label: 'Families & Guests', href: route('events.guests.index', event.id), paths: [`/events/${event.id}/guests`], icon: 'families' },
                { label: 'RSVP Responses', href: route('events.rsvps.index', event.id), paths: [`/events/${event.id}/rsvps`], icon: 'rsvp' },
                { label: 'Meal Options', href: route('events.meals.index', event.id), paths: [`/events/${event.id}/meals`], icon: 'meals' },
                { label: 'Schedule', href: route('events.activities.index', event.id), paths: [`/events/${event.id}/activities`], icon: 'schedule' },
            );
        } else items.push({ label: 'My Invitation', href: route('events.index'), paths: ['/events'], icon: 'events' });
    }
    return items;
});
const currentPath = computed(() => new URL(page.url, 'http://inertia.local').pathname.replace(/\/$/, '') || '/');
const active = (item: Item) => item.paths.some((path) => item.exact ? currentPath.value === path : currentPath.value === path || currentPath.value.startsWith(`${path}/`));
const accountLabel = computed(() => page.props.auth.user.role === 'admin' ? 'Administrator' : 'Account');
const customerEvents = computed(() => page.props.auth.customerEvents || []);
const currentEventId = computed(() => {
    const match = currentPath.value.match(/^\/events\/(\d+)(?:\/|$)/);
    return match ? Number(match[1]) : null;
});
const currentInvitationScope = computed(() => {
    const events = customerEvents.value;
    if (events.length === 1) return events[0];
    if (!currentEventId.value) return null;
    return events.find((event: { id: number | string }) => Number(event.id) === currentEventId.value) || null;
});
const currentInvitation = computed(() => {
    const events = customerEvents.value;
    const directId = currentEventId.value;
    if (events.length === 1) return events[0];
    if (!directId) return null;
    return events.find((event: { id: number | string }) => Number(event.id) === directId) || null;
});
const multipleInvitations = computed(() => page.props.auth.user.role === 'customer' && customerEvents.value.length > 1);
const currentInvitationTitle = computed(() => currentInvitation.value?.title || 'Select Invitation');
const openMobileMenu = () => { mobileOpen.value = true; };
const closeMobileMenu = () => { mobileOpen.value = false; };
const closeOnEscape = (event: KeyboardEvent) => { if (event.key === 'Escape') mobileOpen.value = false; };
watch(mobileOpen, (isOpen) => { document.body.style.overflow = isOpen ? 'hidden' : ''; });
onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => { document.removeEventListener('keydown', closeOnEscape); document.body.style.overflow = ''; });
</script>

<template>
    <div class="fe-app fe-shell">
        <ToastNotifications />
        <aside class="fe-sidebar" aria-label="Primary navigation">
            <div class="fe-sidebar-brand"><Link :href="route('dashboard')" aria-label="FormalInvites dashboard"><img :src="formalInvitesLogo" alt="FormalInvites — Sara Shayma" class="fe-brand-logo" /></Link></div>
            <template v-if="multipleInvitations && currentInvitation">
                <div class="mx-2 mb-4 rounded border border-[color:var(--fe-border)] bg-[color:var(--fe-surface-elevated)] p-3">
                    <p class="text-xs font-semibold tracking-[0.12em] text-[color:var(--fe-text-muted)]">Current Invitation</p>
                    <p class="mt-1 truncate text-sm font-medium">{{ currentInvitationTitle }}</p>
                    <Link :href="route('events.index')" class="mt-2 inline-flex text-xs font-semibold text-[color:var(--fe-primary)] underline">Switch Invitation</Link>
                </div>
            </template>
            <nav class="fe-sidebar-nav"><Link v-for="item in navigation" :key="item.label" :href="item.href" class="fe-sidebar-link" :class="{ 'fe-sidebar-link-active': active(item) }"><span class="fe-sidebar-icon" aria-hidden="true"><SidebarIcon :name="item.icon" /></span>{{ item.label }}</Link></nav>
            <div class="mt-auto border-t border-[color:var(--fe-border)] pt-4"><Dropdown align="left" placement="top" width="48" content-classes="py-1 bg-white border border-[color:var(--fe-border)]"><template #trigger><button type="button" class="fe-sidebar-account"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-[color:var(--fe-primary)] text-sm font-semibold text-white">{{ $page.props.auth.user.name.slice(0,1).toUpperCase() }}</span><span class="min-w-0 text-left"><span class="block truncate text-sm font-semibold">{{ $page.props.auth.user.name }}</span><span class="block text-xs text-[color:var(--fe-text-muted)]">{{ accountLabel }}</span></span></button></template><template #content><DropdownLink :href="route('profile.edit')">Profile</DropdownLink><DropdownLink :href="route('logout')" method="post" as="button">Log out</DropdownLink></template></Dropdown></div>
        </aside>
        <div class="fe-main">
            <header class="fe-mobile-bar lg:hidden"><img :src="formalInvitesLogo" alt="FormalInvites — Sara Shayma" class="fe-mobile-logo" /><button type="button" class="fe-mobile-toggle ms-auto" :aria-expanded="mobileOpen" aria-controls="mobile-navigation" @click="openMobileMenu"><span class="sr-only">Open navigation</span><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg></button></header>
            <header v-if="$slots.header" class="border-b border-[color:var(--fe-border)] bg-[color:var(--fe-surface)]"><div class="mx-auto max-w-[var(--fe-page-wide)] px-4 py-5 sm:px-6 lg:px-8"><slot name="header" /></div></header>
            <main><slot /></main>
        </div>
        <Transition enter-active-class="transition-transform duration-200 ease-out" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-150 ease-in" leave-from-class="translate-x-0" leave-to-class="translate-x-full"><aside v-if="mobileOpen" id="mobile-navigation" class="fe-mobile-menu lg:hidden" aria-label="Mobile navigation" aria-modal="true" role="dialog"><header class="fe-mobile-menu-header"><img :src="formalInvitesLogo" alt="FormalInvites — Sara Shayma" class="fe-mobile-logo" /><button type="button" class="fe-mobile-toggle ms-auto" @click="closeMobileMenu"><span class="sr-only">Close navigation</span><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button></header><nav class="fe-mobile-menu-nav"><Link v-for="item in navigation" :key="item.label" :href="item.href" class="fe-sidebar-link" :class="{ 'fe-sidebar-link-active': active(item) }" @click="closeMobileMenu"><span class="fe-sidebar-icon" aria-hidden="true"><SidebarIcon :name="item.icon" /></span>{{ item.label }}</Link></nav><div class="fe-mobile-menu-account"><div class="flex min-w-0 items-center gap-3 px-1"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[color:var(--fe-primary)] text-sm font-semibold text-white">{{ $page.props.auth.user.name.slice(0,1).toUpperCase() }}</span><span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ $page.props.auth.user.name }}</span><span class="block text-xs text-[color:var(--fe-text-muted)]">{{ accountLabel }}</span></span></div><div class="mt-3 grid gap-1"><Link :href="route('profile.edit')" class="fe-sidebar-link" @click="closeMobileMenu">Profile</Link><Link :href="route('logout')" method="post" as="button" class="fe-sidebar-link w-full" @click="closeMobileMenu">Log out</Link></div></div></aside></Transition>
    </div>
</template>
