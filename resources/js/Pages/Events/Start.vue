<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ entitlements: Array<{ id: number; exact_guest_capacity: number }> }>();
const selectedId = ref(props.entitlements.length === 1 ? props.entitlements[0].id : null);
const starting = ref(false);
const start = () => {
    if (!selectedId.value || starting.value) return;
    starting.value = true;
    router.post(route('events.store'), { start_setup: true, entitlement_id: selectedId.value }, { onFinish: () => { starting.value = false; } });
};
</script>

<template>
    <Head title="Create Invitation" />
    <AuthenticatedLayout>
        <template #header><PageHeader title="Create Your Invitation" eyebrow="My Invitation" subtitle="Choose the invitation entitlement you would like to use. An invitation is created only after you confirm."><template #actions><Link :href="route('dashboard')" class="fe-btn fe-btn-secondary">Back to Dashboard</Link></template></PageHeader></template>
        <main class="fe-page fe-page-standard"><section class="mx-auto max-w-2xl rounded-3xl border border-[color:var(--fe-border)] bg-[color:var(--fe-surface)] p-5 shadow-sm sm:p-8"><p class="fe-page-eyebrow">Available invitations</p><h2 class="fe-section-title mt-1">{{ entitlements.length === 1 ? 'Ready to begin?' : 'Which invitation would you like to create?' }}</h2><p class="fe-section-copy mt-3">Choose the guest capacity for this celebration. You can enter its details and select its design next.</p><div class="mt-6 space-y-3"><button v-for="(entitlement, index) in entitlements" :key="entitlement.id" type="button" class="w-full rounded-xl border p-4 text-left transition" :class="selectedId === entitlement.id ? 'border-[color:var(--fe-primary)] bg-[color:var(--fe-surface-elevated)] ring-2 ring-[color:var(--fe-primary)]/20' : 'border-[color:var(--fe-border)]'" @click="selectedId = entitlement.id"><p class="font-semibold">Invitation #{{ index + 1 }}</p><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">Up to {{ entitlement.exact_guest_capacity }} guests</p></button></div><div class="mt-7 flex justify-end"><button type="button" :disabled="!selectedId || starting" class="fe-btn fe-btn-primary" @click="start">{{ starting ? 'Starting...' : 'Start This Invitation' }}</button></div></section></main>
    </AuthenticatedLayout>
</template>
