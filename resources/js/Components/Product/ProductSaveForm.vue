<script setup>
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { reactive, ref, watch } from "vue";
import ImageUpload from "./ImageUpload.vue";

const page = usePage();
const status = reactive({
    title: "Create Product",
    buttonTitle: "Create Product",
});
let params = new URLSearchParams(window.location.search);
let id = params.get("id");

const form = useForm({
    name: "",
    unit_type: "kg",
    purchase_price: "", 
    selling_price: "", 
    qty: "",
    img_url: "https://png.pngtree.com/png-vector/20210604/ourmid/pngtree-gray-network-placeholder-png-image_3416659.jpg",
    category_id: "",
    id: id,
});

// বিক্রয় মূল্য এবং লাভ গণনা
const profit = ref(0);

// যখন ক্রয় মূল্য বা বিক্রয় মূল্য পরিবর্তন হবে, তখন লাভ গণনা করা হবে
const calculateProfit = () => {
    if (form.purchase_price && form.selling_price) {
        const buyPrice = parseFloat(form.purchase_price);
        const sellingPrice = parseFloat(form.selling_price);
        
        if (buyPrice > 0 && sellingPrice > 0) {
            profit.value = (sellingPrice - buyPrice).toFixed(2);
        }
    }
};

// ক্রয় মূল্য বা বিক্রয় মূল্য পরিবর্তন হলে লাভ গণনা করা
watch(() => form.purchase_price, calculateProfit);
watch(() => form.selling_price, calculateProfit);

let URL = "/create-product";
if (id !== 0 && page.props.product !== null) {
    status.title = "Update Product";
    status.buttonTitle = "Update Product";
    URL = "/update-product";
    form.name = page.props.product.name;
    form.selling_price = page.props.product.price; 
    form.purchase_price = page.props.product.purchase_price ?? "";
    form.unit_type = page.props.product.unit_type || 'pcs';
    form.qty = page.props.product.opening_stock ?? page.props.product.stock_display ?? page.props.product.stock_quantity ?? page.props.product.unit;
    form.img_url = page.props.product.img_url;
    form.category_id = page.props.product.category_id;
    
    
}

const unitSuffix = (unitType) => {
    if (unitType === 'pcs') return 'PCS';
    if (unitType === 'gm') return 'GM';
    return 'KG';
};

const openingStockStep = () => form.unit_type === 'pcs' ? '1' : '0.001';

const openingStockMin = () => form.unit_type === 'pcs' ? '1' : '0.001';

const submitForm = () => {
    
    form.price = form.selling_price;
    
    form.post(URL, {
        preserveScroll: true,
        onSuccess: () => {
            setTimeout(() => {
                router.get("/product-page");
            }, 500);
        },
    });
};
</script>

<template>
    <div class="pos-page">
        <section class="mx-auto w-full max-w-3xl pos-section">
            <div class="mb-6 border-b border-slate-200 pb-4">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Product</p>
                <h1 class="mt-1 text-3xl font-bold text-slate-900">{{ status.title }}</h1>
                <p class="text-sm text-slate-500">Manage bakery stock with clear pricing and quantity controls.</p>
            </div>

            <form @submit.prevent="submitForm" class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Select Category</label>
                    <select v-model="form.category_id" name="category" id="category" class="pos-input">
                      <option value="" selected>select category</option>
                      <option v-for="page in page.props.categories" :key="page.id" :value="page.id">{{ page.name }}</option>
                    </select>
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Name</label>
                    <input v-model="form.name" class="pos-input" type="text" id="name" name="name" />
                    <input type="text" v-model="form.id" hidden name="id" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Unit Type</label>
                    <select v-model="form.unit_type" class="pos-input">
                        <option value="kg">KG</option>
                        <option value="gm">GM</option>
                        <option value="pcs">PCS</option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-700">Purchase Price / {{ unitSuffix(form.unit_type) }}</label>
                    <input v-model="form.purchase_price" class="pos-input" type="number" step="0.01" id="purchase_price" name="purchase_price" />
                </div>

                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-700">Selling Price / {{ unitSuffix(form.unit_type) }}</label>
                    <input v-model="form.selling_price" class="pos-input" type="number" step="0.01" id="selling_price" name="selling_price" />
                </div>

                <div v-if="profit != 0" class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 md:col-span-2">
                    <p class="font-semibold text-emerald-700">Estimated profit: {{ profit }} Taka</p>
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Opening Stock / {{ unitSuffix(form.unit_type) }}</label>
                    <input v-model="form.qty" class="pos-input" type="number" :min="openingStockMin()" :step="openingStockStep()" id="qty" name="qty" />
                </div>

                <div class="grid gap-2 md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Image</label>
                    <ImageUpload :productImage="form.img_url" @image="(e) => (form.img_url = e)"/>
                </div>

                <button @summit.prevent="submitForm" type="submit" class="pos-button-primary md:col-span-2">
                    {{ status.buttonTitle }}
                </button>
            </form>
        </section>
    </div>
</template>