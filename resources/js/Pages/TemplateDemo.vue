<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import InvitationPreviewRenderer from '@/Components/InvitationPreviewRenderer.vue';
import { referenceDemos } from '@/demo/referenceDemos';
const props = defineProps<{ number: string | null; media: Record<string, string> }>();
const invitation = computed(() => props.number ? { ...referenceDemos[Number(props.number) - 1], media: props.media } : null);
// Demo fixtures and reference media remain isolated from Event data.

</script>
<template>
    <Head :title="invitation ? invitation.event.title : 'Reference template demos'" />
    <main v-if="invitation" :style="{ background: invitation.demo_background || ['#f7e9df','#f5f1e8','#faf8f4','#faf7f2'][Number(number) - 1], minHeight: '100vh' }"><InvitationPreviewRenderer :invitation="invitation" /></main>
    <main v-else class="min-h-screen bg-stone-100 px-6 py-16 text-stone-800"><div class="mx-auto max-w-3xl"><p class="text-sm uppercase tracking-widest">FormalEvites · Local review</p><h1 class="mt-4 font-serif text-4xl">Reference template demos</h1><p class="mt-5 leading-relaxed">Reconstructed layouts with reference demo details. Missing licensed photography and artwork are marked in their original slots. RSVP forms here are demonstrations and do not send responses.</p><div class="mt-10 grid gap-4"><a v-for="(item, i) in referenceDemos" :key="i" :href="`/template-demos/${i + 1}`" class="rounded-xl border border-stone-300 bg-white p-6 text-xl">{{ i + 1 }} · {{ item.demo_label ? `${item.demo_label} — ` : '' }}{{ item.event.title }} <span aria-hidden="true">→</span></a></div></div></main>
</template>
