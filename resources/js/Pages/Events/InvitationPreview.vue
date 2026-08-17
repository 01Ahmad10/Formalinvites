<script setup lang="ts">
import InvitationPreviewRenderer from '@/Components/InvitationPreviewRenderer.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps<{ invitation: any; previewLabel?: string }>();
</script>

<template>
    <Head title="Invitation Preview" />
    <main class="min-h-screen bg-gray-100 p-4 sm:p-8"><div class="mx-auto mb-5 flex max-w-3xl justify-between gap-3"><Link :href="route('events.invitation.edit', route().params.event)" class="rounded border border-gray-300 bg-white px-3 py-2 text-sm">Back to Invitation</Link><span v-if="invitation.template" class="rounded bg-white px-3 py-2 text-sm text-gray-600">{{ previewLabel || 'Working Preview' }}: {{ invitation.template.name }}</span></div><InvitationPreviewRenderer :invitation="invitation" /></main>
</template>
