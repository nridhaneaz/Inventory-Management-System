<script setup>
import { reactive } from 'vue';

const profile = reactive({
	name: 'Staff User',
	email: 'staff@citybakewaretrade.com',
	mobile: '+880 1XXXXXXXXX',
	role: 'Cashier',
	status: 'Active',
});

const activity = [
	{ label: 'Today Sales', value: '18' },
	{ label: 'Invoices Handled', value: '42' },
	{ label: 'Login Status', value: 'Authenticated' },
];
</script>

<template>
<div class="pos-page">
	<section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
		<div class="pos-section">
			<div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Profile</p>
					<h1 class="mt-1 text-3xl font-bold text-slate-900">Staff Profile</h1>
					<p class="text-sm text-slate-500">View the current cashier/account details for the POS system.</p>
				</div>
				<span class="pos-pill bg-emerald-100 text-emerald-700">{{ profile.status }}</span>
			</div>

			<div class="grid gap-4 md:grid-cols-[120px_1fr]">
				<div class="flex h-28 w-28 items-center justify-center rounded-3xl bg-gradient-to-br from-amber-500 to-orange-600 text-4xl font-black text-white shadow-lg shadow-amber-500/20">
					{{ profile.name.charAt(0) }}
				</div>

				<div class="grid gap-4">
					<div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
						<div class="grid gap-3 sm:grid-cols-2">
							<div>
								<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Name</p>
								<p class="mt-1 text-lg font-semibold text-slate-900">{{ profile.name }}</p>
							</div>
							<div>
								<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Role</p>
								<p class="mt-1 text-lg font-semibold text-slate-900">{{ profile.role }}</p>
							</div>
							<div>
								<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Email</p>
								<p class="mt-1 text-sm font-medium text-slate-700">{{ profile.email }}</p>
							</div>
							<div>
								<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mobile</p>
								<p class="mt-1 text-sm font-medium text-slate-700">{{ profile.mobile }}</p>
							</div>
						</div>
					</div>

					<div class="grid gap-3 sm:grid-cols-3">
						<div v-for="item in activity" :key="item.label" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
							<p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ item.label }}</p>
							<p class="mt-2 text-2xl font-bold text-slate-900">{{ item.value }}</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="pos-section">
			<div class="mb-5 border-b border-slate-200 pb-4">
				<p class="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">Quick Details</p>
				<h2 class="mt-1 text-2xl font-bold text-slate-900">Editable Profile Card</h2>
				<p class="text-sm text-slate-500">UI-only placeholder for staff profile information.</p>
			</div>

			<div class="grid gap-4">
				<label class="grid gap-2">
					<span class="text-sm font-semibold text-slate-700">Display Name</span>
					<input type="text" class="pos-input" :value="profile.name" />
				</label>
				<label class="grid gap-2">
					<span class="text-sm font-semibold text-slate-700">Email</span>
					<input type="email" class="pos-input" :value="profile.email" />
				</label>
				<label class="grid gap-2">
					<span class="text-sm font-semibold text-slate-700">Mobile</span>
					<input type="text" class="pos-input" :value="profile.mobile" />
				</label>
			</div>

			<div class="mt-5 rounded-3xl bg-amber-50 p-4 text-sm text-amber-900">
				This screen is now populated as a profile view. If you want save/edit actions later, I can wire them to the existing backend flow without changing routes.
			</div>
		</div>
	</section>
</div>
</template>

<style scoped>

</style>
