<script setup lang="ts">
defineProps<{ title: string; message?: string }>();
</script>

<template><div class="fe-empty-state"><p class="fe-empty-state-title">{{ title }}</p><p v-if="message" class="fe-empty-state-copy">{{ message }}</p><div v-if="$slots.action" class="mt-4"><slot name="action" /></div></div></template>
