<script setup lang="ts">
withDefaults(defineProps<{ tone?: 'neutral' | 'success' | 'warning' | 'error' | 'accent' }>(), { tone: 'neutral' });
</script>

<template><span class="fe-badge" :class="`fe-badge-${tone}`"><slot /></span></template>
