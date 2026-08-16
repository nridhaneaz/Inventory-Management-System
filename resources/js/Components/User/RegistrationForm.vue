
<script setup>

import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3'
const page = usePage();

const form = useForm({
    name: '',
    email: '',
    mobile: '',
    password: '',
})

const submitForm = () => {
    if(form.name==''){
   alert('Name is required');
}else if(form.email==''){
    alert('Email is required');

}else if(form.mobile==''){
    alert('Mobile is required');

}else if(form.password==''){
    alert('Password is required');

}else{
    form.post('/user-registration',{
        onSuccess: () => {
            if(page.props.flash.status===true){
                router.get("/login-page")
            }
            else {
                alert(page.props.flash.message)
            }
        }
    });

}

}

</script>

<template>
<div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(249,115,22,0.16),_transparent_30%),linear-gradient(180deg,_#fffdf8_0%,_#f8fafc_100%)] flex items-center justify-center p-4">
  <div class="w-full max-w-3xl rounded-[32px] border border-white/70 bg-white/90 p-6 shadow-[0_30px_80px_rgba(15,23,42,0.18)] backdrop-blur md:p-10">
    <div class="mb-6 border-b border-slate-200 pb-4">
      <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-700">City Bakeware Trade</p>
      <h1 class="mt-2 text-3xl font-bold text-slate-900">Create staff account</h1>
      <p class="mt-2 text-sm text-slate-500">Register access for the bakery POS system.</p>
    </div>

    <form @submit.prevent="submitForm" class="grid gap-4 md:grid-cols-2">
      <div class="grid gap-2 md:col-span-2">
        <label class="text-sm font-semibold text-slate-700" for="name">Name</label>
        <input v-model="form.name" class="pos-input" type="text" id="name" name="name" placeholder="John Doe">
      </div>
      <div class="grid gap-2">
        <label class="text-sm font-semibold text-slate-700" for="email">Email</label>
        <input v-model="form.email" class="pos-input" type="email" id="email" name="email" placeholder="john@example.com">
      </div>
      <div class="grid gap-2">
        <label class="text-sm font-semibold text-slate-700" for="mobile">Mobile</label>
        <input v-model="form.mobile" class="pos-input" type="text" id="mobile" name="mobile">
      </div>
      <div class="grid gap-2 md:col-span-2">
        <label class="text-sm font-semibold text-slate-700" for="password">Password</label>
        <input v-model="form.password" class="pos-input" type="password" id="password" name="password">
      </div>

      <button type="submit" class="pos-button-primary md:col-span-2">Register</button>
    </form>

    <div class="mt-6 text-center text-sm text-slate-600">
      Don't have an account?
      <Link href="/login-page" class="font-semibold text-amber-700 hover:text-amber-800">Sign In</Link>
    </div>
  </div>
</div>

</template>

<style scoped>

</style>
