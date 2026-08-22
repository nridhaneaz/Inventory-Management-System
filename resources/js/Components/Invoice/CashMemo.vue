<script setup>
import { computed } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    business: { type: Object, default: () => ({}) },
    printId: { type: String, default: 'cash-memo-print' },
});

const businessInfo = computed(() => ({
    name: props.business?.name || 'CITY BAKEWARE TRADE',
    nameBn: props.business?.name_bn || 'সিটি বেকওয়্যার ট্রেড',
    descriptionBn: props.business?.description_bn || '',
    proprietorBn: props.business?.proprietor_bn || '',
    proprietorTitleBn: props.business?.proprietor_title_bn || 'প্রোপ্রাইটর',
    addressBn: props.business?.address_bn || 'ছাপড়া মসজিদ, আজিমপুর, ঢাকা-১২০৫।',
    phones: props.business?.phones || ['01849-534270', '01576-975785'],
    logo: props.business?.logo || null,
}));

const bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
const toBengaliNumber = (value) => String(value).replace(/\d/g, (digit) => bengaliDigits[Number(digit)]);
const formatMoneyBn = (value) => toBengaliNumber(`৳${Number(value || 0).toFixed(2)}`);

const invoiceDate = computed(() => {
    const date = new Date(props.invoice?.created_at || Date.now());
    return toBengaliNumber(`${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}/${date.getFullYear()}`);
});
const invoiceNumber = computed(() => toBengaliNumber(String(props.invoice?.id ?? 0).padStart(4, '0')));
const lineItems = computed(() => props.invoice?.invoice_products || []);
const customerName = computed(() => props.invoice?.customer?.name || '-');
const customerAddress = computed(() => props.invoice?.customer?.address || '-');
const subtotal = computed(() => Number(props.invoice?.total ?? 0));
const deliveryCharge = computed(() => Number(props.invoice?.delivery_charge ?? 0));
const discount = computed(() => Number(props.invoice?.discount ?? 0));
const vat = computed(() => Number(props.invoice?.vat ?? 0));
const grandTotal = computed(() => Number(props.invoice?.payable ?? 0));
const amountPaid = computed(() => Number(props.invoice?.amount_paid ?? props.invoice?.payable ?? 0));
const balanceDue = computed(() => Number(props.invoice?.balance_due ?? 0));
const paymentType = computed(() => String(props.invoice?.payment_type || (balanceDue.value > 0 ? 'due' : 'paid')).toUpperCase());
const codDeliveryNote = computed(() => props.invoice?.delivery_charge_paid ? 'Delivery charge prepaid' : 'Delivery charge unpaid');

const formatQuantity = (product) => {
    const quantity = Number(product.quantity ?? product.qty ?? 0);
    const unit = String(product.unit ?? 'pcs').toLowerCase();
    const number = unit === 'pcs' ? quantity.toFixed(0) : quantity.toFixed(3).replace(/\.?0+$/, '');
    return `${toBengaliNumber(number)} ${unit.toUpperCase()}`;
};
const lineDescription = (product) => product.product?.name || product.item_name || '-';
const formatRate = (product) => toBengaliNumber(Number(product.sale_price ?? product.product?.price ?? 0).toFixed(2));
const formatAmount = (product) => toBengaliNumber(Number(product.subtotal ?? product.sale_price ?? 0).toFixed(2));
</script>

<template>
    <div :id="printId" class="cash-memo">
        <header class="cash-memo__header">
            <div class="cash-memo__header-top">
                <div class="cash-memo__logo-wrap">
                    <img v-if="businessInfo.logo" :src="businessInfo.logo" alt="Logo" class="cash-memo__logo" />
                    <div v-else class="cash-memo__logo-placeholder">{{ businessInfo.nameBn }}</div>
                </div>
                <div class="cash-memo__brand">
                    <h1 class="cash-memo__brand-name">{{ businessInfo.name }}</h1>
                    <p v-if="businessInfo.descriptionBn" class="cash-memo__brand-desc">{{ businessInfo.descriptionBn }}</p>
                </div>
                <div class="cash-memo__owner">
                    <p v-if="businessInfo.proprietorBn" class="cash-memo__owner-name">{{ businessInfo.proprietorBn }}</p>
                    <p>{{ businessInfo.proprietorTitleBn }}</p>
                    <p v-for="phone in businessInfo.phones" :key="phone">মোবাইল: {{ phone }}</p>
                </div>
            </div>
            <div class="cash-memo__address-bar">{{ businessInfo.addressBn }}</div>
        </header>

        <div class="cash-memo__title-row">
            <div>নং: {{ invoiceNumber }}</div>
            <div class="cash-memo__memo-title">ক্যাশ মেমো/চালান</div>
            <div class="cash-memo__memo-date">তারিখ: {{ invoiceDate }}</div>
        </div>
        <p class="cash-memo__payment-type">Payment Type: <strong>{{ paymentType }}</strong><span v-if="paymentType === 'COD'"> — {{ codDeliveryNote }}</span></p>

        <div class="cash-memo__customer">
            <p><strong>নাম:</strong> {{ customerName }}</p>
            <p><strong>ঠিকানা:</strong> {{ customerAddress }}</p>
        </div>

        <table class="cash-memo__table">
            <thead>
                <tr><th>নং</th><th>বিবরণ</th><th>পরিমাণ</th><th>দর</th><th>টাকা</th></tr>
            </thead>
            <tbody>
                <tr v-for="(product, index) in lineItems" :key="product.id || index" class="cash-memo__item-row">
                    <td>{{ toBengaliNumber(String(index + 1).padStart(2, '0')) }}</td>
                    <td>
                        {{ lineDescription(product) }}
                        <small v-if="product.note" class="cash-memo__item-note">{{ product.note }}</small>
                    </td>
                    <td>{{ formatQuantity(product) }}</td>
                    <td>{{ formatRate(product) }}</td>
                    <td>{{ formatAmount(product) }}</td>
                </tr>
                <tr v-if="lineItems.length === 0"><td colspan="5" class="cash-memo__empty">কোনো পণ্য নেই</td></tr>
            </tbody>
            <tfoot>
                <tr><td colspan="4" class="cash-memo__summary-label">Subtotal</td><td>{{ formatMoneyBn(subtotal) }}</td></tr>
                <tr><td colspan="4" class="cash-memo__summary-label">Delivery Charge</td><td>{{ formatMoneyBn(deliveryCharge) }}</td></tr>
                <tr><td colspan="4" class="cash-memo__summary-label">Discount</td><td>{{ formatMoneyBn(discount) }}</td></tr>
                <tr v-if="vat > 0"><td colspan="4" class="cash-memo__summary-label">VAT</td><td>{{ formatMoneyBn(vat) }}</td></tr>
                <tr><td colspan="4" class="cash-memo__summary-label cash-memo__grand-total">Grand Total</td><td class="cash-memo__grand-total">{{ formatMoneyBn(grandTotal) }}</td></tr>
                <tr><td colspan="4" class="cash-memo__summary-label">Paid Amount</td><td>{{ formatMoneyBn(amountPaid) }}</td></tr>
                <tr><td colspan="4" class="cash-memo__summary-label cash-memo__due-amount">Due Amount</td><td class="cash-memo__due-amount">{{ formatMoneyBn(balanceDue) }}</td></tr>
            </tfoot>
        </table>
        <p class="cash-memo__footer">ধন্যবাদ, আবার আসবেন।</p>
    </div>
</template>

<style scoped>
.cash-memo { --memo-ink: #111; --memo-border: #202020; max-width: 900px; margin: 0 auto; padding: 12px; background: #fff; color: var(--memo-ink); font-family: "Noto Sans Bengali", "Segoe UI", sans-serif; }
.cash-memo__header-top { display: grid; grid-template-columns: 90px 1fr 180px; gap: 12px; align-items: start; }
.cash-memo__logo-wrap, .cash-memo__logo, .cash-memo__logo-placeholder { width: 80px; height: 80px; }
.cash-memo__logo, .cash-memo__logo-placeholder { box-sizing: border-box; border: 2px solid var(--memo-ink); border-radius: 50%; }
.cash-memo__logo { object-fit: cover; }
.cash-memo__logo-placeholder { display: flex; align-items: center; justify-content: center; padding: 6px; color: var(--memo-ink); font-size: 10px; font-weight: 700; text-align: center; }
.cash-memo__brand-name { margin: 0; color: var(--memo-ink); font-size: 28px; font-weight: 800; letter-spacing: .04em; line-height: 1.1; }
.cash-memo__brand-desc { margin: 4px 0 0; color: #333; font-size: 11px; line-height: 1.4; }
.cash-memo__owner { font-size: 12px; line-height: 1.5; text-align: right; }.cash-memo__owner p { margin: 0; }.cash-memo__owner-name { font-weight: 700; }
.cash-memo__address-bar { margin-top: 8px; padding: 6px 10px; border: 1px solid var(--memo-border); background: #fff; color: var(--memo-ink); font-size: 13px; font-weight: 600; text-align: center; }
.cash-memo__title-row { display: grid; grid-template-columns: 1fr auto 1fr; gap: 8px; align-items: center; margin: 14px 0 4px; font-size: 14px; font-weight: 600; }.cash-memo__memo-title { padding: 6px 18px; border: 1px solid var(--memo-border); background: #fff; color: var(--memo-ink); text-align: center; }.cash-memo__memo-date { text-align: right; }
.cash-memo__payment-type { margin: 0 0 8px; font-size: 12px; text-align: right; }.cash-memo__customer { margin-bottom: 10px; font-size: 14px; line-height: 1.8; }.cash-memo__customer p { margin: 0; }
.cash-memo__table { width: 100%; border-collapse: collapse; font-size: 13px; }.cash-memo__table th, .cash-memo__table td { padding: 6px 8px; border: 1px solid var(--memo-border); background: #fff; color: var(--memo-ink); }.cash-memo__table th { font-weight: 700; text-align: center; }.cash-memo__table td { text-align: center; }.cash-memo__table td:nth-child(2) { text-align: left; }
.cash-memo__summary-label { background: #fff; font-weight: 700; text-align: right !important; }.cash-memo__grand-total, .cash-memo__due-amount { font-weight: 800; }.cash-memo__item-note { display: block; margin-top: 2px; color: #555; font-size: 10px; }.cash-memo__empty { padding: 20px; color: #555; }.cash-memo__footer { margin: 6px 0 0; font-size: 16px; font-weight: 800; text-align: center; }
@media print { .cash-memo { max-width: none; padding: 0; } }
</style>
