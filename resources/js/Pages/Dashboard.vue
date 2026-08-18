<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps<{ events: any[]; isAdmin: boolean; isSupport: boolean }>();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <div class="mb-4 flex items-center justify-between"><p>Your accessible events.</p><Link v-if="isAdmin" :href="route('events.create')" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">Create event</Link></div>
                        <div v-if="events.length" class="space-y-3"><article v-for="event in events" :key="event.id" class="rounded border p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div><div class="font-medium">{{ event.title }}</div><div class="mt-1 text-sm text-gray-600">{{ event.main_date || 'Date to be confirmed' }} · {{ event.invitation_status.replaceAll('_', ' ') }}<span v-if="event.live_version"> · Version {{ event.live_version }}</span><span v-if="event.unpublished_changes" class="ml-1 text-amber-700">· Unpublished Changes</span></div></div><div class="flex flex-wrap gap-2"><Link :href="route('events.show', event.id)" class="rounded border border-gray-300 px-3 py-2 text-sm">Manage</Link><Link v-if="!isSupport" :href="route('events.setup', event.id)" class="rounded border border-indigo-600 px-3 py-2 text-sm text-indigo-700">{{ event.live_version ? 'Edit Invitation' : 'Continue Setup' }}</Link><Link v-if="event.live_version" :href="route('events.invitation.live-preview', event.id)" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">View Invitation</Link></div></div></article></div>
                        <p v-else class="text-gray-600">No events yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
