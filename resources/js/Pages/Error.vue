<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
const props = defineProps<{ status: number }>();
const messages: Record<number, { title: string; text: string }> = { 403: { title: 'Access denied', text: 'You do not have permission to view this page.' }, 404: { title: 'Page not found', text: 'The page you requested could not be found.' }, 500: { title: 'Something went wrong', text: 'Please try again. If the problem continues, contact an administrator.' }, 503: { title: 'Service unavailable', text: 'The application is temporarily unavailable. Please try again shortly.' } };
const message = messages[props.status] || messages[500];
</script>
<template><Head :title="message.title"/><main class="flex min-h-screen items-center justify-center bg-gray-100 p-6"><div class="max-w-md rounded-lg bg-white p-8 text-center shadow"><p class="text-sm text-gray-500">Error {{ status }}</p><h1 class="mt-2 text-2xl font-semibold">{{ message.title }}</h1><p class="mt-3 text-gray-600">{{ message.text }}</p><Link :href="route('dashboard')" class="mt-6 inline-block rounded bg-indigo-600 px-4 py-2 text-white">Back to dashboard</Link></div></main></template>
