<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[color:var(--fe-accent)] text-start text-base font-medium text-[color:var(--fe-primary)] bg-[#f9ece7] focus:outline-none focus:text-[color:var(--fe-primary)] focus:bg-[#f3eadc] focus:border-[color:var(--fe-accent)] transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[color:var(--fe-text-secondary)] hover:text-[color:var(--fe-text)] hover:bg-[color:var(--fe-surface-elevated)] hover:border-[color:var(--fe-border-strong)] focus:outline-none focus:text-[color:var(--fe-text)] focus:bg-[color:var(--fe-surface-elevated)] focus:border-[color:var(--fe-border-strong)] transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
