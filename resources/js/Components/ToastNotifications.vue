<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

type Toast = { id: number; type: 'success' | 'error'; message: string };
const page = usePage<any>();
const toasts = ref<Toast[]>([]);
let nextId = 1;
let lastFlash = '';

const add = (type: Toast['type'], message: string) => {
    const toast = { id: nextId++, type, message };
    toasts.value.push(toast);
    window.setTimeout(() => remove(toast.id), 5000);
};
const remove = (id: number) => { toasts.value = toasts.value.filter((toast) => toast.id !== id); };

watch(() => page.props.flash, (flash) => {
    const key = `${flash?.success || ''}|${flash?.error || ''}`;
    if (!key || key === '|' || key === lastFlash) return;
    lastFlash = key;
    if (flash.success) add('success', flash.success);
    if (flash.error) add('error', flash.error);
}, { immediate: true, deep: true });

router.on('error', (event) => {
    if (Object.keys(event.detail.errors).length) add('error', 'Please review the highlighted fields and try again.');
});
</script>

<template><div class="fixed right-4 top-4 z-50 w-full max-w-sm space-y-2" aria-live="polite"><div v-for="toast in toasts" :key="toast.id" :class="toast.type === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'" class="flex items-start justify-between rounded border p-4 shadow"><span>{{ toast.message }}</span><button @click="remove(toast.id)" class="ml-4 text-lg leading-none" aria-label="Dismiss notification">×</button></div></div></template>
