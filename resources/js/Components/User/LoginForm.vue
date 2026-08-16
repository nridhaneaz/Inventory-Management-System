<script setup>

import { useForm,router,usePage, Link } from '@inertiajs/vue3';

const page = usePage();
const form = useForm({
    email: '',
    password: '',
})

function submitForm() {
    if(form.email==''){
        alert('Email is required');
    }else if(form.password==''){
        alert('Password is required');
    }else{
        form.post('/user-login',{
            onSuccess: () => {
                if(page.props.flash.status===true){
                    router.get("/dashboard-page")
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
  <div class="w-full max-w-5xl overflow-hidden rounded-[32px] border border-white/70 bg-white/90 shadow-[0_30px_80px_rgba(15,23,42,0.18)] backdrop-blur md:grid md:grid-cols-2">
    <div class="hidden flex-col justify-between bg-gradient-to-br from-amber-500 to-orange-700 p-10 text-white md:flex">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-white/75">City Bakeware Trade</p>
        <h1 class="mt-4 text-4xl font-black leading-tight">Fast, clean billing for bakery raw materials.</h1>
        <p class="mt-4 max-w-md text-white/80">Designed for a wholesale and retail bakery supply counter with quick sale flow, stock visibility, and a professional checkout experience.</p>
      </div>
      <div class="rounded-3xl bg-white/10 p-5 text-sm text-white/85 backdrop-blur">
        Baker-friendly POS interface built for speed, clarity, and low-friction checkout.
      </div>
    </div>

    <div class="p-6 md:p-10">
      <div class="mb-8 flex items-center gap-3 md:hidden">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30">
          <span class="material-icons">bakery_dining</span>
        </div>
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-700">City Bakeware Trade</p>
          <p class="text-sm text-slate-500">Bakery supply POS</p>
        </div>
      </div>

      <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-700">Welcome back</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-900">Sign in to the POS</h2>
        <p class="mt-2 text-sm text-slate-500">Use your staff account to continue the sale workflow.</p>
      </div>

      <form @submit.prevent="submitForm" class="grid gap-4">
        <div class="grid gap-2">
          <label class="text-sm font-semibold text-slate-700">Email</label>
          <input v-model="form.email" type="email" class="pos-input" />
        </div>

        <div class="grid gap-2">
          <label class="text-sm font-semibold text-slate-700">Password</label>
          <input v-model="form.password" type="password" class="pos-input" />
        </div>

        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 text-slate-600">
            <input type="checkbox" class="rounded border-slate-300 text-amber-600 focus:ring-amber-200"/>
            Remember me
          </label>
          <Link href="/send-otp-page" class="font-semibold text-amber-700 hover:text-amber-800">Forgot password?</Link>
        </div>

        <button type="submit" class="pos-button-primary w-full">Sign In</button>
      </form>

      <div class="mt-6 text-center text-sm text-slate-600">
        Don't have an account?
        <Link href="/registration-page" class="font-semibold text-amber-700 hover:text-amber-800">Sign up</Link>
      </div>
    </div>
  </div>
</div>
</template>

<style scoped>

</style>
