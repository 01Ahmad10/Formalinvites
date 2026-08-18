<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ event: any; activities: any[]; canManage: boolean; fromSetup: boolean }>();
const pendingActivity = ref<any>(null);
const setActive = () => router.patch(route('events.activities.active', [props.event.id, pendingActivity.value.id]), { is_active: !pendingActivity.value.is_active }, { onFinish: () => { pendingActivity.value = null; } });
</script>

<template>
    <Head :title="`${event.title} Schedule`" />
    <AuthenticatedLayout>
        <template #header><div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-semibold">Schedule</h2><p class="text-sm text-gray-600">Times shown in {{ event.event_timezone }}</p></div><div class="flex gap-2"><Link :href="fromSetup ? route('events.setup',{event:event.id,step:3}) : route('events.show', event.id)" class="rounded border px-3 py-2 text-sm">{{ fromSetup ? 'Back to Invitation Setup' : 'Back to Event' }}</Link><Link v-if="canManage" :href="route('events.activities.create', {event:event.id,from_setup:fromSetup?1:undefined})" class="rounded bg-indigo-600 px-3 py-2 text-sm text-white">Add Activity</Link></div></div></template>
        <div class="mx-auto max-w-6xl p-6"><div class="overflow-x-auto rounded bg-white shadow"><table class="min-w-full text-left text-sm"><thead class="bg-gray-50"><tr><th class="p-3">Activity</th><th class="p-3">Date & time</th><th class="p-3">Venue</th><th class="p-3">Order</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead><tbody><tr v-for="activity in activities" :key="activity.id" class="border-t"><td class="p-3"><strong>{{ activity.title }}</strong><p v-if="activity.activity_type" class="text-gray-500">{{ activity.activity_type }}</p></td><td class="p-3">{{ activity.date }}<br>{{ activity.start_time }}<span v-if="activity.end_time"> – {{ activity.end_date === activity.date ? '' : `${activity.end_date}, ` }}{{ activity.end_time }}</span></td><td class="p-3">{{ activity.venue || 'Not provided' }}<p v-if="activity.address" class="text-gray-500">{{ activity.address }}</p></td><td class="p-3">{{ activity.display_order }}</td><td class="p-3">{{ activity.is_active ? 'Active' : 'Inactive' }}</td><td class="p-3 whitespace-nowrap"><Link :href="route('events.activities.show', [event.id, activity.id])" class="text-indigo-600">View</Link><Link v-if="canManage" :href="route('events.activities.edit', {event:event.id,activity:activity.id,from_setup:fromSetup?1:undefined})" class="ml-3 text-indigo-600">Edit</Link><button v-if="canManage" class="ml-3 text-indigo-600" @click="pendingActivity = activity">{{ activity.is_active ? 'Deactivate' : 'Reactivate' }}</button></td></tr><tr v-if="!activities.length"><td colspan="6" class="p-5 text-center text-gray-600">No activities are scheduled yet.</td></tr></tbody></table></div></div>
        <ConfirmationModal :show="!!pendingActivity" :title="pendingActivity?.is_active ? 'Deactivate activity?' : 'Reactivate activity?'" :message="pendingActivity?.is_active ? 'This activity will no longer be visible on the public RSVP page.' : 'This activity will be visible on the public RSVP page again.'" :confirm-label="pendingActivity?.is_active ? 'Deactivate' : 'Reactivate'" @close="pendingActivity = null" @confirm="setActive" />
    </AuthenticatedLayout>
</template>
