<script setup>
import { ref } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";

const page = usePage();
const searchValue = ref();
const searchField = ref(["name", "price"]);
const headers = [
    { text: "No", value: "id" },
    { text: "Name", value: "name" },
    { text: "Mobile", value: "mobile" },
    { text: "Email", value: "email" },
    { text: "Address", value: "address" },
    { text: "Balance Due", value: "balance_due" },
    { text: "Action", value: "action" },
];

const items = ref(page.props.customers);

const deleteCustomer = (id) => {
    if (confirm("Are you sure you want to delete this customer?")) {
        router.get(`/delete-customer?id=${id}`);
    }
};
</script>

<template>
    <div class="pos-page">
        <section class="pos-section">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Customer Management</h1>
                    <p class="text-sm text-slate-500">Manage bakery customers and billing contacts.</p>
                </div>
                <Link :href="`/customer-save-page?id=${0}`" class="pos-button-primary">
                    <span class="material-icons text-[18px]">person_add</span>
                    Create Customer
                </Link>
            </div>

            <input
                v-model="searchValue"
                type="text"
                class="pos-search mb-4"
                placeholder="Search customers by name, email, or mobile"
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
                <template #item-action="{ id }">
                    <div class="flex flex-wrap gap-2">
                        <Link :href="`/customer-save-page?id=${id}`" class="pos-button-success px-3 py-2">
                            Edit
                        </Link>
                        <button @click="deleteCustomer(id)" class="pos-button-danger px-3 py-2">
                            Delete
                        </button>
                    </div>
                </template>
            </EasyDataTable>
        </section>
    </div>
</template>

<style scoped></style>
