<script setup lang="ts">
defineProps<{ name: string; src?: string | null; background?: boolean }>();
</script>
<template>
    <span class="reference-asset" :class="{ 'reference-asset--background': background, 'reference-asset--missing': !src }" :role="src ? undefined : 'img'" :aria-label="src ? undefined : `Temporary placeholder: ${name}`">
        <img v-if="src" :src="src" :alt="name" :loading="background ? 'eager' : 'lazy'" decoding="async" />
        <span v-else class="reference-asset__label">Temporary asset · {{ name }}</span>
    </span>
</template>
<style scoped>
.reference-asset{display:block;position:relative;overflow:hidden;min-width:0;min-height:0}
.reference-asset--background{position:absolute;inset:0;z-index:-1;pointer-events:none}
.reference-asset img{width:100%;height:100%;object-fit:cover;object-position:50% 50%}
.reference-asset--missing{background:#d9d2ca}
.reference-asset__label{position:absolute;bottom:12px;left:12px;right:12px;z-index:1;text-align:center;font:9px/1.4 Arial,sans-serif;letter-spacing:.04em;color:#51473e;background:#fffc;padding:4px;border-radius:2px}
</style>
