<script setup>
import { computed, nextTick, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const searchValue = ref('');
const searchField = ['name'];
const items = computed(() => page.props.categories || []);

const createRow = () => ({
    uid: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
    name: '',
});

const bulkRows = ref([createRow()]);
const form = useForm({
    categories: [],
});

const rowInputId = (index) => `category-row-${index}`;

const rowHasContent = (row) => Boolean(row.name && row.name.trim());

const ensureTrailingEmptyRow = () => {
    if (bulkRows.value.length === 0) {
        bulkRows.value = [createRow()];
        return;
    }

    const lastRow = bulkRows.value[bulkRows.value.length - 1];

    if (rowHasContent(lastRow)) {
        bulkRows.value.push(createRow());
        return;
    }

    while (
        bulkRows.value.length > 1 &&
        !rowHasContent(bulkRows.value[bulkRows.value.length - 1]) &&
        !rowHasContent(bulkRows.value[bulkRows.value.length - 2])
    ) {
        bulkRows.value.pop();
    }
};

const focusRow = async (index) => {
    await nextTick();
    document.getElementById(rowInputId(index))?.focus();
};

const addRow = async () => {
    bulkRows.value.push(createRow());
    await focusRow(bulkRows.value.length - 1);
};

const handleRowActivity = () => {
    ensureTrailingEmptyRow();
};

const removeRow = async (index) => {
    if (bulkRows.value.length === 1) {
        bulkRows.value = [createRow()];
        form.clearErrors();
        await focusRow(0);
        return;
    }

    bulkRows.value.splice(index, 1);
    form.clearErrors();
    ensureTrailingEmptyRow();
    await focusRow(Math.max(0, Math.min(index, bulkRows.value.length - 1)));
};

const handleEnter = async (index) => {
    if (index < bulkRows.value.length - 1) {
        await focusRow(index + 1);
        return;
    }

    if (index === bulkRows.value.length - 1) {
        await addRow();
        return;
    }
};

const rowError = (index) => form.errors[`categories.${index}.name`];

const submitBulk = () => {
    form.categories = bulkRows.value.map((row) => ({
        name: row.name,
    }));

    form.post('/create-category', {
        preserveScroll: true,
        onSuccess: () => {
            bulkRows.value = [createRow()];
            form.reset();
            form.clearErrors();
            router.reload({
                only: ['categories'],
                preserveScroll: true,
                preserveState: true,
            });
        },
    });
};

const deleteCategory = (id) => {
    if (confirm('Are you sure you want to delete this category?')) {
        router.get(`/delete-category?id=${id}`);
    }
};
</script>

<template>
    <div class="pos-page">
        <section class="pos-section">
            <div class="mb-5 flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">City Bakeware Trade</p>
                    <h1 class="mt-1 text-3xl font-bold text-slate-900">Category Bulk Entry</h1>
                    <p class="mt-1 text-sm text-slate-500">Enter multiple bakery ingredient categories in one pass, Excel-style.</p>
                </div>
                <Link href="/category-save-page?id=0" class="pos-button-neutral">
                    <span class="material-icons text-[18px]">open_in_new</span>
                    Legacy Single Add
                </Link>
            </div>

            <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-white">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-amber-50 text-amber-900">
                        <tr>
                            <th class="w-20 px-4 py-4 font-semibold uppercase tracking-[0.2em]">SL</th>
                            <th class="px-4 py-4 font-semibold uppercase tracking-[0.2em]">Category Name</th>
                            <th class="w-40 px-4 py-4 font-semibold uppercase tracking-[0.2em]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, index) in bulkRows" :key="row.uid" class="align-top">
                            <td class="px-4 py-4 font-semibold text-slate-600">{{ index + 1 }}</td>
                            <td class="px-4 py-4">
                                <input
                                    :id="rowInputId(index)"
                                    v-model="row.name"
                                    type="text"
                                    class="pos-input"
                                    placeholder="Enter category name"
                                    @blur="handleRowActivity"
                                    @keydown.enter.prevent="handleEnter(index)"
                                />
                                <p v-if="rowError(index)" class="mt-2 text-xs font-medium text-rose-600">{{ rowError(index) }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <button
                                    v-if="index < bulkRows.length - 1 || rowHasContent(row)"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-3 py-3 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700"
                                    @click="removeRow(index)"
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

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" class="pos-button-primary" @click="addRow">
                    <span class="material-icons text-[18px]">add</span>
                    Add Row
                </button>

                <button type="button" class="pos-button-success" :disabled="form.processing" @click="submitBulk">
                    <span class="material-icons text-[18px]">save</span>
                    {{ form.processing ? 'Saving...' : 'Save All Categories' }}
                </button>
            </div>
        </section>

        <section class="pos-section">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Existing Categories</h2>
                    <p class="text-sm text-slate-500">Search, edit, and delete the active category list below.</p>
                </div>
            </div>

            <input
                v-model="searchValue"
                type="text"
                class="pos-search mb-4"
                placeholder="Search categories"
            />

            <EasyDataTable
                buttons-pagination
                alternating
                :rows-per-page="5"
                :headers="[
                    { text: 'No', value: 'id' },
                    { text: 'Category', value: 'name', sortable: true },
                    { text: 'Action', value: 'action' },
                ]"
                :items="items"
                :search-value="searchValue"
                :search-field="searchField"
            >
                <template #item-action="{ id }">
                    <div class="flex flex-wrap gap-2">
                        <Link :href="`/category-save-page?id=${id}`" class="pos-button-success px-3 py-2">Edit</Link>
                        <button class="pos-button-danger px-3 py-2" @click="deleteCategory(id)">Delete</button>
                    </div>
                </template>
            </EasyDataTable>
        </section>
    </div>
</template>

<style scoped></style>
