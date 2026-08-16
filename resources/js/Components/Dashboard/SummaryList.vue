<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from '../../bootstrap';
import { createToaster } from "@meforma/vue-toaster";

const page = usePage();
const monthlyProfits = ref([]);
const dailyProfits = ref([]);
const isUpdating = ref(false);
const isUpdatingDaily = ref(false);
const toaster = createToaster({});

const money = new Intl.NumberFormat('en-BD', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const parseNumeric = (value) => {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }

    const cleaned = String(value ?? '').replace(/,/g, '').trim();
    const parsed = Number(cleaned);

    return Number.isFinite(parsed) ? parsed : 0;
};

const compactMoney = (value) => money.format(parseNumeric(value));
const countValue = (value) => new Intl.NumberFormat('en-BD').format(parseNumeric(value));
const statCards = [
    { key: 'collection', label: 'Total Collection', icon: 'payments', tone: 'from-amber-500 to-orange-600', suffix: ' BDT' },
    { key: 'customer', label: 'Total Customer', icon: 'groups', tone: 'from-slate-700 to-slate-900' },
    { key: 'category', label: 'Total Category', icon: 'category', tone: 'from-amber-600 to-amber-700' },
    { key: 'vat', label: 'Vat Collection', icon: 'receipt_long', tone: 'from-emerald-600 to-emerald-700', suffix: ' BDT' },
    { key: 'total', label: 'Total Sale', icon: 'point_of_sale', tone: 'from-stone-700 to-stone-900', suffix: ' BDT' },
    { key: 'invoice', label: 'Invoices', icon: 'description', tone: 'from-orange-600 to-red-600' },
    { key: 'weightStock', label: 'Weight Stock', icon: 'inventory_2', tone: 'from-teal-600 to-cyan-700', unit: 'weight' },
    { key: 'pieceStock', label: 'Pieces Stock', icon: 'inventory_2', tone: 'from-cyan-600 to-sky-700', unit: 'pieces' },
    { key: 'totalProfit', label: 'Total Profit', icon: 'trending_up', tone: 'from-emerald-600 to-green-700', suffix: ' BDT' },
    { key: 'todayProfit', label: "Today's Profit", icon: 'today', tone: 'from-blue-600 to-indigo-700', suffix: ' BDT' },
    { key: 'currentMonthProfit', label: 'Current Month Profit', icon: 'calendar_month', tone: 'from-amber-500 to-yellow-600', suffix: ' BDT' },
];

const formatWeightStock = (value) => {
    const grams = Number(value || 0);

    if (grams >= 1000) {
        return `${(grams / 1000).toFixed(3).replace(/\.0+$/, '').replace(/\.$/, '')} KG`;
    }

    return `${new Intl.NumberFormat('en-BD').format(grams)} GM`;
};

onMounted(() => {
  // Set monthly profits data from the backend
  if (page.props.data.monthlyProfits) {
    monthlyProfits.value = page.props.data.monthlyProfits;
  }
  
  // Set daily profits data from the backend
  if (page.props.data.dailyProfits) {
    dailyProfits.value = page.props.data.dailyProfits;
  }
});

// Format currency function
const formatCurrency = (value) => compactMoney(value);

// Format date function
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

// Function to manually update monthly profit data
const updateMonthlyProfitData = async () => {
  if (isUpdating.value) return;
  
  isUpdating.value = true;
  
  try {
    const response = await axios.post('/update-monthly-profit');
    if (response.data.status) {
      toaster.success("Monthly profit data updated successfully!");
      // Refresh the page to show updated data
      window.location.reload();
    }
  } catch (error) {
    toaster.error("Failed to update monthly profit data");
    console.error(error);
  } finally {
    isUpdating.value = false;
  }
};

// Function to manually update daily profit data
const updateDailyProfitData = async () => {
  if (isUpdatingDaily.value) return;
  
  isUpdatingDaily.value = true;
  
  try {
    const response = await axios.post('/update-daily-profit');
    if (response.data.status) {
      toaster.success("Daily profit data updated successfully!");
      // Refresh the page to show updated data
      window.location.reload();
    }
  } catch (error) {
    toaster.error("Failed to update daily profit data");
    console.error(error);
  } finally {
    isUpdatingDaily.value = false;
  }
};
</script>

<template>
<div class="pos-page">
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        <article
            v-for="card in statCards"
            :key="card.key"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.08)]"
        >
            <div :class="['flex h-full items-center justify-between bg-gradient-to-br p-5 text-white', card.tone]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">{{ card.label }}</p>
                    <h3 class="mt-2 text-3xl font-bold leading-none">
                        <template v-if="card.key === 'todayProfit'">
                            {{ compactMoney(page.props.data.todayProfit?.profit_amount || 0) }}{{ card.suffix || '' }}
                        </template>
                        <template v-else-if="card.key === 'currentMonthProfit'">
                            {{ compactMoney(page.props.data.currentMonthProfit?.profit_amount || 0) }}{{ card.suffix || '' }}
                        </template>
                        <template v-else-if="card.key === 'totalProfit'">
                            {{ compactMoney(page.props.data['totalProfit'] || 0) }}{{ card.suffix || '' }}
                        </template>
                        <template v-else-if="card.key === 'weightStock'">
                            {{ formatWeightStock(page.props.data.weightStock || 0) }}
                        </template>
                        <template v-else-if="card.key === 'pieceStock'">
                            {{ countValue(page.props.data.pieceStock || 0) }} PCS
                        </template>
                        <template v-else-if="card.key === 'collection' || card.key === 'vat' || card.key === 'total'">
                            {{ compactMoney(page.props.data[card.key] || 0) }}{{ card.suffix || '' }}
                        </template>
                        <template v-else>
                            {{ countValue(page.props.data[card.key] || 0) }}
                        </template>
                    </h3>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15">
                    <span class="material-icons text-[24px] leading-none">{{ card.icon }}</span>
                </div>
            </div>
        </article>
    </section>

    <section class="pos-section">
        <div class="mb-5 flex flex-col gap-3 border-b border-slate-200 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Daily Profit History</h2>
                <p class="text-sm text-slate-500">Track day-by-day performance for the bakery counter.</p>
            </div>
            <button @click="updateDailyProfitData" class="pos-button-primary" :disabled="isUpdatingDaily">
                <span class="material-icons text-[18px]" :class="{ 'animate-spin': isUpdatingDaily }">sync</span>
                {{ isUpdatingDaily ? 'Updating...' : 'Update Daily Profit' }}
            </button>
        </div>

        <div class="pos-table-wrap">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-amber-50 text-amber-900">
                        <tr>
                            <th class="px-5 py-4 font-semibold uppercase tracking-[0.2em]">Date</th>
                            <th class="px-5 py-4 font-semibold uppercase tracking-[0.2em]">Total Sales</th>
                            <th class="px-5 py-4 font-semibold uppercase tracking-[0.2em]">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="profit in dailyProfits" :key="profit.date" class="bg-white">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ formatDate(profit.date) }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ formatCurrency(profit.total_sales) }}</td>
                            <td class="px-5 py-4 font-semibold" :class="profit.profit_amount > 0 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ formatCurrency(profit.profit_amount) }}
                            </td>
                        </tr>
                        <tr v-if="dailyProfits.length === 0">
                            <td colspan="3" class="px-5 py-10 text-center text-slate-500">No daily profit history available yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="pos-section">
        <div class="mb-5 flex flex-col gap-3 border-b border-slate-200 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Monthly Profit History</h2>
                <p class="text-sm text-slate-500">Review monthly revenue and margin trends.</p>
            </div>
            <button @click="updateMonthlyProfitData" class="pos-button-success" :disabled="isUpdating">
                <span class="material-icons text-[18px]" :class="{ 'animate-spin': isUpdating }">sync</span>
                {{ isUpdating ? 'Updating...' : 'Update Monthly Profit' }}
            </button>
        </div>

        <div class="pos-table-wrap">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-stone-50 text-stone-700">
                        <tr>
                            <th class="px-5 py-4 font-semibold uppercase tracking-[0.2em]">Month</th>
                            <th class="px-5 py-4 font-semibold uppercase tracking-[0.2em]">Year</th>
                            <th class="px-5 py-4 font-semibold uppercase tracking-[0.2em]">Total Sales</th>
                            <th class="px-5 py-4 font-semibold uppercase tracking-[0.2em]">Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="profit in monthlyProfits" :key="`${profit.month}-${profit.year}`" class="bg-white">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ profit.month }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ profit.year }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ formatCurrency(profit.total_sales) }}</td>
                            <td class="px-5 py-4 font-semibold" :class="profit.profit_amount > 0 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ formatCurrency(profit.profit_amount) }}
                            </td>
                        </tr>
                        <tr v-if="monthlyProfits.length === 0">
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">No monthly profit history available yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
</template>

<style scoped>

</style>