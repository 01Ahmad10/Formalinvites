<script setup lang="ts">
import Button from '@/Components/UI/Button.vue';

withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
    }>(),
    {
        type: 'button',
    },
);
</script>

<template><Button :type="type" variant="secondary" v-bind="$attrs"><slot /></Button></template>
