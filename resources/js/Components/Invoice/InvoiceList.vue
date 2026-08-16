<script setup>
import { ref } from "vue";
import InvoiceDetails from "./InvoiceDetails.vue";
import { usePage, router } from "@inertiajs/vue3";
const show = ref(false);
const customer = ref();
const page = usePage();
const searchValue = ref();
const searchField = ref(["customer.name"]);

const markInvoicePaid = (invoice) => {
    const nextStatus = 'paid';
    router.post('/update-invoice-status', {
        id: invoice.id,
        status: nextStatus,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const target = items.value.find((item) => item.id === invoice.id);
            if (target) {
                target.status = nextStatus;
                target.amount_paid = Number(invoice.payable || 0);
                target.balance_due = 0;
            }
        },
    });
};
const headers = [
    { text: "No", value: "id" },
    { text: "Name", value: "customer.name" },
    { text: "Mobile", value: "customer.mobile" },
    { text: "Total", value: "total" },
    { text: "Vat", value: "vat" },
    { text: "Discount", value: "discount" },
    { text: "Payable", value: "payable" },
    { text: "Paid", value: "amount_paid" },
    { text: "Due", value: "balance_due" },
    { text: "Date", value: "created_at" },
    { text: "Action", value: "action" },
];

// Process the items to ensure they have formatted dates
const processItems = () => {
    return [...page.props.list].map(item => {
        // Add a formatted date if it doesn't exist
        if (item.created_at) {
            item.formatted_date = formatDate(item.created_at);
        } else if (item.date) {
            item.formatted_date = formatDate(item.date);
        } else {
            // If no date exists, use current date as fallback
            item.formatted_date = formatDate(new Date());
        }
        return item;
    }).sort((a, b) => b.id - a.id); // Sort by ID (newest first)
};

// Format date helper function
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`; // Format: DD/MM/YYYY
};

const items = ref(processItems());

const deleteInvoice = (id) => {
    if (confirm("Are you sure you want to delete this Invoice?")) {
        router.get(`/delete-invoice?id=${id}`);
    }
};

const showDetails = (id) => {
    show.value = !show.value;
    customer.value = items.value.find((item) => item.id === id);
}
</script>
<template>
    <div class="pos-page">
        <InvoiceDetails v-model:show="show" :customer="customer"/>

        <section class="pos-section">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Invoice Register</h1>
                    <p class="text-sm text-slate-500">Review completed sales, totals, and bill details.</p>
                </div>
            </div>

            <input
                v-model="searchValue"
                type="text"
                class="pos-search mb-4"
                placeholder="Search invoices by customer or date"
            />

            <EasyDataTable
                buttons-pagination
                alternating
                :headers="headers"
                :items="items"
                :search-value="searchValue"
                :search-field="searchField"
                :rows-per-page="5"
            >
                <template #item-created_at="{ formatted_date }">
                    <span class="font-medium text-slate-700">{{ formatted_date }}</span>
                </template>

                <template #item-action="{ id, status, payable }">
                    <div class="flex items-center gap-2">
                        <button @click="showDetails(id)" class="pos-button-neutral px-3 py-2">
                            <span class="material-icons text-[18px]">visibility</span>
                        </button>
                        <button
                            v-if="status !== 'paid'"
                            @click="markInvoicePaid({ id, payable })"
                            class="pos-button-primary px-3 py-2 text-xs font-semibold"
                        >
                            Mark Paid
                        </button>
                        <button @click="deleteInvoice(id)" class="pos-button-danger px-3 py-2">
                            <span class="material-icons text-[18px]">delete</span>
                        </button>
                    </div>
                </template>
            </EasyDataTable>
        </section>
    </div>
</template>
<style scoped></style>
