<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import ValidationSummary from '@/Components/ValidationSummary.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { couponDiscount, date } from '@/Support/AdminPresentation';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ coupons: any[]; discountTypes: string[]; pagination?: any }>();
const editing = ref<any>(null);
const blank = () => ({ code: '', description: '', discount_type: 'fixed', discount_value: '', is_active: true, starts_at: '', expires_at: '', usage_limit: '' });
const form = useForm(blank());
const edit = (coupon: any) => { editing.value = coupon; form.defaults({ code: coupon.code, description: coupon.description || '', discount_type: coupon.discount_type, discount_value: coupon.discount_value, is_active: coupon.is_active, starts_at: coupon.starts_at || '', expires_at: coupon.expires_at || '', usage_limit: coupon.usage_limit || '' }); form.reset(); };
const cancel = () => { editing.value = null; form.defaults(blank()); form.reset(); };
const submit = () => editing.value ? form.put(route('admin.coupons.update', editing.value.id), { onSuccess: cancel }) : form.post(route('admin.coupons.store'), { onSuccess: cancel });
const couponState = (coupon: any) => !coupon.is_active ? 'Inactive' : coupon.expires_at && new Date(`${coupon.expires_at.slice(0, 10)}T23:59:59`) < new Date() ? 'Expired' : 'Active';
const couponTone = (coupon: any) => couponState(coupon) === 'Active' ? 'success' : couponState(coupon) === 'Expired' ? 'warning' : 'neutral';
</script>

<template>
    <Head title="Coupons" />
    <AuthenticatedLayout>
        <template #header><PageHeader title="Coupons" subtitle="Manage discounts available for client financial records." /></template>
        <div class="fe-page fe-page-standard space-y-6">
            <Card><form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submit"><div class="sm:col-span-2 lg:col-span-3"><h2 class="fe-section-title">{{ editing ? 'Edit Coupon' : 'Create Coupon' }}</h2><p class="fe-section-copy">Set the code, discount, validity, and optional usage limit.</p></div><label>Code<input v-model="form.code" required class="mt-1 w-full" /><InputError :message="form.errors.code" class="mt-1" /></label><label>Discount type<select v-model="form.discount_type" class="mt-1 w-full"><option v-for="type in discountTypes" :key="type" :value="type">{{ type === 'percentage' ? 'Percentage' : 'Fixed amount' }}</option></select></label><label>Discount value<input v-model="form.discount_value" type="number" min="0" step="0.01" required class="mt-1 w-full" /><InputError :message="form.errors.discount_value" class="mt-1" /></label><label>Description<input v-model="form.description" class="mt-1 w-full" /></label><label>Start date<input v-model="form.starts_at" type="date" class="mt-1 w-full" /></label><label>Expiration date<input v-model="form.expires_at" type="date" class="mt-1 w-full" /></label><label>Usage limit<input v-model="form.usage_limit" type="number" min="1" class="mt-1 w-full" /></label><label class="flex items-end gap-2 pb-2"><input v-model="form.is_active" type="checkbox" /> Active</label><div class="flex flex-wrap items-end gap-2"><Button type="submit" :loading="form.processing">{{ editing ? 'Save Coupon' : 'Create Coupon' }}</Button><Button v-if="editing" type="button" variant="secondary" @click="cancel">Cancel</Button></div><div class="sm:col-span-2 lg:col-span-3"><ValidationSummary :errors="form.errors" /></div></form></Card>

            <div v-if="coupons.length" class="fe-table-wrap"><table class="fe-table min-w-[48rem]"><thead><tr><th>Code</th><th>Discount</th><th>Validity</th><th>Usage</th><th>Status</th><th class="text-right">Action</th></tr></thead><tbody><tr v-for="coupon in coupons" :key="coupon.id"><td><p class="font-semibold">{{ coupon.code }}</p><p v-if="coupon.description" class="mt-1 text-xs text-[color:var(--fe-text-muted)]">{{ coupon.description }}</p></td><td class="font-medium">{{ couponDiscount(coupon.discount_type, coupon.discount_value) }}</td><td>{{ coupon.starts_at ? date(coupon.starts_at) : 'Any time' }}<span class="block text-xs text-[color:var(--fe-text-muted)]">{{ coupon.expires_at ? `Ends ${date(coupon.expires_at)}` : 'No expiry' }}</span></td><td>{{ coupon.usage_limit || 'Unlimited' }}</td><td><Badge :tone="couponTone(coupon)">{{ couponState(coupon) }}</Badge></td><td class="text-right"><Button type="button" variant="ghost" @click="edit(coupon)">Edit</Button></td></tr></tbody></table></div><EmptyState v-else title="No coupons created yet." message="Create a coupon when you need a reusable client discount." />
            <Pagination :pagination="pagination" />
        </div>
    </AuthenticatedLayout>
</template>
