<script setup lang="ts">
import { computed, ref } from 'vue';
import ReferenceAsset from './ReferenceAsset.vue';
import ScratchDateTile from './ScratchDateTile.vue';
import DemoEnvelope from './DemoEnvelope.vue';
import WebgencyRsvpDemo from './WebgencyRsvpDemo.vue';
import { useReferenceExperience } from './referenceExperience';
import './webgencyDemos.css';
import NativeEventSections from './NativeEventSections.vue';
import { familyGreeting, invitationText } from './invitationText';
import { webgencyEventContent } from './webgencyEventContent';
const props = defineProps<{ invitation: any }>();
const invitation = computed(() => webgencyEventContent(props.invitation, 'dolce'));
const realRsvpOpen = ref(false);
const t = (text: string) => invitationText(props.invitation, text);
const root = ref<HTMLElement | null>(null);
const { names } = useReferenceExperience(() => props.invitation, root);
const rsvp = ref<InstanceType<typeof WebgencyRsvpDemo> | null>(null);
const revealed = ref(0);
const photos = ref<HTMLElement | null>(null);
const media = computed(() => props.invitation.media || {});
function slide(direction: number) { photos.value?.scrollBy({ left: direction * (photos.value.clientWidth * .84 + 16), behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' }); }
</script>
<template>
    <article ref="root" class="webgency-demo dolce-demo" :class="{ 'real-event': !invitation.reference_demo }">
        <DemoEnvelope v-if="invitation.reference_demo || media.envelopeSeal || media.envelope" mobile-only theme="dolce" :media="media" :image="media.envelope" :video="media.envelopeVideo" name="Dolce Vita paper envelope and gold heart seal" :label="t('Click to open')" />
        <header class="wg-section dolce-hero">
            <div class="wg-canvas">
                <video v-if="media.heroVideo" :src="media.heroVideo" :poster="media.heroPoster" autoplay muted playsinline loop aria-hidden="true" />
                <ReferenceAsset v-else class="wg-photo" name="Italian villa terrace · animated lake and floral arch" :src="media.heroPoster" />
                <div class="dolce-hero-fade" aria-hidden="true"></div>
                <h1 class="wg-script">{{ names }}</h1><p>{{ t('are getting married!') }}</p>
            </div>
        </header>
        <section class="wg-section dolce-date" aria-label="Wedding date">
            <h2 class="wg-heading">{{ t('The Date') }}</h2><p class="dolce-hint">✦ {{ t('Scratch to reveal the date') }} ✦</p>
            <div class="dolce-tiles">
                <div v-for="(part, i) in invitation.reference_demo?.date_parts || invitation.date_parts" :key="i">
                    <ScratchDateTile :value="part" :label="['day','month','year'][i]" :texture="media.scratchTexture" @revealed="revealed++" />
                    <small>{{ t(['DAY','MONTH','YEAR'][i]) }}</small>
                </div>
            </div>
            <p class="dolce-invited" :class="{ visible: revealed === 3 }" role="status">{{ t("You're invited!") }}</p>
        </section>
        <section class="wg-section dolce-letter">
            <div class="wg-canvas">
                <ReferenceAsset class="wg-art dolce-letter-back" name="Open envelope back with blue ribbon" :src="media.letterBack" />
                <div class="dolce-letter-copy" data-reveal><h2 class="wg-script" data-family-greeting>{{ familyGreeting(invitation) }}</h2><p v-for="paragraph in invitation.content.introduction" :key="paragraph">{{ paragraph }}</p></div>
                <ReferenceAsset class="wg-art dolce-letter-front" name="Open envelope front and floral illustration" :src="media.letterFront" />
                <ReferenceAsset class="wg-art dolce-letter-flower" name="Loose envelope flower" :src="media.letterFlower" />
            </div>
        </section>
        <section class="wg-section dolce-schedule">
            <h2 class="wg-heading">{{ t('Schedule of Events') }}</h2>
            <ReferenceAsset class="wg-art dolce-schedule-left" name="Schedule left botanical artwork" :src="media.scheduleLeft" />
            <ReferenceAsset class="wg-art dolce-schedule-right" name="Schedule right botanical artwork" :src="media.scheduleRight" />
            <ol><li v-for="(activity,i) in invitation.event.activities" :key="i"><div data-reveal><time>{{ activity.start_time }}</time><p>{{ activity.title }}</p><div v-if="!invitation.reference_demo" class="native-activity-details"><p>{{ activity.venue }}</p><p>{{ activity.address }}</p><p>{{ activity.description }}</p><a v-if="activity.location_url" :href="activity.location_url" target="_blank" rel="noopener noreferrer">{{ t('Directions') }}</a></div></div></li></ol>
        </section>
        <section class="wg-section dolce-venue">
            <div class="wg-canvas"><h2 class="wg-heading">{{ t('Wedding Venue') }}</h2>
                <ReferenceAsset class="wg-photo dolce-venue-photo" name="Villa Borghese venue photograph" :src="media.venuePhoto" />
                <div class="dolce-venue-fade" aria-hidden="true"></div>
                <div class="dolce-venue-copy"><ReferenceAsset class="wg-art" name="Venue location pin" :src="media.locationIcon" /><h3>{{ invitation.event.venue }}</h3><p>{{ t('Address:') }} {{ invitation.event.address }}</p></div>
            </div>
        </section>
        <section v-if="invitation.reference_demo || invitation.event.dress_code" class="wg-section dolce-dress"><h2 class="wg-heading">{{ t('Dress Code') }}</h2><p class="wg-body">{{ invitation.content.dress_intro }}</p><ReferenceAsset class="wg-art" name="Dress code decorative divider" :src="media.dressDivider" /></section>
        <section v-if="invitation.reference_demo" class="wg-section dolce-gallery" aria-label="Dress code inspiration">
            <div ref="photos" class="dolce-photo-strip"><ReferenceAsset v-for="i in 10" :key="i" class="wg-photo" :name="`Dress inspiration photograph ${i} of 10`" :src="media[`outfit${i}`]" /></div>
            <div class="dolce-gallery-controls"><button type="button" aria-label="Previous dress inspiration" @click="slide(-1)">‹</button><button type="button" aria-label="Next dress inspiration" @click="slide(1)">›</button></div>
        </section>
        <section v-if="invitation.reference_demo" class="wg-section dolce-palette"><div class="wg-canvas"><div class="dolce-colors"><p>Colours:</p><span v-for="color in invitation.content.palette" :key="color" :style="{background:color}" :aria-label="`Color ${color}`"></span></div><div class="dolce-dress-copy"><p>Ladies: {{ invitation.content.ladies }}</p><p>Gentlemen: {{ invitation.content.gentlemen }}</p></div></div></section>
        <NativeEventSections :invitation="invitation" /><section class="wg-section dolce-rsvp" data-section="rsvp"><h2 class="wg-heading">{{ t('Confirm Your Attendance') }}</h2><p class="wg-body">{{ t('To help us prepare for a joyful celebration, kindly confirm your attendance.') }}</p><button type="button" aria-haspopup="dialog" @click="invitation.reference_demo ? rsvp?.open() : realRsvpOpen = !realRsvpOpen">{{ t('RSVP') }}</button><div v-if="!invitation.reference_demo && realRsvpOpen" class="native-rsvp-panel"><slot /></div></section>
        <footer v-if="invitation.content?.ending_enabled !== false" class="wg-section dolce-ending" data-section="ending"><h2 class="wg-script">{{ invitation.content?.ending_title || t('Hope to see you there!') }}</h2><p>{{ invitation.content?.ending_message || names }}</p><div class="wg-canvas"><ReferenceAsset class="wg-photo" name="Ending couple portrait · elegant-couple-love" :src="media.endingPhoto" /><div class="dolce-ending-fade" aria-hidden="true"></div><span aria-hidden="true">♡</span></div></footer>
        <WebgencyRsvpDemo v-if="invitation.reference_demo" ref="rsvp" theme="dolce" :deadline="invitation.event.rsvp_deadline" />
    </article>
</template>
<style scoped>
.dolce-demo{--wg-accent:#4a4a4a}.dolce-hero,.dolce-hero .wg-canvas{height:782px}.dolce-hero .wg-photo,.dolce-hero video{width:100%;height:782px}.dolce-hero h1{position:absolute;top:250px;left:-60px;right:-60px;font-size:45px;line-height:70px}.dolce-hero p{position:absolute;top:308px;width:100%;font-size:22px;line-height:25px}.dolce-hero-fade{position:absolute;bottom:0;height:308px;width:100%;background:linear-gradient(0deg,#fffdfb,transparent)}
.dolce-date{height:350px}.dolce-date h2{position:relative;top:-11px}.dolce-hint{color:#7a9aaa;font-size:18px;line-height:21px;margin-top:-6px!important}.dolce-tiles{display:flex;gap:16px;justify-content:center;width:432px;max-width:calc(100% - 24px);margin:22px auto 0}.dolce-tiles>div{flex:1;min-width:0}.dolce-tiles :deep(.scratch-tile){width:100%;max-width:none;height:auto;aspect-ratio:1/1.15;border-radius:12px;font-size:38px;box-shadow:0 2px 16px #b4915524,0 1px 3px #0000000d}.dolce-tiles>div:nth-child(2) :deep(.scratch-tile){font-size:26px}.dolce-tiles small{display:block;font:10px/20px Georgia,serif;letter-spacing:2px;color:#7a9aaa;margin-top:9px}.dolce-invited{opacity:0;transition:opacity .5s;font:28px/1.4 'WG Script Fallback',serif;margin-top:16px!important}.dolce-invited.visible{opacity:1}
.dolce-letter,.dolce-letter>.wg-canvas{height:670px}.dolce-letter-back,.dolce-letter-front{width:490px;height:490px;top:21px;left:50%;transform:translateX(-50%)}.dolce-letter-front{z-index:2;background:transparent!important}.dolce-letter-copy{position:absolute;top:174px;left:50%;transform:translateX(-50%);width:302px;min-height:327px;padding:30px 17px;background:#fffdfb}.dolce-letter-copy[data-reveal]{transform:translate(-50%,35px)}.dolce-letter-copy.arrived{transform:translate(-50%,0)}.dolce-letter-copy h2{font-size:33px;line-height:41px;width:340px;margin-left:-36px}.dolce-letter-copy p{font-size:18px;line-height:23px;margin-top:14px}.dolce-letter-flower{width:108px;height:99px;top:-142px;left:calc(50% - 65px)}
.dolce-schedule{height:653px;padding-top:64px}.dolce-schedule ol{list-style:none;padding:0;position:relative;width:360px;max-width:100%;margin:20px auto 0}.dolce-schedule ol:before{content:'';position:absolute;left:calc(50% - 2px);top:30px;bottom:35px;width:4px;background:#9bc9e1}.dolce-schedule li{height:80px;position:relative;width:50%;margin-left:50%;padding:0 14px 0 28px}.dolce-schedule li:nth-child(even){margin-left:0;padding:0 28px 0 14px}.dolce-schedule li:before{content:'';position:absolute;left:-5px;top:30px;width:10px;height:10px;transform:rotate(45deg);background:#9bc9e1}.dolce-schedule li:nth-child(even):before{left:auto;right:-5px}.dolce-schedule time{font:30px/47px 'WG Script Fallback',Georgia,serif}.dolce-schedule li p{font:20px/22px 'WG Script Fallback',Georgia,serif}.dolce-schedule-left{width:957px;height:335px;left:calc(50% - 478px);top:114px}.dolce-schedule-right{width:957px;height:431px;left:calc(50% - 478px);top:214px}
.dolce-venue,.dolce-venue>.wg-canvas{height:714px}.dolce-venue h2{position:absolute;top:68px;left:0;right:0;line-height:51px}.dolce-venue-photo{position:absolute;top:142px;width:100%;height:572px}.dolce-venue-fade{position:absolute;top:142px;width:100%;height:249px;background:linear-gradient(#fffdfb,transparent)}.dolce-venue-copy{position:absolute;top:147px;left:89px;text-align:left}.dolce-venue-copy h3{font:22px/26px Georgia,serif}.dolce-venue-copy p{font:19px/23px Georgia,serif;margin-top:4px}.dolce-venue-copy .wg-art{width:51px;height:51px;left:-61px;top:-3px}
.dolce-dress{height:254px;padding-top:50px}.dolce-dress .wg-heading{line-height:51px}.dolce-dress .wg-body{width:342px;font-size:19px;line-height:25px;margin-top:23px}.dolce-dress>.wg-art{width:90px;height:32px;left:calc(50% - 45px);top:210px}
.dolce-gallery{height:515px;overflow:hidden}.dolce-photo-strip{height:500px;display:flex;gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding-inline:max(16px,calc((100% - 404px)/2))}.dolce-photo-strip>.wg-photo{width:404px;height:500px;flex:0 0 404px;scroll-snap-align:center}.dolce-photo-strip::-webkit-scrollbar{display:none}.dolce-gallery-controls{position:absolute;inset:calc(50% - 18px) 12px auto;display:flex;justify-content:space-between;pointer-events:none}.dolce-gallery-controls button{pointer-events:auto;width:36px;height:36px;border:1px solid #7775;background:#fffd;border-radius:50%;font:24px/1 Georgia,serif;color:#4a4a4a;cursor:pointer}
.dolce-palette{height:308px;padding-top:34px}.dolce-colors{display:flex;align-items:center;justify-content:center;padding:0 16px}.dolce-colors p{margin-right:14px}.dolce-colors span{width:60px;height:60px;border-radius:50%;margin-left:-10px;flex-shrink:0}.dolce-dress-copy{width:387px;max-width:calc(100% - 32px);margin:39px auto 0;text-align:left;font-size:19px;line-height:24px}.dolce-dress-copy p+p{margin-top:24px}
.dolce-rsvp{height:238px;padding-top:22px}.dolce-rsvp .wg-body{width:372px;font-size:18px;line-height:23px;margin-top:20px}.dolce-rsvp>button{width:149px;height:41px;border:0;border-radius:24px;background:#9bc9e1;color:white;font:22px/1 Georgia,serif;margin-top:36px;cursor:pointer}.dolce-ending{height:669px;padding-top:42px}.dolce-ending h2{font-size:33px;line-height:43px}.dolce-ending>p{font:30px/39px Georgia,serif;margin-top:9px}.dolce-ending>.wg-canvas{height:517px;margin-top:19px}.dolce-ending .wg-photo{width:100%;height:517px}.dolce-ending-fade{position:absolute;top:0;width:100%;height:141px;background:linear-gradient(#fffdfb,#fffdfbcc 51%,transparent)}.dolce-ending .wg-canvas>span:not(.reference-asset){position:absolute;top:0;left:0;right:0;font-size:30px}
@media(max-width:959px){.dolce-schedule{height:608px;padding-top:19px}.dolce-venue,.dolce-venue>.wg-canvas{height:684px}.dolce-venue h2{top:38px}.dolce-venue-photo,.dolce-venue-fade{top:112px}.dolce-venue-copy{top:117px;left:calc(50% - 131px)}.dolce-rsvp{height:208px;padding-top:0}.dolce-rsvp .wg-body{margin-top:12px}.dolce-hero .wg-canvas{width:440px;max-width:none;left:50%;margin:0;transform:translateX(-50%)}}
@media(max-width:430px){.dolce-colors span{width:52px;height:52px}.dolce-colors p{font-size:17px;margin-right:10px}.dolce-photo-strip>.wg-photo{flex-basis:84vw;width:84vw}.dolce-dress-copy{font-size:18px}.dolce-letter-front,.dolce-letter-back{max-width:100vw}.dolce-palette{height:335px}}
/* The unlicensed reference font is not available: allow its fallback to wrap without covering the RSVP control. */
.dolce-letter-copy.arrived{transform:translate(-50%,-150px)}
.dolce-rsvp{height:auto;min-height:238px;padding-bottom:9px}
@media(max-width:959px){.dolce-rsvp{min-height:208px}}

.native-rsvp-panel{position:relative;max-width:560px;margin:28px auto;padding:0 16px;text-align:start}.real-event [data-family-greeting]{overflow-wrap:anywhere}.real-event .native-activity-details{font:15px/1.5 Georgia,serif;grid-column:1/-1}.real-event .native-activity-details a{text-decoration:underline}.real-event ol li{height:auto;min-height:80px;padding-block:12px}.real-event .dolce-schedule{height:auto;padding-bottom:48px}.real-event [data-section="ending"] p{white-space:pre-line;overflow-wrap:anywhere}
</style>
