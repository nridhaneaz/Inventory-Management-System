<script setup>
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { createToaster } from "@meforma/vue-toaster";
const toaster = createToaster({ });
 
const page = usePage();

const searchValue = ref();
const searchField = ref(["name"]);
const headers = [
    { text: "No", value: "id" },
    { text: "Image", value: "img_url" },
    { text: "Name", value: "name", sortable: true },
    { text: "Selling_price", value: "price", sortable: true },
    { text: "Purchase_price", value: "purchase_price", sortable: true },
    { text: "Profit", value: "profit", sortable: true },
    { text: "Qty", value: "unit" },
    { text: "Action", value: "action" },
];

const items = ref(page.props.products);

// Calculate total quantity and profit
const totalQty = computed(() => {
    return items.value.reduce((total, product) => {
        return total + (parseInt(product.unit) || 0);
    }, 0);
});

const totalProfit = computed(() => {
    return items.value.reduce((total, product) => {
        // If profit is available directly, use it
        if (product.profit !== null && product.profit !== undefined) {
            return total + (parseFloat(product.profit) * (parseInt(product.unit) || 0));
        }
        
        // Otherwise calculate from purchase_price and price (selling_price)
        if (product.purchase_price && product.price) {
            const profitPerUnit = parseFloat(product.price) - parseFloat(product.purchase_price);
            return total + (profitPerUnit * (parseInt(product.unit) || 0));
        }
        
        return total;
    }, 0).toFixed(2);
});

const deleteProduct = (id) => {
    if (confirm("Are you sure you want to delete this product?")) {
        router.get(`/delete-product?id=${id}`);
        toaster.success("Product deleted successfully");
    }
};

if(page.props.flash.status === true){
    toaster.success(page.props.flash.message);
}
</script>

<template>
    <div class="p-4 bg-[#f8f8f8]">
        <input
            v-model="searchValue"
            type="text"
            class="mb-2 px-2 py-1 w-[300px] border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
            placeholder="Search...."
        />
        
        <!-- Total Summary Card -->
        <div class="mb-4 p-4 bg-white rounded-lg shadow-md">
            <h2 class="text-lg font-semibold mb-2">Product Summary</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 p-3 rounded-md border border-blue-200">
                    <p class="text-gray-600">Total Products Quantity</p>
                    <p class="text-2xl font-bold text-blue-700">{{ totalQty }}</p>
                </div>
                
            </div>
        </div>
        
        <EasyDataTable
            buttons-pagination
            alternating
            :headers="headers"
            :items="items"
            :search-value="searchValue"
            :search-field="searchField"
            :rows-per-page="5"
        >
           <template #item-img_url="{ img_url }">
                <img :src="img_url" class="w-10 h-10" />
           </template>
           
           <template #item-purchase_price="{ purchase_price }">
                <span>{{ purchase_price ? purchase_price : '-' }}</span>
           </template>
           
           <template #item-profit="{ profit }">
                <span v-if="profit !== null" :class="profit > 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'">
                    {{ profit }}
                </span>
                <span v-else>-</span>
           </template>
           
           <template #item-profit_percentage="{ profit_percentage }">
                <span v-if="profit_percentage !== null" :class="profit_percentage > 0 ? 'text-green-600 font-medium' : 'text-red-600 font-medium'">
                    {{ profit_percentage }}%
                </span>
                <span v-else>-</span>
           </template>
           
            <template #item-action="{ id }">
                <Link
                    :href="`/product-save-page?id=${id}`"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                    Edit
                </Link>
                <button
                    @click="deleteProduct(id)"
                    class="bg-red-500 ml-1 text-white font-bold py-2 px-4 rounded"
                >
                    Delete
                </button>
            </template>
        </EasyDataTable>
        <div class="mt-4">
            <Link
                :href="`/product-save-page?id=${0}`"
                class="w-full px-4 py-2 text-white bg-orange-600 rounded-lg hover:bg-orange-700"
            >
                Add Product
            </Link>
        </div>
    </div>
</template>

<style scoped></style>