<script setup lang="ts">
import InvitationPreviewRenderer from '@/Components/InvitationPreviewRenderer.vue';
import EditorialLuxury from '@/Components/InvitationTemplates/EditorialLuxury.vue';
import ModernCinematic from '@/Components/InvitationTemplates/ModernCinematic.vue';
import PublicRsvpExperience from '@/Components/PublicRsvpExperience.vue';
import PublicRomanticFloral from '@/Components/InvitationTemplates/PublicRomanticFloral.vue';
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ invitation: any; party: any; rsvp: any; meals: any[]; closed: boolean; confirmation: string | null }>();
const opened = ref(false);
const reducedMotion = ref(false);
const parallax = ref(0);
const audio = ref<HTMLAudioElement | null>(null);
const isMuted = ref(true);
// Media is intentionally a trusted Template component concern. No Event-supplied
// URLs are accepted; this Stage has no local production media asset yet.
const introVideoSource = ref<string | null>(props.invitation.experience?.intro_video || null);
const audioSource: string | null = props.invitation.experience?.audio || null;
const isRomanticFloral = computed(() => props.invitation.template?.component_key === 'romantic-floral');
const isEditorialLuxury = computed(() => props.invitation.template?.component_key === 'editorial-luxury');
const isModernCinematic = computed(() => props.invitation.template?.component_key === 'modern-cinematic');
const cinematicHosts = computed(() => [props.invitation.event.host_name, props.invitation.event.second_host_name].filter(Boolean).join(' & ') || props.invitation.event.title);
const cinematicShowTitle = computed(() => props.invitation.event.title?.trim().toLocaleLowerCase() !== cinematicHosts.value.trim().toLocaleLowerCase());
let mediaQuery: MediaQueryList | null = null;
let animationFrame: number | null = null;
let observer: IntersectionObserver | null = null;
const updateMotion = () => { reducedMotion.value = mediaQuery?.matches ?? false; };
const onScroll = () => { if (reducedMotion.value || animationFrame !== null) return; animationFrame = requestAnimationFrame(() => { parallax.value = Math.min(window.scrollY * 0.08, 32); animationFrame = null; }); };
const setupReveals = () => { const elements = document.querySelectorAll<HTMLElement>('.reveal-on-scroll'); if (reducedMotion.value || !('IntersectionObserver' in window)) { elements.forEach((element) => element.classList.add('is-visible')); return; } observer?.disconnect(); observer = new IntersectionObserver((entries) => entries.forEach((entry) => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer?.unobserve(entry.target); } }), { threshold: 0.12 }); elements.forEach((element) => observer?.observe(element)); };
const openInvitation = async () => { opened.value = true; if (audioSource && audio.value) { audio.value.muted = false; audio.value.play().then(() => { isMuted.value = false; }).catch(() => { isMuted.value = true; }); } await nextTick(); setupReveals(); document.getElementById('invitation-content')?.focus(); };
const toggleAudio = () => { if (!audio.value) return; audio.value.muted = !audio.value.muted; isMuted.value = audio.value.muted; if (!audio.value.muted) audio.value.play().catch(() => { isMuted.value = true; audio.value!.muted = true; }); };
onMounted(() => { mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)'); updateMotion(); mediaQuery.addEventListener('change', updateMotion); window.addEventListener('scroll', onScroll, { passive: true }); });
onBeforeUnmount(() => { mediaQuery?.removeEventListener('change', updateMotion); window.removeEventListener('scroll', onScroll); observer?.disconnect(); if (animationFrame !== null) cancelAnimationFrame(animationFrame); audio.value?.pause(); });
</script>

<template>
    <Head :title="invitation.event.title" />
    <main class="min-h-screen overflow-x-hidden bg-[#f8f0ec] text-stone-800" :class="{ 'motion-reduce': reducedMotion }">
        <audio v-if="audioSource" ref="audio" :src="audioSource" loop preload="none" muted />
        <Transition :name="isModernCinematic ? 'cinematic-opening' : isEditorialLuxury ? 'editorial-opening' : 'card-opening'" mode="out-in">
        <section v-if="!opened" class="romantic-intro-scene relative flex min-h-[100dvh] items-center justify-center overflow-hidden px-4 py-6 sm:px-8" :class="{ 'cinematic-intro-scene': isModernCinematic }" :style="{ transform: reducedMotion ? undefined : `translateY(${parallax}px)` }">
            <video v-if="introVideoSource" class="absolute inset-0 h-full w-full object-cover" autoplay muted playsinline loop @error="introVideoSource = null"><source :src="introVideoSource" type="video/mp4"></video>
            <div v-if="isRomanticFloral" class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_#fffdf7_0%,_#f7e8df_40%,_#d6a7a8_100%)]"></div>
            <div v-else-if="isEditorialLuxury" class="editorial-cover-background absolute inset-0"></div>
            <div v-else-if="isModernCinematic" class="cinematic-intro-background absolute inset-0"><span class="cinematic-intro-orb cinematic-intro-orb--one"></span><span class="cinematic-intro-orb cinematic-intro-orb--two"></span><span class="cinematic-intro-grid"></span></div>
            <div v-else class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_#fdf2f2_0%,_#f7e5e5_45%,_#d6a7a8_100%)]"></div>
            <div v-if="isRomanticFloral" class="paper-grain absolute inset-0"></div>
            <svg v-if="isRomanticFloral" aria-hidden="true" preserveAspectRatio="xMidYMid meet" viewBox="0 0 420 560" class="scene-botanical scene-botanical--top"><path fill="currentColor" d="M168 0c20 78 52 133 126 180-77 10-125 39-157 91 4-86-28-146-100-190 64-8 106-34 131-81Z"/><path fill="none" stroke="currentColor" stroke-width="3" d="M68 30c80 77 117 180 96 326M163 132c-49-8-88 13-117 57M145 219c55-11 106 15 142 69M116 295c-42 11-72 41-86 85"/></svg>
            <svg v-if="isRomanticFloral" aria-hidden="true" preserveAspectRatio="xMidYMid meet" viewBox="0 0 420 560" class="scene-botanical scene-botanical--bottom"><path fill="currentColor" d="M168 0c20 78 52 133 126 180-77 10-125 39-157 91 4-86-28-146-100-190 64-8 106-34 131-81Z"/><path fill="none" stroke="currentColor" stroke-width="3" d="M68 30c80 77 117 180 96 326M163 132c-49-8-88 13-117 57M145 219c55-11 106 15 142 69M116 295c-42 11-72 41-86 85"/></svg>
            <button v-if="isRomanticFloral" type="button" class="romantic-envelope group relative z-10 block text-left focus:outline-none" :aria-label="`Open invitation for ${invitation.party_name || invitation.event.title}`" @click="openInvitation">
                <span class="absolute inset-x-[5%] bottom-[-1.25rem] h-12 rounded-[50%] bg-rose-950/20 blur-2xl transition duration-500 group-hover:scale-110"></span>
                <span class="romantic-envelope-shell relative block rounded-[1.35rem] bg-[#ead8c2] shadow-[0_2rem_5rem_rgba(74,23,35,0.3)] transition duration-500 ease-out group-hover:-translate-y-2 group-hover:shadow-[0_2.7rem_6rem_rgba(74,23,35,0.38)] group-focus-visible:-translate-y-2 group-focus-visible:ring-4 group-focus-visible:ring-rose-700/40">
                    <span class="absolute inset-x-0 top-0 h-[44%] origin-top rounded-t-[1.1rem] bg-[linear-gradient(145deg,_#f9f0e5,_#dcc0aa)] shadow-sm transition duration-700 ease-out group-hover:[transform:rotateX(7deg)]"></span>
                    <span class="romantic-invitation-card relative block overflow-hidden rounded-[0.95rem] border border-[#caa57a]/70 bg-[#fffdf8] text-center shadow-inner">
                        <span class="pointer-events-none absolute inset-0 opacity-70 [background-image:radial-gradient(rgba(104,62,55,0.08)_0.7px,transparent_0.7px)] [background-size:7px_7px]"></span>
                        <svg aria-hidden="true" preserveAspectRatio="xMidYMid meet" viewBox="0 0 180 180" class="card-botanical card-botanical--top"><path fill="currentColor" d="M90 0c10 48 28 79 78 108-51 7-78 28-93 72 0-48-22-82-75-105C44 67 73 42 90 0Z"/><path fill="none" stroke="currentColor" stroke-width="2" d="M21 22c57 43 74 88 67 144M68 57C41 47 24 54 8 77M78 91c33-7 57 9 75 36"/></svg>
                        <svg aria-hidden="true" preserveAspectRatio="xMidYMid meet" viewBox="0 0 180 180" class="card-botanical card-botanical--bottom"><path fill="currentColor" d="M90 0c10 48 28 79 78 108-51 7-78 28-93 72 0-48-22-82-75-105C44 67 73 42 90 0Z"/><path fill="none" stroke="currentColor" stroke-width="2" d="M21 22c57 43 74 88 67 144M68 57C41 47 24 54 8 77M78 91c33-7 57 9 75 36"/></svg>
                        <span class="relative flex min-h-[25rem] flex-col items-center justify-center"><span class="text-[0.62rem] font-semibold uppercase tracking-[0.42em] text-rose-950/60">FormalEvites</span><span class="mt-8 font-serif text-lg italic text-stone-600 sm:text-xl">You&rsquo;re invited</span><span class="mt-5 font-serif text-[clamp(2.5rem,6vw,5.2rem)] leading-[0.95] text-rose-950" :style="{ fontFamily: invitation.settings.heading_font === 'modern_sans' ? 'ui-sans-serif, system-ui, sans-serif' : 'Georgia, serif' }">{{ invitation.event.title }}</span><span v-if="invitation.party_name" class="mt-8 text-xs uppercase tracking-[0.22em] text-stone-500">For</span><span v-if="invitation.party_name" class="mt-2 font-serif text-2xl text-stone-800 sm:text-3xl">{{ invitation.party_name }}</span><span class="mt-10 inline-flex min-h-12 items-center rounded-full bg-rose-950 px-7 py-3 text-sm font-semibold tracking-[0.13em] text-white shadow-lg transition duration-300 group-hover:bg-rose-800 group-focus-visible:bg-rose-800">Tap to open <span aria-hidden="true" class="ml-2 text-base">→</span></span><span class="mt-4 text-xs text-stone-500">{{ reducedMotion ? 'Reduced motion is enabled.' : 'Open your invitation' }}</span></span>
                    </span>
                    <span class="absolute bottom-[-0.2rem] left-1/2 grid h-14 w-14 -translate-x-1/2 place-items-center rounded-full border-4 border-[#e8d2b0] bg-rose-900 text-xs font-serif text-[#f9e8ca] shadow-lg">FE</span>
                </span>
            </button>
            <button v-else-if="isEditorialLuxury" type="button" class="editorial-cover" :aria-label="`Open invitation for ${invitation.party_name || invitation.event.title}`" @click="openInvitation"><span class="editorial-cover-line editorial-cover-line--top"></span><span class="editorial-cover-line editorial-cover-line--side"></span><span class="editorial-cover-content"><span class="editorial-cover-kicker">{{ invitation.event.event_type || 'Private event' }}</span><span class="editorial-cover-title">{{ invitation.event.host_name || invitation.event.title }}<template v-if="invitation.event.second_host_name"> <em>&amp;</em> {{ invitation.event.second_host_name }}</template></span><span class="editorial-cover-date">{{ invitation.event.main_date || invitation.event.title }}</span><span v-if="invitation.party_name" class="editorial-cover-party">Invitation for {{ invitation.party_name }}</span><span class="editorial-cover-action">Open invitation <span aria-hidden="true">→</span></span></span><span class="editorial-cover-volume">{{ invitation.event.main_date?.slice(-4) || '01' }}</span></button>
            <button v-else-if="isModernCinematic" type="button" class="cinematic-card group" :aria-label="`Open invitation for ${invitation.party_name || invitation.event.title}`" @click="openInvitation">
                <span class="cinematic-card-reflection" aria-hidden="true"></span><span class="cinematic-card-line cinematic-card-line--top" aria-hidden="true"></span><span class="cinematic-card-line cinematic-card-line--bottom" aria-hidden="true"></span>
                <span class="cinematic-card-content"><span class="cinematic-card-brand">FormalEvites</span><span class="cinematic-card-kicker">{{ invitation.event.event_type || 'Private event' }}</span><span class="cinematic-card-hosts">{{ cinematicHosts }}</span><span v-if="cinematicShowTitle" class="cinematic-card-title">{{ invitation.event.title }}</span><span v-if="invitation.party_name" class="cinematic-card-party">For {{ invitation.party_name }}</span><span class="cinematic-card-action">Open invitation <span aria-hidden="true">→</span></span></span>
            </button>
            <div v-else class="relative z-10 w-full max-w-md rounded-3xl bg-white/90 p-10 text-center shadow-2xl"><p class="text-xs font-semibold uppercase tracking-[0.35em] text-rose-900/70">FormalEvites</p><h1 class="mt-8 font-serif text-4xl text-rose-900">{{ invitation.event.title }}</h1><button type="button" class="mt-9 rounded-full bg-rose-900 px-7 py-3 font-medium text-white focus:outline-none focus:ring-4 focus:ring-rose-300" @click="openInvitation">Open invitation</button></div>
        </section>
        <section v-else id="invitation-content" tabindex="-1" class="invitation-reveal relative px-4 py-8 focus:outline-none sm:px-6 sm:py-12" :class="{ 'romantic-opened-scene': isRomanticFloral, 'editorial-opened-scene': isEditorialLuxury, 'cinematic-opened-scene': isModernCinematic }"><div v-if="isRomanticFloral" class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(ellipse_at_top,_#f7dede,_transparent_70%)]"></div><div v-else-if="isModernCinematic" class="cinematic-opened-aurora pointer-events-none absolute inset-x-0 top-0 h-[52rem]" :style="{ transform: reducedMotion ? undefined : `translateY(${parallax}px)` }"></div><button v-if="audioSource" type="button" class="fixed right-4 top-4 z-10 min-h-11 rounded-full bg-white/90 px-4 text-sm shadow focus:outline-none focus:ring-2 focus:ring-rose-500" :aria-pressed="!isMuted" @click="toggleAudio">{{ isMuted ? 'Enable sound' : 'Mute sound' }}</button><div class="relative mx-auto max-w-3xl space-y-8" :class="{ 'romantic-opened-stack': isRomanticFloral, 'editorial-opened-stack': isEditorialLuxury, 'cinematic-opened-stack': isModernCinematic }"><PublicRomanticFloral v-if="isRomanticFloral" :invitation="invitation" /><EditorialLuxury v-else-if="isEditorialLuxury" :invitation="invitation" /><ModernCinematic v-else-if="isModernCinematic" :invitation="invitation" /><InvitationPreviewRenderer v-else :invitation="invitation" /><section id="rsvp" class="scroll-mt-6" :class="{ 'romantic-rsvp-area': isRomanticFloral, 'editorial-rsvp-area': isEditorialLuxury, 'cinematic-rsvp-area': isModernCinematic }"><div class="mb-4 text-center" :class="{ 'cinematic-rsvp-heading': isModernCinematic }"><p class="text-xs font-semibold uppercase tracking-[0.28em] text-rose-900/60">Please respond</p><p class="mt-2 text-sm text-stone-600">{{ invitation.event.rsvp_deadline ? `RSVP by ${invitation.event.rsvp_deadline}` : 'Please let us know if you can attend.' }}</p></div><PublicRsvpExperience :party="party" :rsvp="rsvp" :meals="meals" :closed="closed" :confirmation="confirmation" :romantic="isRomanticFloral" :editorial="isEditorialLuxury" :cinematic="isModernCinematic" /></section></div></section>
        </Transition>
    </main>
</template>

<style scoped>
@keyframes invitation-reveal { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
.invitation-reveal { animation: invitation-reveal 650ms ease-out both; }
.romantic-intro-scene { min-height: 100dvh; background: radial-gradient(ellipse at top, #fffdf7 0%, #f7e8df 40%, #d6a7a8 100%); }
.romantic-envelope { width: min(calc(100vw - 2rem), 44rem); max-width: 44rem; }
.romantic-envelope-shell { padding: clamp(0.6rem, 1.2vw, 0.9rem); background: #ead8c2; box-shadow: 0 2rem 5rem rgba(74, 23, 35, 0.3); }
.romantic-invitation-card { min-height: clamp(30rem, 62vw, 39rem); padding: clamp(2.5rem, 7vw, 6rem) clamp(1.5rem, 7vw, 6.5rem); }
.romantic-invitation-card > .relative.flex { min-height: 25rem; }
.scene-botanical, .card-botanical { position: absolute; display: block; pointer-events: none; overflow: hidden; color: rgba(144, 86, 100, 0.32); }
.scene-botanical { width: clamp(14rem, 28vw, 24rem); height: auto; z-index: 1; }
.scene-botanical--top { top: -4rem; left: -4rem; }
.scene-botanical--bottom { right: -4rem; bottom: -5rem; transform: rotate(180deg); color: rgba(115, 61, 75, 0.3); }
.card-botanical { width: clamp(8rem, 18vw, 12rem); height: auto; z-index: 1; color: rgba(153, 86, 103, 0.27); }
.card-botanical--top { top: -1.25rem; left: -1.25rem; }
.card-botanical--bottom { right: -1.25rem; bottom: -1.25rem; transform: rotate(180deg); }
.romantic-invitation-card > span.relative.flex > span:nth-child(3) { color: #4a1723; font-size: clamp(2.5rem, 6vw, 5.2rem); }
.romantic-invitation-card > span.relative.flex > span[class*="mt-10"] { position: relative; z-index: 2; display: inline-flex; align-items: center; min-height: 3rem; margin-top: 2.5rem; border-radius: 9999px; padding: 0.75rem 1.75rem; background: #4a1723; color: #fff; box-shadow: 0 0.6rem 1.2rem rgba(74, 23, 35, 0.22); }
.romantic-envelope-shell > span:last-child { position: absolute; bottom: -0.2rem; left: 50%; z-index: 3; display: grid; width: 3.5rem; height: 3.5rem; transform: translateX(-50%); place-items: center; border: 4px solid #e8d2b0; border-radius: 50%; background: #6f2334; color: #f9e8ca; }
.paper-grain { opacity: 0.28; background-image: radial-gradient(rgba(112, 66, 58, 0.18) 0.55px, transparent 0.65px), linear-gradient(120deg, rgba(255,255,255,0.45), transparent 60%); background-size: 6px 6px, 100% 100%; mix-blend-mode: multiply; }
.card-opening-leave-active { transition: opacity 420ms ease-in, transform 420ms cubic-bezier(.4,0,.2,1); }
.card-opening-leave-to { opacity: 0; transform: scale(1.035) translateY(-1.5rem); }
.card-opening-enter-active { transition: opacity 650ms ease-out, transform 650ms cubic-bezier(.2,.8,.2,1); }
.card-opening-enter-from { opacity: 0; transform: translateY(1.25rem); }
.editorial-cover-background { background: #171716; }
.editorial-cover { position: relative; z-index: 2; display: block; width: min(calc(100vw - 2rem), 76rem); min-height: min(80dvh, 46rem); padding: clamp(2rem, 6vw, 5rem); overflow: hidden; border: 1px solid rgba(247, 242, 232, 0.52); background: #171716; color: #f7f2e8; text-align: left; box-shadow: 0 2rem 5rem rgba(0, 0, 0, 0.34); }
.editorial-cover:focus-visible { outline: 4px solid rgba(213, 183, 122, 0.8); outline-offset: 5px; }
.editorial-cover-content { position: relative; z-index: 2; display: flex; min-height: calc(min(80dvh, 46rem) - clamp(4rem, 12vw, 10rem)); flex-direction: column; align-items: flex-start; justify-content: center; max-width: 47rem; }
.editorial-cover-kicker, .editorial-cover-party { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.23em; text-transform: uppercase; }
.editorial-cover-title { max-width: 11ch; margin-top: 1.3rem; font-family: Georgia, serif; font-size: clamp(3.3rem, 9vw, 8.5rem); font-weight: 400; line-height: 0.84; letter-spacing: -0.06em; }
.editorial-cover-title em { font-size: 0.48em; font-weight: 400; }
.editorial-cover-date { margin-top: 2.6rem; font-size: clamp(1rem, 2vw, 1.35rem); letter-spacing: 0.02em; }
.editorial-cover-party { margin-top: 0.9rem; color: #d5b77a; }
.editorial-cover-action { display: inline-flex; gap: 0.65rem; margin-top: auto; padding-top: 2.5rem; font-size: 0.77rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; }
.editorial-cover-volume { position: absolute; right: clamp(1.2rem, 5vw, 4rem); bottom: clamp(0.6rem, 2vw, 1.7rem); color: rgba(247, 242, 232, 0.1); font-family: Georgia, serif; font-size: clamp(8rem, 22vw, 18rem); line-height: 0.7; }
.editorial-cover-line { position: absolute; display: block; background: rgba(247, 242, 232, 0.38); }
.editorial-cover-line--top { top: clamp(2rem, 6vw, 5rem); right: clamp(2rem, 6vw, 5rem); width: min(28vw, 18rem); height: 1px; }
.editorial-cover-line--side { top: clamp(2rem, 6vw, 5rem); right: clamp(2rem, 6vw, 5rem); width: 1px; height: min(23vw, 13rem); }
.editorial-opening-leave-active { transition: opacity 520ms ease-in, clip-path 720ms cubic-bezier(.7,0,.2,1), transform 720ms cubic-bezier(.7,0,.2,1); }
.editorial-opening-leave-to { opacity: 0; clip-path: inset(0 0 0 100%); transform: translateX(-1.25rem); }
.editorial-opening-enter-active { transition: opacity 620ms ease-out, transform 620ms cubic-bezier(.2,.8,.2,1); }
.editorial-opening-enter-from { opacity: 0; transform: translateY(1rem); }
:global(.reveal-on-scroll) { opacity: 1; transform: none; }
:global(.reveal-on-scroll.is-visible) { animation: section-reveal 600ms ease-out both; }
.romantic-opened-scene { background: radial-gradient(ellipse at top, #f8e7e2 0%, #fdf8f4 42%, #f4e5df 100%); }
.romantic-opened-stack { max-width: 52rem; }
.romantic-rsvp-area { padding: clamp(1.75rem, 4vw, 3rem); border: 1px solid rgba(194, 138, 145, 0.34); border-radius: 2rem; background: rgba(255, 250, 248, 0.8); box-shadow: 0 1.2rem 3rem rgba(91, 38, 50, 0.1); }
.romantic-rsvp-area > div:first-child { margin-bottom: 1.75rem; }
.editorial-opened-scene { background: #f2eee6; }
.editorial-opened-stack { max-width: 76rem; }
.editorial-rsvp-area { padding: clamp(1.5rem, 5vw, 4rem); border-top: 1px solid rgba(31, 29, 26, 0.35); border-bottom: 1px solid rgba(31, 29, 26, 0.35); background: rgba(247, 242, 232, 0.58); }
.editorial-rsvp-area > div:first-child { margin-bottom: 2rem; text-align: left; }
.cinematic-intro-scene { background: #05070c; }
.cinematic-intro-background { overflow: hidden; background: radial-gradient(ellipse at 72% 8%, rgba(36, 59, 103, 0.7), transparent 43%), radial-gradient(ellipse at 18% 94%, rgba(145, 99, 37, 0.22), transparent 43%), linear-gradient(135deg, #080b13 0%, #05060a 46%, #11192c 100%); }
.cinematic-intro-background::before, .cinematic-intro-background::after { position: absolute; content: ''; pointer-events: none; }
.cinematic-intro-background::before { inset: 1.2rem; border: 1px solid rgba(230, 218, 188, 0.13); }
.cinematic-intro-background::after { inset: 0; background: linear-gradient(110deg, rgba(0, 0, 0, 0.68), transparent 48%, rgba(0, 0, 0, 0.34)); }
.cinematic-intro-orb { position: absolute; display: block; border: 1px solid rgba(210, 176, 106, 0.2); border-radius: 50%; pointer-events: none; }
.cinematic-intro-orb--one { top: -28rem; right: -10rem; width: min(72vw, 74rem); height: min(72vw, 74rem); box-shadow: 0 0 0 4rem rgba(210, 176, 106, 0.025), 0 0 0 11rem rgba(210, 176, 106, 0.02); }
.cinematic-intro-orb--two { bottom: -18rem; left: -12rem; width: min(46vw, 42rem); height: min(46vw, 42rem); border-color: rgba(137, 166, 185, 0.18); }
.cinematic-intro-grid { position: absolute; inset: 0; display: block; pointer-events: none; opacity: 0.25; background-image: linear-gradient(rgba(235, 230, 217, 0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(235, 230, 217, 0.04) 1px, transparent 1px); background-size: 5rem 5rem; mask-image: linear-gradient(to bottom, transparent, #000 20%, #000 76%, transparent); }
.cinematic-card { position: relative; z-index: 2; display: block; width: min(78vw, 42rem); min-height: min(70vh, 42rem); padding: clamp(2rem, 5vw, 4.5rem); overflow: hidden; border: 1px solid rgba(235, 226, 199, 0.56); background: linear-gradient(145deg, rgba(22, 31, 48, 0.88), rgba(6, 8, 13, 0.88)); color: #f5f1e8; text-align: left; box-shadow: 0 2.5rem 7rem rgba(0, 0, 0, 0.52), inset 0 1px 0 rgba(255, 255, 255, 0.12); backdrop-filter: blur(7px); transition: transform 500ms cubic-bezier(.2,.8,.2,1), box-shadow 500ms ease, border-color 400ms ease; }
.cinematic-card:hover, .cinematic-card:focus-visible { transform: translateY(-0.7rem) rotateX(1deg) rotateY(-1deg); border-color: rgba(232, 201, 127, 0.88); box-shadow: 0 3.2rem 8rem rgba(0, 0, 0, 0.62), 0 0 3rem rgba(191, 150, 76, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.16); }
.cinematic-card:focus-visible { outline: 4px solid rgba(232, 201, 127, 0.72); outline-offset: 5px; }
.cinematic-card-reflection { position: absolute; top: -42%; right: -19%; width: 73%; height: 180%; pointer-events: none; background: linear-gradient(112deg, transparent 34%, rgba(255, 244, 213, 0.11) 47%, transparent 56%); transform: rotate(10deg); transition: transform 650ms ease; }
.cinematic-card:hover .cinematic-card-reflection, .cinematic-card:focus-visible .cinematic-card-reflection { transform: translateX(-18%) rotate(10deg); }
.cinematic-card-line { position: absolute; display: block; height: 1px; pointer-events: none; background: linear-gradient(90deg, transparent, rgba(222, 188, 112, 0.85), transparent); }
.cinematic-card-line--top { top: clamp(1.5rem, 4vw, 3rem); right: clamp(1.5rem, 4vw, 3rem); left: clamp(1.5rem, 4vw, 3rem); }
.cinematic-card-line--bottom { right: clamp(1.5rem, 4vw, 3rem); bottom: clamp(1.5rem, 4vw, 3rem); left: clamp(1.5rem, 4vw, 3rem); }
.cinematic-card-content { position: relative; z-index: 1; display: flex; min-height: calc(min(70vh, 42rem) - clamp(4rem, 10vw, 9rem)); flex-direction: column; align-items: flex-start; justify-content: center; }
.cinematic-card-brand, .cinematic-card-kicker, .cinematic-card-party { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.24em; text-transform: uppercase; }
.cinematic-card-brand { color: #d8c18b; }
.cinematic-card-kicker { margin-top: 1.3rem; color: rgba(245, 241, 232, 0.56); }
.cinematic-card-hosts { max-width: 11ch; margin-top: clamp(3.5rem, 9vw, 6rem); color: #f7f2e8; font-family: ui-serif, Georgia, serif; font-size: clamp(3rem, 6vw, 6.2rem); font-weight: 400; line-height: 0.87; letter-spacing: -0.055em; overflow-wrap: anywhere; }
.cinematic-card-title { max-width: 28ch; margin-top: 1.3rem; color: rgba(245, 241, 232, 0.76); font-size: clamp(0.95rem, 1.5vw, 1.2rem); line-height: 1.5; }
.cinematic-card-party { margin-top: 2rem; color: #d8c18b; }
.cinematic-card-action { display: inline-flex; gap: 0.7rem; margin-top: auto; padding-top: 2.5rem; color: #f7f2e8; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; }
.cinematic-opening-leave-active { transition: opacity 520ms ease-in, transform 780ms cubic-bezier(.6,0,.15,1), filter 580ms ease; }
.cinematic-opening-leave-to { opacity: 0; filter: blur(3px); transform: scale(1.07); }
.cinematic-opening-enter-active { transition: opacity 700ms ease-out, clip-path 820ms cubic-bezier(.2,.8,.2,1), transform 700ms ease-out; }
.cinematic-opening-enter-from { opacity: 0; clip-path: inset(0 0 100% 0); transform: translateY(1.5rem); }
.cinematic-opened-scene { background: #07090e; color: #eef0ed; }
.cinematic-opened-aurora { background: radial-gradient(ellipse at 72% -5%, rgba(37, 64, 109, 0.38), transparent 48%), radial-gradient(ellipse at 12% 48%, rgba(173, 128, 51, 0.1), transparent 44%); }
.cinematic-opened-stack { max-width: 86rem; }
.cinematic-rsvp-area { padding: clamp(1.5rem, 5vw, 4rem); border-top: 1px solid rgba(210, 176, 106, 0.32); border-bottom: 1px solid rgba(210, 176, 106, 0.32); background: rgba(8, 11, 18, 0.68); }
.cinematic-rsvp-heading { margin-bottom: 2rem; text-align: left; }
.cinematic-rsvp-heading p:first-child { color: #d2b06a; }
.cinematic-rsvp-heading p:last-child { color: rgba(238, 240, 237, 0.76); }
@keyframes section-reveal { from { opacity: 0.92; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
@media (prefers-reduced-motion: reduce) { .invitation-reveal, :global(.reveal-on-scroll.is-visible) { animation: none; } .card-opening-leave-active, .card-opening-enter-active, .editorial-opening-leave-active, .editorial-opening-enter-active, .cinematic-opening-leave-active, .cinematic-opening-enter-active { transition: none; } .cinematic-card, .cinematic-card:hover, .cinematic-card:focus-visible, .cinematic-card-reflection, .cinematic-card:hover .cinematic-card-reflection, .cinematic-card:focus-visible .cinematic-card-reflection { transform: none; transition: none; } }
@media (max-width: 640px) { .romantic-envelope { width: calc(100vw - 2rem); } .romantic-invitation-card { min-height: 30rem; padding: 2.5rem 1.5rem; } .scene-botanical { width: 15rem; } .editorial-cover { min-height: calc(100dvh - 3rem); } .editorial-cover-content { min-height: calc(100dvh - 7rem); } .editorial-cover-title { font-size: clamp(3.1rem, 16vw, 5rem); } .cinematic-card { width: min(100%, 32rem); min-height: min(74dvh, 39rem); padding: 2rem 1.5rem; } .cinematic-card-content { min-height: calc(min(74dvh, 39rem) - 4rem); } .cinematic-card-hosts { font-size: clamp(3rem, 13vw, 4.7rem); } .cinematic-intro-background::before { inset: 0.7rem; } }
</style>
