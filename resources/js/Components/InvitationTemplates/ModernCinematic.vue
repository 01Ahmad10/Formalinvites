<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ invitation: any }>();

const headingFamily = computed(() => props.invitation.settings.heading_font === 'modern_sans'
    ? 'ui-sans-serif, system-ui, sans-serif'
    : 'ui-serif, Georgia, serif');
const bodyFamily = computed(() => props.invitation.settings.body_font === 'serif'
    ? 'ui-serif, Georgia, serif'
    : 'ui-sans-serif, system-ui, sans-serif');
const hostLine = computed(() => [props.invitation.event.host_name, props.invitation.event.second_host_name].filter(Boolean).join(' & ') || props.invitation.event.title);
const showEventTitle = computed(() => props.invitation.event.title?.trim().toLocaleLowerCase() !== hostLine.value.trim().toLocaleLowerCase());
</script>

<template>
    <article
        class="cinematic-document"
        :style="{
            '--cinematic-primary': invitation.settings.primary_color,
            '--cinematic-secondary': invitation.settings.secondary_color,
            '--cinematic-accent': invitation.settings.accent_color,
            '--cinematic-background': invitation.settings.background_color,
            '--cinematic-text': invitation.settings.text_color,
            '--cinematic-heading': headingFamily,
            color: invitation.settings.text_color,
            fontFamily: bodyFamily,
        }"
    >
        <header class="cinematic-hero">
            <div class="cinematic-hero-light" aria-hidden="true"></div>
            <p class="cinematic-eyebrow">{{ invitation.event.event_type || 'Private event' }}</p>
            <div class="cinematic-hero-identity">
                <p class="cinematic-chapter">01</p>
                <div>
                    <p class="cinematic-prelude">FormalEvites presents</p>
                    <h1>{{ hostLine }}</h1>
                    <p v-if="showEventTitle" class="cinematic-event-title">{{ invitation.event.title }}</p>
                    <p v-if="invitation.party_name" class="cinematic-party">An invitation for {{ invitation.party_name }}</p>
                </div>
            </div>
            <div v-if="invitation.event.main_date || invitation.event.start_time" class="cinematic-hero-date">
                <span>{{ invitation.event.main_date || 'Date to be confirmed' }}</span>
                <span v-if="invitation.event.start_time">{{ invitation.event.start_time }}<template v-if="invitation.event.end_time"> – <template v-if="invitation.event.end_date && invitation.event.end_date !== invitation.event.main_date">{{ invitation.event.end_date }}, </template>{{ invitation.event.end_time }}</template></span>
            </div>
        </header>

        <div class="cinematic-body">
            <section v-if="invitation.event.main_date" class="reveal-on-scroll cinematic-date-scene">
                <p class="cinematic-section-label">02 / THE MOMENT</p>
                <p class="cinematic-date">{{ invitation.event.main_date }}</p>
                <p class="cinematic-time">{{ invitation.event.start_time || 'Time to be confirmed' }}<template v-if="invitation.event.end_time"> – <template v-if="invitation.event.end_date && invitation.event.end_date !== invitation.event.main_date">{{ invitation.event.end_date }}, </template>{{ invitation.event.end_time }}</template><span v-if="invitation.event.timezone"> · {{ invitation.event.timezone }}</span></p>
            </section>

            <section v-if="invitation.event.activities?.length" class="reveal-on-scroll cinematic-section">
                <div class="cinematic-section-heading"><p class="cinematic-section-label">03 / PROGRAMME</p><h2>The schedule</h2></div>
                <ol class="cinematic-timeline">
                    <li v-for="(activity, index) in invitation.event.activities" :key="`${activity.title}-${index}`">
                        <span class="cinematic-number">{{ String(index + 1).padStart(2, '0') }}</span>
                        <div class="cinematic-timeline-main">
                            <h3>{{ activity.title }}</h3>
                            <p>{{ activity.date }} · {{ activity.start_time }}<template v-if="activity.end_time"> – <template v-if="activity.end_date !== activity.date">{{ activity.end_date }}, </template>{{ activity.end_time }}</template></p>
                        </div>
                        <div class="cinematic-timeline-detail">
                            <p v-if="activity.venue || activity.address">{{ activity.venue }}<template v-if="activity.address"> · {{ activity.address }}</template></p>
                            <p v-if="activity.description">{{ activity.description }}</p>
                            <p v-if="activity.location_notes">{{ activity.location_notes }}</p>
                        </div>
                    </li>
                </ol>
            </section>

            <section v-if="invitation.event.venue || invitation.event.address" class="reveal-on-scroll cinematic-location">
                <div><p class="cinematic-section-label">04 / LOCATION</p><h2>{{ invitation.event.venue || 'Venue to be confirmed' }}</h2></div>
                <div class="cinematic-location-copy"><p v-if="invitation.event.address">{{ invitation.event.address }}</p><a v-if="invitation.event.location_url" :href="invitation.event.location_url" target="_blank" rel="noopener noreferrer">Open location <span aria-hidden="true">↗</span></a></div>
            </section>

            <section v-if="invitation.event.guest_information || invitation.event.dress_code || invitation.event.parking_information || invitation.event.transportation_information || invitation.event.accommodation_information" class="reveal-on-scroll cinematic-section cinematic-notes">
                <div class="cinematic-section-heading"><p class="cinematic-section-label">05 / DETAILS</p><h2>For your evening</h2></div>
                <div class="cinematic-notes-grid">
                    <div v-if="invitation.event.guest_information" class="cinematic-note cinematic-note--wide"><h3>Guest information</h3><p>{{ invitation.event.guest_information }}</p></div>
                    <div v-if="invitation.event.dress_code" class="cinematic-note"><h3>Dress code</h3><p>{{ invitation.event.dress_code }}</p></div>
                    <div v-if="invitation.event.parking_information" class="cinematic-note"><h3>Parking</h3><p>{{ invitation.event.parking_information }}</p></div>
                    <div v-if="invitation.event.transportation_information" class="cinematic-note"><h3>Transportation</h3><p>{{ invitation.event.transportation_information }}</p></div>
                    <div v-if="invitation.event.accommodation_information" class="cinematic-note"><h3>Accommodation</h3><p>{{ invitation.event.accommodation_information }}</p></div>
                </div>
            </section>

            <footer class="reveal-on-scroll cinematic-closing"><span aria-hidden="true"></span><p>We look forward to celebrating with you.</p><strong>{{ hostLine }}</strong></footer>
        </div>
    </article>
</template>

<style scoped>
.cinematic-document { container-type: inline-size; max-width: 100%; overflow: hidden; background: var(--cinematic-background); border: 1px solid color-mix(in srgb, var(--cinematic-primary) 38%, transparent); box-shadow: 0 2rem 6rem rgba(0, 0, 0, 0.36); }
.cinematic-document :is(h1, h2, h3, p, a) { min-width: 0; overflow-wrap: anywhere; }
.cinematic-hero { position: relative; min-height: min(74rem, 88vh); padding: clamp(2rem, 7vw, 7rem); overflow: hidden; background: linear-gradient(135deg, color-mix(in srgb, var(--cinematic-secondary) 58%, #050609), var(--cinematic-background) 58%, #000); }
.cinematic-hero::before, .cinematic-hero::after { position: absolute; content: ''; pointer-events: none; }
.cinematic-hero::before { inset: 1.3rem; border: 1px solid color-mix(in srgb, var(--cinematic-primary) 32%, transparent); }
.cinematic-hero::after { right: -18%; bottom: -24%; width: min(52rem, 90cqi); aspect-ratio: 1; border: 1px solid color-mix(in srgb, var(--cinematic-primary) 26%, transparent); border-radius: 50%; box-shadow: 0 0 0 2.5rem color-mix(in srgb, var(--cinematic-primary) 4%, transparent), 0 0 0 7rem color-mix(in srgb, var(--cinematic-primary) 3%, transparent); }
.cinematic-hero-light { position: absolute; top: -35%; left: 34%; width: min(45rem, 88cqi); aspect-ratio: 1; border-radius: 50%; pointer-events: none; background: radial-gradient(circle, color-mix(in srgb, var(--cinematic-primary) 17%, transparent), transparent 64%); filter: blur(3rem); }
.cinematic-eyebrow, .cinematic-section-label { position: relative; z-index: 1; margin: 0; color: var(--cinematic-primary); font-size: 0.68rem; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; }
.cinematic-hero-identity { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(4rem, 0.36fr) minmax(0, 1fr); gap: clamp(1.5rem, 5vw, 5rem); align-items: start; margin-top: clamp(6rem, 16vw, 13rem); }
.cinematic-chapter { margin: -0.16em 0 0; color: color-mix(in srgb, var(--cinematic-text) 20%, transparent); font-family: var(--cinematic-heading); font-size: clamp(5rem, 15cqi, 12rem); line-height: 0.75; }
.cinematic-prelude { margin: 0; color: color-mix(in srgb, var(--cinematic-text) 62%, transparent); font-size: 0.82rem; letter-spacing: 0.13em; text-transform: uppercase; }
.cinematic-hero h1 { max-width: 11ch; margin: 1rem 0 0; color: var(--cinematic-text); font-family: var(--cinematic-heading); font-size: clamp(3.4rem, 8.5cqi, 8.8rem); font-weight: 400; line-height: 0.88; letter-spacing: -0.055em; }
.cinematic-event-title { max-width: 28ch; margin: 1.8rem 0 0; color: color-mix(in srgb, var(--cinematic-text) 82%, transparent); font-size: clamp(1rem, 1.6cqi, 1.35rem); line-height: 1.45; }
.cinematic-party { margin: 2rem 0 0; color: var(--cinematic-primary); font-size: 0.74rem; font-weight: 700; letter-spacing: 0.17em; text-transform: uppercase; }
.cinematic-hero-date { position: absolute; z-index: 1; right: clamp(2rem, 7vw, 7rem); bottom: clamp(2rem, 6vw, 6rem); display: flex; max-width: calc(100% - 4rem); flex-wrap: wrap; justify-content: flex-end; gap: 0.5rem 1.2rem; color: color-mix(in srgb, var(--cinematic-text) 82%, transparent); font-size: 0.82rem; letter-spacing: 0.08em; text-align: right; text-transform: uppercase; }
.cinematic-body { background: linear-gradient(180deg, color-mix(in srgb, var(--cinematic-background) 90%, #111827), var(--cinematic-background)); }
.cinematic-date-scene, .cinematic-section, .cinematic-location { position: relative; padding: clamp(3rem, 8vw, 8rem) clamp(2rem, 7vw, 7rem); }
.cinematic-date-scene { background: color-mix(in srgb, var(--cinematic-secondary) 40%, var(--cinematic-background)); border-top: 1px solid color-mix(in srgb, var(--cinematic-primary) 25%, transparent); border-bottom: 1px solid color-mix(in srgb, var(--cinematic-primary) 25%, transparent); }
.cinematic-date { max-width: 11ch; margin: 1.8rem 0 0; color: var(--cinematic-text); font-family: var(--cinematic-heading); font-size: clamp(3rem, 7.5cqi, 7rem); font-weight: 400; line-height: 0.88; letter-spacing: -0.045em; }
.cinematic-time { margin: 1.5rem 0 0; color: color-mix(in srgb, var(--cinematic-text) 78%, transparent); font-size: clamp(0.88rem, 1.7cqi, 1.1rem); line-height: 1.6; }
.cinematic-time span { color: var(--cinematic-primary); }
.cinematic-section-heading { display: grid; grid-template-columns: minmax(10rem, 0.7fr) 2fr; gap: 1.5rem; align-items: baseline; }
.cinematic-section-heading h2, .cinematic-location h2 { margin: 0; color: var(--cinematic-text); font-family: var(--cinematic-heading); font-size: clamp(2.5rem, 5.2cqi, 4.8rem); font-weight: 400; line-height: 0.92; letter-spacing: -0.04em; }
.cinematic-timeline { margin: clamp(2.5rem, 5vw, 5rem) 0 0; padding: 0; border-top: 1px solid color-mix(in srgb, var(--cinematic-text) 18%, transparent); list-style: none; }
.cinematic-timeline li { display: grid; grid-template-columns: 4rem minmax(10rem, 1fr) minmax(12rem, 0.9fr); gap: clamp(1rem, 3vw, 3rem); padding: clamp(1.7rem, 3vw, 2.7rem) 0; border-bottom: 1px solid color-mix(in srgb, var(--cinematic-text) 16%, transparent); }
.cinematic-number { color: var(--cinematic-primary); font-size: 0.74rem; font-weight: 700; letter-spacing: 0.15em; }
.cinematic-timeline h3 { margin: 0; color: var(--cinematic-text); font-family: var(--cinematic-heading); font-size: clamp(1.5rem, 2.7cqi, 2.35rem); font-weight: 400; line-height: 1; }
.cinematic-timeline p { margin: 0.65rem 0 0; color: color-mix(in srgb, var(--cinematic-text) 70%, transparent); font-size: 0.9rem; line-height: 1.6; }
.cinematic-timeline-detail p + p { margin-top: 0.75rem; }
.cinematic-location { display: grid; grid-template-columns: minmax(0, 1fr) minmax(12rem, 0.8fr); gap: clamp(2rem, 8vw, 8rem); background: linear-gradient(135deg, color-mix(in srgb, var(--cinematic-secondary) 48%, var(--cinematic-background)), var(--cinematic-background)); border-top: 1px solid color-mix(in srgb, var(--cinematic-primary) 22%, transparent); border-bottom: 1px solid color-mix(in srgb, var(--cinematic-primary) 22%, transparent); }
.cinematic-location-copy { align-self: end; color: color-mix(in srgb, var(--cinematic-text) 78%, transparent); line-height: 1.65; }
.cinematic-location-copy p { margin: 0; white-space: pre-line; }
.cinematic-location-copy a { display: inline-flex; gap: 0.6rem; margin-top: 1.5rem; color: var(--cinematic-primary); font-size: 0.78rem; font-weight: 700; letter-spacing: 0.12em; text-decoration: none; text-transform: uppercase; }
.cinematic-location-copy a:focus-visible { outline: 3px solid color-mix(in srgb, var(--cinematic-primary) 65%, transparent); outline-offset: 4px; }
.cinematic-notes { background: color-mix(in srgb, var(--cinematic-background) 86%, #070707); }
.cinematic-notes-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: clamp(2.5rem, 5vw, 5rem); border-top: 1px solid color-mix(in srgb, var(--cinematic-text) 16%, transparent); }
.cinematic-note { padding: 1.6rem 1.4rem 1.6rem 0; border-bottom: 1px solid color-mix(in srgb, var(--cinematic-text) 16%, transparent); }
.cinematic-note:nth-child(even) { padding-right: 0; padding-left: 1.4rem; border-left: 1px solid color-mix(in srgb, var(--cinematic-text) 16%, transparent); }
.cinematic-note--wide { grid-column: 1 / -1; padding-right: 0; }
.cinematic-note h3 { margin: 0; color: var(--cinematic-primary); font-size: 0.72rem; letter-spacing: 0.15em; text-transform: uppercase; }
.cinematic-note p { margin: 0.85rem 0 0; color: color-mix(in srgb, var(--cinematic-text) 76%, transparent); line-height: 1.65; white-space: pre-line; }
.cinematic-closing { padding: clamp(5rem, 12vw, 11rem) clamp(2rem, 7vw, 7rem); background: #050609; text-align: center; }
.cinematic-closing > span { display: block; width: min(12rem, 38%); height: 1px; margin: 0 auto 1.8rem; background: var(--cinematic-primary); }
.cinematic-closing p { margin: 0; color: color-mix(in srgb, var(--cinematic-text) 70%, transparent); font-family: var(--cinematic-heading); font-size: clamp(1.2rem, 2.1cqi, 1.7rem); font-style: italic; }
.cinematic-closing strong { display: block; margin-top: 0.65rem; color: var(--cinematic-text); font-family: var(--cinematic-heading); font-size: clamp(2rem, 3.8cqi, 3.5rem); font-weight: 400; }
@container (max-width: 46rem) { .cinematic-hero { min-height: 42rem; padding: 2rem clamp(1.5rem, 7cqi, 3rem) 3rem; } .cinematic-hero::before { inset: 0.85rem; } .cinematic-hero-identity, .cinematic-section-heading, .cinematic-location { grid-template-columns: 1fr; } .cinematic-hero-identity { gap: 2rem; margin-top: 5rem; } .cinematic-chapter { font-size: clamp(5rem, 15cqi, 7rem); } .cinematic-hero h1 { font-size: clamp(3.1rem, 10cqi, 4.8rem); } .cinematic-hero-date { position: relative; right: auto; bottom: auto; justify-content: flex-start; margin-top: 4rem; text-align: left; } .cinematic-date-scene, .cinematic-section, .cinematic-location { padding: clamp(2.7rem, 7cqi, 4rem) clamp(1.5rem, 7cqi, 3rem); } .cinematic-timeline li { grid-template-columns: 2.5rem minmax(0, 1fr); gap: 0.7rem; } .cinematic-timeline-detail { grid-column: 2; } .cinematic-notes-grid { grid-template-columns: 1fr; } .cinematic-note, .cinematic-note:nth-child(even) { padding: 1.4rem 0; border-left: 0; } .cinematic-note--wide { grid-column: auto; } .cinematic-closing { padding: 5rem clamp(1.5rem, 7cqi, 3rem); } }
</style>
