<script setup lang="ts">
import { computed, defineAsyncComponent, useSlots } from 'vue';
import PublicRsvpExperience from './PublicRsvpExperience.vue';

const props = defineProps<{ invitation: any }>();
const slots = useSlots();
const registry: Record<string, any> = {
    'romantic-floral': defineAsyncComponent(() => import('./InvitationTemplates/PublicRomanticFloral.vue')),
    'editorial-luxury': defineAsyncComponent(() => import('./InvitationTemplates/EditorialLuxury.vue')),
    'modern-cinematic': defineAsyncComponent(() => import('./InvitationTemplates/ModernCinematic.vue')),
    'dolce-vita': defineAsyncComponent(() => import('./InvitationTemplates/DolceVita.vue')),
    'blossom-oud': defineAsyncComponent(() => import('./InvitationTemplates/BlossomOud.vue')),
    'sacred-garden': defineAsyncComponent(() => import('./InvitationTemplates/SacredGarden.vue')),
    'royal-plum': defineAsyncComponent(() => import('./InvitationTemplates/RoyalPlum.vue')),
};
const component = computed(() => props.invitation.template ? registry[props.invitation.template.component_key] : null);
</script>

<template>
    <component :is="component" v-if="component" :invitation="invitation" :dir="invitation.content?.primary_locale === 'ar' ? 'rtl' : 'ltr'">
        <template v-if="slots.default || !invitation.reference_demo" #default>
            <slot>
                <PublicRsvpExperience v-if="invitation.preview_context?.party" :key="invitation.preview_context.party.id" :party="invitation.preview_context.party" :rsvp="invitation.preview_context.party.rsvp" :meals="invitation.preview_context.meals" :closed="invitation.preview_context.closed" :confirmation="null" :theme="invitation.template?.component_key" :locale="invitation.content?.primary_locale" preview />
                <p v-else>{{ invitation.content?.primary_locale === 'ar' ? 'اختَر عائلة لمعاينة تأكيد الحضور وخيارات الوجبات.' : 'Choose a family to preview attendance and meal choices.' }}</p>
            </slot>
        </template>
    </component>
    <div v-else class="rounded bg-white p-8 text-center text-gray-600 shadow">Select an invitation template before previewing this Event.</div>
    <slot v-if="!['romantic-floral', 'editorial-luxury', 'modern-cinematic', 'royal-plum', 'dolce-vita', 'blossom-oud', 'sacred-garden'].includes(invitation.template?.component_key)" />
</template>
