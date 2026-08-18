<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

type Toast = { id: number; type: 'success' | 'error' | 'warning' | 'info'; message: string };
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

<template><div class="fixed right-4 top-4 z-50 w-[calc(100%-2rem)] max-w-sm space-y-2" aria-live="polite"><div v-for="toast in toasts" :key="toast.id" class="fe-toast flex items-start justify-between" :class="`fe-toast-${toast.type}`"><span class="text-sm font-medium leading-5">{{ toast.message }}</span><button @click="remove(toast.id)" class="ml-4 min-h-6 min-w-6 rounded text-lg leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-current" aria-label="Dismiss notification">×</button></div></div></template>
