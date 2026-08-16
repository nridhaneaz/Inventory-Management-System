<script setup>
import { Link, useForm,router,usePage } from '@inertiajs/vue3';

const page = usePage();
const form = useForm({
    email: '',
})

function submitForm() {
    if(form.email==''){
        alert('Email is required');
    }else{
        form.post('/send-otp',{
            onSuccess: () => {
                if(page.props.flash.status===true){
                    router.get("/verify-otp-page")
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
  <div class="w-full max-w-lg rounded-[32px] border border-white/70 bg-white/90 p-6 shadow-[0_30px_80px_rgba(15,23,42,0.18)] backdrop-blur md:p-10">
    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-700">City Bakeware Trade</p>
    <h2 class="mt-2 text-3xl font-bold text-slate-900">Send OTP</h2>
    <p class="mt-2 text-sm text-slate-500">Verify your account before resetting the password.</p>

    <form @submit.prevent="submitForm" class="mt-6 grid gap-4">
      <div class="grid gap-2">
        <label class="text-sm font-semibold text-slate-700">Email</label>
        <input v-model="form.email" type="email" class="pos-input" />
      </div>

      <button class="pos-button-primary w-full">Next</button>
    </form>

    <div class="mt-6 text-center text-sm text-slate-600">
      Don't have an account?
      <Link href="/registration-page" class="font-semibold text-amber-700 hover:text-amber-800">Sign up</Link>
    </div>
  </div>
</div>
</template>

<style scoped>

</style>

