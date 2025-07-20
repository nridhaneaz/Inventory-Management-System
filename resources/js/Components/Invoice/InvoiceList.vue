<script setup>
import { ref } from "vue";
import InvoiceDetails from "./InvoiceDetails.vue";
import { Link, usePage, useForm, router } from "@inertiajs/vue3";
import { createToaster } from "@meforma/vue-toaster";
const toaster = createToaster({ });
const show = ref(false);
const customer = ref();
const page = usePage();
const searchValue = ref();
const searchField = ref(["customer.name"]);
const headers = [
    { text: "No", value: "id" },
    { text: "Name", value: "customer.name" },
    { text: "Mobile", value: "customer.mobile" },
    { text: "Total", value: "total" },
    { text: "Vat", value: "vat" },
    { text: "Discount", value: "discount" },
    { text: "Payable", value: "payable" },
    { text: "Date", value: "created_at" }, // Make sure this matches your actual date field name
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

if(page.props.flash.status === true){
    toaster.success(page.props.flash.message);
}

const showDetails = (id) => {
    show.value = !show.value;
    customer.value = items.value.find((item) => item.id === id);
}
</script>
<template>
    <div class="p-4 bg-[#f8f8f8]">
        <InvoiceDetails v-model:show="show" :customer="customer"/>
        <input
            v-model="searchValue"
            type="text"
            class="mb-2 px-2 py-1 w-[300px] border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
            placeholder="Search...."
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
            <template #item-created_at="{ created_at, date, formatted_date }">
                {{ formatted_date }}
            </template>
            <template #item-action="{ id }">
                <button
                    @click="showDetails(id)">
                    <span class="material-icons">visibility</span>
                </button>
                <button @click="deleteInvoice(id)">
                    <span class="material-icons">delete</span>
                </button>
            </template>
        </EasyDataTable>
    </div>
</template>
<style scoped></style>