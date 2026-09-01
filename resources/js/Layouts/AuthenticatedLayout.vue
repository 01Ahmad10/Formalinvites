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
type SidebarIconName = 'dashboard' | 'events' | 'clients' | 'payments' | 'packages' | 'coupons' | 'templates';
type Item = { label: string; href: string; paths: string[]; icon: SidebarIconName };
const navigation = computed<Item[]>(() => {
    const items: Item[] = [{ label: 'Dashboard', href: route('dashboard'), paths: ['/dashboard'], icon: 'dashboard' }, { label: 'Events', href: route('events.index'), paths: ['/events'], icon: 'events' }];
    if (page.props.auth.user.role === 'admin') items.splice(1, 0,
        { label: 'Clients', href: route('admin.customers.index'), paths: ['/admin/customers', '/admin/clients'], icon: 'clients' },
        { label: 'Payments', href: route('admin.payments.index'), paths: ['/admin/payments'], icon: 'payments' },
        { label: 'Packages', href: route('admin.packages.index'), paths: ['/admin/packages'], icon: 'packages' },
        { label: 'Coupons', href: route('admin.coupons.index'), paths: ['/admin/coupons'], icon: 'coupons' },
        { label: 'Templates', href: route('admin.templates.index'), paths: ['/admin/templates'], icon: 'templates' },
    );
    return items;
});
const currentPath = computed(() => new URL(page.url, 'http://inertia.local').pathname.replace(/\/$/, '') || '/');
const active = (item: Item) => item.paths.some((path) => currentPath.value === path || currentPath.value.startsWith(`${path}/`));
const accountLabel = computed(() => page.props.auth.user.role === 'admin' ? 'Administrator' : 'Account');
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
