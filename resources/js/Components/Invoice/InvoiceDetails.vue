<script setup>
import CashMemo from './CashMemo.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { printCashMemo } from '../../utils/printCashMemo';
import { resolvePrintFormat } from '../../utils/printCashMemo';

const props = defineProps({
    show: Boolean,
    customer: Object,
});

const emit = defineEmits(['update:show']);
const page = usePage();

const business = page.props.business || {};
const printFormat = ref(localStorage.getItem('invoice_print_format') || 'auto');
const printOrientation = ref(localStorage.getItem('invoice_print_orientation') || 'portrait');
const autoOverflowed = ref(false);
const productCount = computed(() => props.customer?.invoice_products?.length || 0);
const selectedPrintFormat = computed(() => resolvePrintFormat(productCount.value, printFormat.value));

const rememberPrintSettings = () => {
    localStorage.setItem('invoice_print_format', printFormat.value);
    localStorage.setItem('invoice_print_orientation', printOrientation.value);
};

const printInvoice = () => {
    autoOverflowed.value = false;
    rememberPrintSettings();
    printCashMemo('invoice-cash-memo-print', {
        format: printFormat.value,
        orientation: printOrientation.value,
        onAutoUpgrade: () => { autoOverflowed.value = true; },
        onOverflow: () => window.alert('This invoice does not fit in Half A4. Choose Auto or Full A4 to print without cutting content.'),
    });
};
</script>

<template>
    <div
        v-if="show && customer"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm invoice-preview-modal"
    >
        <div class="my-4 w-full max-w-4xl rounded-[28px] border border-white/60 bg-white p-4 shadow-[0_30px_80px_rgba(15,23,42,0.22)] md:p-6">
            <CashMemo :invoice="customer" :business="business" print-id="invoice-cash-memo-print" />

            <div class="no-print mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Print Format</label>
                    <select v-model="printFormat" class="pos-input w-full">
                        <option value="auto">Auto</option>
                        <option value="half">Half A4</option>
                        <option value="full">Full A4</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Orientation</label>
                    <select v-model="printOrientation" class="pos-input w-full">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                </div>
                <p class="text-sm text-slate-600 sm:col-span-2">
                    <template v-if="autoOverflowed">{{ productCount }} products -&gt; Full A4 (content exceeds Half A4 height)</template>
                    <template v-else>{{ productCount }} products -&gt; {{ selectedPrintFormat === 'half' ? 'Half A4' : 'Full A4' }}</template>
                </p>
            </div>

            <div class="mt-5 flex items-center justify-end gap-3 no-print">
                <button type="button" class="pos-button-danger" @click="$emit('update:show', false)">
                    Close
                </button>
                <button type="button" class="pos-button-primary" @click="printInvoice">
                    Print Invoice
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
