<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/UI/Button.vue';

defineProps<{ show: boolean; title: string; message: string; confirmLabel?: string; processing?: boolean }>();
defineEmits(['confirm', 'close']);
</script>

<template><Modal :show="show" max-width="md" @close="$emit('close')"><div class="p-5 sm:p-6"><h3 class="text-lg font-semibold text-[color:var(--fe-text)]">{{ title }}</h3><p class="mt-2 break-words text-sm text-[color:var(--fe-text-secondary)]">{{ message }}</p><div class="mt-6 flex flex-wrap justify-end gap-3"><Button variant="secondary" @click="$emit('close')">Cancel</Button><Button :loading="processing" @click="$emit('confirm')">{{ confirmLabel || 'Confirm' }}</Button></div></div></Modal></template>
