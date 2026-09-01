<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{ pagination?: { current_page: number; last_page: number; total: number; prev_page_url: string | null; next_page_url: string | null } }>();
</script>

<template>
    <nav v-if="pagination && pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="Pagination">
        <p class="text-[color:var(--fe-text-muted)]">Page {{ pagination.current_page }} of {{ pagination.last_page }} · {{ pagination.total }} total</p>
        <div class="flex gap-2">
            <Link v-if="pagination.prev_page_url" :href="pagination.prev_page_url" class="fe-btn fe-btn-secondary">Previous</Link>
            <Link v-if="pagination.next_page_url" :href="pagination.next_page_url" class="fe-btn fe-btn-secondary">Next</Link>
        </div>
    </nav>
</template>
