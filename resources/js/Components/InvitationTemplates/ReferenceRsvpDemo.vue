<script setup lang="ts">
import { ref } from 'vue';
const props = withDefaults(defineProps<{ variant?: 'coast' | 'theatre' | 'cinematic' }>(), { variant: 'coast' });
const response = ref(props.variant === 'cinematic' ? 'accepted' : '');
const sent = ref(false);
</script>
<template>
    <form class="reference-demo-form" :class="`demo-${variant}`" @submit.prevent="sent = true">
        <p v-if="variant !== 'cinematic'" class="demo-count">Number of Persons: 1</p>
        <label class="demo-name-label" for="demo-guest-name">{{ variant === 'coast' ? 'Your name' : 'Name of the person attending:' }}</label><input id="demo-guest-name" required placeholder="Enter your full name" autocomplete="name" />
        <p v-if="variant !== 'coast'" class="demo-question">Are you attending?</p><div class="demo-choices"><label><input v-model="response" type="radio" value="accepted" required name="demo-response" /> {{ variant === 'cinematic' ? 'Accepts with pleasure' : 'Joyfully Accept' }}</label><label><input v-model="response" type="radio" value="declined" name="demo-response" /> {{ variant === 'cinematic' ? 'Declines with regret' : 'Regretfully Decline' }}</label></div>
        <textarea v-if="variant !== 'theatre'" aria-label="Notes" :placeholder="variant === 'cinematic' ? 'Any special requests or notes...' : 'Notes'" rows="3"></textarea>
        <button type="submit">{{ variant === 'coast' ? 'SEND RSVP' : 'Submit' }}</button>
        <p v-if="sent" role="status">Demo response shown. Nothing was sent or saved.</p>
        <small v-else>Local demo · no response will be sent</small>
    </form>
</template>
<style scoped>
.reference-demo-form{display:grid;gap:12px;max-width:460px;margin:auto;text-align:center;font:12px/1.45 'Invitation Sans',Arial,sans-serif;color:inherit;padding:0 17.6px}
.reference-demo-form p{margin:0 0 12px}.reference-demo-form input:not([type=radio]),.reference-demo-form textarea{width:100%;border:1px solid #decac0;border-radius:7.2px;background:#fff9;padding:14px;color:inherit;font:12px/1.45 'Invitation Sans',Arial,sans-serif;min-height:46px}
.demo-choices{display:flex;gap:10px}.demo-choices label{display:flex;align-items:center;justify-content:center;gap:6px;flex:1;font-size:10px;border:1px solid #dfcfc7;border-radius:999px;background:#fffa;padding:10px 4px}.demo-choices input{appearance:auto;width:13px;height:13px;accent-color:#ad7468}
.reference-demo-form button{min-height:45px;border-radius:7.2px;background:linear-gradient(#c99487,#ad7468);color:#fff;font-size:11.2px;font-weight:650;letter-spacing:1.344px;box-shadow:0 8px 20px #78514933}.reference-demo-form small{font:9px Arial,sans-serif;opacity:.7}
.demo-theatre{padding:24px;max-width:620px;border:1px solid #5c20181a;border-radius:20px;background:#faf8f5;font:16px/1.35 Arial,sans-serif;gap:20px}.demo-theatre>p{order:-4}.demo-theatre>.demo-question{order:-3;text-align:start;margin:0}.demo-theatre .demo-choices{order:-2}.demo-theatre .demo-choices label{font:16px Arial,sans-serif;border-radius:12px;padding:14.4px 16px;background:#ffffffd1;min-height:52px;border-color:#5c201833}.demo-theatre input:not([type=radio]){font:16px Arial,sans-serif;border-radius:12px}.demo-theatre button{background:#5c2018;border-radius:12px;font:600 16px Georgia,serif;min-height:53px;box-shadow:none}.demo-theatre .demo-choices input{accent-color:#5c2018}@container(max-width:600px){.demo-theatre .demo-choices{flex-direction:column}}
.demo-cinematic{padding:0;gap:16px;font:14px Georgia,serif;text-align:start}.demo-cinematic .demo-question{order:-2;margin:0}.demo-cinematic .demo-choices{order:-1;flex-direction:column;gap:8px}.demo-cinematic .demo-choices label{border:0;border-radius:0;justify-content:start;padding:0;background:transparent;font:14px Georgia,serif}.demo-cinematic input:not([type=radio]),.demo-cinematic textarea{font:14px Georgia,serif;border:1px solid #a67d2b66;background:#fff9;border-radius:4px}.demo-cinematic button{background:#6e1622;border-radius:4px;font:12.8px Cinzel,serif;letter-spacing:1px;box-shadow:none}
/* Local specimen skins only; controls and response behavior are unchanged. */
.demo-coast{max-width:560px;padding:16.8px;border:1px solid #b2705240;border-radius:8.8px;background:#fffaf6db;box-shadow:0 12px 34px #5e302b1f;font:16px/1.45 Georgia,serif}
.demo-coast .demo-count{font:11.84px/1.45 Georgia,serif;margin:0 0 .8px}
.demo-coast .demo-name-label{text-align:start;font:650 10.08px/1.45 'Invitation Sans',Arial,sans-serif;letter-spacing:.4032px;margin-bottom:-5.28px}
.demo-coast input:not([type=radio]),.demo-coast textarea{font:16px/1.45 Georgia,serif;padding:10.4px 12px;border:1px solid #8b4c4033;border-radius:4.8px;background:#ffffffc7}
.demo-coast textarea{height:69.16px;min-height:69.16px}
.demo-coast .demo-choices{gap:7.2px}.demo-coast .demo-choices label{font:11.84px/1.45 Georgia,serif;min-height:40px;padding:8px;border-color:#9c173640;background:#ffffff94}
.demo-coast .demo-choices label:has(input:checked){background:linear-gradient(#c99487,#ad7468);color:white;border-color:#ad7468}
.demo-coast button{font-family:'Invitation Sans',Arial,sans-serif}
.demo-theatre{position:relative;margin-top:63.2px;padding:32px;border-radius:16px;background:#ffffff99;font:16px/1.35 'Invitation Sans',Arial,sans-serif}
.demo-theatre .demo-name-label,.demo-theatre .demo-question{text-align:start;font:600 12px/16px 'Invitation Sans',Arial,sans-serif;letter-spacing:1.92px;text-transform:uppercase}
.demo-theatre .demo-name-label,.demo-theatre .demo-question{margin-bottom:-11.2px}.demo-theatre .demo-count{position:absolute;bottom:calc(100% + 28px);left:0;right:0;margin:0}.demo-theatre small{position:absolute;top:calc(100% + 10px);left:0;right:0}
.demo-theatre input:not([type=radio]){padding:12px 14.4px;min-height:47px;background:#ffffffd1;border-color:#5c201833;font:16px/21px 'Invitation Sans',Arial,sans-serif}
.demo-theatre .demo-choices label{font:16px/21px 'Invitation Sans',Arial,sans-serif}.demo-theatre .demo-choices label:has(input:checked){background:#5c20180d;border-color:#5c2018}
.demo-theatre button{font:600 16px/21px 'Invitation Sans',Arial,sans-serif;letter-spacing:normal;color:#faf8f5}
@media(max-width:600px){.demo-theatre{padding:24px}}
</style>
