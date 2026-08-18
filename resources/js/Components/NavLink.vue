<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    href: string;
    active?: boolean;
}>();

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[color:var(--fe-accent)] text-sm font-medium leading-5 text-[color:var(--fe-text)] focus:outline-none focus:border-[color:var(--fe-accent)] transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[color:var(--fe-text-secondary)] hover:text-[color:var(--fe-text)] hover:border-[color:var(--fe-border-strong)] focus:outline-none focus:text-[color:var(--fe-text)] focus:border-[color:var(--fe-border-strong)] transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
