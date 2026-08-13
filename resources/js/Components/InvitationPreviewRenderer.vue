<script setup lang="ts">
import { computed } from 'vue';
import ElegantClassic from './InvitationTemplates/ElegantClassic.vue';
import ModernMinimal from './InvitationTemplates/ModernMinimal.vue';
import RomanticFloral from './InvitationTemplates/RomanticFloral.vue';

const props = defineProps<{ invitation: any }>();
const registry: Record<string, any> = { 'elegant-classic': ElegantClassic, 'modern-minimal': ModernMinimal, 'romantic-floral': RomanticFloral };
const component = computed(() => props.invitation.template ? registry[props.invitation.template.component_key] : null);
</script>

<template>
    <component :is="component" v-if="component" :invitation="invitation" />
    <div v-else class="rounded bg-white p-8 text-center text-gray-600 shadow">Select an invitation template before previewing this Event.</div>
</template>
