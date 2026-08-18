<script setup lang="ts">
withDefaults(defineProps<{ compact?: boolean }>(), { compact: false });
</script>

<template>
    <span class="inline-flex items-center whitespace-nowrap text-[1.05rem] font-semibold tracking-[-0.035em] text-[color:var(--fe-text)] sm:text-lg">
        Formal<span class="text-[color:var(--fe-accent)]">Evites</span>
        <span v-if="!compact" class="ml-2 hidden border-l border-[color:var(--fe-border-strong)] pl-2 text-[0.6rem] font-bold uppercase tracking-[0.16em] text-[color:var(--fe-text-muted)] sm:inline">Event management</span>
    </span>
</template>
