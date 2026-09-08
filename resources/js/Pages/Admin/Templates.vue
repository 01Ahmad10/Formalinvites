<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import { templatePreviewUrl } from '@/Support/templatePreviews';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
const props = defineProps<{ templates: any[] }>();
const pending = ref<any>(null);
const unavailablePreviews = ref<Record<string, boolean>>({});
const toggle = () => router.patch(route('admin.templates.active', pending.value.id), { is_active: !pending.value.is_active }, { onFinish: () => pending.value = null });
const markPreviewUnavailable = (key: string) => { unavailablePreviews.value[key] = true; };
</script>
<template>
  <Head title="Invitation Templates" />
  <AuthenticatedLayout><template #header><PageHeader title="Invitation Templates" subtitle="Manage the invitation designs available to clients." /></template>
    <div class="fe-page fe-page-wide"><div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3"><article v-for="template in templates" :key="template.id" class="fe-card overflow-hidden p-0"><div class="aspect-[3/4] overflow-hidden bg-stone-100"><img v-if="templatePreviewUrl(template.component_key) && !unavailablePreviews[template.component_key]" :src="templatePreviewUrl(template.component_key)!" :alt="`${template.name} invitation preview`" class="h-full w-full object-cover object-top" loading="lazy" @error="markPreviewUnavailable(template.component_key)" /><div v-else class="flex h-full flex-col items-center justify-center gap-2 bg-stone-100 px-6 text-center text-sm text-[color:var(--fe-text-muted)]"><span>Preview unavailable</span><span class="font-semibold text-[color:var(--fe-text-secondary)]">{{ template.name }}</span></div></div><div class="p-5"><div class="flex items-start justify-between gap-3"><div><h2 class="text-lg font-semibold">{{ template.name }}</h2><p class="mt-1 text-sm text-[color:var(--fe-text-secondary)]">{{ template.supported_event_types.map((type: string) => type.replaceAll('_', ' ')).join(' · ') }}</p></div><Badge :tone="template.is_active ? 'success' : 'neutral'">{{ template.is_active ? 'Active' : 'Inactive' }}</Badge></div><p class="mt-4 text-sm text-[color:var(--fe-text-muted)]">{{ template.events_count }} assigned {{ template.events_count === 1 ? 'event' : 'events' }}</p><div class="mt-5 flex flex-wrap gap-3"><a v-if="template.demo_url" :href="template.demo_url" target="_blank" rel="noopener noreferrer" class="fe-btn fe-btn-secondary">View Demo</a><Button variant="secondary" @click="pending = template">{{ template.is_active ? 'Deactivate' : 'Activate' }}</Button></div></div></article></div></div>
    <ConfirmationModal :show="!!pending" :title="pending?.is_active ? 'Deactivate template?' : 'Activate template?'" :message="pending?.is_active ? 'This design will not be available for new invitations.' : 'This design will be available for new invitations.'" :confirm-label="pending?.is_active ? 'Deactivate' : 'Activate'" @close="pending = null" @confirm="toggle" />
  </AuthenticatedLayout>
</template>
