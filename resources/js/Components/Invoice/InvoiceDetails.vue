<script setup>
import CashMemo from './CashMemo.vue';
import { usePage } from '@inertiajs/vue3';
import { printCashMemo } from '../../utils/printCashMemo';

const props = defineProps({
    show: Boolean,
    customer: Object,
});

const emit = defineEmits(['update:show']);
const page = usePage();

const business = page.props.business || {};

const printInvoice = () => {
    printCashMemo('invoice-cash-memo-print');
};
</script>

<template>
    <div
        v-if="show && customer"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm invoice-preview-modal"
    >
        <div class="w-full max-w-4xl rounded-[28px] border border-white/60 bg-white p-4 shadow-[0_30px_80px_rgba(15,23,42,0.22)] md:p-6">
            <CashMemo :invoice="customer" :business="business" print-id="invoice-cash-memo-print" />

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
