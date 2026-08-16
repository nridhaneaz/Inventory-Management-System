<script setup>
import { computed, nextTick, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { createToaster } from '@meforma/vue-toaster';
import axios from '../../bootstrap';

const page = usePage();
const toaster = createToaster({});

const searchValue = ref('');
const searchField = ['name', 'category_name'];
const categories = computed(() => page.props.categories || []);
const items = ref((page.props.products || []).map((product) => ({
    ...product,
    category_name: product.category_name ?? product.category?.name ?? '-',
    stock_display: product.stock_display ?? '-',
    price_label: product.price_label ?? '-',
})));

const isSaving = ref(false);
const selectedProduct = ref(null);
const showDetails = ref(false);
const imageInputRefs = ref([]);

const unitOptions = [
    { value: 'kg', label: 'KG' },
    { value: 'gm', label: 'GM' },
    { value: 'pcs', label: 'PCS' },
];

const createRow = () => ({
    uid: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
    name: '',
    category_id: '',
    category_label: '',
    unit_type: 'kg',
    purchase_price: '',
    selling_price: '',
    qty: '',
    img_url: null,
    imagePreview: '',
    imageName: '',
});

const bulkRows = ref([createRow()]);
const form = useForm({
    products: [],
});

const rowInputId = (index, field) => `product-row-${index}-${field}`;
const rowHasContent = (row) => Boolean(
    (row.name && row.name.trim()) ||
    row.category_id ||
    (row.category_label && row.category_label.trim()) ||
    row.purchase_price ||
    row.selling_price ||
    row.qty ||
    row.img_url,
);

const formatUnitLabel = (unitType) => {
    if (unitType === 'pcs') return 'PCS';
    if (unitType === 'gm') return 'GM';
    return 'KG';
};

const syncCategorySelection = (row) => {
    const typedValue = (row.category_label || '').trim();

    if (!typedValue) {
        row.category_id = '';
        return;
    }

    const matchedCategory = categories.value.find(
        (category) => category.name.toLowerCase() === typedValue.toLowerCase(),
    );

    row.category_id = matchedCategory ? matchedCategory.id : '';
    if (matchedCategory) {
        row.category_label = matchedCategory.name;
    }
};

const activeCategoryRow = ref(null);

const filteredCategoriesForRow = (row) => {
    const query = (row.category_label || '').trim().toLowerCase();

    if (!query) {
        return categories.value;
    }

    return categories.value.filter((category) =>
        String(category.name || '').toLowerCase().includes(query),
    );
};

const openCategoryDropdown = (index) => {
    activeCategoryRow.value = index;
    router.reload({
        only: ['categories'],
        preserveScroll: true,
        preserveState: true,
    });
};

const closeCategoryDropdown = () => {
    activeCategoryRow.value = null;
};

const selectCategory = (row, category) => {
    row.category_id = category.id;
    row.category_label = category.name;
    closeCategoryDropdown();
};

const handleCategoryBlur = (row) => {
    syncCategorySelection(row);
    closeCategoryDropdown();
};

const focusField = async (index, field) => {
    await nextTick();
    document.getElementById(rowInputId(index, field))?.focus();
};

const addRow = async () => {
    bulkRows.value.push(createRow());
    await focusField(bulkRows.value.length - 1, 'name');
};

const removeRow = async (rowUid) => {
    const index = bulkRows.value.findIndex((row) => row.uid === rowUid);

    if (index === -1) {
        return;
    }

    const nextFocusIndex = Math.max(0, index - 1);

    if (bulkRows.value.length === 1) {
        bulkRows.value = [createRow()];
        form.clearErrors();
        await focusField(0, 'name');
        return;
    }

    bulkRows.value = bulkRows.value.filter((row) => row.uid !== rowUid);
    form.clearErrors();

    const targetIndex = Math.min(nextFocusIndex, bulkRows.value.length - 1);
    await focusField(targetIndex, 'name');
};

const handleEnter = async (index, field) => {
    const sequence = ['name', 'category_id', 'unit_type', 'purchase_price', 'selling_price', 'qty'];
    const currentIndex = sequence.indexOf(field);

    // If user pressed Enter on the qty (opening stock) field,
    // create a new row when on the last row, otherwise move to next row's name.
    if (field === 'qty') {
        if (index === bulkRows.value.length - 1) {
            await addRow();
        } else {
            await focusField(index + 1, 'name');
        }

        return;
    }

    const nextField = sequence[currentIndex + 1];
    if (nextField) {
        await focusField(index, nextField);
    }
};

const setImageInputRef = (el, index) => {
    if (el) {
        imageInputRefs.value[index] = el;
    }
};

const triggerImagePicker = (index) => {
    imageInputRefs.value[index]?.click();
};

const clearRowImage = (index) => {
    const row = bulkRows.value[index];

    if (!row) {
        return;
    }

    row.img_url = null;
    row.imagePreview = '';
    row.imageName = '';
};

const handleImageChange = (event, index) => {
    const file = event.target.files?.[0] || null;
    const row = bulkRows.value[index];

    if (!row) {
        return;
    }

    row.img_url = file;
    row.imageName = file ? file.name : '';
    row.imagePreview = file ? URL.createObjectURL(file) : '';
};

const rowError = (index, field) => form.errors[`products.${index}.${field}`];

const submitBulk = async () => {
    if (!bulkRows.value.some((row) => rowHasContent(row))) {
        toaster.error('Add at least one product before saving.');
        return;
    }

    const formData = new FormData();

    bulkRows.value
        .filter((row) => rowHasContent(row))
        .forEach((row, index) => {
            const matchedCategory = categories.value.find(
                (category) => category.name.toLowerCase() === (row.category_label || '').trim().toLowerCase(),
            );

            const categoryId = row.category_id || matchedCategory?.id || '';

            formData.append(`products[${index}][name]`, row.name ?? '');
            formData.append(`products[${index}][category_id]`, categoryId ?? '');
            formData.append(`products[${index}][unit_type]`, row.unit_type ?? 'pcs');
            formData.append(`products[${index}][purchase_price]`, row.purchase_price ?? '');
            formData.append(`products[${index}][selling_price]`, row.selling_price ?? '');
            formData.append(`products[${index}][qty]`, row.qty ?? '');

            if (row.img_url) {
                formData.append(`products[${index}][img_url]`, row.img_url);
            }
        });

    isSaving.value = true;
    form.clearErrors();

    try {
        const response = await axios.post('/create-product', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                Accept: 'application/json',
            },
        });

        const savedProducts = (response.data?.products || []).map((product) => ({
            ...product,
            category_name: product.category_name ?? product.category?.name ?? '-',
            stock_display: product.stock_display ?? '-',
            price_label: product.price_label ?? '-',
        }));

        if (savedProducts.length > 0) {
            items.value = [...savedProducts, ...items.value];
        }

        bulkRows.value = [createRow()];
        form.clearErrors();
        toaster.success(response.data?.message || 'Products created successfully');
    } catch (error) {
        if (error.response?.status === 422 && error.response?.data?.errors) {
            Object.entries(error.response.data.errors).forEach(([key, messages]) => {
                form.setError(key, Array.isArray(messages) ? messages[0] : messages);
            });
            toaster.error('Please fix the highlighted row errors.');
            return;
        }

        toaster.error(error.response?.data?.message || 'Failed to save products');
    } finally {
        isSaving.value = false;
    }
};

const openDetails = (product) => {
    selectedProduct.value = product;
    showDetails.value = true;
};

const closeDetails = () => {
    showDetails.value = false;
    selectedProduct.value = null;
};

const deleteProduct = (id) => {
    if (confirm('Are you sure you want to delete this product?')) {
        router.get(`/delete-product?id=${id}`);
    }
};

const productStockLabel = (product) => product.stock_display || '-';

const totalProducts = computed(() => items.value.length);
const totalStockBase = computed(() => items.value.reduce((total, product) => total + Number(product.stock_quantity || product.unit || 0), 0));
const totalProfit = computed(() => items.value.reduce((total, product) => {
    if (product.profit !== null && product.profit !== undefined) {
        return total + Number(product.profit || 0);
    }

    if (product.purchase_price !== null && product.purchase_price !== undefined && product.price !== null && product.price !== undefined) {
        return total + (Number(product.price) - Number(product.purchase_price));
    }

    return total;
}, 0).toFixed(2));
</script>

<template>
    <div class="pos-page">
        <section class="pos-section">
            <div class="mb-5 flex flex-col gap-4 border-b border-slate-200 pb-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Inventory Management</p>
                    <h1 class="mt-1 text-3xl font-bold text-slate-900">Product Management</h1>
                    <p class="mt-1 text-sm text-slate-500">Excel-style bulk entry for products, with the existing stock table below.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="pos-pill bg-amber-100 text-amber-800">Bulk entry</span>
                    <span class="pos-pill bg-slate-100 text-slate-700">Tab friendly</span>
                    <Link href="/product-save-page?id=0" class="pos-button-neutral">
                        <span class="material-icons text-[18px]">open_in_new</span>
                        Legacy Single Add
                    </Link>
                </div>
            </div>

            <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <p>Enter products row by row. Use <strong>Add Row</strong> for more lines, or press Enter on the opening stock field to jump to the next row.</p>
            </div>

            <div class="mb-5 grid gap-3 md:grid-cols-3">
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Total Products</p>
                    <p class="mt-2 text-3xl font-bold text-amber-900">{{ totalProducts }}</p>
                </div>
                <div class="rounded-3xl border border-sky-200 bg-sky-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Current Stock</p>
                    <p class="mt-2 text-3xl font-bold text-sky-900">{{ totalStockBase }}</p>
                </div>
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Estimated Profit</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-900">{{ totalProfit }}</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-white">
                <table class="min-w-[1400px] text-left text-sm">
                    <thead class="bg-amber-50 text-amber-900">
                        <tr>
                            <th class="w-20 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">SL</th>
                            <th class="w-64 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Product Name</th>
                            <th class="w-56 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Category</th>
                            <th class="w-28 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Unit</th>
                            <th class="w-40 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Purchase Price</th>
                            <th class="w-40 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Selling Price</th>
                            <th class="w-36 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Opening Stock</th>
                            <th class="w-56 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Image</th>
                            <th class="w-24 px-4 py-4 text-[11px] font-semibold uppercase tracking-[0.16em]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, index) in bulkRows" :key="row.uid" class="align-top">
                            <td class="px-4 py-4 align-top font-semibold text-slate-600 select-none cursor-default" tabindex="-1" role="cell" aria-readonly="true">{{ index + 1 }}</td>

                            <td class="px-4 py-4">
                                <input
                                    :id="rowInputId(index, 'name')"
                                    v-model="row.name"
                                    type="text"
                                    class="pos-input"
                                    placeholder="Whipping Cream"
                                    @keydown.enter.prevent="handleEnter(index, 'name')"
                                />
                                <p v-if="rowError(index, 'name')" class="mt-2 text-xs font-medium text-rose-600">{{ rowError(index, 'name') }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <div class="relative">
                                    <input
                                        :id="rowInputId(index, 'category_id')"
                                        v-model="row.category_label"
                                        type="text"
                                        class="pos-input"
                                        placeholder="Search category"
                                        autocomplete="off"
                                        @input="syncCategorySelection(row)"
                                        @focus="openCategoryDropdown(index)"
                                        @blur="handleCategoryBlur(row)"
                                        @keydown.enter.prevent="handleEnter(index, 'category_id')"
                                    />
                                    <div
                                        v-if="activeCategoryRow === index"
                                        class="absolute left-0 right-0 top-full z-30 mt-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"
                                    >
                                        <button
                                            v-for="category in filteredCategoriesForRow(row)"
                                            :key="category.id"
                                            type="button"
                                            class="flex w-full items-center px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-amber-50"
                                            @mousedown.prevent="selectCategory(row, category)"
                                        >
                                            {{ category.name }}
                                        </button>
                                        <p
                                            v-if="filteredCategoriesForRow(row).length === 0"
                                            class="px-4 py-3 text-sm text-slate-500"
                                        >
                                            No matching category. Create it from Category page first.
                                        </p>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Choose from existing categories.</p>
                                <p v-if="row.category_label && !row.category_id" class="mt-1 text-xs font-medium text-amber-700">
                                    Select a category from the list.
                                </p>
                                <p v-if="rowError(index, 'category_id')" class="mt-2 text-xs font-medium text-rose-600">{{ rowError(index, 'category_id') }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <select
                                    :id="rowInputId(index, 'unit_type')"
                                    v-model="row.unit_type"
                                    class="pos-input"
                                    @keydown.enter.prevent="handleEnter(index, 'unit_type')"
                                >
                                    <option v-for="option in unitOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </td>

                            <td class="px-4 py-4">
                                <input
                                    :id="rowInputId(index, 'purchase_price')"
                                    v-model="row.purchase_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="pos-input"
                                    placeholder="450"
                                    @keydown.enter.prevent="handleEnter(index, 'purchase_price')"
                                />
                                <p v-if="rowError(index, 'purchase_price')" class="mt-2 text-xs font-medium text-rose-600">{{ rowError(index, 'purchase_price') }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <input
                                    :id="rowInputId(index, 'selling_price')"
                                    v-model="row.selling_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="pos-input"
                                    placeholder="550"
                                    @keydown.enter.prevent="handleEnter(index, 'selling_price')"
                                />
                                <p v-if="rowError(index, 'selling_price')" class="mt-2 text-xs font-medium text-rose-600">{{ rowError(index, 'selling_price') }}</p>
                            </td>

                            <td class="px-4 py-4">
                                <input
                                    :id="rowInputId(index, 'qty')"
                                    v-model="row.qty"
                                    type="number"
                                    :min="row.unit_type === 'pcs' ? '1' : '0.001'"
                                    :step="row.unit_type === 'pcs' ? '1' : '0.001'"
                                    class="pos-input"
                                    :placeholder="row.unit_type === 'pcs' ? '100' : '10'"
                                    @keydown.enter.prevent="handleEnter(index, 'qty')"
                                />
                                <p v-if="rowError(index, 'qty')" class="mt-2 text-xs font-medium text-rose-600">{{ rowError(index, 'qty') }}</p>
                                <p class="mt-1 text-xs text-slate-500">Stored as base quantity automatically.</p>
                            </td>

                            <td class="px-4 py-4">
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-3">
                                    <div v-if="row.imagePreview" class="space-y-2">
                                        <img :src="row.imagePreview" class="h-24 w-full rounded-2xl object-cover" alt="Product preview" />
                                        <p class="text-xs font-medium text-slate-600">{{ row.imageName }}</p>
                                    </div>
                                    <div v-else class="flex h-24 flex-col items-center justify-center text-center text-slate-500">
                                        <span class="material-icons text-3xl text-amber-500">add_a_photo</span>
                                        <p class="mt-1 text-xs font-semibold text-slate-600">Optional image</p>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button type="button" class="pos-button-neutral px-3 py-2 text-xs" @click="triggerImagePicker(index)">
                                            Upload
                                        </button>
                                        <button v-if="row.img_url" type="button" class="pos-button-danger px-3 py-2 text-xs" @click="clearRowImage(index)">
                                            Clear
                                        </button>
                                    </div>

                                    <input
                                        :ref="(el) => setImageInputRef(el, index)"
                                        type="file"
                                        class="hidden"
                                        accept="image/*"
                                        @change="handleImageChange($event, index)"
                                    />
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-3 py-3 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700"
                                    @click.stop.prevent="removeRow(row.uid)"
                                    aria-label="Remove row"
                                    title="Remove row"
                                >
                                    <span class="material-icons text-[18px]">delete_outline</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="form.errors.products" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ form.errors.products }}
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" class="pos-button-primary" @click="addRow">
                    <span class="material-icons text-[18px]">add</span>
                    Add Row
                </button>

                <button type="button" class="pos-button-success" :disabled="isSaving" @click="submitBulk">
                    <span class="material-icons text-[18px]">save</span>
                    {{ isSaving ? 'Saving...' : 'Save All Products' }}
                </button>
            </div>
        </section>

        <section class="pos-section">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Existing Products</h2>
                    <p class="text-sm text-slate-500">Search, edit, delete, view details, and review stock and profit.</p>
                </div>
            </div>

            <input
                v-model="searchValue"
                type="text"
                class="pos-search mb-4"
                placeholder="Search products by name or category"
            />

            <EasyDataTable
                buttons-pagination
                alternating
                :headers="[
                    { text: 'No', value: 'id' },
                    { text: 'Product Name', value: 'name', sortable: true },
                    { text: 'Category', value: 'category_name', sortable: true },
                    { text: 'Unit', value: 'unit_type', sortable: true },
                    { text: 'Purchase Price', value: 'purchase_price', sortable: true },
                    { text: 'Selling Price', value: 'price_label', sortable: true },
                    { text: 'Profit', value: 'profit', sortable: true },
                    { text: 'Current Stock', value: 'stock_display', sortable: true },
                    { text: 'Image', value: 'img_url' },
                    { text: 'Action', value: 'action' },
                ]"
                :items="items"
                :search-value="searchValue"
                :search-field="searchField"
                :rows-per-page="5"
            >
                <template #item-id="{ id }">
                    <span class="font-medium text-slate-700">{{ items.findIndex(product => product.id === id) + 1 }}</span>
                </template>
                <template #item-category_name="{ category_name }">
                    <span class="font-medium text-slate-700">{{ category_name || '-' }}</span>
                </template>

                <template #item-unit_type="{ unit_type }">
                    <span class="font-medium text-slate-700">{{ formatUnitLabel(unit_type) }}</span>
                </template>

                <template #item-purchase_price="{ purchase_price }">
                    <span class="font-medium text-slate-700">{{ purchase_price !== null && purchase_price !== undefined ? purchase_price : '-' }}</span>
                </template>

                <template #item-price_label="{ price_label }">
                    <span class="font-medium text-slate-700">{{ price_label || '-' }}</span>
                </template>

                <template #item-stock_display="{ stock_display }">
                    <span class="font-semibold text-slate-700">{{ stock_display || '-' }}</span>
                </template>

                <template #item-profit="{ profit }">
                    <span v-if="profit !== null && profit !== undefined" :class="Number(profit) > 0 ? 'font-semibold text-emerald-600' : 'font-semibold text-rose-600'">
                        {{ profit }}
                    </span>
                    <span v-else class="text-slate-400">-</span>
                </template>

                <template #item-img_url="{ img_url }">
                    <img v-if="img_url" :src="img_url" class="h-12 w-12 rounded-2xl object-cover ring-1 ring-slate-200" />
                    <div v-else class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <span class="material-icons text-[18px]">image_not_supported</span>
                    </div>
                </template>

                <template #item-action="{ id }">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="openDetails(items.find((product) => product.id === id))" class="pos-button-neutral px-3 py-2">View Details</button>
                        <Link :href="`/product-save-page?id=${id}`" class="pos-button-success px-3 py-2">Edit</Link>
                        <button type="button" @click="deleteProduct(id)" class="pos-button-danger px-3 py-2">Delete</button>
                    </div>
                </template>
            </EasyDataTable>
        </section>

        <div v-if="showDetails && selectedProduct" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-2xl rounded-[28px] border border-white/60 bg-white p-5 shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
                <div class="mb-4 flex items-start justify-between gap-3 border-b border-slate-200 pb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Product Details</p>
                        <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ selectedProduct.name }}</h3>
                    </div>
                    <button type="button" class="pos-button-neutral px-3 py-2" @click="closeDetails">Close</button>
                </div>

                <div class="grid gap-4 md:grid-cols-[140px_1fr]">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">
                        <img v-if="selectedProduct.img_url" :src="selectedProduct.img_url" class="h-40 w-full object-cover" alt="Product image" />
                        <div v-else class="flex h-40 items-center justify-center text-slate-400">
                            <span class="material-icons text-5xl">inventory_2</span>
                        </div>
                    </div>

                    <div class="grid gap-3 text-sm">
                        <div class="grid gap-1 rounded-2xl bg-slate-50 p-3"><span class="text-slate-500">Category</span><span class="font-semibold text-slate-900">{{ selectedProduct.category_name || '-' }}</span></div>
                        <div class="grid gap-1 rounded-2xl bg-slate-50 p-3"><span class="text-slate-500">Unit</span><span class="font-semibold text-slate-900">{{ formatUnitLabel(selectedProduct.unit_type) }}</span></div>
                        <div class="grid gap-1 rounded-2xl bg-slate-50 p-3"><span class="text-slate-500">Purchase Price</span><span class="font-semibold text-slate-900">{{ selectedProduct.purchase_price ?? '-' }}</span></div>
                        <div class="grid gap-1 rounded-2xl bg-slate-50 p-3"><span class="text-slate-500">Selling Price</span><span class="font-semibold text-slate-900">{{ selectedProduct.price_label || selectedProduct.price || '-' }}</span></div>
                        <div class="grid gap-1 rounded-2xl bg-slate-50 p-3"><span class="text-slate-500">Profit</span><span class="font-semibold text-slate-900">{{ selectedProduct.profit ?? '-' }}</span></div>
                        <div class="grid gap-1 rounded-2xl bg-slate-50 p-3"><span class="text-slate-500">Current Stock</span><span class="font-semibold text-slate-900">{{ productStockLabel(selectedProduct) }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>