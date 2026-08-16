<script setup>
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

const formStatus=reactive({
    title:'Create Category',
    buttonTitle:'Create Category',
})

const page = usePage();

const form = useForm({
    name: '',
    id: ''
})

let params=new URLSearchParams(window.location.search);
let id=params.get('id');
let list = page.props.category;

let URL='/create-category';
if(id !==0 && list !== null ){
    URL='/update-category';
    form.name=list.name;
    form.id=id;
    formStatus.title='Update Category';
    formStatus.buttonTitle='Update Category';
}

const submitForm = () => {
    form.post(URL, {
        preserveScroll: true,
        onSuccess: () => {
            setTimeout(() => {
                router.get("/category-page");
            }, 500);
        }
    })
}

</script>

<template>
    <div class="pos-page">
      <section class="mx-auto w-full max-w-xl pos-section">
        <div class="mb-6 border-b border-slate-200 pb-4">
          <p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Category</p>
          <h2 class="mt-1 text-3xl font-bold text-slate-900">{{ formStatus.title }}</h2>
          <p class="text-sm text-slate-500">Group ingredients and shop stock into clear categories.</p>
        </div>

        <form @submit.prevent="submitForm" class="grid gap-4">
          <div class="grid gap-2">
            <label class="text-sm font-semibold text-slate-700">Category Name</label>
            <input v-model="form.name" type="text" class="pos-input" />
            <input type="text" v-model="form.id" hidden>
          </div>

          <button class="pos-button-primary w-full">{{ formStatus.buttonTitle }}</button>
        </form>
      </section>
    </div>
    </template>

<style scoped>

</style>
