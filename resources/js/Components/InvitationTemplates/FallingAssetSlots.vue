<script setup lang="ts">
const props = defineProps<{ name: string; src?: string | null }>();
// Fixed, pointer-transparent decoration. The missing artwork stays explicitly labelled.
// The references use independent falling-leaf and falling-petal timing ranges.
const petals = props.name === 'petal';
const particles = Array.from({ length: 18 }, (_, i) => ({
    left: `${(i * 37 + 11) % 100}%`,
    size: `${(petals ? 20 : 18) + (i * 13) % (petals ? 22 : 29)}px`,
    duration: `${(petals ? 9 : 8) + (i * 17) % (petals ? 110 : 80) / 10}s`,
    delay: `${petals ? (i * 7.3) % 14 : -(i * 1.7) % 12}s`,
    spin: `${(petals ? 4 : 3) + (i * 13) % (petals ? 70 : 60) / 10}s`,
    drift: `${(i * 29) % 81 - 40}px`,
}));
</script>
<template><div class="falling-slots" :class="{ 'falling-slots--petals': petals }" aria-hidden="true"><span v-for="(particle,i) in particles" :key="i" :style="{left:particle.left,width:particle.size,height:particle.size,animationDuration:particle.duration,animationDelay:particle.delay,'--leaf-drift':particle.drift}"><img v-if="src" :src="src" alt="" :style="{animationDuration:particle.spin,animationDelay:petals ? particle.delay : undefined}" /><small v-else :style="{animationDuration:particle.spin,animationDelay:petals ? particle.delay : undefined}">{{ name }}<br />placeholder</small></span></div></template>
<style scoped>
.falling-slots{position:fixed;inset:0;pointer-events:none;z-index:1150;overflow:hidden}.falling-slots>span{position:absolute;top:-64px;animation:asset-fall linear infinite}.falling-slots img,.falling-slots small{display:block;width:100%;height:100%;object-fit:contain;animation:asset-spin linear infinite}.falling-slots small{font:5px/1.3 Arial,sans-serif;border:1px dashed #93735066;color:#73552f80;display:grid;place-content:center;text-align:center;background:#f7e9df22}.falling-slots--petals{z-index:30}.falling-slots--petals>span{top:-12vh;animation-name:petal-fall,petal-fade;animation-timing-function:linear,ease-in}.falling-slots--petals img,.falling-slots--petals small{animation-timing-function:ease-in-out}@keyframes petal-fall{from{transform:translateY(-12vh)}to{transform:translateY(115vh)}}@keyframes petal-fade{0%,100%{opacity:0}15%,85%{opacity:1}}
@keyframes asset-fall{from{transform:translate3d(0,-10vh,0) rotate(0deg)}to{transform:translate3d(var(--leaf-drift),120vh,0) rotate(520deg)}}@keyframes asset-spin{to{transform:rotate(360deg)}}@media(prefers-reduced-motion:reduce){.falling-slots{display:none}}
</style>
