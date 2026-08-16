<script setup>
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();

const navigation = [
    { href: '/dashboard-page', label: 'Dashboard', icon: 'dashboard' },
    { href: '/customer-page', label: 'Customer', icon: 'groups' },
    { href: '/category-page', label: 'Category', icon: 'category' },
    { href: '/product-page', label: 'Products', icon: 'inventory_2' },
    { href: '/invoice-page', label: 'Invoices', icon: 'receipt_long' },
    { href: '/sale-page', label: 'Create Sale', icon: 'point_of_sale' },
];

const isActive = (href) => page.url === href;

function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("-translate-x-full");
    const main = document.getElementById("top-nav");
    if (main) {
        main.classList.toggle("ml-0");
    }
}
</script>

<template>
    <div id="header" class="fixed left-0 right-0 top-0 z-50 h-[64px] border-b border-white/60 bg-white/90 backdrop-blur md:hidden">
        <div class="flex h-full items-center px-4">
            <button @click="toggleSidebar" id="menu-toggle" class="pos-button-neutral px-3 py-2">
                <span class="material-icons text-[20px]">menu</span>
            </button>
            <div class="ml-3">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-700">City Bakeware Trade</p>
                <p class="text-sm text-slate-500">Bakery raw-material POS</p>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <aside
        id="sidebar"
        class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white/95 shadow-[0_24px_60px_rgba(15,23,42,0.12)] backdrop-blur-xl transition-transform duration-300 md:translate-x-0"
    >
        <div class="border-b border-slate-200 px-6 py-6">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30">
                    <span class="material-icons">bakery_dining</span>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">City Bakeware Trade</h1>
                    <p class="text-xs font-medium uppercase tracking-[0.28em] text-slate-500">POS Dashboard</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-4 py-5">
            <ul class="space-y-2">
                <li v-for="item in navigation" :key="item.href">
                    <Link
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition"
                        :class="isActive(item.href) ? 'bg-amber-50 text-amber-800 shadow-inner ring-1 ring-amber-200' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        <span class="material-icons text-[20px] transition group-hover:scale-105" :class="isActive(item.href) ? 'text-amber-600' : 'text-slate-400'">{{ item.icon }}</span>
                        <span>{{ item.label }}</span>
                    </Link>
                </li>
            </ul>
        </nav>
        <div class="border-t border-slate-200 p-4">
            <Link
                href="/user-logout"
                class="pos-button-danger w-full"
            >
                <span class="material-icons text-[18px]">logout</span>
                Logout
            </Link>
        </div>
    </aside>
    <main id="top-nav" class="pos-shell ml-0 p-4 pt-[80px] md:ml-72 md:p-6 md:pt-6">
        <slot></slot>
    </main>
</template>

<style scoped></style>
