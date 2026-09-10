<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import formalInvitesLogo from '@/assets/brand/formalinvites-logo.webp';

defineProps<{ canLogin?: boolean }>();
</script>

<template>
    <Head title="FormalEvites" />
    <main class="flex min-h-screen items-center justify-center bg-[color:var(--fe-page,#f7f4f1)] px-6 text-center">
        <section class="max-w-lg rounded-3xl border border-[color:var(--fe-border,#ded5ce)] bg-white p-8 shadow-sm sm:p-12">
            <img :src="formalInvitesLogo" alt="FormalEvites" class="mx-auto h-16 w-auto object-contain" />
            <p class="mt-8 text-xs font-bold uppercase tracking-[.24em] text-[color:var(--fe-text-muted,#6f665e)]">FormalEvites</p>
            <h1 class="mt-3 font-serif text-4xl text-[color:var(--fe-text,#2c2723)]">Beautiful invitations, thoughtfully managed.</h1>
            <p class="mt-4 text-[color:var(--fe-text-secondary,#645c55)]">Sign in to create, personalize, and manage your invitation.</p>
            <Link v-if="canLogin" :href="route('login')" class="fe-btn fe-btn-primary mt-8">Log in</Link>
        </section>
    </main>
</template>
