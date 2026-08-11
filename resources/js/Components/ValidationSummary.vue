<script setup lang="ts">
defineProps<{ errors: Record<string, string> }>();
</script>

<template><div v-if="Object.keys(errors).length" class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700"><p class="font-medium">Please correct the following:</p><ul class="mt-1 list-disc pl-5"><li v-for="(message, field) in errors" :key="field">{{ message }}</li></ul></div></template>
