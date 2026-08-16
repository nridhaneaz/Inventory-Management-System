<template>
    <div class="pos-page">
        <section class="grid gap-6 xl:grid-cols-[1.15fr_1fr]">
            <div class="space-y-6">
                <section class="pos-section">
                    <div class="mb-4 border-b border-slate-200 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Create Sale</p>
                        <h1 class="mt-1 text-3xl font-bold text-slate-900">POS Workspace</h1>
                        <p class="text-sm text-slate-500">Select customer, add products, complete sale and print cash memo.</p>
                    </div>

                    <div class="grid gap-3">
                        <label class="text-sm font-semibold text-slate-700">Customer</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input
                                v-model="customerSearch"
                                type="text"
                                class="pos-input flex-1"
                                placeholder="Search existing customer..."
                                @focus="showCustomerDropdown = true"
                            />
                            <button type="button" class="pos-button-primary shrink-0" @click="openCustomerModal">
                                <span class="material-icons text-[18px]">person_add</span>
                                New Customer
                            </button>
                        </div>

                        <div
                            v-if="showCustomerDropdown && filteredCustomers.length > 0"
                            class="rounded-2xl border border-slate-200 bg-white shadow-lg"
                        >
                            <button
                                v-for="item in filteredCustomers"
                                :key="item.id"
                                type="button"
                                class="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left hover:bg-amber-50 last:border-b-0"
                                @click="selectCustomer(item)"
                            >
                                <span>
                                    <span class="font-semibold text-slate-900">{{ item.name }}</span>
                                    <span class="block text-xs text-slate-500">{{ item.mobile || 'No mobile' }}</span>
                                </span>
                                <span v-if="Number(item.balance_due) > 0" class="text-xs font-semibold text-rose-600">
                                    Due {{ currency(item.balance_due) }}
                                </span>
                            </button>
                        </div>

                        <div v-if="customer.id" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Selected Customer</p>
                            <p class="mt-1 text-lg font-bold text-slate-900">{{ customer.name }}</p>
                            <p class="text-sm text-slate-600">{{ customer.mobile || 'No mobile' }}</p>
                            <p v-if="customer.address" class="text-sm text-slate-600">{{ customer.address }}</p>
                        </div>
                    </div>
                </section>

                <section class="pos-section">
                    <div class="mb-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">Product Search</h2>
                                <p class="text-sm text-slate-500">Search products and add to cart.</p>
                            </div>
                            <button type="button" class="pos-button-neutral shrink-0" @click="openCustomItemModal">
                                <span class="material-icons text-[18px]">add_box</span>
                                Manual Cash Memo Items
                            </button>
                        </div>
                    </div>
                    <input
                        v-model="searchProduct"
                        type="text"
                        class="pos-search mb-4"
                        placeholder="Search product by name..."
                    />
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-amber-50 text-amber-900">
                                <tr>
                                    <th class="px-3 py-3 text-left">Product</th>
                                    <th class="px-3 py-3 text-left">Unit</th>
                                    <th class="px-3 py-3 text-left">Price</th>
                                    <th class="px-3 py-3 text-left">Stock</th>
                                    <th class="px-3 py-3 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="product in filteredProducts" :key="product.id">
                                    <td class="px-3 py-3 font-medium text-slate-900">{{ product.name }}</td>
                                    <td class="px-3 py-3">{{ unitLabel(product.unit_type) }}</td>
                                    <td class="px-3 py-3">{{ product.price_label || currency(product.price) }}</td>
                                    <td class="px-3 py-3">{{ product.stock_display || '-' }}</td>
                                    <td class="px-3 py-3">
                                        <button
                                            type="button"
                                            class="pos-button-primary px-3 py-2 text-xs"
                                            @click="addProduct(product)"
                                        >
                                            Add
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredProducts.length === 0">
                                    <td colspan="5" class="px-3 py-8 text-center text-slate-500">No products found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="pos-section">
                <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Sale Cart</h2>
                        <p class="text-sm text-slate-500">{{ new Date().toLocaleDateString('en-CA') }}</p>
                    </div>
                    <span class="pos-pill bg-amber-100 text-amber-800">Live POS</span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-amber-50 text-amber-900">
                            <tr>
                                <th class="px-3 py-3 text-left">No</th>
                                <th class="px-3 py-3 text-left">Product</th>
                                <th class="px-3 py-3 text-left">Unit</th>
                                <th class="px-3 py-3 text-left">Qty</th>
                                <th class="px-3 py-3 text-left">Price</th>
                                <th class="px-3 py-3 text-left">Amount</th>
                                <th class="px-3 py-3 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(product, index) in productList" :key="product.id">
                                <td class="px-3 py-3">{{ index + 1 }}</td>
                                <td class="px-3 py-3 font-medium">{{ product.name }}</td>
                                <td class="px-3 py-3">{{ unitLabel(product.unit_type) }}</td>
                                <td class="px-3 py-3">
                                    <input
                                        v-model="product.quantity"
                                        type="text"
                                        inputmode="decimal"
                                        class="pos-input min-w-28"
                                        placeholder="Enter qty"
                                        @input="updateLineSubtotal(product)"
                                        @blur="normalizeLineQuantity(product)"
                                    />
                                </td>
                                <td class="px-3 py-3">{{ priceLabel(product) }}</td>
                                <td class="px-3 py-3">{{ currency(product.subtotal) }}</td>
                                <td class="px-3 py-3">
                                    <button type="button" class="pos-button-danger px-2 py-1 text-xs" @click="removeProduct(index)">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="productList.length === 0">
                                <td colspan="7" class="px-3 py-10 text-center text-slate-500">No products in cart.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-semibold">{{ currency(calculate.subtotal) }}</span></div>

                    <label class="grid gap-2">
                        <span class="font-medium text-slate-700">Delivery Charge</span>
                        <input v-model="calculate.deliveryCharge" type="number" class="pos-input" min="0" step="0.01" @input="calculateTotal" />
                    </label>

                    <label class="grid gap-2">
                        <span class="font-medium text-slate-700">Discount (%)</span>
                        <input v-model="calculate.discountP" type="number" class="pos-input" min="0" @input="calculateTotal" />
                    </label>

                    <div class="flex justify-between"><span class="text-slate-500">Discount</span><span class="font-semibold">{{ currency(calculate.discount) }}</span></div>

                    <label class="grid gap-2">
                        <span class="font-medium text-slate-700">VAT (%)</span>
                        <input v-model="calculate.vatP" type="number" class="pos-input" min="0" @input="calculateTotal" />
                    </label>

                    <div class="flex justify-between"><span class="text-slate-500">VAT</span><span class="font-semibold">{{ currency(calculate.vat) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Current Sale Total</span><span class="font-semibold">{{ currency(Number(calculate.payable || 0) - Number(calculate.deliveryCharge || 0)) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Delivery Charge</span><span class="font-semibold">{{ currency(calculate.deliveryCharge) }}</span></div>
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base">
                        <span class="font-bold text-slate-900">Grand Total</span>
                        <span class="font-bold text-slate-900">{{ currency(calculate.grandTotal) }}</span>
                    </div>

                    <label class="grid gap-2">
                        <span class="font-medium text-slate-700">Payment Type</span>
                        <select v-model="paymentType" class="pos-input" @change="applyPaymentType">
                            <option value="paid">Paid</option>
                            <option value="due">Due</option>
                            <option value="cod">COD</option>
                        </select>
                    </label>

                    <label v-if="paymentType === 'cod'" class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input v-model="deliveryChargePaid" type="checkbox" @change="applyCodDeliveryPayment" />
                        Delivery charge paid separately
                    </label>

                    <label class="grid gap-2">
                        <span class="font-medium text-slate-700">Paid Amount</span>
                        <input v-model="calculate.amountPaid" type="number" class="pos-input" min="0" step="0.01" :readonly="paymentType === 'paid'" @input="handleAmountPaidInput" />
                    </label>

                    <div class="flex justify-between"><span class="text-slate-500">Due</span><span class="font-bold text-rose-700">{{ currency(calculate.due) }}</span></div>
                </div>

                <button
                    type="button"
                    class="pos-button-success mt-5 w-full py-3 text-base"
                    :disabled="isSaving"
                    @click="completeSale"
                >
                    {{ isSaving ? 'Creating...' : 'Create Cash Memo' }}
                </button>
            </aside>
        </section>

        <!-- New Customer Modal -->
        <div v-if="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-[28px] border border-white/60 bg-white p-5 shadow-xl">
                <h3 class="text-xl font-bold text-slate-900">Create New Customer</h3>
                <p class="mt-1 text-sm text-slate-500">Customer will be saved permanently and selected for this sale.</p>

                <form class="mt-4 grid gap-3" @submit.prevent="saveCustomer">
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">
                        Name *
                        <input v-model="customerForm.name" type="text" class="pos-input" required />
                        <span v-if="customerForm.errors.name" class="text-xs text-rose-600">{{ customerForm.errors.name }}</span>
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">
                        Mobile *
                        <input v-model="customerForm.mobile" type="text" class="pos-input" required />
                        <span v-if="customerForm.errors.mobile" class="text-xs text-rose-600">{{ customerForm.errors.mobile }}</span>
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">
                        Email
                        <input v-model="customerForm.email" type="email" class="pos-input" />
                        <span v-if="customerForm.errors.email" class="text-xs text-rose-600">{{ customerForm.errors.email }}</span>
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">
                        Address *
                        <input v-model="customerForm.address" type="text" class="pos-input" required />
                        <span v-if="customerForm.errors.address" class="text-xs text-rose-600">{{ customerForm.errors.address }}</span>
                    </label>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">
                        Notes
                        <textarea v-model="customerForm.notes" rows="2" class="pos-input"></textarea>
                    </label>

                    <div class="mt-2 flex justify-end gap-2">
                        <button type="button" class="pos-button-neutral" @click="closeCustomerModal">Cancel</button>
                        <button type="submit" class="pos-button-primary" :disabled="customerForm.processing">
                            {{ customerForm.processing ? 'Saving...' : 'Save Customer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manual Cash Memo Items Modal -->
        <div v-if="showCustomItemModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-7xl rounded-[28px] border border-white/60 bg-white p-5 shadow-xl">
                <h3 class="text-xl font-bold text-slate-900">Manual Cash Memo Items</h3>
                <p class="mt-1 text-sm text-slate-500">Type as many one-time items as needed. These rows are saved with this sale only and do not affect inventory stock.</p>

                <form class="mt-4" @submit.prevent="addCustomItemRows">
                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <table class="min-w-[1140px] w-full text-sm">
                            <thead class="bg-slate-50 text-slate-700">
                                <tr>
                                    <th class="px-2 py-3 text-left">No</th>
                                    <th class="px-2 py-3 text-left">Description / Item Name *</th>
                                    <th class="px-2 py-3 text-left">Unit</th>
                                    <th class="px-2 py-3 text-left">Qty *</th>
                                    <th class="px-2 py-3 text-left">Rate *</th>
                                    <th class="px-2 py-3 text-left">Purchase Price</th>
                                    <th class="px-2 py-3 text-left">Profit</th>
                                    <th class="px-2 py-3 text-left">Amount</th>
                                    <th class="px-2 py-3 text-left">Note</th>
                                    <th class="px-2 py-3 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(row, index) in customItemRows" :key="row.key">
                                    <td class="px-2 py-2 font-medium">{{ index + 1 }}</td>
                                    <td class="px-2 py-2"><input v-model="row.name" class="pos-input min-w-52" placeholder="Type new item..." /></td>
                                    <td class="px-2 py-2">
                                        <select v-model="row.unit" class="pos-input min-w-20">
                                            <option value="pcs">PCS</option><option value="kg">KG</option><option value="gm">GM</option>
                                        </select>
                                    </td>
                                    <td class="px-2 py-2"><input v-model="row.quantity" type="number" min="0.001" step="0.001" class="pos-input min-w-20" /></td>
                                    <td class="px-2 py-2"><input v-model="row.sellingPrice" type="number" min="0" step="0.01" class="pos-input min-w-24" /></td>
                                    <td class="px-2 py-2"><input v-model="row.costPrice" type="number" min="0" step="0.01" class="pos-input min-w-24" placeholder="Optional" /></td>
                                    <td class="px-2 py-2 font-semibold text-emerald-700">{{ customRowProfit(row) === null ? '—' : currency(customRowProfit(row)) }}</td>
                                    <td class="px-2 py-2 font-semibold">{{ currency(customRowAmount(row)) }}</td>
                                    <td class="px-2 py-2"><input v-model="row.note" class="pos-input min-w-32" placeholder="Optional" /></td>
                                    <td class="px-2 py-2"><button type="button" class="pos-button-danger px-2 py-1 text-xs" @click="removeCustomItemRow(index)">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <button type="button" class="pos-button-neutral" @click="addCustomItemRow">+ Add Row</button>
                        <div class="flex gap-2">
                            <button type="button" class="pos-button-neutral" @click="closeCustomItemModal">Cancel</button>
                            <button type="submit" class="pos-button-primary">Add All Items to Cart</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Invoice Preview Modal -->
        <div v-if="showInvoicePreview && completedInvoice" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm print-modal">
            <div class="w-full max-w-4xl rounded-[28px] border border-white/60 bg-white p-4 md:p-6 print-modal__content">
                <CashMemo :invoice="completedInvoice" :business="business" print-id="sale-cash-memo-print" />

                <div class="mt-5 flex justify-end gap-3 no-print">
                    <button type="button" class="pos-button-neutral" @click="closeInvoicePreview">Close</button>
                    <button type="button" class="pos-button-primary" @click="printInvoice">Print Cash Memo</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { createToaster } from '@meforma/vue-toaster';
import axios from '../../bootstrap';
import CashMemo from '../Invoice/CashMemo.vue';
import { printCashMemo } from '../../utils/printCashMemo';

const page = usePage();
const toaster = createToaster({});
const business = computed(() => page.props.business || {});

const customers = ref([...(page.props.customers || [])]);
const products = ref([...(page.props.products || [])]);

const customerSearch = ref('');
const showCustomerDropdown = ref(false);
const searchProduct = ref('');
const productList = ref([]);
const isSaving = ref(false);
const isAmountPaidManuallySet = ref(false);
const paymentType = ref('paid');
const deliveryChargePaid = ref(false);
const showCustomerModal = ref(false);
const showCustomItemModal = ref(false);
const showInvoicePreview = ref(false);
const completedInvoice = ref(null);

const customer = reactive({
    id: '',
    name: '',
    mobile: '',
    address: '',
    balance_due: 0,
});

const customerForm = reactive({
    name: '',
    mobile: '',
    email: '',
    address: '',
    notes: '',
    processing: false,
    errors: {},
});

let customItemRowKey = 0;
const newCustomItemRow = () => ({
    key: ++customItemRowKey,
    name: '',
    unit: 'pcs',
    quantity: 1,
    sellingPrice: '',
    costPrice: '',
    note: '',
});
const customItemRows = ref([newCustomItemRow()]);

const calculate = reactive({
    subtotal: 0,
    deliveryCharge: 0,
    discountP: 0,
    discount: 0,
    vatP: 0,
    vat: 0,
    payable: 0,
    grandTotal: 0,
    amountPaid: 0,
    due: 0,
});

const unitLabel = (unitType) => {
    if (unitType === 'pcs') return 'PCS';
    if (unitType === 'gm') return 'GM';
    return 'KG';
};

const quantityMin = (unitType) => (unitType === 'pcs' ? '1' : '0.001');
const priceLabel = (product) => `৳${Number(product.unit_price || product.price || 0).toFixed(2)} / ${unitLabel(product.unit_type)}`;
const currency = (value) => `৳${Number(value || 0).toFixed(2)}`;

const filteredCustomers = computed(() => {
    const query = customerSearch.value.trim().toLowerCase();
    if (!query) {
        return customers.value.slice(0, 8);
    }

    return customers.value.filter((item) => {
        return (
            String(item.name || '').toLowerCase().includes(query) ||
            String(item.mobile || '').includes(query) ||
            String(item.email || '').toLowerCase().includes(query)
        );
    }).slice(0, 8);
});

const filteredProducts = computed(() => {
    const query = searchProduct.value.trim().toLowerCase();
    if (!query) {
        return products.value.slice(0, 10);
    }

    return products.value.filter((product) => String(product.name || '').toLowerCase().includes(query)).slice(0, 10);
});

const selectCustomer = (item) => {
    customer.id = item.id;
    customer.name = item.name;
    customer.mobile = item.mobile || '';
    customer.address = item.address || '';
    customer.balance_due = Number(item.balance_due || 0);
    customerSearch.value = item.name;
    showCustomerDropdown.value = false;
    isAmountPaidManuallySet.value = false;
    applyAutomaticPaymentAmount();
    syncDue();
};

const openCustomerModal = () => {
    showCustomerModal.value = true;
};

const closeCustomerModal = () => {
    showCustomerModal.value = false;
    customerForm.errors = {};
};

const saveCustomer = async () => {
    customerForm.processing = true;
    customerForm.errors = {};

    try {
        const response = await axios.post('/create-customer', {
            name: customerForm.name,
            mobile: customerForm.mobile,
            email: customerForm.email,
            address: customerForm.address,
            notes: customerForm.notes,
        }, {
            headers: { Accept: 'application/json' },
        });

        const savedCustomer = response.data?.customer;
        if (savedCustomer) {
            const exists = customers.value.find((item) => item.id === savedCustomer.id);
            if (!exists) {
                customers.value.unshift(savedCustomer);
            }
            selectCustomer(savedCustomer);
            closeCustomerModal();
            customerForm.name = '';
            customerForm.mobile = '';
            customerForm.email = '';
            customerForm.address = '';
            customerForm.notes = '';
            toaster.success('Customer created and selected.');
        }
    } catch (error) {
        if (error.response?.status === 422) {
            customerForm.errors = error.response.data?.errors || {};
            if (error.response.data?.errors?.mobile?.[0] === 'Customer already exists.') {
                toaster.error('Customer already exists. Please select the existing customer.');
            }
        } else {
            toaster.error(error.response?.data?.message || 'Failed to create customer.');
        }
    } finally {
        customerForm.processing = false;
    }
};

const addProduct = (product) => {
    const existing = productList.value.find((item) => item.id === product.id);
    if (existing) {
        return;
    }

    const unitType = product.unit_type || 'pcs';
    productList.value.push({
        id: product.id,
        name: product.name,
        unit_type: unitType,
        quantity: '',
        stock_quantity: Number(product.stock_quantity || product.unit || 0),
        unit_price: Number(product.price || 0),
        subtotal: 0,
    });
};

const openCustomItemModal = () => {
    if (customItemRows.value.length === 0) {
        customItemRows.value.push(newCustomItemRow());
    }
    showCustomItemModal.value = true;
};

const closeCustomItemModal = () => {
    showCustomItemModal.value = false;
};

const customRowAmount = (row) => {
    const quantity = parseQuantity(row.quantity);
    const sellingPrice = Number(row.sellingPrice);
    return quantity !== null && quantity > 0 && Number.isFinite(sellingPrice) && sellingPrice >= 0
        ? quantity * sellingPrice
        : 0;
};

const customRowProfit = (row) => {
    if (row.costPrice === '') {
        return null;
    }

    const quantity = parseQuantity(row.quantity);
    const sellingPrice = Number(row.sellingPrice);
    const purchasePrice = Number(row.costPrice);

    return quantity !== null && quantity > 0 && Number.isFinite(sellingPrice) && Number.isFinite(purchasePrice)
        ? (sellingPrice - purchasePrice) * quantity
        : null;
};

const addCustomItemRow = () => {
    customItemRows.value.push(newCustomItemRow());
};

const removeCustomItemRow = (index) => {
    customItemRows.value.splice(index, 1);
    if (customItemRows.value.length === 0) {
        addCustomItemRow();
    }
};

const addCustomItemRows = () => {
    const rowsWithValues = customItemRows.value.filter((row) => (
        row.name.trim() || row.sellingPrice !== '' || String(row.quantity || '') !== '1' || row.note.trim()
    ));

    if (rowsWithValues.length === 0) {
        toaster.error('Add at least one manual item.');
        return;
    }

    const items = [];
    for (const row of rowsWithValues) {
        const quantity = parseQuantity(row.quantity);
        const sellingPrice = Number(row.sellingPrice);
        const costPrice = row.costPrice === '' ? null : Number(row.costPrice);

        if (!row.name.trim() || quantity === null || quantity <= 0 || !Number.isFinite(sellingPrice) || sellingPrice < 0) {
            toaster.error('Each manual row needs a valid item name, quantity, and rate.');
            return;
        }

        if (costPrice !== null && (!Number.isFinite(costPrice) || costPrice < 0)) {
            toaster.error('Enter a valid cost price or leave it empty.');
            return;
        }

        items.push({
            id: `custom-${Date.now()}-${row.key}`,
            name: row.name.trim(),
            unit_type: row.unit,
            quantity: String(quantity),
            unit_price: sellingPrice,
            cost_price: costPrice,
            note: row.note.trim(),
            is_custom_item: true,
            subtotal: (quantity * sellingPrice).toFixed(2),
        });
    }

    productList.value.push(...items);
    calculateTotal();
    showCustomItemModal.value = false;
    customItemRows.value = [newCustomItemRow()];
};

const removeProduct = (index) => {
    productList.value.splice(index, 1);
    calculateTotal();
};

const parseQuantity = (value) => {
    const parsed = Number(String(value ?? '').replace(/,/g, '').trim());
    return Number.isFinite(parsed) ? parsed : null;
};

const maxQuantityFor = (product) => (
    product.is_custom_item
        ? Number.POSITIVE_INFINITY
        : (
    product.unit_type === 'pcs'
        ? Number(product.stock_quantity || 0)
        : Number(product.stock_quantity || 0) / (product.unit_type === 'kg' ? 1000 : 1)
        )
);

const updateLineSubtotal = (product) => {
    const qty = parseQuantity(product.quantity);

    if (qty === null || qty <= 0) {
        product.subtotal = 0;
        calculateTotal();
        return;
    }

    product.subtotal = (Number(product.unit_price || 0) * qty).toFixed(2);
    calculateTotal();
};

const normalizeLineQuantity = (product) => {
    const qty = parseQuantity(product.quantity);
    const maxQuantity = maxQuantityFor(product);
    const minQuantity = Number(quantityMin(product.unit_type));

    if (qty === null || qty <= 0) {
        product.quantity = '';
        product.subtotal = 0;
        calculateTotal();
        return;
    }

    let normalizedQty = qty;

    if (product.unit_type === 'pcs') {
        normalizedQty = Math.floor(normalizedQty);
    }

    if (!product.is_custom_item && normalizedQty > maxQuantity) {
        normalizedQty = maxQuantity;
        toaster.error(`Only ${normalizedQty} ${unitLabel(product.unit_type)} available for ${product.name}.`);
    }

    if (normalizedQty < minQuantity) {
        normalizedQty = minQuantity;
    }

    product.quantity = product.unit_type === 'pcs'
        ? String(normalizedQty)
        : String(Number(normalizedQty.toFixed(3)).toString());

    updateLineSubtotal(product);
};

const isValidLineQuantity = (product) => {
    const qty = parseQuantity(product.quantity);
    const maxQuantity = maxQuantityFor(product);
    const minQuantity = Number(quantityMin(product.unit_type));

    if (qty === null || qty <= 0) {
        return false;
    }

    if (product.unit_type === 'pcs' && !Number.isInteger(qty)) {
        return false;
    }

    return qty >= minQuantity && (product.is_custom_item || qty <= maxQuantity);
};

const calculateTotal = () => {
    let subtotal = 0;
    productList.value.forEach((product) => {
        subtotal += parseFloat(product.subtotal || 0);
    });

    const deliveryCharge = parseFloat(calculate.deliveryCharge || 0);

    calculate.subtotal = subtotal.toFixed(2);
    calculate.discount = ((subtotal * parseFloat(calculate.discountP || 0)) / 100).toFixed(2);
    const afterDiscount = subtotal - parseFloat(calculate.discount);
    calculate.vat = ((afterDiscount * parseFloat(calculate.vatP || 0)) / 100).toFixed(2);
    calculate.payable = (afterDiscount + parseFloat(calculate.vat) + deliveryCharge).toFixed(2);
    calculate.grandTotal = calculate.payable;

    if (!isAmountPaidManuallySet.value) {
        applyAutomaticPaymentAmount();
    } else if (Number(calculate.amountPaid || 0) > Number(calculate.grandTotal || 0)) {
        calculate.amountPaid = calculate.grandTotal;
    }

    syncDue();
};

const syncDue = () => {
    calculate.due = Math.max(0, parseFloat(calculate.grandTotal) - parseFloat(calculate.amountPaid || 0)).toFixed(2);
};

const handleAmountPaidInput = () => {
    isAmountPaidManuallySet.value = true;
    if (Number(calculate.amountPaid || 0) > Number(calculate.grandTotal || 0)) {
        calculate.amountPaid = calculate.grandTotal;
    }
    syncDue();
};

const applyAutomaticPaymentAmount = () => {
    if (paymentType.value === 'paid') {
        calculate.amountPaid = calculate.grandTotal;
    } else if (paymentType.value === 'cod' && deliveryChargePaid.value) {
        calculate.amountPaid = Math.min(Number(calculate.deliveryCharge || 0), Number(calculate.grandTotal || 0));
    } else {
        calculate.amountPaid = 0;
    }
};

const applyPaymentType = () => {
    isAmountPaidManuallySet.value = false;
    if (paymentType.value !== 'cod') {
        deliveryChargePaid.value = false;
    }
    applyAutomaticPaymentAmount();
    syncDue();
};

const applyCodDeliveryPayment = () => {
    isAmountPaidManuallySet.value = false;
    applyAutomaticPaymentAmount();
    syncDue();
};

const resetSaleForm = () => {
    productList.value = [];
    customer.id = '';
    customer.name = '';
    customer.mobile = '';
    customer.address = '';
    customer.balance_due = 0;
    customerSearch.value = '';
    calculate.subtotal = 0;
    calculate.deliveryCharge = 0;
    calculate.discountP = 0;
    calculate.discount = 0;
    calculate.vatP = 0;
    calculate.vat = 0;
    calculate.payable = 0;
    calculate.grandTotal = 0;
    calculate.amountPaid = 0;
    calculate.due = 0;
    isAmountPaidManuallySet.value = false;
    paymentType.value = 'paid';
    deliveryChargePaid.value = false;
};

const completeSale = async () => {
    if (!customer.id) {
        toaster.error('Please select or create a customer.');
        return;
    }

    if (productList.value.length === 0) {
        toaster.error('Add at least one product to the cart.');
        return;
    }

    productList.value.forEach((product) => normalizeLineQuantity(product));

    const invalidProduct = productList.value.find((product) => !isValidLineQuantity(product));
    if (invalidProduct) {
        toaster.error(`Enter a valid quantity for ${invalidProduct.name}.`);
        return;
    }

    isSaving.value = true;

    try {
        const response = await axios.post('/create-invoice', {
            cus_id: customer.id,
            products: productList.value.map((product) => ({
                id: product.id,
                is_custom_item: Boolean(product.is_custom_item),
                name: product.is_custom_item ? product.name : undefined,
                quantity: Number(product.quantity),
                unit: product.unit_type,
                unit_price: Number(product.unit_price),
                subtotal: Number(product.subtotal),
                cost_price: product.is_custom_item ? product.cost_price : undefined,
                note: product.is_custom_item ? product.note : undefined,
            })),
            total: calculate.subtotal,
            discount: calculate.discount,
            vat: calculate.vat,
            payable: calculate.payable,
            delivery_charge: calculate.deliveryCharge,
            amount_paid: calculate.amountPaid,
            payment_type: paymentType.value,
            delivery_charge_paid: deliveryChargePaid.value,
        }, {
            headers: { Accept: 'application/json' },
        });

        completedInvoice.value = response.data?.invoice;
        showInvoicePreview.value = true;
        toaster.success(response.data?.message || 'Sale completed successfully.');

        completedInvoice.value.invoice_products?.forEach((line) => {
            const product = products.value.find((item) => item.id === line.product_id);
            if (product) {
                const remaining = Number(product.stock_quantity || product.unit || 0) - Number(line.base_quantity || 0);
                product.stock_quantity = Math.max(0, remaining);
                product.unit = product.stock_quantity;
            }
        });

        products.value = products.value.filter((product) => Number(product.stock_quantity ?? product.unit ?? 0) > 0);

        const customerIndex = customers.value.findIndex((item) => item.id === customer.id);
        if (customerIndex !== -1 && completedInvoice.value?.customer) {
            customers.value[customerIndex] = completedInvoice.value.customer;
        }

        resetSaleForm();
    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data?.errors || {};
            const firstError = Object.values(errors).flat()[0];
            toaster.error(firstError || 'Please fix validation errors.');
        } else {
            toaster.error(error.response?.data?.message || 'Failed to complete sale.');
        }
    } finally {
        isSaving.value = false;
    }
};

const closeInvoicePreview = () => {
    showInvoicePreview.value = false;
};

const printInvoice = () => {
    if (!printCashMemo('sale-cash-memo-print')) {
        toaster.error('Unable to open print window.');
    }
};
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    .print-modal,
    .print-modal * {
        visibility: visible;
    }

    .print-modal {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white;
    }

    .no-print {
        display: none !important;
    }
}
</style>
