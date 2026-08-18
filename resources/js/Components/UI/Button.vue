<script setup lang="ts">
withDefaults(defineProps<{
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
    loading?: boolean;
}>(), { type: 'button', variant: 'primary', loading: false });
</script>

<template>
    <button v-bind="$attrs" :type="type" :disabled="loading || Boolean($attrs.disabled)" class="fe-btn" :class="`fe-btn-${variant}`">
        <span v-if="loading" class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true" />
        <slot />
    </button>
</template>
