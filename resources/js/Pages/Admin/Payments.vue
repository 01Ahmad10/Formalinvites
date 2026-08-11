<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import ValidationSummary from '@/Components/ValidationSummary.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps<{ payments: any[]; summary: any; customers: any[]; events: any[]; coupons: any[]; filters: any }>();
const filters = reactive({ ...props.filters });
const form = useForm({ customer_id: '', event_id: '', event_package_id: '', original_amount: '', coupon_id: '', discount_type: '', discount_value: '', paid_amount: '', payment_method: 'cash', reference: '', payment_date: '', notes: '' });
const selectedCustomerEvents = computed(() => props.events.filter((event) => String(event.customer_id) === String(form.customer_id)));
const selectedEvent = computed(() => selectedCustomerEvents.value.find((event) => String(event.id) === String(form.event_id)));
const selectedCoupon = computed(() => props.coupons.find((coupon) => String(coupon.id) === String(form.coupon_id)));
const preview = computed(() => {
    const originalAmount = Math.max(Number(form.original_amount) || 0, 0);
    const type = selectedCoupon.value?.discount_type || form.discount_type;
    const value = Number(selectedCoupon.value?.discount_value ?? form.discount_value) || 0;
    const discount = Math.min(originalAmount, type === 'percentage' ? originalAmount * (value / 100) : type === 'fixed' ? value : 0);
    return { originalAmount, discount, finalAmount: Math.max(originalAmount - discount, 0) };
});
const money = (value: any) => `$${Number(value || 0).toFixed(2)}`;
const apply = () => router.get(route('admin.payments.index'), filters, { preserveState: true, replace: true });
const reset = () => { filters.search = ''; filters.status = ''; filters.customer_id = ''; apply(); };

watch(() => form.customer_id, () => {
    form.event_id = '';
    form.event_package_id = '';
    form.original_amount = '';
});

watch(selectedEvent, (event) => {
    form.event_package_id = event?.event_package_id || '';
    form.original_amount = event?.package?.price ?? '';
});

const chooseCoupon = () => {
    if (form.coupon_id) { form.discount_type = ''; form.discount_value = ''; }
};

const chooseDiscount = () => {
    if (form.discount_type || form.discount_value !== '') form.coupon_id = '';
};
</script>

<template>
    <Head title="Financial records" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold">Financial records</h2></template>
        <div class="mx-auto max-w-7xl space-y-5 p-6">
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded bg-white p-4 shadow"><p class="text-sm text-gray-500">Total final amounts</p><p class="text-xl font-semibold">{{ money(summary.total_final_amount) }}</p></div>
                <div class="rounded bg-white p-4 shadow"><p class="text-sm text-gray-500">Total paid</p><p class="text-xl font-semibold">{{ money(summary.total_paid_amount) }}</p></div>
                <div class="rounded bg-white p-4 shadow"><p class="text-sm text-gray-500">Total remaining</p><p class="text-xl font-semibold">{{ money(summary.total_remaining_amount) }}</p></div>
                <div class="rounded bg-white p-4 shadow"><p class="text-sm text-gray-500">Unpaid</p><p class="text-xl font-semibold">{{ summary.unpaid_count }}</p></div>
                <div class="rounded bg-white p-4 shadow"><p class="text-sm text-gray-500">Partially paid</p><p class="text-xl font-semibold">{{ summary.partially_paid_count }}</p></div>
                <div class="rounded bg-white p-4 shadow"><p class="text-sm text-gray-500">Paid</p><p class="text-xl font-semibold">{{ summary.paid_count }}</p></div>
            </section>

            <section class="bg-white p-4 shadow">
                <h3 class="mb-3 font-semibold">Create financial record</h3>
                <form @submit.prevent="form.post(route('admin.payments.store'))" class="grid gap-3 sm:grid-cols-3">
                    <div><label class="mb-1 block text-sm">Customer</label><select v-model="form.customer_id" required class="w-full rounded border-gray-300"><option value="">Select customer</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select><InputError :message="form.errors.customer_id" class="mt-1" /></div>
                    <div><label class="mb-1 block text-sm">Event</label><select v-model="form.event_id" :disabled="!form.customer_id" class="w-full rounded border-gray-300"><option value="">No event</option><option v-for="event in selectedCustomerEvents" :key="event.id" :value="event.id">{{ event.title }}</option></select><p v-if="form.customer_id && !selectedCustomerEvents.length" class="mt-1 text-sm text-gray-500">No events available for this customer.</p><InputError :message="form.errors.event_id" class="mt-1" /></div>
                    <div><label class="mb-1 block text-sm">Package</label><p class="min-h-10 rounded border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ selectedEvent ? (selectedEvent.package?.name || 'No package assigned.') : 'Select an event to derive its package.' }}</p><input v-model="form.event_package_id" type="hidden" /><InputError :message="form.errors.event_package_id" class="mt-1" /></div>
                    <div><label class="mb-1 block text-sm">Original amount</label><input v-model="form.original_amount" type="number" min="0" step="0.01" required class="w-full rounded border-gray-300" /><InputError :message="form.errors.original_amount" class="mt-1" /></div>
                    <div><label class="mb-1 block text-sm">Coupon code (optional)</label><select v-model="form.coupon_id" :disabled="!!form.discount_type || form.discount_value !== ''" @change="chooseCoupon" class="w-full rounded border-gray-300"><option value="">No coupon</option><option v-for="coupon in coupons" :key="coupon.id" :value="coupon.id">{{ coupon.code }}{{ coupon.description ? ` — ${coupon.description}` : '' }}</option></select><InputError :message="form.errors.coupon_id" class="mt-1" /></div>
                    <div><label class="mb-1 block text-sm">Manual discount (optional)</label><select v-model="form.discount_type" :disabled="!!form.coupon_id" @change="chooseDiscount" class="w-full rounded border-gray-300"><option value="">No manual discount</option><option value="fixed">Fixed amount</option><option value="percentage">Percentage</option></select><InputError :message="form.errors.discount_type" class="mt-1" /></div>
                    <div><label class="mb-1 block text-sm">Discount value</label><input v-model="form.discount_value" :disabled="!form.discount_type || !!form.coupon_id" @input="chooseDiscount" type="number" min="0" step="0.01" class="w-full rounded border-gray-300" /><InputError :message="form.errors.discount_value" class="mt-1" /></div>
                    <div class="rounded border border-gray-200 bg-gray-50 p-3 text-sm"><p class="font-medium">Financial preview</p><p>Original: {{ money(preview.originalAmount) }}</p><p>Discount: {{ money(preview.discount) }}</p><p>Final: {{ money(preview.finalAmount) }}</p></div>
                    <div><label class="mb-1 block text-sm">Initial payment (optional)</label><input v-model="form.paid_amount" type="number" min="0" step="0.01" class="w-full rounded border-gray-300" /><InputError :message="form.errors.paid_amount" class="mt-1" /></div>
                    <div><label class="mb-1 block text-sm">Payment method</label><input v-model="form.payment_method" class="w-full rounded border-gray-300" /></div>
                    <div><label class="mb-1 block text-sm">Reference</label><input v-model="form.reference" class="w-full rounded border-gray-300" /></div>
                    <div><label class="mb-1 block text-sm">Payment date</label><input v-model="form.payment_date" type="date" class="w-full rounded border-gray-300" /></div>
                    <div><label class="mb-1 block text-sm">Notes</label><input v-model="form.notes" class="w-full rounded border-gray-300" /></div>
                    <div class="sm:col-span-3"><ValidationSummary :errors="form.errors" /></div>
                    <button class="w-fit rounded bg-indigo-600 px-3 py-2 text-white">Create financial record</button>
                </form>
            </section>

            <form @submit.prevent="apply" class="flex flex-wrap gap-2"><input v-model="filters.search" placeholder="Search customer, event, coupon, reference" class="rounded border-gray-300" /><select v-model="filters.status" class="rounded border-gray-300"><option value="">All statuses</option><option value="unpaid">Unpaid</option><option value="partially_paid">Partially paid</option><option value="paid">Paid</option></select><select v-model="filters.customer_id" class="rounded border-gray-300"><option value="">All customers</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select><button class="rounded bg-gray-800 px-3 text-white">Search</button><button type="button" @click="reset" class="text-gray-600">Reset</button></form>

            <div class="overflow-x-auto bg-white shadow"><table class="min-w-full text-left text-sm"><thead class="bg-gray-50 text-gray-600"><tr><th class="p-3">Customer</th><th class="p-3">Event</th><th class="p-3">Package</th><th class="p-3">Original</th><th class="p-3">Discount / coupon</th><th class="p-3">Final</th><th class="p-3">Paid</th><th class="p-3">Remaining</th><th class="p-3">Status</th><th class="p-3">Latest payment</th><th class="p-3"></th></tr></thead><tbody><tr v-for="payment in payments" :key="payment.id" class="border-t"><td class="p-3">{{ payment.customer?.name }}</td><td class="p-3">{{ payment.event?.title || 'Not provided' }}</td><td class="p-3">{{ payment.package?.name || 'Not provided' }}</td><td class="p-3">{{ money(payment.original_amount) }}</td><td class="p-3">{{ money(payment.discount) }}<br /><span class="text-gray-500">{{ payment.coupon_code || 'No coupon' }}</span></td><td class="p-3">{{ money(payment.final_amount) }}</td><td class="p-3">{{ money(payment.paid_amount) }}</td><td class="p-3">{{ money(Math.max(Number(payment.final_amount) - Number(payment.paid_amount), 0)) }}</td><td class="p-3 capitalize">{{ payment.status.replaceAll('_', ' ') }}</td><td class="p-3">{{ payment.latest_transaction?.payment_date || 'No payment yet' }}<br /><span class="text-gray-500">{{ payment.latest_transaction?.reference || '' }}</span></td><td class="p-3"><Link :href="route('admin.payments.show', payment.id)" class="text-indigo-600">View details</Link></td></tr></tbody></table><p v-if="!payments.length" class="p-4 text-gray-600">No financial records match your search or filters.</p></div>
        </div>
    </AuthenticatedLayout>
</template>
