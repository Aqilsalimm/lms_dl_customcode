<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  TrendingUp, Users, ShoppingBag, Percent, 
  BarChart3, Calendar, ArrowUpRight, DollarSign,
  Award, RefreshCcw, UserPlus, CheckCircle, Tag
} from 'lucide-vue-next';

const props = defineProps({
  topCourses: Array,
  conversionMetrics: Object,
  revenueTrends: Object,
  studentGrowth: Object
});

// Trend Tab Active State
const activeTrendTab = ref('daily'); // 'daily', 'weekly', 'monthly'

const activeDataset = computed(() => {
  if (activeTrendTab.value === 'daily') return props.revenueTrends.daily;
  if (activeTrendTab.value === 'weekly') return props.revenueTrends.weekly;
  return props.revenueTrends.monthly;
});

// Format Price helper
const formatPrice = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val);
};

// SVG Line Chart Computeds
const maxRevenue = computed(() => {
  const vals = activeDataset.value.map(d => d.revenue);
  const max = Math.max(...vals, 100000); // fallback to 100k
  return max;
});

const chartPoints = computed(() => {
  const data = activeDataset.value;
  if (!data || data.length === 0) return [];
  const width = 750;
  const height = 240;
  const paddingX = 40;
  const paddingY = 30;
  
  return data.map((d, i) => {
    const x = paddingX + (i / (data.length - 1)) * (width - 2 * paddingX);
    const y = height - paddingY - (d.revenue / maxRevenue.value) * (height - 2 * paddingY);
    return { x, y, label: d.label, val: d.revenue };
  });
});

const linePath = computed(() => {
  const pts = chartPoints.value;
  if (pts.length === 0) return '';
  return pts.map((p, i) => (i === 0 ? `M ${p.x} ${p.y}` : `L ${p.x} ${p.y}`)).join(' ');
});

const areaPath = computed(() => {
  const pts = chartPoints.value;
  if (pts.length === 0) return '';
  const height = 240;
  const paddingY = 30;
  const first = pts[0];
  const last = pts[pts.length - 1];
  return `M ${first.x} ${height - paddingY} L ${first.x} ${first.y} ` + 
         pts.slice(1).map(p => `L ${p.x} ${p.y}`).join(' ') + 
         ` L ${last.x} ${height - paddingY} Z`;
});

// Total Sales & Total Revenue compute
const totalSalesCount = computed(() => {
  return props.topCourses.reduce((sum, item) => sum + item.sales_count, 0);
});

const totalRevenueSum = computed(() => {
  return activeDataset.value.reduce((sum, item) => sum + item.revenue, 0);
});

// Hover state for points to display tooltips
const hoveredPoint = ref(null);
</script>

<template>
  <Head title="e-Commerce Analytics Dashboard" />

  <DashboardWrapper>
    <div class="max-w-6xl mx-auto flex flex-col gap-8">
      
      <!-- HEADER -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <div class="flex items-center gap-2 text-[#264790] text-xs font-bold uppercase tracking-wider">
            <BarChart3 :size="14" />
            e-Commerce Insight
          </div>
          <h1 class="text-2xl font-extrabold text-[#1A2B49] tracking-tight mt-1">Analytics Dashboard</h1>
          <p class="text-sm text-slate-500 mt-1">Analisis penjualan kelas, tingkat konversi checkout, pertumbuhan siswa, dan pendapatan LMS.</p>
        </div>
      </div>

      <!-- METRICS GRID -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- CARD 1: REVENUE -->
        <div class="bg-white rounded-[2rem] border border-slate-150 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between gap-4 hover:shadow-[0_12px_40px_rgb(0,0,0,0.03)] transition-all duration-300 relative overflow-hidden group">
          <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50/40 rounded-bl-[4rem] -z-10 group-hover:scale-110 transition-transform duration-300"></div>
          <div class="flex items-center justify-between">
            <span class="text-slate-450 text-xs font-bold uppercase tracking-wider">Total Pendapatan</span>
            <div class="w-10 h-10 rounded-2xl bg-[#EBF3FF] text-[#264790] flex items-center justify-center shrink-0 shadow-sm">
              <DollarSign :size="18" />
            </div>
          </div>
          <div>
            <h3 class="text-2xl font-black text-[#1A2B49] tracking-tight">{{ formatPrice(totalRevenueSum) }}</h3>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
              <TrendingUp :size="12" class="text-emerald-500" />
              <span>Berdasarkan periode grafik</span>
            </p>
          </div>
        </div>

        <!-- CARD 2: CONVERSION RATE -->
        <div class="bg-white rounded-[2rem] border border-slate-150 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between gap-4 hover:shadow-[0_12px_40px_rgb(0,0,0,0.03)] transition-all duration-300 relative overflow-hidden group">
          <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50/40 rounded-bl-[4rem] -z-10 group-hover:scale-110 transition-transform duration-300"></div>
          <div class="flex items-center justify-between">
            <span class="text-slate-450 text-xs font-bold uppercase tracking-wider">Conversion Rate</span>
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 shadow-sm">
              <Percent :size="18" />
            </div>
          </div>
          <div>
            <h3 class="text-2xl font-black text-[#1A2B49] tracking-tight">{{ conversionMetrics.conversion_rate }}%</h3>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
              <CheckCircle :size="12" class="text-emerald-500" />
              <span>{{ conversionMetrics.successful_checkouts }} dari {{ conversionMetrics.total_checkouts }} checkout</span>
            </p>
          </div>
        </div>

        <!-- CARD 3: TOTAL STUDENTS -->
        <div class="bg-white rounded-[2rem] border border-slate-150 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between gap-4 hover:shadow-[0_12px_40px_rgb(0,0,0,0.03)] transition-all duration-300 relative overflow-hidden group">
          <div class="absolute top-0 right-0 w-24 h-24 bg-indigo-50/40 rounded-bl-[4rem] -z-10 group-hover:scale-110 transition-transform duration-300"></div>
          <div class="flex items-center justify-between">
            <span class="text-slate-450 text-xs font-bold uppercase tracking-wider">Total Siswa</span>
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 shadow-sm">
              <Users :size="18" />
            </div>
          </div>
          <div>
            <h3 class="text-2xl font-black text-[#1A2B49] tracking-tight">{{ studentGrowth.total_students }}</h3>
            <p class="text-xs text-slate-550 mt-1 flex items-center gap-1">
              <UserPlus :size="12" class="text-[#264790]" />
              <span class="font-extrabold">+{{ studentGrowth.new_students_this_month }}</span> siswa baru bulan ini
            </p>
          </div>
        </div>

        <!-- CARD 4: REPEAT BUYER RATE -->
        <div class="bg-white rounded-[2rem] border border-slate-150 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.015)] flex flex-col justify-between gap-4 hover:shadow-[0_12px_40px_rgb(0,0,0,0.03)] transition-all duration-300 relative overflow-hidden group">
          <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50/40 rounded-bl-[4rem] -z-10 group-hover:scale-110 transition-transform duration-300"></div>
          <div class="flex items-center justify-between">
            <span class="text-slate-450 text-xs font-bold uppercase tracking-wider">Repeat Buyer Rate</span>
            <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-650 flex items-center justify-center shrink-0 shadow-sm">
              <RefreshCcw :size="18" />
            </div>
          </div>
          <div>
            <h3 class="text-2xl font-black text-[#1A2B49] tracking-tight">{{ studentGrowth.repeat_buyer_rate }}%</h3>
            <p class="text-xs text-slate-550 mt-1 flex items-center gap-1">
              <span class="font-extrabold">{{ studentGrowth.repeat_buyers }}</span> siswa membeli >1 kelas
            </p>
          </div>
        </div>

      </div>

      <!-- MAIN CHART & INFO AREA -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- REVENUE TREND (2 Columns) -->
        <div class="bg-white rounded-[2.5rem] border border-slate-150 p-6 shadow-[0_10px_35px_rgb(0,0,0,0.01)] flex flex-col gap-6 lg:col-span-2">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h3 class="text-base font-extrabold text-[#1A2B49]">Tren Pendapatan Penjualan</h3>
              <p class="text-xs text-slate-550">Visualisasi dinamika omset penjualan kelas LMS.</p>
            </div>
            
            <!-- Periods tabs -->
            <div class="flex bg-slate-50 border border-slate-150 p-1 rounded-full w-max shadow-inner">
              <button 
                @click="activeTrendTab = 'daily'"
                :class="[activeTrendTab === 'daily' ? 'bg-white text-[#264790] shadow-sm font-extrabold' : 'text-slate-500 font-semibold']"
                class="px-4 py-1.5 rounded-full text-xs transition-all cursor-pointer"
              >
                Harian
              </button>
              <button 
                @click="activeTrendTab = 'weekly'"
                :class="[activeTrendTab === 'weekly' ? 'bg-white text-[#264790] shadow-sm font-extrabold' : 'text-slate-500 font-semibold']"
                class="px-4 py-1.5 rounded-full text-xs transition-all cursor-pointer"
              >
                Mingguan
              </button>
              <button 
                @click="activeTrendTab = 'monthly'"
                :class="[activeTrendTab === 'monthly' ? 'bg-white text-[#264790] shadow-sm font-extrabold' : 'text-slate-500 font-semibold']"
                class="px-4 py-1.5 rounded-full text-xs transition-all cursor-pointer"
              >
                Bulanan
              </button>
            </div>
          </div>

          <!-- Custom SVG Chart -->
          <div class="relative w-full h-[260px] bg-slate-50/50 rounded-3xl border border-slate-100 p-4 overflow-hidden flex flex-col justify-end">
            
            <!-- Horizontal grid lines -->
            <div class="absolute inset-0 p-4 flex flex-col justify-between pointer-events-none text-[9px] font-mono text-slate-400">
              <div class="border-b border-dashed border-slate-200 w-full pt-1 flex justify-between">
                <span>{{ formatPrice(maxRevenue) }}</span>
              </div>
              <div class="border-b border-dashed border-slate-200 w-full pt-1 flex justify-between">
                <span>{{ formatPrice(maxRevenue * 0.75) }}</span>
              </div>
              <div class="border-b border-dashed border-slate-200 w-full pt-1 flex justify-between">
                <span>{{ formatPrice(maxRevenue * 0.5) }}</span>
              </div>
              <div class="border-b border-dashed border-slate-200 w-full pt-1 flex justify-between">
                <span>{{ formatPrice(maxRevenue * 0.25) }}</span>
              </div>
              <div class="w-full flex justify-between text-transparent pt-1">
                <span>0</span>
              </div>
            </div>

            <!-- SVG Container -->
            <svg viewBox="0 0 750 240" class="w-full h-full z-10 overflow-visible">
              <defs>
                <!-- Line Area Gradient -->
                <linearGradient id="chartAreaGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#264790" stop-opacity="0.25" />
                  <stop offset="100%" stop-color="#264790" stop-opacity="0.0" />
                </linearGradient>
                <!-- Line Color Gradient -->
                <linearGradient id="chartLineGrad" x1="0" y1="0" x2="1" y2="0">
                  <stop offset="0%" stop-color="#264790" />
                  <stop offset="100%" stop-color="#44A6D9" />
                </linearGradient>
              </defs>

              <!-- Area Path -->
              <path :d="areaPath" fill="url(#chartAreaGrad)" />

              <!-- Line Path -->
              <path :d="linePath" fill="none" stroke="url(#chartLineGrad)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />

              <!-- Interactive Points -->
              <circle 
                v-for="(pt, idx) in chartPoints" 
                :key="idx" 
                :cx="pt.x" 
                :cy="pt.y" 
                r="5.5" 
                fill="#FFFFFF" 
                stroke="#264790" 
                stroke-width="3.5" 
                class="hover:scale-150 hover:r-7 transition-all duration-150 cursor-pointer"
                @mouseenter="hoveredPoint = pt"
                @mouseleave="hoveredPoint = null"
              />
            </svg>

            <!-- Chart Tooltip -->
            <div 
              v-if="hoveredPoint"
              class="absolute bg-slate-900 text-white text-[10px] font-bold py-2 px-3.5 rounded-xl border border-slate-800 shadow-2xl pointer-events-none transition-all duration-100 z-35 flex flex-col gap-0.5"
              :style="{ left: `${(hoveredPoint.x / 750) * 100}%`, bottom: `${240 - hoveredPoint.y + 10}px`, transform: 'translateX(-50%)' }"
            >
              <span class="text-slate-400 font-medium">{{ hoveredPoint.label }}</span>
              <span class="text-blue-400 font-extrabold text-xs mt-0.5">{{ formatPrice(hoveredPoint.val) }}</span>
            </div>
          </div>

          <!-- Y-Axis label legends -->
          <div class="flex justify-between items-center px-4 text-[10px] font-bold text-slate-500 font-mono">
            <span>{{ activeDataset[0]?.label }}</span>
            <span class="text-slate-400 text-[9px] font-sans italic">Arahkan kursor ke titik grafik untuk info detail</span>
            <span>{{ activeDataset[activeDataset.length - 1]?.label }}</span>
          </div>

        </div>

        <!-- CONVERSION ENGINE METRICS (1 Column) -->
        <div class="bg-white rounded-[2.5rem] border border-slate-150 p-6 shadow-[0_10px_35px_rgb(0,0,0,0.01)] flex flex-col justify-between gap-6">
          <div>
            <h3 class="text-base font-extrabold text-[#1A2B49]">Checkout Conversion</h3>
            <p class="text-xs text-slate-550">Tingkat keberhasilan pembayaran transaksi.</p>
          </div>

          <!-- Donut Progress Ring -->
          <div class="flex items-center justify-center py-6 relative">
            <svg class="w-40 h-40 transform -rotate-90 overflow-visible" viewBox="0 0 100 100">
              <!-- Background Ring -->
              <circle 
                cx="50" 
                cy="50" 
                r="40" 
                fill="transparent" 
                stroke="#F1F5F9" 
                stroke-width="10" 
              />
              <!-- Progress Ring -->
              <circle 
                cx="50" 
                cy="50" 
                r="40" 
                fill="transparent" 
                stroke="#264790" 
                stroke-width="10" 
                stroke-linecap="round"
                :stroke-dasharray="2 * Math.PI * 40"
                :stroke-dashoffset="(1 - (conversionMetrics.conversion_rate / 100)) * (2 * Math.PI * 40)"
                class="transition-all duration-700 ease-out"
              />
            </svg>
            
            <!-- Inside label -->
            <div class="absolute flex flex-col items-center justify-center">
              <span class="text-3xl font-black text-[#1A2B49] tracking-tight">{{ conversionMetrics.conversion_rate }}%</span>
              <span class="text-[9px] font-extrabold text-[#264790] uppercase tracking-wider mt-0.5">Success Rate</span>
            </div>
          </div>

          <!-- Legend metrics list -->
          <div class="flex flex-col gap-3.5 bg-slate-50/50 border border-slate-100 rounded-3xl p-5 shadow-inner">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
              <span class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#264790]"></span>
                Berhasil Bayar
              </span>
              <span class="font-extrabold text-[#1A2B49]">{{ conversionMetrics.successful_checkouts }}</span>
            </div>
            
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 border-t border-slate-200/50 pt-3">
              <span class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                Gagal / Pending
              </span>
              <span class="font-extrabold text-slate-700">
                {{ Math.max(conversionMetrics.total_checkouts - conversionMetrics.successful_checkouts, 0) }}
              </span>
            </div>
          </div>

        </div>

      </div>

      <!-- BOTTOM ROW: TOP PERFORMING COURSES & STUDENT BEHAVIOR -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- TOP PERFORMING COURSES -->
        <div class="bg-white rounded-[2.5rem] border border-slate-150 p-6 shadow-[0_10px_35px_rgb(0,0,0,0.01)] flex flex-col gap-6">
          <div>
            <h3 class="text-base font-extrabold text-[#1A2B49]">Top Performing Courses</h3>
            <p class="text-xs text-slate-550">Kelas terlaris dengan total omset tertinggi.</p>
          </div>

          <div v-if="topCourses.length === 0" class="py-12 text-center text-xs font-bold text-slate-400">
            Belum ada data transaksi kelas yang terekam.
          </div>

          <div v-else class="flex flex-col gap-4">
            <div 
              v-for="(course, idx) in topCourses" 
              :key="course.id"
              class="flex items-center justify-between gap-4 p-4 border border-slate-100 rounded-3xl hover:bg-slate-50/50 transition-colors group"
            >
              <!-- Rank badge & Title info -->
              <div class="flex items-center gap-3 min-w-0">
                <div 
                  :class="[
                    idx === 0 ? 'bg-amber-100 text-amber-700 border-amber-200' : 
                    idx === 1 ? 'bg-slate-100 text-slate-700 border-slate-200' : 
                    'bg-orange-50 text-orange-650 border-orange-100'
                  ]"
                  class="w-8 h-8 rounded-full border flex items-center justify-center font-extrabold text-xs shrink-0"
                >
                  #{{ idx + 1 }}
                </div>
                <div class="min-w-0">
                  <h4 class="text-xs font-extrabold text-[#1A2B49] truncate group-hover:text-[#264790] transition-colors">
                    {{ course.title }}
                  </h4>
                  <p class="text-[10px] text-slate-450 mt-0.5">
                    Total: <span class="font-extrabold text-[#264790]">{{ course.sales_count }} penjualan</span>
                  </p>
                </div>
              </div>

              <!-- Revenue badge -->
              <div class="shrink-0 text-right">
                <span class="text-xs font-black text-[#1A2B49]">{{ formatPrice(course.total_revenue) }}</span>
                <span class="block text-[8px] text-slate-400 font-mono mt-0.5">EST. REVENUE</span>
              </div>
            </div>
          </div>
        </div>

        <!-- STUDENT GROWTH & BEHAVIOR -->
        <div class="bg-white rounded-[2.5rem] border border-slate-150 p-6 shadow-[0_10px_35px_rgb(0,0,0,0.01)] flex flex-col gap-6">
          <div>
            <h3 class="text-base font-extrabold text-[#1A2B49]">Student Repeat Purchase Behavior</h3>
            <p class="text-xs text-slate-550">Analisis perbandingan perilaku belanja antara pembeli baru vs pembeli setia.</p>
          </div>

          <!-- Bar stack breakdown representation -->
          <div class="flex flex-col gap-6">
            
            <!-- Stat values -->
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-slate-50/50 border border-slate-100 p-4 rounded-3xl flex flex-col gap-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pembeli Baru</span>
                <span class="text-xl font-black text-slate-700">{{ studentGrowth.new_buyers }}</span>
                <span class="text-[9px] text-slate-500">Membeli 1 kelas</span>
              </div>
              <div class="bg-slate-50/50 border border-slate-100 p-4 rounded-3xl flex flex-col gap-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pembeli Setia</span>
                <span class="text-xl font-black text-[#264790]">{{ studentGrowth.repeat_buyers }}</span>
                <span class="text-[9px] text-[#264790] font-semibold">Membeli &ge;2 kelas</span>
              </div>
            </div>

            <!-- Visual bar stack -->
            <div class="flex flex-col gap-2">
              <span class="text-[10px] font-extrabold text-slate-450 uppercase tracking-wider">Porsi Tipe Pembeli</span>
              <div class="w-full h-7 rounded-full overflow-hidden flex shadow-inner bg-slate-100 border border-slate-200/50">
                <div 
                  v-if="studentGrowth.total_buyers > 0"
                  :style="{ width: `${(studentGrowth.new_buyers / studentGrowth.total_buyers) * 100}%` }" 
                  class="bg-slate-400 h-full flex items-center justify-center text-[10px] font-black text-white"
                  title="Pembeli Baru"
                >
                  {{ Math.round((studentGrowth.new_buyers / studentGrowth.total_buyers) * 100) }}%
                </div>
                <div 
                  v-if="studentGrowth.total_buyers > 0"
                  :style="{ width: `${(studentGrowth.repeat_buyers / studentGrowth.total_buyers) * 100}%` }" 
                  class="bg-[#264790] h-full flex items-center justify-center text-[10px] font-black text-white border-l border-white/20"
                  title="Pembeli Setia (Repeat)"
                >
                  {{ Math.round((studentGrowth.repeat_buyers / studentGrowth.total_buyers) * 100) }}%
                </div>
              </div>
              
              <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 mt-1">
                <span class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-slate-450"></span>
                  Siswa Baru (One-time Buyer)
                </span>
                <span class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-[#264790]"></span>
                  Siswa Lama (Repeat Buyer)
                </span>
              </div>
            </div>

            <!-- Insight note -->
            <div class="border border-indigo-50 bg-indigo-50/30 rounded-3xl p-4 flex items-start gap-3 mt-1.5">
              <Award :size="18" class="text-[#264790] mt-0.5 shrink-0" />
              <p class="text-[11px] text-slate-600 leading-normal font-medium">
                Tingkat repeat buyer sebesar <span class="font-extrabold text-[#264790]">{{ studentGrowth.repeat_buyer_rate }}%</span> mengindikasikan ketertarikan siswa yang tinggi untuk terus melanjutkan pembelajaran pada kelas tingkat lanjut Anda.
              </p>
            </div>

          </div>
        </div>

      </div>

    </div>
  </DashboardWrapper>
</template>
