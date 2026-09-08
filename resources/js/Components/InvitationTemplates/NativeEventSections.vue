<script setup lang="ts">
import ReferenceGifts from './ReferenceGifts.vue';
import { invitationText } from './invitationText';
defineProps<{ invitation: any }>();
</script>
<template>
    <template v-if="!invitation.reference_demo">
        <section v-if="invitation.content?.story_enabled" class="wg-section native-event-section" data-section="story"><h2 class="wg-heading">{{ invitation.content.story_heading || invitationText(invitation, 'Our Story') }}</h2><p>{{ invitation.content.story_body }}</p></section>
        <section v-if="invitation.content?.gift_registry_enabled" class="wg-section native-event-section" data-section="gifts"><h2 class="wg-heading">{{ invitationText(invitation, 'Gift Registry') }}</h2><p>{{ invitation.content.gift_registry_intro }}</p><ReferenceGifts :methods="invitation.gift_methods || []" /></section>
    </template>
</template>
<style scoped>
.native-event-section{padding:48px 24px;max-width:620px;margin:auto;color:var(--wg-ink);overflow-wrap:anywhere}.native-event-section p{white-space:pre-line;line-height:1.7;margin:24px auto;font-size:19px}.native-event-section :deep(a){color:var(--wg-accent)}
.native-event-section :deep(.gift-method){border-color:var(--wg-accent)}.native-event-section :deep(.gift-method strong),.native-event-section :deep(.gift-method button){color:var(--wg-accent)}
</style>
