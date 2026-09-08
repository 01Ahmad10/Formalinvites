<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
defineProps<{ theme: 'dolce' | 'blossom' | 'sacred'; deadline?: string }>();
const dialog = ref<HTMLDialogElement | null>(null);
const name = ref(''), count = ref(''), attendance = ref(''), notes = ref(''), song = ref(''), children = ref('');
const submitted = ref(false);
let previousOverflow: string | undefined;
function open() {
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    dialog.value?.showModal();
}
function restoreScroll() { if (previousOverflow !== undefined) { document.body.style.overflow = previousOverflow; previousOverflow = undefined; } }
function close() { dialog.value?.close(); restoreScroll(); }
function dismissBackdrop(event: MouseEvent) { if (dialog.value?.open && event.target === event.currentTarget) close(); }
function submit() { submitted.value = true; }
onBeforeUnmount(restoreScroll);
defineExpose({ open });
</script>
<template>
    <component :is="theme === 'blossom' ? 'div' : 'dialog'" ref="dialog" class="wg-rsvp" :class="`wg-rsvp--${theme}`" @close="restoreScroll" @click="dismissBackdrop" :aria-label="theme === 'blossom' ? 'Confirmez Votre Présence' : 'Confirm Your Attendance'">
        <div class="wg-rsvp-inner">
            <template v-if="theme !== 'blossom'">
                <button type="button" class="wg-rsvp-close" aria-label="Close RSVP" @click="close">×</button>
                <h2>Confirm Your Attendance{{ theme === 'dolce' ? '!' : '' }}</h2>
                <p class="wg-rsvp-deadline">Please RSVP before {{ deadline }}</p>
            </template>
            <form @submit.prevent="submit">
                <label :for="`wg-name-${theme}`">{{ theme === 'blossom' ? 'Nom' : 'Your name' }}</label>
                <input :id="`wg-name-${theme}`" v-model="name" required autocomplete="name" />
                <template v-if="theme === 'blossom'">
                    <label :for="`wg-count-${theme}`">Nombre de personnes</label>
                    <input :id="`wg-count-${theme}`" v-model="count" type="number" min="1" required />
                </template>
                <fieldset>
                    <legend>{{ theme === 'blossom' ? 'Serez-vous présent?' : theme === 'dolce' ? 'Will you come?' : 'Will you be attending?' }}</legend>
                    <label><input v-model="attendance" type="radio" :name="`wg-attendance-${theme}`" value="accept" required />{{ theme === 'blossom' ? 'Oui, je serai présent(e)' : theme === 'dolce' ? 'Yes, I will' : 'Accepts with pleasure' }}</label>
                    <label><input v-model="attendance" type="radio" :name="`wg-attendance-${theme}`" value="decline" />{{ theme === 'blossom' ? 'Désolé(e), je ne pourrai pas être présent(e)' : theme === 'dolce' ? 'Unfortunately, I cant :(' : 'Declines with regret' }}</label>
                    <label v-if="theme === 'dolce'"><input v-model="attendance" type="radio" :name="`wg-attendance-${theme}`" value="later" />Ill tell you a bit later</label>
                </fieldset>
                <template v-if="theme === 'dolce'">
                    <label for="wg-food">Do have have any food intolerances?</label><input id="wg-food" v-model="notes" />
                </template>
                <template v-if="theme === 'sacred'">
                    <label for="wg-guests">Number of Guests Attending</label><input id="wg-guests" v-model="count" type="number" min="0" />
                    <label for="wg-song">A Song That Gets You Dancing</label><input id="wg-song" v-model="song" />
                    <label for="wg-children">Children Attending</label><p class="wg-field-help">Please include names and ages.</p><input id="wg-children" v-model="children" />
                </template>
                <button class="wg-rsvp-submit" type="submit">{{ theme === 'blossom' ? 'SOUMETTRE' : 'Submit' }}</button>
                <p class="wg-demo-notice" role="status">{{ submitted ? (theme === 'blossom' ? 'Démonstration uniquement — aucune réponse enregistrée.' : 'Demo only — no response was sent or saved.') : (theme === 'blossom' ? 'Démo locale · aucune réponse ne sera envoyée' : 'Local demo · no response will be sent') }}</p>
            </form>
        </div>
    </component>
</template>
<style scoped>
.wg-rsvp{border:0;padding:0;margin:auto;width:min(560px,100%);max-width:100%;max-height:90dvh;overflow:auto;background:#fffaf8;color:#4a4a4a;text-align:left;font:18px/1.55 Georgia,serif}
.wg-rsvp::backdrop{background:#0009}.wg-rsvp-inner{position:relative;padding:40px 45px}.wg-rsvp h2{text-align:center;font:30px/1.25 Georgia,serif;margin:0 0 12px}.wg-rsvp-deadline{text-align:center;margin:0 0 28px}.wg-rsvp-close{position:absolute;right:8px;top:4px;font:32px/1 Arial,sans-serif;border:0;background:transparent;padding:5px;color:inherit;cursor:pointer}
.wg-rsvp form>label,.wg-rsvp legend{display:block;font:22px/1.55 Georgia,serif;margin:0 0 6px}.wg-rsvp form>input{width:100%;height:60px;background:transparent;border:1px solid #c9c9c9;border-radius:5px;padding:0 20px;font:16px Georgia,serif;color:inherit;margin:0 0 28px}.wg-rsvp fieldset{padding:0;margin:0 0 28px;border:0}.wg-rsvp fieldset label{display:flex;align-items:center;gap:10px;margin:9px 0;font:15px/1.5 Arial,sans-serif}.wg-rsvp input[type=radio]{width:20px;height:20px;flex:none;accent-color:var(--wg-accent);margin:0}.wg-rsvp-submit{width:100%;height:60px;border:0;border-radius:20px;background:#9bc9e1;color:white;font:16px Georgia,serif;cursor:pointer}.wg-demo-notice{margin-top:10px!important;font:10px/1.4 Arial,sans-serif;color:#6a6259}.wg-field-help{font:14px/1.4 Arial,sans-serif;margin:0 0 15px}
.wg-rsvp--sacred .wg-rsvp-submit{background:#5b1020}.wg-rsvp--blossom{width:343px;max-width:calc(100% - 16px);max-height:none;overflow:visible;background:transparent;color:#2a2a2a}.wg-rsvp--blossom .wg-rsvp-inner{padding:0}.wg-rsvp--blossom form>label,.wg-rsvp--blossom legend{font:18px/27px Georgia,serif;margin-bottom:5px}.wg-rsvp--blossom form>input{height:45px;border-color:#ac9778;font-size:23px;margin-bottom:17px}.wg-rsvp--blossom fieldset{margin-bottom:18px}.wg-rsvp--blossom fieldset label{font:15px/1.35 Georgia,serif}.wg-rsvp--blossom .wg-rsvp-submit{display:block;width:160px;height:42px;margin:auto;background:#747b54;border-radius:5px;font-size:15px;color:#fff}.wg-rsvp--blossom .wg-demo-notice{text-align:center}
@media(max-width:600px){.wg-rsvp:not(.wg-rsvp--blossom){max-height:calc(100dvh - 40px)}.wg-rsvp:not(.wg-rsvp--blossom) .wg-rsvp-inner{padding:40px 20px 24px}.wg-rsvp h2{font-size:23px}.wg-rsvp form>label,.wg-rsvp legend{font-size:18px}.wg-rsvp form>input{height:50px}}
</style>
