<script setup>
import { reactive } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const status = reactive({
    title: 'Create Customer',
    buttonTitle: 'Create Customer',
});

let params = new URLSearchParams(window.location.search);
let id = params.get('id');

const form = useForm({
    name: '',
    email: '',
    mobile: '',
    address: '',
    notes: '',
    id: id,
});

let list = page.props.customer;

let URL = '/create-customer';
if (id !== 0 && list !== null) {
    URL = '/update-customer';
    form.name = list.name;
    form.email = list.email;
    form.mobile = list.mobile;
    form.address = list.address || '';
    form.notes = list.notes || '';
    status.title = 'Update Customer';
    status.buttonTitle = 'Update Customer';
}

const submitForm = () => {
    form.post(URL, {
        preserveScroll: true,
        onSuccess: () => {
            setTimeout(() => {
                router.get('/customer-page');
            }, 500);
        },
    });
};
</script>

<template>
    <div class="pos-page">
        <section class="mx-auto w-full max-w-2xl pos-section">
            <div class="mb-6 border-b border-slate-200 pb-4">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Customer</p>
                <h1 class="mt-1 text-3xl font-bold text-slate-900">{{ status.title }}</h1>
                <p class="text-sm text-slate-500">Keep billing details clean for faster checkout.</p>
            </div>

            <form @submit.prevent="submitForm" class="grid gap-4">
                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-700" for="name">Name *</label>
                    <input v-model="form.name" class="pos-input" type="text" id="name" name="name" required>
                    <input type="text" v-model="form.id" hidden name="id">
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-700" for="mobile">Mobile *</label>
                    <input v-model="form.mobile" class="pos-input" type="text" id="mobile" name="mobile" required>
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-700" for="email">Email</label>
                    <input v-model="form.email" class="pos-input" type="email" id="email" name="email">
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-700" for="address">Address *</label>
                    <input v-model="form.address" class="pos-input" type="text" id="address" name="address" required>
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-700" for="notes">Notes</label>
                    <textarea v-model="form.notes" class="pos-input" id="notes" name="notes" rows="3"></textarea>
                </div>

                <button type="submit" class="pos-button-primary mt-2 w-full">{{ status.buttonTitle }}</button>
            </form>
        </section>
    </div>
</template>

<style scoped></style>
