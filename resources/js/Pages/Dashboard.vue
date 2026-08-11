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
                        <div class="mb-4 flex items-center justify-between"><p>Your accessible events.</p><Link :href="route('events.create')" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">Create event</Link></div>
                        <div v-if="events.length" class="space-y-3"><Link v-for="event in events" :key="event.id" :href="route('events.show', event.id)" class="block rounded border p-3 hover:bg-gray-50"><div class="font-medium">{{ event.title }}</div><div class="text-sm text-gray-600">{{ event.main_date || 'Date to be confirmed' }} · {{ event.status }} · {{ event.package ? `${event.package.minimum_guests}-${event.package.maximum_guests} guests` : 'No package assigned' }} · Payment: {{ event.payments?.[0]?.status || 'Not recorded' }}</div></Link></div>
                        <p v-else class="text-gray-600">No events yet.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
