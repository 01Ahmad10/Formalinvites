<script setup lang="ts">
import Modal from '@/Components/Modal.vue';

defineProps<{ show: boolean; title: string; message: string; confirmLabel?: string; processing?: boolean }>();
defineEmits(['confirm', 'close']);
</script>

<template><Modal :show="show" max-width="md" @close="$emit('close')"><div class="p-6"><h3 class="text-lg font-semibold">{{ title }}</h3><p class="mt-2 text-sm text-gray-600">{{ message }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" @click="$emit('close')" class="rounded border border-gray-300 px-3 py-2">Cancel</button><button type="button" :disabled="processing" @click="$emit('confirm')" class="rounded bg-indigo-600 px-3 py-2 text-white disabled:opacity-50">{{ confirmLabel || 'Confirm' }}</button></div></div></Modal></template>
