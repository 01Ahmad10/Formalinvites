<script setup lang="ts">
defineProps<{ name: 'dashboard' | 'events' | 'clients' | 'payments' | 'packages' | 'coupons' | 'templates' | 'live' | 'setup' | 'revenue' | 'collected' | 'outstanding' | 'rsvp' }>();
</script>

<template>
    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24">
        <template v-if="name === 'dashboard'"><path d="M3.75 12 12 4l8.25 8" /><path d="M5.25 10.5V20h13.5v-9.5" /><path d="M9.5 20v-5.5h5V20" /></template>
        <template v-else-if="name === 'events'"><rect x="3.75" y="5.25" width="16.5" height="15" rx="1.5" /><path d="M7.5 3.75v3M16.5 3.75v3M3.75 10h16.5M8 14h3M8 17h5" /></template>
        <template v-else-if="name === 'clients'"><circle cx="12" cy="8" r="3" /><path d="M5.5 20c.55-3.2 3-5 6.5-5s5.95 1.8 6.5 5" /><path d="M18.5 8.5a2.5 2.5 0 0 1 0 4.75M19.5 15.5c1.45.65 2.3 1.85 2.55 3.5" /></template>
        <template v-else-if="name === 'payments'"><path d="M4.5 6.5A2.5 2.5 0 0 1 7 4h10a2.5 2.5 0 0 1 2.5 2.5v11A2.5 2.5 0 0 1 17 20H7a2.5 2.5 0 0 1-2.5-2.5z" /><path d="M4.5 8.5h15M15.5 14.25h.01" /></template>
        <template v-else-if="name === 'packages'"><path d="m4 8 8-4 8 4-8 4zM4 8v8l8 4 8-4V8M12 12v8" /></template>
        <template v-else-if="name === 'coupons'"><path d="M4.5 5.5h15v5a2 2 0 0 0 0 4v4h-15v-4a2 2 0 0 0 0-4z" /><path d="M12 7.5v1M12 15.5v1" /></template>
        <template v-else-if="name === 'live'"><path d="M4 12a8 8 0 1 0 16 0 8 8 0 0 0-16 0" /><path d="m8.5 12 2.25 2.25L15.75 9" /></template>
        <template v-else-if="name === 'setup'"><circle cx="12" cy="12" r="8" /><path d="M12 7.5V12l3 2" /></template>
        <template v-else-if="name === 'revenue'"><path d="M4.5 7A2.5 2.5 0 0 1 7 4.5h10A2.5 2.5 0 0 1 19.5 7v10A2.5 2.5 0 0 1 17 19.5H7A2.5 2.5 0 0 1 4.5 17z" /><path d="M4.5 8h15M14.5 14h2" /></template>
        <template v-else-if="name === 'collected'"><circle cx="12" cy="12" r="8" /><path d="m8.5 12 2.25 2.25L15.75 9" /></template>
        <template v-else-if="name === 'outstanding'"><path d="M12 4 20 19H4z" /><path d="M12 9v4M12 16h.01" /></template>
        <template v-else-if="name === 'rsvp'"><circle cx="9" cy="8" r="2.75" /><path d="M3.75 19c.45-3 2.15-4.75 5.25-4.75 1.45 0 2.6.4 3.45 1.15M15 9.5l1.5 1.5 3-3" /></template>
        <template v-else><rect x="4" y="3.75" width="16" height="16.5" rx="1.5" /><path d="M8 8h8M8 12h8M8 16h5" /></template>
    </svg>
</template>
