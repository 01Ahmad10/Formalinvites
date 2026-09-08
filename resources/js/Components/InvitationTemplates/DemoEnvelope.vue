<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import ReferenceAsset from './ReferenceAsset.vue';
const props = defineProps<{ image?: string; video?: string; label: string; name: string; mobileOnly?: boolean; theme?: 'dolce' | 'blossom'; media?: Record<string, string> }>();
const opened = ref(false);
const dismissed = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;
function open() {
    if (opened.value) return;
    opened.value = true;
    timer = setTimeout(() => { dismissed.value = true; }, matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : props.video ? 30000 : 1400);
}
onBeforeUnmount(() => clearTimeout(timer));
</script>
<template>
    <div v-if="!dismissed" class="demo-envelope" :class="[{ 'demo-envelope--mobile': mobileOnly, 'demo-envelope--opening': opened && !video }, theme && `demo-envelope--${theme}`]">
        <button type="button" :aria-label="label" :disabled="opened" @click="open">
            <template v-if="theme === 'dolce' && media?.envelopeSeal">
                <img v-for="part in ['Bottom','Left','Right','Top','Seal']" :key="part" :src="media[`envelope${part}`]" :class="`envelope-piece dolce-${part}`" alt="" />
            </template>
            <template v-else-if="theme === 'blossom' && media?.envelopeFront">
                <img v-for="part in ['left','right','bottom']" :key="part" :src="image" :class="`envelope-piece blossom-${part}`" alt="" />
                <img :src="media.envelopePaper" class="envelope-piece blossom-paper" alt="" />
                <img :src="media.envelopeFront" class="envelope-piece blossom-seal" alt="" />
                <img :src="media.flowerLine" class="envelope-piece blossom-flower" alt="" />
            </template>
            <ReferenceAsset v-else :name="name" :src="image" class="demo-envelope-art" />
            <video v-if="opened && video" :src="video" autoplay muted playsinline aria-hidden="true" @ended="dismissed = true" @error="dismissed = true" />
            <span v-if="!opened" class="demo-envelope-label">{{ label }}</span>
        </button>
    </div>
</template>
<style scoped>
.demo-envelope{position:fixed;inset:0;z-index:100;display:flex;justify-content:center;background:var(--wg-paper);transition:opacity 1.4s ease;overflow:hidden}
.demo-envelope button{position:relative;display:block;width:min(100%,var(--wg-canvas));height:100%;padding:0;border:0;background:transparent;color:var(--wg-accent);cursor:pointer}
.demo-envelope-art,.demo-envelope video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.demo-envelope-art :deep(img),.demo-envelope video{object-fit:contain}
.demo-envelope-art.reference-asset--missing{background:var(--wg-paper);outline:1px dashed #99856c55;outline-offset:-12px}
.demo-envelope-label{position:absolute;top:61%;left:0;right:0;font:20px/1.55 Georgia,serif;text-align:center}
.demo-envelope--opening{opacity:0;pointer-events:none}
.envelope-piece{position:absolute;max-width:none;object-fit:fill;pointer-events:none}
.dolce-Bottom{width:467px;height:864px;left:calc(50% - 502px);top:-13px}
.dolce-Left{width:467px;height:864px;left:calc(50% + 35px);top:-13px}
.dolce-Right{width:1011px;height:556px;left:calc(50% - 505px);top:271px}
.dolce-Top{width:1012px;height:405px;left:calc(50% - 506px);top:-6px}
.dolce-Seal{width:241px;height:241px;left:calc(50% - 120px);top:283px}
.demo-envelope--dolce .demo-envelope-label{top:478px;color:#52839c}
.blossom-left{width:856px;height:640px;left:calc(50% - 635px);top:105px;transform:rotate(-90deg)}
.blossom-right{width:856px;height:684px;left:calc(50% - 235px);top:83px;transform:rotate(90deg)}
.blossom-bottom{width:815px;height:787px;left:calc(50% - 407px);top:221px;transform:rotate(180deg)}
.blossom-paper{width:807px;height:569px;left:calc(50% - 404px);top:-99px}
.blossom-seal{width:160px;height:160px;left:calc(50% - 80px);top:310px}
.blossom-flower{width:188px;height:38px;left:calc(50% - 94px);top:510px}
.demo-envelope--blossom .demo-envelope-label{top:470px}
@media(min-width:960px){.demo-envelope--mobile{display:none}}
@media(prefers-reduced-motion:reduce){.demo-envelope{transition:none}}
</style>
