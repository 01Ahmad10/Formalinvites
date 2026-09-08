<script setup lang="ts">
import { ref } from 'vue';
defineProps<{ methods: any[] }>();
const copied = ref<number | null>(null);
const failed = ref(false);
async function copy(value: string, index: number) {
    try { await navigator.clipboard.writeText(value); copied.value = index; failed.value = false; }
    catch { failed.value = true; }
}
</script>
<template><div class="reference-gifts"><div v-for="(method, index) in methods" :key="index" class="gift-method"><strong>{{ method.label }}</strong><div><span>{{ method.details }}</span><button v-if="method.details" type="button" :aria-label="`Copy ${method.label}`" @click="copy(method.details, index)">{{ copied === index ? '✓' : '⧉' }}</button></div><a v-if="method.external_url" :href="method.external_url" target="_blank" rel="noopener noreferrer">View registry ↗</a></div><p v-if="copied !== null" class="copy-status" role="status">Copied</p><p v-if="failed" role="status">Please select and copy the details.</p></div></template>
<style scoped>
.reference-gifts{display:grid;gap:12px;position:relative}.gift-method{padding:16px;border:1px solid #b4694d33;border-radius:8.8px;background:#ffffffb3;text-align:center}.gift-method strong{font:700 16px/1.45 Georgia,serif;color:#ad7468;display:block}.gift-method>div{display:flex;justify-content:center;align-items:center;gap:8.8px;margin-top:7.2px;overflow-wrap:anywhere}.gift-method button{flex-shrink:0;width:34.4px;height:34.4px;border-radius:50%;background:#fffaf6;color:#ad7468;border:1px solid #ad746847;box-shadow:0 5px 14px #7851491a;transition:transform .2s ease}.gift-method button:hover{transform:translateY(-1px)}.copy-status{position:absolute;bottom:-25px;width:100%;font:11px Arial,sans-serif}.gift-method a{display:inline-block;margin-top:8px;text-decoration:underline}
</style>
