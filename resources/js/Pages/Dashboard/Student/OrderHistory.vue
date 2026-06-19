<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard, Calendar, ShoppingBag, ArrowUpRight, HelpCircle } from 'lucide-vue-next';

const props = defineProps({
  orders: Array
});

const formatPrice = (val) => {
  return parseFloat(val).toLocaleString('id-ID');
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};
</script>

<template>
  <Head title="Riwayat Transaksi" />

  <GuestLayout>
    <DashboardWrapper>
      <div class="mb-8">
        <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight flex items-center gap-3">
          <div class="p-3 bg-[#264790]/5 rounded-2xl text-[#264790]">
            <CreditCard :size="28" stroke-width="2.5" />
          </div>
          Riwayat Transaksi
        </h2>
        <p class="text-slate-500 font-medium text-sm mt-2 pl-1">
          Daftar kwitansi, invoice, serta status pembayaran dari semua transaksi pembelian kelas Anda.
        </p>
      </div>

      <!-- Tabel Riwayat Pembelian -->
      <div class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-100">
                <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-widest">Order ID</th>
                <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-widest">Item Kelas</th>
                <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-widest">Tanggal</th>
                <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-widest">Metode</th>
                <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-widest text-right">Jumlah</th>
                <th class="px-6 py-4 text-xs font-extrabold text-slate-400 uppercase tracking-widest text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr 
                v-for="order in orders" 
                :key="order.id"
                class="hover:bg-slate-50/50 transition-colors"
              >
                <!-- Order ID -->
                <td class="px-6 py-5">
                  <div class="flex flex-col">
                    <span class="font-extrabold text-[#1A2B49] text-xs">#{{ order.id }}</span>
                    <span class="text-[9px] text-slate-400 font-bold uppercase truncate max-w-[120px] mt-0.5">
                      {{ order.transaction_id || 'MIDTRANS-MOCK' }}
                    </span>
                  </div>
                </td>

                <!-- Item Kelas -->
                <td class="px-6 py-5">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-50 flex items-center justify-center shrink-0 text-[#44A6D9]">
                      <ShoppingBag :size="16" />
                    </div>
                    <div>
                      <span class="font-black text-sm text-[#1A2B49] leading-snug line-clamp-1">
                        {{ order.buyable?.title || 'Kelas/Bundle Terhapus' }}
                      </span>
                      <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                        {{ order.buyable_type === 'App\\Models\\Course' ? 'Course' : 'Bundle' }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Tanggal -->
                <td class="px-6 py-5">
                  <span class="text-xs text-slate-500 font-semibold flex items-center gap-1.5">
                    <Calendar :size="12" class="text-slate-300" />
                    {{ formatDate(order.created_at) }}
                  </span>
                </td>

                <!-- Metode Pembayaran -->
                <td class="px-6 py-5">
                  <span class="text-xs text-[#1A2B49] font-bold uppercase tracking-wider">
                    {{ order.payment_type || 'MOCK_GATEWAY' }}
                  </span>
                </td>

                <!-- Jumlah Bayar -->
                <td class="px-6 py-5 text-right">
                  <div class="flex flex-col items-end">
                    <span class="font-black text-sm text-[#264790]">
                      Rp {{ formatPrice(order.amount) }}
                    </span>
                    <span v-if="order.discount_amount > 0" class="text-[10px] text-emerald-500 font-bold">
                      Diskon Rp {{ formatPrice(order.discount_amount) }}
                    </span>
                  </div>
                </td>

                <!-- Status Badge -->
                <td class="px-6 py-5 text-center">
                  <span 
                    :class="[
                      order.status === 'completed'
                        ? 'bg-emerald-50 text-emerald-600 border border-emerald-100'
                        : order.status === 'pending'
                          ? 'bg-amber-50 text-amber-600 border border-amber-100'
                          : 'bg-rose-50 text-rose-600 border border-rose-100'
                    ]"
                    class="text-[9px] font-extrabold px-3 py-1.5 rounded-full uppercase tracking-wider"
                  >
                    {{ order.status === 'completed' ? 'Success' : order.status === 'pending' ? 'Pending' : order.status }}
                  </span>
                </td>
              </tr>

              <tr v-if="orders.length === 0">
                <td colspan="6" class="py-20 text-center text-slate-400 font-bold">
                  Belum ada riwayat transaksi pembayaran.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </DashboardWrapper>
  </GuestLayout>
</template>
