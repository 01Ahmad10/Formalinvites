<script setup lang="ts">
import Button from '@/Components/UI/Button.vue';

withDefaults(defineProps<{ type?: 'button' | 'submit' | 'reset' }>(), { type: 'submit' });
</script>

<template><Button :type="type" variant="primary" v-bind="$attrs"><slot /></Button></template>
