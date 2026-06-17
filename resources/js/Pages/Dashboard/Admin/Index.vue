<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  Users, BookOpen, TrendingUp, Shield, ArrowRight,
  TrendingDown, CheckCircle, XCircle, AlertCircle, ShoppingBag
} from 'lucide-vue-next';

const props = defineProps({
  metrics: Object,
  recentTransactions: Array,
  monthlyRevenue: Array,
  recentUsers: Array
});

const isUpdatingRole = ref(false);

const changeUserRole = (userId, newRole) => {
  isUpdatingRole.value = true;
  router.post(`/dashboard/change-role/${userId}`, {
    role: newRole
  }, {
    onFinish: () => {
      isUpdatingRole.value = false;
    }
  });
};
</script>

<template>
  <Head title="Admin Dashboard" />

  <GuestLayout>
    <DashboardWrapper>
      
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-extrabold text-[#1A2B49]">Dashboard Overview</h3>
        <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1 shadow-sm">
          <Shield :size="12" /> Admin Control
        </span>
      </div>

      <!-- Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <!-- Metric 1: Revenue -->
        <div class="bg-white rounded-[2rem] p-6 pr-8 lg:pr-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
          <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 shrink-0">
            <TrendingUp :size="24" stroke-width="2.5" />
          </div>
          <div class="flex-1 overflow-hidden">
            <p class="text-slate-400 text-[10px] font-extrabold uppercase tracking-widest mb-0.5 truncate">Total Pendapatan</p>
            <h3 class="text-xl font-extrabold text-[#1A2B49] truncate">{{ metrics.total_revenue }}</h3>
          </div>
        </div>

        <!-- Metric 2: Students -->
        <div class="bg-white rounded-[2rem] p-6 pr-8 lg:pr-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
          <div class="w-14 h-14 rounded-full bg-sky-50 flex items-center justify-center text-sky-600 shrink-0">
            <Users :size="24" stroke-width="2.5" />
          </div>
          <div class="flex-1 overflow-hidden">
            <p class="text-slate-400 text-[10px] font-extrabold uppercase tracking-widest mb-0.5 truncate">Total Siswa</p>
            <h3 class="text-xl font-extrabold text-[#1A2B49] truncate">{{ metrics.total_students }} Siswa</h3>
          </div>
        </div>

        <!-- Metric 3: Instructors -->
        <div class="bg-white rounded-[2rem] p-6 pr-8 lg:pr-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
          <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
            <Shield :size="24" stroke-width="2.5" />
          </div>
          <div class="flex-1 overflow-hidden">
            <p class="text-slate-400 text-[10px] font-extrabold uppercase tracking-widest mb-0.5 truncate">Total Pengajar</p>
            <h3 class="text-xl font-extrabold text-[#1A2B49] truncate">{{ metrics.total_instructors }} Guru</h3>
          </div>
        </div>

        <!-- Metric 4: Courses -->
        <div class="bg-white rounded-[2rem] p-6 pr-8 lg:pr-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
          <div class="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
            <BookOpen :size="24" stroke-width="2.5" />
          </div>
          <div class="flex-1 overflow-hidden">
            <p class="text-slate-400 text-[10px] font-extrabold uppercase tracking-widest mb-0.5 truncate">Total Kursus</p>
            <h3 class="text-xl font-extrabold text-[#1A2B49] truncate">{{ metrics.total_courses }} Kelas</h3>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Recent Transactions -->
        <div class="lg:col-span-2 bg-white rounded-[2rem] overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-lg font-extrabold text-[#1A2B49] flex items-center gap-2">
              <ShoppingBag :size="20" class="text-indigo-600" /> Transaksi Terbaru
            </h3>
          </div>
          
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 text-[10px] font-extrabold uppercase tracking-widest bg-white">
                  <th class="py-4 px-6">Siswa</th>
                  <th class="py-4 px-6">Produk</th>
                  <th class="py-4 px-6">Jumlah</th>
                  <th class="py-4 px-6">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50 text-sm">
                <tr v-for="tx in recentTransactions" :key="tx.id" class="hover:bg-slate-50/50 transition-colors">
                  <td class="py-4 px-6">
                    <div class="font-bold text-[#1A2B49]">{{ tx.user?.name }}</div>
                    <div class="text-slate-400 text-xs">{{ tx.user?.email }}</div>
                  </td>
                  <td class="py-4 px-6 font-medium text-[#1A2B49]">
                    {{ tx.buyable?.title || 'Course/Bundle' }}
                  </td>
                  <td class="py-4 px-6 font-bold text-[#1A2B49]">
                    Rp {{ number_format(tx.amount) }}
                  </td>
                  <td class="py-4 px-6">
                    <span 
                      :class="{
                        'bg-emerald-50 text-emerald-700 border-emerald-100': tx.status === 'completed',
                        'bg-amber-50 text-amber-700 border-amber-100': tx.status === 'pending',
                        'bg-rose-50 text-rose-700 border-rose-100': tx.status === 'failed'
                      }"
                      class="px-2.5 py-1 text-[10px] font-extrabold rounded-full border uppercase tracking-wider"
                    >
                      {{ tx.status }}
                    </span>
                  </td>
                </tr>
                <tr v-if="recentTransactions.length === 0">
                  <td colspan="4" class="py-8 px-6 text-center text-slate-400 font-medium">Belum ada transaksi saat ini.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- User Management & Role Toggles -->
        <div class="bg-white rounded-[2rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
            <Users :size="20" class="text-sky-600" /> Kontrol Role User
          </h3>
          
          <div class="flex flex-col gap-4">
            <div v-for="user in recentUsers" :key="user.id" class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
              <div class="min-w-0 pr-3">
                <div class="font-bold text-sm text-[#1A2B49] truncate">{{ user.name }}</div>
                <div class="text-slate-400 text-xs mb-2 truncate">{{ user.email }}</div>
                <span class="px-2.5 py-1 bg-white border border-slate-200 text-slate-500 font-extrabold text-[10px] rounded-lg uppercase tracking-wider shadow-sm">
                  {{ user.role }}
                </span>
              </div>
              <div class="shrink-0">
                <select 
                  @change="changeUserRole(user.id, $event.target.value)"
                  :disabled="isUpdatingRole"
                  class="bg-white border border-slate-200 hover:border-[#264790] focus:border-[#264790] rounded-xl px-3 py-1.5 text-xs font-bold text-[#1A2B49] outline-none shadow-sm cursor-pointer transition-colors"
                >
                  <option value="" disabled selected>Ubah Role</option>
                  <option value="student">Student</option>
                  <option value="instructor">Instructor</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
            </div>
          </div>
        </div>

      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>

<script>
export default {
  methods: {
    number_format(val) {
      return parseFloat(val).toLocaleString('id-ID');
    }
  }
}
</script>
