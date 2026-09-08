<script setup lang="ts">
withDefaults(defineProps<{ invitation: any; variant: 'romantic' | 'editorial' | 'cinematic'; part?: 'all' | 'body' | 'ending' }>(), { part: 'all' });
</script>

<template>
    <div v-if="part === 'ending' ? invitation.content?.ending_enabled : (invitation.content?.story_enabled || invitation.content?.gift_registry_enabled || (part === 'all' && invitation.content?.ending_enabled))" class="invitation-content-sections" :class="`invitation-content-sections--${variant}`" :dir="invitation.content?.primary_locale === 'ar' ? 'rtl' : 'ltr'">
        <section v-if="part !== 'ending' && invitation.content?.story_enabled && (invitation.content.story_heading || invitation.content.story_body)" data-section="story" class="invitation-content-section reveal-on-scroll"><p class="invitation-content-kicker">Our story</p><h2>{{ invitation.content.story_heading || 'Our Story' }}</h2><p v-if="invitation.content.story_body">{{ invitation.content.story_body }}</p></section>
        <section v-if="part !== 'ending' && invitation.content?.gift_registry_enabled && (invitation.content.gift_registry_intro || invitation.gift_methods?.length)" data-section="gifts" class="invitation-content-section reveal-on-scroll"><p class="invitation-content-kicker">Gift registry</p><h2>A thoughtful note</h2><p v-if="invitation.content.gift_registry_intro">{{ invitation.content.gift_registry_intro }}</p><ul v-if="invitation.gift_methods?.length" class="invitation-gift-list"><li v-for="(method, index) in invitation.gift_methods" :key="`${method.label}-${index}`"><strong>{{ method.label }}</strong><p v-if="method.details">{{ method.details }}</p><a v-if="method.external_url" :href="method.external_url" target="_blank" rel="noopener noreferrer">Open registry <span aria-hidden="true">↗</span></a></li></ul></section>
        <footer v-if="part !== 'body' && invitation.content?.ending_enabled && (invitation.content.ending_title || invitation.content.ending_message)" data-section="ending" class="invitation-content-closing reveal-on-scroll"><p v-if="invitation.content.ending_title">{{ invitation.content.ending_title }}</p><strong v-if="invitation.content.ending_message">{{ invitation.content.ending_message }}</strong></footer>
    </div>
</template>

<style scoped>
.invitation-content-sections { overflow-wrap: anywhere; margin-top: clamp(3.5rem, 8vw, 7rem); }
.invitation-content-section { padding: clamp(2.2rem, 6vw, 4.5rem) 0; border-top: 1px solid currentColor; }
.invitation-content-kicker { margin: 0; font-size: .7rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; opacity: .7; }
.invitation-content-section h2 { margin: .75rem 0 0; font-family: Georgia, serif; font-size: clamp(2rem, 4vw, 3.4rem); font-weight: 400; line-height: 1; }
.invitation-content-section > p:not(.invitation-content-kicker), .invitation-gift-list p { margin: 1.2rem 0 0; line-height: 1.7; white-space: pre-line; }
.invitation-gift-list { display: grid; gap: 1rem; margin: 1.8rem 0 0; padding: 0; list-style: none; }
.invitation-gift-list li { padding: 1.25rem; border: 1px solid currentColor; }
.invitation-gift-list p { font-size: .92rem; opacity: .8; }
.invitation-gift-list a { display: inline-flex; gap: .4rem; margin-top: .85rem; color: inherit; font-size: .82rem; font-weight: 700; text-decoration: none; text-transform: uppercase; }
.invitation-content-closing { padding: clamp(4rem, 9vw, 7rem) 1rem 0; border-top: 1px solid currentColor; text-align: center; }
.invitation-content-closing p { margin: 0; font-family: Georgia, serif; font-size: 1.3rem; font-style: italic; }
.invitation-content-closing strong { display: block; margin-top: .65rem; font-family: Georgia, serif; font-size: clamp(1.7rem, 3vw, 2.7rem); font-weight: 400; line-height: 1.2; white-space: pre-line; }
.invitation-content-sections--cinematic { padding-inline: clamp(1.5rem, 7cqi, 7rem); padding-bottom: 3rem; color: var(--cinematic-text); }
.invitation-content-sections--cinematic .invitation-content-section, .invitation-content-sections--cinematic .invitation-content-closing { border-color: color-mix(in srgb, var(--cinematic-primary) 36%, transparent); }
.invitation-content-sections--cinematic .invitation-content-kicker, .invitation-content-sections--cinematic a { color: var(--cinematic-primary); }
</style>
