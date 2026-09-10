<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InvitationPreviewRenderer from '@/Components/InvitationPreviewRenderer.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ event: any; templates: any[]; overrides: any; resolvedSettings: any; parties: any[]; canManage: boolean; previewInvitation: any; liveVersion: number | null }>();
const form = useForm({ template_id: props.event.template_id || '', settings: { palette_key: props.overrides.palette_key || '', font_pair_key: props.overrides.font_pair_key || '' } });
const previewPartyId = ref('');
const selectedTemplate = computed(() => props.templates.find((template) => template.id === Number(form.template_id)) || props.event.template);
const paletteOptions = computed(() => selectedTemplate.value?.palettes || []);
const fontPairOptions = computed(() => selectedTemplate.value?.font_pairs || []);
const previewPalette = computed(() => paletteOptions.value.find((palette: any) => palette.key === form.settings.palette_key) || paletteOptions.value.find((palette: any) => palette.key === selectedTemplate.value?.default_palette_key));
const previewFontPair = computed(() => fontPairOptions.value.find((fontPair: any) => fontPair.key === form.settings.font_pair_key) || fontPairOptions.value.find((fontPair: any) => fontPair.key === selectedTemplate.value?.default_font_pair_key));
const previewInvitation = computed(() => ({ ...props.previewInvitation, template: selectedTemplate.value ? { name: selectedTemplate.value.name, component_key: selectedTemplate.value.component_key, is_active: selectedTemplate.value.is_active } : null, settings: { ...props.resolvedSettings, ...(previewPalette.value || {}), ...(previewFontPair.value || {}) }, party_name: previewPartyId.value ? props.parties.find((party) => party.id === Number(previewPartyId.value))?.name : null }));
watch(() => form.template_id, (templateId, previousTemplateId) => { if (previousTemplateId !== undefined && templateId !== previousTemplateId) { form.settings.palette_key = ''; form.settings.font_pair_key = ''; } });
const submit = () => form.transform((data) => ({ template_id: data.template_id || null, settings: { palette_key: data.settings.palette_key || null, font_pair_key: data.settings.font_pair_key || null } })).put(route('events.invitation.update', props.event.id));
</script>

<template>
    <Head :title="`${event.title} Invitation`" />
    <AuthenticatedLayout>
        <template #header><div class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-semibold">Invitation</h2><p class="text-sm text-gray-600">Presentation settings only; Event, guest, RSVP, and payment data are unchanged.</p></div><Link :href="route('events.show', event.id)" class="rounded border border-gray-300 px-3 py-2 text-sm">Back to Invitation</Link></div></template>
        <div class="mx-auto grid max-w-6xl gap-6 p-6 lg:grid-cols-2">
            <form v-if="canManage" class="space-y-5 rounded bg-white p-6 shadow" @submit.prevent="submit">
                <div><label class="block text-sm font-medium">Selected template</label><select v-model="form.template_id" class="mt-1 w-full rounded border-gray-300"><option value="">No template selected</option><option v-for="template in templates" :key="template.id" :value="template.id">{{ template.name }}{{ template.is_active ? '' : ' (inactive — retained assignment)' }}</option></select><p v-if="event.template && !event.template.is_active" class="mt-2 text-sm text-amber-700">The assigned template is inactive. It remains viewable but cannot be newly selected for another Event.</p><InputError :message="form.errors.template_id" class="mt-1" /></div>
                <div><label class="block text-sm font-medium">Color style</label><select v-model="form.settings.palette_key" :disabled="!selectedTemplate" class="mt-1 w-full rounded border-gray-300"><option value="">Use Template default</option><option v-for="palette in paletteOptions" :key="palette.key" :value="palette.key">{{ palette.label }}</option></select><p class="mt-1 text-sm text-gray-500">Colors are defined by the selected Template.</p><InputError :message="form.errors['settings.palette_key']" class="mt-1" /></div>
                <div><label class="block text-sm font-medium">Typography</label><select v-model="form.settings.font_pair_key" :disabled="!selectedTemplate" class="mt-1 w-full rounded border-gray-300"><option value="">Use Template default</option><option v-for="fontPair in fontPairOptions" :key="fontPair.key" :value="fontPair.key">{{ fontPair.label }}</option></select><p class="mt-1 text-sm text-gray-500">Font pairs are defined by the selected Template.</p><InputError :message="form.errors['settings.font_pair_key']" class="mt-1" /></div>
                <button :disabled="form.processing" class="rounded bg-indigo-600 px-4 py-2 text-white">Save invitation settings</button>
            </form>
            <section v-else class="rounded bg-white p-6 shadow"><h3 class="font-semibold">Invitation configuration</h3><p class="mt-2 text-gray-600">This invitation is available for viewing only.</p><p class="mt-4">Selected template: <strong>{{ event.template?.name || 'Not selected' }}</strong></p></section>
            <section class="space-y-4"><div class="rounded bg-white p-5 shadow"><div class="flex flex-wrap items-end justify-between gap-3"><div><h3 class="font-semibold">Preview</h3><p class="mt-1 text-sm text-gray-600">Preview uses the current editable Event configuration. Select a party for a personalized preview.</p></div><div><label class="block text-sm">Preview as invitation party</label><select v-model="previewPartyId" class="mt-1 rounded border-gray-300 text-sm"><option value="">No party selected</option><option v-for="party in parties" :key="party.id" :value="party.id">{{ party.name }}</option></select></div></div><div class="mt-4 flex flex-wrap gap-2"><Link :href="route('events.invitation.preview', { event: event.id, party_id: previewPartyId || undefined })" class="inline-block rounded border border-indigo-600 px-3 py-2 text-sm text-indigo-600">Open Preview</Link><Link v-if="liveVersion" :href="route('events.invitation.live-preview', event.id)" class="inline-block rounded border border-gray-300 px-3 py-2 text-sm">View Live Version {{ liveVersion }}</Link></div></div><InvitationPreviewRenderer :invitation="previewInvitation" /></section>
        </div>
    </AuthenticatedLayout>
</template>
