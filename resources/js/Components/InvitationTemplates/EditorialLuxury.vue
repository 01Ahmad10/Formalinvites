<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ invitation: any }>();
const headingFamily = computed(() => props.invitation.settings.heading_font === 'modern_sans' ? 'ui-sans-serif, system-ui, sans-serif' : props.invitation.settings.heading_font === 'classic_serif' ? 'ui-serif, Georgia, serif' : 'Georgia, serif');
const bodyFamily = computed(() => props.invitation.settings.body_font === 'serif' ? 'ui-serif, Georgia, serif' : 'ui-sans-serif, system-ui, sans-serif');
</script>

<template>
    <article class="editorial-document" :style="{ backgroundColor: invitation.settings.background_color, color: invitation.settings.text_color, fontFamily: bodyFamily, '--editorial-heading': headingFamily }">
        <header class="editorial-hero">
            <p class="editorial-kicker">{{ invitation.event.event_type || 'Private event' }}</p>
            <span class="editorial-hero-rule" :style="{ backgroundColor: invitation.settings.accent_color }"></span>
            <div class="editorial-hero-grid">
                <p class="editorial-volume">{{ invitation.event.main_date?.slice(-4) || 'EVENT' }}</p>
                <div>
                    <p class="editorial-intro">An invitation</p>
                    <h1 :style="{ color: invitation.settings.primary_color }">{{ invitation.event.host_name }}<span v-if="invitation.event.second_host_name"> <em>&amp;</em> {{ invitation.event.second_host_name }}</span></h1>
                    <p v-if="invitation.party_name" class="editorial-party">For {{ invitation.party_name }}</p>
                </div>
            </div>
            <h2>{{ invitation.event.title }}</h2>
        </header>

        <div class="editorial-body">
            <section v-if="invitation.event.main_date" class="reveal-on-scroll editorial-date-block">
                <p class="editorial-index">01 / DATE</p>
                <p class="editorial-date">{{ invitation.event.main_date }}</p>
                <p class="editorial-time">{{ invitation.event.start_time || 'Time to be confirmed' }}<span v-if="invitation.event.end_time"> — <span v-if="invitation.event.end_date && invitation.event.end_date !== invitation.event.main_date">{{ invitation.event.end_date }}, </span>{{ invitation.event.end_time }}</span></p>
                <p class="editorial-timezone">{{ invitation.event.timezone }}</p>
            </section>

            <section v-if="invitation.event.activities?.length" class="reveal-on-scroll editorial-section">
                <div class="editorial-section-label"><p class="editorial-index">02 / PROGRAMME</p><h2>Schedule</h2></div>
                <ol class="editorial-schedule">
                    <li v-for="(activity, index) in invitation.event.activities" :key="`${activity.title}-${index}`">
                        <span class="editorial-number">{{ String(index + 1).padStart(2, '0') }}</span>
                        <div><h3>{{ activity.title }}</h3><p class="editorial-activity-time">{{ activity.date }} · {{ activity.start_time }}<span v-if="activity.end_time"> — <span v-if="activity.end_date !== activity.date">{{ activity.end_date }}, </span>{{ activity.end_time }}</span></p></div>
                        <div class="editorial-activity-detail"><p v-if="activity.venue || activity.address">{{ activity.venue }}<span v-if="activity.address"> · {{ activity.address }}</span></p><p v-if="activity.description">{{ activity.description }}</p><p v-if="activity.location_notes">{{ activity.location_notes }}</p></div>
                    </li>
                </ol>
            </section>

            <section v-if="invitation.event.venue || invitation.event.address" class="reveal-on-scroll editorial-location">
                <div><p class="editorial-index">03 / PLACE</p><h2>{{ invitation.event.venue || 'Venue to be confirmed' }}</h2></div>
                <div><p v-if="invitation.event.address" class="editorial-copy">{{ invitation.event.address }}</p><a v-if="invitation.event.location_url" :href="invitation.event.location_url" target="_blank" rel="noopener noreferrer" class="editorial-link">Open location <span aria-hidden="true">↗</span></a></div>
            </section>

            <section v-if="invitation.event.guest_information || invitation.event.dress_code || invitation.event.parking_information || invitation.event.transportation_information || invitation.event.accommodation_information" class="reveal-on-scroll editorial-section editorial-logistics">
                <div class="editorial-section-label"><p class="editorial-index">04 / NOTES</p><h2>Guest information</h2></div>
                <div class="editorial-notes-grid">
                    <div v-if="invitation.event.guest_information" class="editorial-note editorial-note--wide"><h3>For guests</h3><p>{{ invitation.event.guest_information }}</p></div>
                    <div v-if="invitation.event.dress_code" class="editorial-note"><h3>Dress code</h3><p>{{ invitation.event.dress_code }}</p></div>
                    <div v-if="invitation.event.parking_information" class="editorial-note"><h3>Parking</h3><p>{{ invitation.event.parking_information }}</p></div>
                    <div v-if="invitation.event.transportation_information" class="editorial-note"><h3>Transportation</h3><p>{{ invitation.event.transportation_information }}</p></div>
                    <div v-if="invitation.event.accommodation_information" class="editorial-note"><h3>Accommodation</h3><p>{{ invitation.event.accommodation_information }}</p></div>
                </div>
            </section>

            <footer v-if="invitation.event.host_name" class="reveal-on-scroll editorial-closing"><span :style="{ backgroundColor: invitation.settings.accent_color }"></span><p>With anticipation,</p><strong>{{ invitation.event.host_name }}<template v-if="invitation.event.second_host_name"> &amp; {{ invitation.event.second_host_name }}</template></strong></footer>
        </div>
    </article>
</template>

<style scoped>
.editorial-document { container-type: inline-size; max-width: 100%; overflow: hidden; border: 1px solid color-mix(in srgb, currentColor 16%, transparent); box-shadow: 0 1.5rem 4.5rem rgba(12, 14, 16, 0.13); }
.editorial-document :is(h1, h2, h3, p, a) { min-width: 0; overflow-wrap: anywhere; }
.editorial-hero { padding: clamp(2rem, 6vw, 5rem) clamp(1.5rem, 7vw, 6rem) clamp(4rem, 10vw, 8rem); border-bottom: 1px solid color-mix(in srgb, currentColor 18%, transparent); }
.editorial-kicker, .editorial-index { margin: 0; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.21em; text-transform: uppercase; }
.editorial-hero-rule { display: block; width: 4.5rem; height: 1px; margin: 1.5rem 0 3.5rem; }
.editorial-hero-grid { display: grid; grid-template-columns: minmax(6rem, 0.55fr) 2fr; gap: clamp(1.5rem, 5vw, 5rem); align-items: start; }
.editorial-volume { margin: -0.15em 0 0; font-family: Georgia, serif; font-size: clamp(4.5rem, 13vw, 10rem); line-height: 0.7; opacity: 0.12; }
.editorial-intro { margin: 0; font-family: Georgia, serif; font-size: 1.1rem; font-style: italic; }
.editorial-hero h1 { max-width: 14ch; margin: 0.35rem 0 0; font-family: var(--editorial-heading); font-size: clamp(2.8rem, 7vw, 6.6rem); font-weight: 400; line-height: 0.88; letter-spacing: -0.045em; }
.editorial-hero h1 em { font-size: 0.48em; font-weight: 400; }
.editorial-party { margin: 1.8rem 0 0; font-size: 0.9rem; letter-spacing: 0.04em; }
.editorial-hero h2 { max-width: 22ch; margin: clamp(3.5rem, 9vw, 7rem) 0 0 auto; font-family: var(--editorial-heading); font-size: clamp(1rem, 2.3vw, 1.7rem); font-weight: 500; line-height: 1.25; text-align: right; }
.editorial-body { padding: clamp(2.5rem, 7vw, 6rem); }
.editorial-date-block { display: grid; grid-template-columns: 1fr auto; gap: 0.55rem 2rem; align-items: end; padding-bottom: clamp(3rem, 8vw, 6rem); border-bottom: 1px solid color-mix(in srgb, currentColor 18%, transparent); }
.editorial-date-block .editorial-index { grid-column: 1 / -1; margin-bottom: 1.2rem; }
.editorial-date { margin: 0; font-family: var(--editorial-heading); font-size: clamp(2.25rem, 5vw, 4.4rem); line-height: 0.95; }
.editorial-time { margin: 0; font-size: 1rem; text-align: right; }
.editorial-timezone { grid-column: 1 / -1; margin: 0.3rem 0 0; font-size: 0.78rem; opacity: 0.6; }
.editorial-section, .editorial-location { margin-top: clamp(3.5rem, 9vw, 7rem); }
.editorial-section-label { display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; align-items: baseline; }
.editorial-section-label h2, .editorial-location h2 { margin: 0; font-family: var(--editorial-heading); font-size: clamp(2rem, 4vw, 3.6rem); font-weight: 400; line-height: 0.95; }
.editorial-schedule { margin: 2.6rem 0 0; padding: 0; border-top: 1px solid color-mix(in srgb, currentColor 18%, transparent); list-style: none; }
.editorial-schedule li { display: grid; grid-template-columns: 4rem minmax(10rem, 1fr) minmax(12rem, 0.9fr); gap: 1.5rem; padding: 1.7rem 0; border-bottom: 1px solid color-mix(in srgb, currentColor 14%, transparent); }
.editorial-number { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em; }
.editorial-schedule h3 { margin: 0; font-family: var(--editorial-heading); font-size: 1.6rem; font-weight: 400; }
.editorial-activity-time, .editorial-activity-detail p { margin: 0.4rem 0 0; font-size: 0.88rem; line-height: 1.55; opacity: 0.78; }
.editorial-activity-detail p + p { margin-top: 0.65rem; }
.editorial-location { display: grid; grid-template-columns: 1fr 1fr; gap: clamp(2rem, 8vw, 7rem); padding: clamp(2rem, 5vw, 4rem) 0; border-top: 1px solid color-mix(in srgb, currentColor 18%, transparent); border-bottom: 1px solid color-mix(in srgb, currentColor 18%, transparent); }
.editorial-copy { margin: 0; white-space: pre-line; line-height: 1.65; }
.editorial-link { display: inline-flex; gap: 0.55rem; margin-top: 1.5rem; color: inherit; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.08em; text-decoration: none; text-transform: uppercase; }
.editorial-link:focus-visible { outline: 3px solid color-mix(in srgb, currentColor 25%, transparent); outline-offset: 4px; }
.editorial-notes-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0; margin-top: 2.6rem; border-top: 1px solid color-mix(in srgb, currentColor 18%, transparent); }
.editorial-note { padding: 1.6rem 1.4rem 1.6rem 0; border-bottom: 1px solid color-mix(in srgb, currentColor 14%, transparent); }
.editorial-note:nth-child(even) { padding-right: 0; padding-left: 1.4rem; border-left: 1px solid color-mix(in srgb, currentColor 14%, transparent); }
.editorial-note--wide { grid-column: 1 / -1; padding-right: 0; }
.editorial-note h3 { margin: 0; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.13em; text-transform: uppercase; }
.editorial-note p { margin: 0.8rem 0 0; white-space: pre-line; line-height: 1.6; }
.editorial-closing { margin-top: clamp(4rem, 9vw, 7rem); text-align: right; }
.editorial-closing > span { display: block; width: 4rem; height: 1px; margin: 0 0 1.25rem auto; }
.editorial-closing p { margin: 0; font-family: var(--editorial-heading); font-size: 1.1rem; font-style: italic; }
.editorial-closing strong { display: block; margin-top: 0.25rem; font-family: var(--editorial-heading); font-size: 1.55rem; font-weight: 400; }
/* The protected dashboard preview is a narrow component even on a wide viewport.
   Container queries keep its editorial hierarchy while preventing its grids from
   using desktop-sized viewport measurements. */
@container (max-width: 46rem) {
    .editorial-hero { padding: clamp(2rem, 6cqi, 3rem) clamp(1.5rem, 7cqi, 3rem) clamp(4rem, 10cqi, 5rem); }
    .editorial-hero-rule { margin: 1.5rem 0 2.5rem; }
    .editorial-hero-grid, .editorial-date-block, .editorial-section-label, .editorial-location { grid-template-columns: 1fr; }
    .editorial-volume { font-size: clamp(4.5rem, 13cqi, 6rem); }
    .editorial-hero h1 { font-size: clamp(2.8rem, 9cqi, 4.6rem); }
    .editorial-hero h2 { margin-top: clamp(3.5rem, 9cqi, 4.5rem); text-align: left; }
    .editorial-body { padding: clamp(2.5rem, 7cqi, 3rem); }
    .editorial-time { text-align: left; }
    .editorial-schedule li { grid-template-columns: 2.5rem minmax(0, 1fr); gap: 0.7rem; }
    .editorial-activity-detail { grid-column: 2; }
    .editorial-notes-grid { grid-template-columns: 1fr; }
    .editorial-note, .editorial-note:nth-child(even) { padding: 1.4rem 0; border-left: 0; }
    .editorial-note--wide { grid-column: auto; }
}
@media (max-width: 640px) { .editorial-hero-grid, .editorial-date-block, .editorial-section-label, .editorial-location { grid-template-columns: 1fr; } .editorial-volume { font-size: 5rem; } .editorial-hero h2 { margin-top: 4rem; text-align: left; } .editorial-time { text-align: left; } .editorial-schedule li { grid-template-columns: 2.5rem 1fr; gap: 0.7rem; } .editorial-activity-detail { grid-column: 2; } .editorial-notes-grid { grid-template-columns: 1fr; } .editorial-note, .editorial-note:nth-child(even) { padding: 1.4rem 0; border-left: 0; } .editorial-note--wide { grid-column: auto; } }
</style>
