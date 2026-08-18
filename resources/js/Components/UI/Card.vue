<script setup lang="ts">
withDefaults(defineProps<{ muted?: boolean }>(), { muted: false });
</script>

<template>
    <section class="fe-card" :class="{ 'fe-card-muted': muted }"><div class="fe-card-body"><slot /></div></section>
</template>
