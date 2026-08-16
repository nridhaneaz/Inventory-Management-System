
<template>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(249,115,22,0.16),_transparent_28%),linear-gradient(180deg,_#fffdf8_0%,_#f8fafc_100%)] flex items-center justify-center p-4">
      <div class="w-full max-w-2xl rounded-[32px] border border-white/70 bg-white/90 p-8 text-center shadow-[0_30px_80px_rgba(15,23,42,0.16)] backdrop-blur">
        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-amber-700">City Bakeware Trade</p>
        <h2 class="mt-3 text-4xl font-black tracking-tight text-slate-900">Bakery Supply POS</h2>
        <p class="mx-auto mt-4 max-w-xl text-base text-slate-600">Fast billing, stock-aware product selection, and clean invoice handling for bakery raw materials, tools, and ingredients.</p>

        <div class="mt-8 flex justify-center">
            <Link href="/login-page" class="pos-button-primary px-6 py-3 text-base">
                Start Sale
            </Link>
        </div>
      </div>
    </div>
</template>



<script setup>
import { Link } from '@inertiajs/vue3'
</script>
