<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { createToaster } from "@meforma/vue-toaster";

const page = usePage();
const monthlyProfits = ref([]);
const dailyProfits = ref([]);
const isUpdating = ref(false);
const isUpdatingDaily = ref(false);
const toaster = createToaster({});

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
const formatCurrency = (value) => {
  return parseFloat(value).toFixed(2);
};

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
<!-- component -->
<div class="flex flex-wrap mb-2">
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pl-2">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-users fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">Total Collection</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['collection'] }} BDT<span class="text-green-400"><i class="fas fa-caret-down"></i></span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pl-2">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-users fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">Total Customer</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['customer'] }} <span class="text-blue-400"><i class="fas fa-caret-up"></i></span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pr-2 xl:pr-3 xl:pl-1">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-user-plus fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right pr-1">
                    <h5 class="text-white font-bold">Total Category</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['category'] }} <span class="text-orange-400"><i class="fas fa-caret-up"></i></span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pr-2 xl:pr-3 xl:pl-1">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-user-plus fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right pr-1">
                    <h5 class="text-white font-bold">Vat Collection</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['vat'] }} BDT<span class="text-orange-400"><i class="fas fa-caret-up"></i></span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pl-2 xl:pl-3 xl:pr-2">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-server fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">Total Sale</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['total'] }} BDT</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pr-2 xl:pl-2 xl:pr-3">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-tasks fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">Invoices</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['invoice'] }} </h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Summary Cards -->
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pl-2">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-box fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">Total Products Quantity</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['totalQty'] || 0 }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pl-2">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-dollar-sign fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">Total Profit</h5>
                    <h3 class="text-white text-3xl font-bold">{{ page.props.data['totalProfit'] || '0' }} BDT</h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Profit Card -->
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pl-2">
        <div class="bg-blue-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-calendar-day fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">Today's Profit</h5>
                    <h3 class="text-white text-3xl font-bold">{{ formatCurrency(page.props.data.todayProfit?.profit_amount || 0) }} BDT</h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Current Month Profit Card -->
    <div class="w-full md:w-1/2 xl:w-1/3 pt-3 px-3 md:pl-2">
        <div class="bg-green-700 border rounded shadow p-2">
            <div class="flex flex-row items-center">
                <div class="flex-shrink pl-1 pr-4"><i class="fas fa-chart-line fa-2x fa-fw fa-inverse"></i></div>
                <div class="flex-1 text-right">
                    <h5 class="text-white font-bold">{{ page.props.data.currentMonthProfit?.month }} {{ page.props.data.currentMonthProfit?.year }} Profit</h5>
                    <h3 class="text-white text-3xl font-bold">{{ formatCurrency(page.props.data.currentMonthProfit?.profit_amount || 0) }} BDT</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Profit History Section -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Daily Profit History</h2>
        
        <!-- Update Daily Profit Button -->
        <button 
            @click="updateDailyProfitData" 
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex items-center"
            :disabled="isUpdatingDaily"
        >
            <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': isUpdatingDaily }"></i>
            {{ isUpdatingDaily ? 'Updating...' : 'Update Daily Profit' }}
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                    <th class="py-2 px-4 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Sales (BDT)</th>
                    <th class="py-2 px-4 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Profit (BDT)</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="profit in dailyProfits" :key="profit.date" 
                    class="hover:bg-gray-50">
                    <td class="py-3 px-4 border-b border-gray-200 font-medium">{{ formatDate(profit.date) }}</td>
                    <td class="py-3 px-4 border-b border-gray-200">{{ formatCurrency(profit.total_sales) }}</td>
                    <td class="py-3 px-4 border-b border-gray-200 font-medium" 
                        :class="profit.profit_amount > 0 ? 'text-green-600' : 'text-red-600'">
                        {{ formatCurrency(profit.profit_amount) }}
                    </td>
                </tr>
                
                <!-- Show message if no profit history -->
                <tr v-if="dailyProfits.length === 0">
                    <td colspan="3" class="py-3 px-4 text-center text-gray-500">No daily profit history available</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Monthly Profit History Section -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Monthly Profit History</h2>
        
        <!-- Update Monthly Profit Button -->
        <button 
            @click="updateMonthlyProfitData" 
            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex items-center"
            :disabled="isUpdating"
        >
            <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': isUpdating }"></i>
            {{ isUpdating ? 'Updating...' : 'Update Monthly Profit' }}
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Month</th>
                    <th class="py-2 px-4 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Year</th>
                    <th class="py-2 px-4 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Sales (BDT)</th>
                    <th class="py-2 px-4 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Profit (BDT)</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="profit in monthlyProfits" :key="`${profit.month}-${profit.year}`" 
                    class="hover:bg-gray-50">
                    <td class="py-3 px-4 border-b border-gray-200">{{ profit.month }}</td>
                    <td class="py-3 px-4 border-b border-gray-200">{{ profit.year }}</td>
                    <td class="py-3 px-4 border-b border-gray-200">{{ formatCurrency(profit.total_sales) }}</td>
                    <td class="py-3 px-4 border-b border-gray-200 font-medium" 
                        :class="profit.profit_amount > 0 ? 'text-green-600' : 'text-red-600'">
                        {{ formatCurrency(profit.profit_amount) }}
                    </td>
                </tr>
                
                <!-- Show message if no profit history -->
                <tr v-if="monthlyProfits.length === 0">
                    <td colspan="4" class="py-3 px-4 text-center text-gray-500">No profit history available</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</template>

<style scoped>

</style>