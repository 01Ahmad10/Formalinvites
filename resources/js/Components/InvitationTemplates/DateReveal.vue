<script setup lang="ts">
import { ref } from 'vue';

defineProps<{ date: string | null; variant: 'editorial' | 'cinematic' }>();
const revealed = ref(false);
</script>

<template>
    <button v-if="date" type="button" class="date-reveal" :class="`date-reveal--${variant}`" :aria-pressed="revealed" @click="revealed = true"><span class="date-reveal__label">{{ revealed ? 'The date is set' : 'Tap to reveal the date' }}</span><strong :class="{ 'date-reveal__date--hidden': !revealed }">{{ date }}</strong><small v-if="!revealed">Tap or press Enter</small></button>
</template>

<style scoped>
.date-reveal { display: grid; width: 100%; gap: .75rem; padding: clamp(2rem, 5cqi, 4rem); border: 1px solid currentColor; background: transparent; color: inherit; text-align: center; }
.date-reveal__label, small { font-size: .68rem; font-weight: 700; letter-spacing: .19em; text-transform: uppercase; opacity: .7; }
strong { font-family: Georgia, serif; font-size: clamp(2.1rem, 5cqi, 4.5rem); font-weight: 400; transition: filter .55s ease, opacity .55s ease; }
.date-reveal__date--hidden { filter: blur(.72rem); opacity: .45; }
.date-reveal--cinematic { color: var(--cinematic-text); border-color: color-mix(in srgb, var(--cinematic-primary) 48%, transparent); }
.date-reveal--cinematic .date-reveal__label, .date-reveal--cinematic small { color: var(--cinematic-primary); }
</style>
