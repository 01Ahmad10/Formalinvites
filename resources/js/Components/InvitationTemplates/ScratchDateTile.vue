<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
const props = defineProps<{ value: string; label: string; texture?: string }>();
const emit = defineEmits<{ revealed: [] }>();
const canvas = ref<HTMLCanvasElement | null>(null);
const revealed = ref(false);
let drawing = false;
let previous: { x: number; y: number } | null = null;
let observer: ResizeObserver;
let coating: HTMLImageElement | null = null;
function loadCoating() {
    if (coating) coating.onload = coating.onerror = null;
    coating = null;
    if (props.texture) {
        const image = new Image();
        image.onload = () => { coating = image; paint(); };
        image.onerror = () => { coating = null; paint(); };
        image.src = props.texture;
        coating = image;
    }
    paint();
}
function paint() {
    const el = canvas.value;
    if (!el || revealed.value) return;
    const { width, height } = el.getBoundingClientRect();
    el.width = Math.round(width); el.height = Math.round(height);
    const ctx = el.getContext('2d')!;
    if (coating?.complete && coating.naturalWidth) {
        ctx.drawImage(coating, 0, 0, width, height);
        return;
    }
    ctx.fillStyle = '#d8c7a8'; ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = '#665540'; ctx.font = '9px Arial'; ctx.textAlign = 'center';
    ctx.fillText('Temporary asset', width / 2, height / 2 - 5);
    ctx.fillText('scratch-gold', width / 2, height / 2 + 10);
}
function reveal() { if (!revealed.value) { revealed.value = true; emit('revealed'); } }
function scratch(event: PointerEvent) {
    if (!drawing || revealed.value || !canvas.value) return;
    const el = canvas.value, box = el.getBoundingClientRect();
    const point = { x: (event.clientX - box.left) * el.width / box.width, y: (event.clientY - box.top) * el.height / box.height };
    const ctx = el.getContext('2d')!;
    ctx.globalCompositeOperation = 'destination-out'; ctx.lineWidth = 36; ctx.lineCap = 'round';
    ctx.beginPath(); ctx.moveTo(previous?.x ?? point.x, previous?.y ?? point.y); ctx.lineTo(point.x, point.y); ctx.stroke();
    previous = point;
    checkCoverage();
}
function start(event: PointerEvent) { drawing = true; previous = null; canvas.value?.setPointerCapture(event.pointerId); scratch(event); }
function finish() {
    drawing = false; previous = null;
    checkCoverage();
}
function checkCoverage() {
    const el = canvas.value; if (!el || revealed.value) return;
    const pixels = el.getContext('2d')!.getImageData(0, 0, el.width, el.height).data;
    let erased = 0; for (let i = 3; i < pixels.length; i += 4) if (pixels[i] < 128) erased++;
    if (erased / (el.width * el.height) > .5) reveal();
}
onMounted(() => { loadCoating(); observer = new ResizeObserver(paint); if (canvas.value) observer.observe(canvas.value); });
watch(() => props.texture, loadCoating);
onBeforeUnmount(() => { observer?.disconnect(); if (coating) coating.onload = coating.onerror = null; });
</script>
<template><button type="button" class="scratch-tile" :aria-label="revealed ? `${label}: ${value}` : `Reveal ${label}`" :aria-pressed="revealed" @keydown.enter.prevent="reveal" @keydown.space.prevent="reveal" @click="event => { if (event.detail === 0) reveal(); }"><span :aria-hidden="!revealed">{{ value }}</span><canvas ref="canvas" :class="{ erased: revealed }" aria-hidden="true" @pointerdown="start" @pointermove="scratch" @pointerup="finish" @pointercancel="finish"></canvas></button></template>
<style scoped>
.scratch-tile{position:relative;width:128px;height:128px;border-radius:50%;overflow:hidden;background:white;box-shadow:inset 0 0 0 1px #5c201814;color:inherit;font:clamp(24px,4vw,32px) Georgia,serif;display:grid;place-items:center}.scratch-tile canvas{position:absolute;inset:0;width:100%;height:100%;touch-action:none;transition:opacity .7s ease}.scratch-tile canvas.erased{opacity:0;pointer-events:none}@media(max-width:600px){.scratch-tile{width:112px;height:112px;flex:1;max-width:112px;font-size:24px}}
</style>
