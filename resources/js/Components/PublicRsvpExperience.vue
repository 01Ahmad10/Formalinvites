<script setup lang="ts">
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ party: any; rsvp: any; meals: any[]; closed: boolean; confirmation: string | null; romantic?: boolean; editorial?: boolean; cinematic?: boolean; theme?: string; locale?: string; preview?: boolean }>();
const arabic: Record<string, string> = {"Your RSVP": "ردّكم على الدعوة", "Response:": "الرد:", "Attending": "سأحضر", "Not attending": "لن أحضر", "Last updated:": "آخر تحديث:", "Edit RSVP": "تعديل الرد", "Guest responses": "ردود الضيوف", "Status:": "الحالة:", "Guest type:": "نوع الضيف:", "Child": "طفل", "Adult": "بالغ", "Meal:": "الوجبة:", "Dietary note:": "ملاحظات غذائية:", "Message to host": "رسالة للمضيف", "RSVP closed": "انتهت مهلة الرد", "The RSVP deadline has passed. Please contact the host if you need assistance.": "انتهت مهلة تأكيد الحضور. يُرجى التواصل مع المضيف للمساعدة.", "RSVP": "تأكيد الحضور", "Will you attend?": "هل ستحضرون؟", "No meal selected": "لم تُحدّد وجبة", "Additional guests": "ضيوف إضافيون", "Remove": "إزالة", "First name": "الاسم الأول", "Last name": "اسم العائلة", "Adult / Child": "بالغ / طفل", "Meal": "الوجبة", "Dietary / allergy note (optional)": "ملاحظات غذائية / حساسية (اختياري)", "+ Add Guest": "+ إضافة ضيف", "Maximum additional guest capacity reached.": "بلغتم الحد الأقصى للضيوف الإضافيين.", "Message to host (optional)": "رسالة للمضيف (اختياري)", "Please correct the highlighted RSVP details.": "يُرجى تصحيح تفاصيل الرد المبيّنة.", "Update RSVP": "تحديث الرد", "Submit RSVP": "إرسال الرد", "Cancel": "إلغاء", "Preview only — no response was saved.": "هذه معاينة فقط — لم يُحفظ أي رد."};
const t = (text: string) => props.locale === 'ar' ? arabic[text] || text : text;
const hasSubmittedResponse = computed(() => ['attending', 'not_attending'].includes(props.rsvp?.status));
const isEditing = ref(false);
const previous = (id: number) => props.rsvp?.person_responses?.find((person: any) => person.party_member_id === id);
const form = useForm({
    status: props.rsvp?.status === 'pending' ? '' : props.rsvp?.status || '',
    guest_message: props.rsvp?.guest_message || '',
    members: props.party.members.map((member: any) => {
        const response = previous(member.id);
        return { id: member.id, is_attending: response?.is_attending ?? false, event_meal_option_id: response?.event_meal_option_id || '', dietary_note: response?.dietary_note || '' };
    }),
    additional_guests: (props.rsvp?.person_responses || []).filter((person: any) => !person.is_original_party_member).map((person: any) => ({ first_name: person.first_name, last_name: person.last_name || '', member_type: person.member_type, event_meal_option_id: person.event_meal_option_id || '', dietary_note: person.dietary_note || '' })),
});
const availableAdditionalSlots = computed(() => Math.max(Number(props.party.maximum_party_size || 0) - Number(props.party.listed_member_count || 0), 0));
const addGuest = () => { if (form.additional_guests.length < availableAdditionalSlots.value) form.additional_guests.push({ first_name: '', last_name: '', member_type: 'adult', event_meal_option_id: '', dietary_note: '' }); };
const previewNotice = ref(false);
const submit = () => { if (props.preview) { previewNotice.value = true; return; } form.post(route('public.rsvp.submit', route().params.token), { onSuccess: () => { isEditing.value = false; } }); };
</script>

<template>
    <div class="public-rsvp" :dir="locale === 'ar' ? 'rtl' : 'ltr'" :class="{ 'romantic-rsvp': romantic || theme === 'romantic-floral', 'editorial-rsvp': editorial || theme === 'editorial-luxury', 'cinematic-rsvp': cinematic || theme === 'modern-cinematic', 'dolce-rsvp': theme === 'dolce-vita', 'blossom-rsvp': theme === 'blossom-oud', 'sacred-rsvp': theme === 'sacred-garden' }">
    <p v-if="previewNotice" role="status">{{ t('Preview only — no response was saved.') }}</p>
    <section v-if="hasSubmittedResponse && !isEditing" class="rounded-2xl border border-white/60 bg-white/90 p-6 shadow-sm backdrop-blur">
        <p v-if="confirmation" class="rsvp-confirmation mb-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-800">{{ confirmation }}</p>
        <div class="flex items-start justify-between gap-4"><div><h2 class="font-serif text-2xl font-semibold">{{ t("Your RSVP") }}</h2><p class="mt-1 text-stone-700">Response: <strong>{{ rsvp.status === 'attending' ? t('Attending') : t('Not attending') }}</strong></p><p class="text-sm text-stone-500">Last updated: {{ rsvp.last_updated_at || rsvp.submitted_at }}</p></div><button v-if="!closed" type="button" class="rounded-full border border-stone-400 px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-rose-500" @click="isEditing = true">{{ t("Edit RSVP") }}</button></div>
        <div v-if="rsvp.status === 'attending' && rsvp.person_responses?.length" class="mt-5 border-t border-stone-200 pt-4"><h3 class="font-medium">{{ t("Guest responses") }}</h3><div class="mt-3 space-y-2 text-sm"><div v-for="(person, index) in rsvp.person_responses" :key="person.party_member_id || `additional-${index}`" class="rsvp-response-row rounded-xl bg-rose-50/60 p-3"><strong>{{ person.first_name }} {{ person.last_name }}</strong><dl class="mt-2 space-y-1 text-stone-700"><div><dt class="inline font-medium">{{ t("Status:") }}</dt> <dd class="inline">{{ person.is_attending ? t('Attending') : t('Not attending') }}</dd></div><div v-if="!person.is_original_party_member"><dt class="inline font-medium">{{ t("Guest type:") }}</dt> <dd class="inline">{{ person.member_type === 'child' ? t('Child') : t('Adult') }}</dd></div><div v-if="person.is_attending && person.meal_option"><dt class="inline font-medium">{{ t("Meal:") }}</dt> <dd class="inline">{{ person.meal_option.name }}</dd></div><div v-if="person.is_attending && person.dietary_note"><dt class="inline font-medium">{{ t("Dietary note:") }}</dt> <dd class="inline">{{ person.dietary_note }}</dd></div></dl></div></div></div>
        <div v-if="rsvp.guest_message" class="mt-4 border-t border-stone-200 pt-4"><h3 class="font-medium">{{ t("Message to host") }}</h3><p class="mt-1 whitespace-pre-line text-stone-700">{{ rsvp.guest_message }}</p></div>
    </section>

    <section v-if="closed" class="rounded-2xl border border-white/60 bg-white/90 p-6 shadow-sm"><h2 class="font-serif text-2xl font-semibold">{{ t("RSVP closed") }}</h2><p class="mt-2 text-stone-700">{{ t("The RSVP deadline has passed. Please contact the host if you need assistance.") }}</p></section>

    <form v-if="!closed && (!hasSubmittedResponse || isEditing)" class="space-y-5 rounded-2xl border border-white/60 bg-white/90 p-6 shadow-sm backdrop-blur" @submit.prevent="submit">
        <div><h2 class="font-serif text-2xl font-semibold">{{ t("RSVP") }}</h2><p class="mt-1 text-stone-600">{{ t("Will you attend?") }}</p><label class="me-4 mt-3 inline-flex items-center gap-2"><input v-model="form.status" type="radio" value="attending" required> {{ t("Attending") }}</label><label class="inline-flex items-center gap-2"><input v-model="form.status" type="radio" value="not_attending"> {{ t("Not attending") }}</label><p v-if="form.errors.status" class="mt-1 text-sm text-red-700">{{ form.errors.status }}</p></div>
        <div v-if="form.status === 'attending'" class="space-y-4"><div v-for="(member, index) in party.members" :key="member.id" class="rounded-xl border border-stone-200 p-4"><div class="flex justify-between gap-3"><strong>{{ member.first_name }} {{ member.last_name }}</strong><label class="inline-flex items-center gap-2"><input v-model="form.members[index].is_attending" type="checkbox" :aria-label="`Attendance for ${member.first_name} ${member.last_name}`"> {{ t("Attending") }}</label></div><div v-if="form.members[index].is_attending" class="mt-3 grid gap-2 sm:grid-cols-2"><select v-if="meals.length" v-model="form.members[index].event_meal_option_id" :aria-label="`Meal for ${member.first_name} ${member.last_name}`" class="rounded border-stone-300"><option value="">{{ t("No meal selected") }}</option><option v-for="meal in meals" :key="meal.id" :value="meal.id">{{ meal.name }}</option></select><input v-model="form.members[index].dietary_note" :aria-label="`Dietary note for ${member.first_name} ${member.last_name}`" :placeholder="t('Dietary / allergy note (optional)')" class="rounded border-stone-300"></div></div>
            <section v-if="availableAdditionalSlots > 0" class="space-y-3 rounded-xl border border-dashed border-rose-300 p-4"><div><h3 class="font-semibold">{{ t("Additional guests") }}</h3><p class="text-sm text-stone-600">{{ locale === 'ar' ? `يمكنكم إضافة ${availableAdditionalSlots} من الضيوف.` : `You may add up to ${availableAdditionalSlots} additional ${availableAdditionalSlots === 1 ? 'guest' : 'guests'}.` }}</p></div><div v-for="(guest, index) in form.additional_guests" :key="index" class="rounded-xl border border-stone-200 p-4"><div class="flex justify-between"><strong>{{ locale === 'ar' ? 'ضيف إضافي' : 'Additional guest' }} {{ index + 1 }}</strong><button type="button" class="text-rose-700 underline" @click="form.additional_guests.splice(index, 1)">{{ t("Remove") }}</button></div><div class="mt-3 grid gap-3 sm:grid-cols-2"><div><label class="mb-1 block text-sm">{{ t("First name") }}</label><input v-model="guest.first_name" :aria-label="`First name for additional guest ${index + 1}`" required class="w-full rounded border-stone-300"></div><div><label class="mb-1 block text-sm">{{ t("Last name") }}</label><input v-model="guest.last_name" :aria-label="`Last name for additional guest ${index + 1}`" class="w-full rounded border-stone-300"></div><div><label class="mb-1 block text-sm">{{ t("Adult / Child") }}</label><select v-model="guest.member_type" :aria-label="`Adult or child for additional guest ${index + 1}`" class="w-full rounded border-stone-300"><option value="adult">{{ t("Adult") }}</option><option value="child">{{ t("Child") }}</option></select></div><div v-if="meals.length"><label class="mb-1 block text-sm">{{ t("Meal") }}</label><select v-model="guest.event_meal_option_id" :aria-label="`Meal for additional guest ${index + 1}`" class="w-full rounded border-stone-300"><option value="">{{ t("No meal selected") }}</option><option v-for="meal in meals" :key="meal.id" :value="meal.id">{{ meal.name }}</option></select></div><div class="sm:col-span-2"><label class="mb-1 block text-sm">{{ t("Dietary / allergy note (optional)") }}</label><input v-model="guest.dietary_note" :aria-label="`Dietary note for additional guest ${index + 1}`" class="w-full rounded border-stone-300"></div></div></div><button v-if="form.additional_guests.length < availableAdditionalSlots" type="button" class="rounded-full border border-rose-700 px-4 py-2 text-sm font-medium text-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-500" @click="addGuest">{{ t("+ Add Guest") }}</button><p v-else class="text-sm text-stone-600">{{ t("Maximum additional guest capacity reached.") }}</p></section>
        </div>
        <div><label class="mb-1 block font-medium">{{ t("Message to host (optional)") }}</label><textarea v-model="form.guest_message" aria-label="Message to host (optional)" class="w-full rounded border-stone-300" /></div><ul v-if="Object.keys(form.errors).length" role="alert"><li v-for="(error, field) in form.errors" :key="field">{{ error }}</li></ul><p v-if="Object.keys(form.errors).length" class="text-sm text-red-700">{{ t("Please correct the highlighted RSVP details.") }}</p><div class="flex gap-3"><button type="submit" :disabled="form.processing" class="rounded-full bg-rose-800 px-5 py-2.5 font-medium text-white focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:opacity-60">{{ hasSubmittedResponse ? t('Update RSVP') : t('Submit RSVP') }}</button><button v-if="hasSubmittedResponse" type="button" class="text-stone-700 underline" @click="isEditing = false">{{ t("Cancel") }}</button></div>
    </form>
    </div>
</template>

<style scoped>
.public-rsvp { container-type: inline-size; min-width: 0; overflow-wrap: anywhere; }
.public-rsvp :is(input:not([type=radio]):not([type=checkbox]), select, textarea) { min-width: 0; width: 100%; max-width: 100%; }
.public-rsvp .flex { flex-wrap: wrap; }
.public-rsvp input:is([type='radio'], [type='checkbox']) { appearance: auto; width: 1rem; height: 1rem; padding: 0; }
@container (max-width: 32rem) {
    .public-rsvp :is(form, section.p-6) { padding: .75rem; }
    .public-rsvp .p-4 { padding: .5rem; }
    .public-rsvp .grid { grid-template-columns: minmax(0, 1fr); }
    .public-rsvp .sm\:col-span-2 { grid-column: auto; }
}


.dolce-rsvp{--rsvp-ink:#4a4a4a;--rsvp-accent:#48768e;--rsvp-border:#9bc9e1;--rsvp-paper:#fffdfb;--rsvp-radius:12px}
.blossom-rsvp{--rsvp-ink:#454545;--rsvp-accent:#866739;--rsvp-border:#ac9778;--rsvp-paper:#f9e6d4;--rsvp-radius:8px}
.sacred-rsvp{--rsvp-ink:#6c513f;--rsvp-accent:#6e1622;--rsvp-border:#a67d2b66;--rsvp-paper:#f9f0e0;--rsvp-radius:4px}
/* Match the reconstructed public pages while retaining the shared form and rules. */
.romantic-rsvp{--rsvp-ink:#654840;--rsvp-accent:#ad7468;--rsvp-border:#decac0;--rsvp-paper:#fffaf6;--rsvp-radius:7.2px}
.editorial-rsvp{--rsvp-ink:#5c2018;--rsvp-accent:#5c2018;--rsvp-border:#5c201833;--rsvp-paper:#faf8f5;--rsvp-radius:12px}
.cinematic-rsvp{--rsvp-ink:#4f4638;--rsvp-accent:#6e1622;--rsvp-border:#a67d2b66;--rsvp-paper:#fff9;--rsvp-radius:4px}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp){color:var(--rsvp-ink);font:14px/1.5 Arial,sans-serif}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) :is(section,form){border:1px solid var(--rsvp-border);border-radius:var(--rsvp-radius);background:var(--rsvp-paper);box-shadow:none}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) :is(h2,h3){font:400 24px/1.2 Georgia,serif;color:var(--rsvp-ink);margin:0 0 12px}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) :is(input,select,textarea){min-height:44px;border:1px solid var(--rsvp-border);border-radius:var(--rsvp-radius);background:#fffc;color:var(--rsvp-ink);padding:10px}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) input:is([type=radio],[type=checkbox]){min-height:0;accent-color:var(--rsvp-accent);padding:0}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) :is(input,select,textarea):focus{outline:2px solid var(--rsvp-accent);outline-offset:2px}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) .rsvp-response-row{background:#ffffff80;border:1px solid var(--rsvp-border)}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) button[type=submit]{background:var(--rsvp-accent);border-radius:var(--rsvp-radius);color:white;box-shadow:none}
:is(.romantic-rsvp,.editorial-rsvp,.cinematic-rsvp,.dolce-rsvp,.blossom-rsvp,.sacred-rsvp) button[type=button]{color:var(--rsvp-accent);border-color:var(--rsvp-accent)}
.romantic-rsvp button[type=submit]{background:linear-gradient(#c99487,#ad7468)}
</style>
