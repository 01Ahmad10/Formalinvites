<script setup lang="ts">
defineProps<{ title: string; subtitle?: string; eyebrow?: string }>();
</script>

<template>
    <div class="fe-page-header">
        <div><p v-if="eyebrow" class="fe-page-eyebrow">{{ eyebrow }}</p><h1 class="fe-page-title">{{ title }}</h1><p v-if="subtitle" class="fe-page-subtitle">{{ subtitle }}</p></div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2"><slot name="actions" /></div>
    </div>
</template>
