<script setup lang="ts">
defineProps<{ invitation: any }>();
</script>

<template>
    <article class="romantic-invitation-document" :style="{ color: invitation.settings.text_color }">
        <header class="romantic-hero" :style="{ backgroundColor: invitation.settings.background_color }">
            <svg aria-hidden="true" viewBox="0 0 180 180" class="hero-botanical hero-botanical--top"><path fill="currentColor" d="M90 0c10 48 28 79 78 108-51 7-78 28-93 72 0-48-22-82-75-105C44 67 73 42 90 0Z"/><path fill="none" stroke="currentColor" stroke-width="2" d="M21 22c57 43 74 88 67 144M68 57C41 47 24 54 8 77M78 91c33-7 57 9 75 36"/></svg>
            <svg aria-hidden="true" viewBox="0 0 180 180" class="hero-botanical hero-botanical--bottom"><path fill="currentColor" d="M90 0c10 48 28 79 78 108-51 7-78 28-93 72 0-48-22-82-75-105C44 67 73 42 90 0Z"/><path fill="none" stroke="currentColor" stroke-width="2" d="M21 22c57 43 74 88 67 144M68 57C41 47 24 54 8 77M78 91c33-7 57 9 75 36"/></svg>
            <p v-if="invitation.event.event_type" class="romantic-eyebrow">{{ invitation.event.event_type }}</p>
            <p class="romantic-intro-line">Together with their families</p>
            <h1 class="romantic-hosts" :style="{ color: invitation.settings.primary_color }">{{ invitation.event.host_name }}<span v-if="invitation.event.second_host_name"> <span aria-hidden="true" class="romantic-ampersand">&amp;</span> {{ invitation.event.second_host_name }}</span></h1>
            <p v-if="invitation.party_name" class="romantic-party">Invitation for <strong>{{ invitation.party_name }}</strong></p>
            <span class="romantic-rule" :style="{ backgroundColor: invitation.settings.accent_color }"></span>
            <h2 class="romantic-event-title">{{ invitation.event.title }}</h2>
        </header>

        <div class="romantic-document-body">
            <section v-if="invitation.event.main_date" class="reveal-on-scroll romantic-date-section">
                <p class="romantic-eyebrow">Save the date</p>
                <p class="romantic-date">{{ invitation.event.main_date }}</p>
                <p v-if="invitation.event.start_time || invitation.event.end_time" class="romantic-time">{{ invitation.event.start_time || 'Time to be confirmed' }}<span v-if="invitation.event.end_time"> – <span v-if="invitation.event.end_date && invitation.event.end_date !== invitation.event.main_date">{{ invitation.event.end_date }}, </span>{{ invitation.event.end_time }}</span></p>
                <p class="romantic-timezone">{{ invitation.event.timezone }}</p>
            </section>

            <section v-if="invitation.event.activities?.length" class="reveal-on-scroll romantic-section">
                <div class="romantic-section-heading"><p class="romantic-eyebrow">The celebration</p><h2>Schedule</h2></div>
                <ol class="romantic-timeline">
                    <li v-for="(activity, index) in invitation.event.activities" :key="`${activity.title}-${index}`">
                        <span class="romantic-timeline-dot" :style="{ backgroundColor: invitation.settings.accent_color }"></span>
                        <h3>{{ activity.title }}</h3>
                        <p class="romantic-meta">{{ activity.date }} · {{ activity.start_time }}<span v-if="activity.end_time"> – <span v-if="activity.end_date !== activity.date">{{ activity.end_date }}, </span>{{ activity.end_time }}</span></p>
                        <p v-if="activity.venue || activity.address" class="romantic-meta">{{ activity.venue }}<span v-if="activity.address"> · {{ activity.address }}</span></p>
                        <p v-if="activity.description" class="romantic-copy">{{ activity.description }}</p>
                    </li>
                </ol>
            </section>

            <section v-if="invitation.event.venue || invitation.event.address" class="reveal-on-scroll romantic-location">
                <p class="romantic-eyebrow">Location</p>
                <h2>{{ invitation.event.venue || 'Venue to be confirmed' }}</h2>
                <p v-if="invitation.event.address" class="romantic-copy">{{ invitation.event.address }}</p>
                <a v-if="invitation.event.location_url" :href="invitation.event.location_url" target="_blank" rel="noopener noreferrer" class="romantic-location-link">Open location <span aria-hidden="true">→</span></a>
            </section>

            <section v-if="invitation.event.dress_code || invitation.event.parking_information || invitation.event.transportation_information || invitation.event.accommodation_information || invitation.event.guest_information" class="reveal-on-scroll romantic-details">
                <div v-if="invitation.event.guest_information" class="romantic-detail-card romantic-detail-card--wide"><h2>Guest information</h2><p>{{ invitation.event.guest_information }}</p></div>
                <div v-if="invitation.event.dress_code" class="romantic-detail-card"><h2>Dress code</h2><p>{{ invitation.event.dress_code }}</p></div>
                <div v-if="invitation.event.parking_information" class="romantic-detail-card"><h2>Parking</h2><p>{{ invitation.event.parking_information }}</p></div>
                <div v-if="invitation.event.transportation_information" class="romantic-detail-card"><h2>Transportation</h2><p>{{ invitation.event.transportation_information }}</p></div>
                <div v-if="invitation.event.accommodation_information" class="romantic-detail-card"><h2>Accommodation</h2><p>{{ invitation.event.accommodation_information }}</p></div>
            </section>

            <footer v-if="invitation.event.host_name" class="reveal-on-scroll romantic-closing">
                <span class="romantic-rule" :style="{ backgroundColor: invitation.settings.accent_color }"></span>
                <p>With love,</p>
                <p class="romantic-closing-names">{{ invitation.event.host_name }}<span v-if="invitation.event.second_host_name"> &amp; {{ invitation.event.second_host_name }}</span></p>
            </footer>
        </div>
    </article>
</template>

<style scoped>
.romantic-invitation-document { overflow: hidden; border: 1px solid rgba(194, 138, 145, 0.4); border-radius: 2rem; background: #fffaf8; box-shadow: 0 1.5rem 4.4rem rgba(91, 38, 50, 0.16); }
.romantic-hero { position: relative; overflow: hidden; padding: clamp(4rem, 10vw, 7.5rem) clamp(1.5rem, 7vw, 5.5rem); text-align: center; }
.hero-botanical { position: absolute; width: clamp(9rem, 20vw, 14rem); height: auto; color: rgba(155, 93, 106, 0.22); pointer-events: none; }
.hero-botanical--top { top: -1.5rem; left: -1.5rem; }
.hero-botanical--bottom { right: -1.5rem; bottom: -1.5rem; transform: rotate(180deg); }
.romantic-eyebrow { position: relative; margin: 0; font-size: 0.7rem; font-weight: 600; letter-spacing: 0.28em; text-transform: uppercase; opacity: 0.68; }
.romantic-intro-line { position: relative; margin: 2rem 0 0; font-family: Georgia, serif; font-size: clamp(1.05rem, 2vw, 1.3rem); font-style: italic; opacity: 0.75; }
.romantic-hosts { position: relative; margin: 1rem 0 0; font-family: Georgia, serif; font-size: clamp(2.3rem, 6vw, 4.5rem); font-weight: 400; line-height: 1.08; }
.romantic-ampersand { font-size: 0.65em; font-style: italic; }
.romantic-party { position: relative; margin: 2rem 0 0; font-size: 1rem; }
.romantic-rule { display: block; width: 5rem; height: 1px; margin: 2rem auto; }
.romantic-event-title { position: relative; margin: 0; font-family: Georgia, serif; font-size: clamp(1.9rem, 4vw, 3rem); font-weight: 400; }
.romantic-document-body { padding: clamp(2.5rem, 6vw, 5rem); }
.romantic-date-section { text-align: center; }
.romantic-date { margin: 0.8rem 0 0; font-family: Georgia, serif; font-size: clamp(2rem, 4vw, 3rem); }
.romantic-time { margin: 0.5rem 0 0; font-size: 1.08rem; }
.romantic-timezone { margin: 0.4rem 0 0; font-size: 0.85rem; opacity: 0.6; }
.romantic-section, .romantic-location, .romantic-details, .romantic-closing { margin-top: clamp(3.5rem, 7vw, 5.5rem); }
.romantic-section-heading, .romantic-closing { text-align: center; }
.romantic-section-heading h2, .romantic-location h2, .romantic-detail-card h2 { margin: 0.7rem 0 0; font-family: Georgia, serif; font-size: clamp(1.7rem, 3vw, 2.3rem); font-weight: 400; }
.romantic-timeline { max-width: 36rem; margin: 2rem auto 0; padding: 0 0 0 1.7rem; border-left: 1px solid rgba(185, 121, 133, 0.4); list-style: none; }
.romantic-timeline li { position: relative; padding: 0 0 1.8rem; }
.romantic-timeline li:last-child { padding-bottom: 0; }
.romantic-timeline-dot { position: absolute; top: 0.45rem; left: -2.06rem; width: 0.72rem; height: 0.72rem; border: 3px solid #fffaf8; border-radius: 50%; box-shadow: 0 0 0 1px rgba(185, 121, 133, 0.35); }
.romantic-timeline h3 { margin: 0; font-family: Georgia, serif; font-size: 1.35rem; font-weight: 400; }
.romantic-meta { margin: 0.35rem 0 0; font-size: 0.9rem; opacity: 0.72; }
.romantic-copy { margin: 0.85rem 0 0; white-space: pre-line; line-height: 1.65; }
.romantic-location { padding: clamp(1.75rem, 4vw, 3rem); border: 1px solid rgba(194, 138, 145, 0.36); border-radius: 1.35rem; background: linear-gradient(145deg, rgba(255, 252, 248, 0.95), rgba(247, 231, 225, 0.72)); text-align: center; }
.romantic-location-link { display: inline-flex; gap: 0.5rem; margin-top: 1.3rem; padding: 0.7rem 1.15rem; border: 1px solid currentColor; border-radius: 9999px; color: inherit; font-size: 0.9rem; font-weight: 600; text-decoration: none; }
.romantic-location-link:focus-visible { outline: 3px solid rgba(146, 54, 73, 0.35); outline-offset: 3px; }
.romantic-details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
.romantic-detail-card { padding: 1.5rem; border: 1px solid rgba(194, 138, 145, 0.28); border-radius: 1.1rem; background: rgba(255, 252, 248, 0.84); }
.romantic-detail-card--wide { grid-column: 1 / -1; }
.romantic-detail-card h2 { font-size: 1.25rem; }
.romantic-detail-card p { margin: 0.65rem 0 0; white-space: pre-line; line-height: 1.6; }
.romantic-closing { padding-top: 1rem; }
.romantic-closing .romantic-rule { margin-bottom: 1.3rem; }
.romantic-closing p { margin: 0; font-family: Georgia, serif; font-size: 1.1rem; font-style: italic; }
.romantic-closing .romantic-closing-names { margin-top: 0.35rem; font-size: 1.5rem; font-style: normal; }
@media (max-width: 640px) { .romantic-invitation-document { border-radius: 1.35rem; } .romantic-details { grid-template-columns: 1fr; } .romantic-detail-card--wide { grid-column: auto; } .romantic-document-body { padding: 2.5rem 1.5rem; } }
</style>
