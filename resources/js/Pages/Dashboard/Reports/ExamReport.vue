<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
  FileBarChart, Filter, RefreshCw, Download, Printer, 
  TrendingUp, TrendingDown, AlertTriangle, CheckCircle2, 
  XCircle, Award, Clock, Users, BookOpen, Search, Eye, 
  Sparkles, Layers, ChevronRight, ShieldAlert, FileSpreadsheet, Activity
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
  filters: Object,
  coursesList: Array,
  kpiMetrics: Object,
  trendChart: Array,
  distributionData: Object,
  comparisonMatrix: Array,
  anomalyFlags: Array,
  actionableInsights: Array,
  tableData: Array,
  auditMetadata: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Filter Form State
const filterForm = ref({
  date_preset: props.filters.date_preset || '30_days',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
  course_id: props.filters.course_id || '',
  assessment_type: props.filters.assessment_type || 'all',
  status: props.filters.status || 'all',
  search: props.filters.search || '',
});

const isFilterOpen = ref(false);
const selectedAttempt = ref(null);
const isDetailModalOpen = ref(false);
const isRealtimeActive = ref(true);
const realtimeNotificationCount = ref(0);

// Apply Filters to Page
const applyFilters = () => {
  router.get(route('dashboard.reports.exam'), filterForm.value, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Reset Filters
const resetFilters = () => {
  filterForm.value = {
    date_preset: '30_days',
    start_date: '',
    end_date: '',
    course_id: '',
    assessment_type: 'all',
    status: 'all',
    search: '',
  };
  applyFilters();
};

// Open Detail Modal
const openDetail = (attempt) => {
  selectedAttempt.value = attempt;
  isDetailModalOpen.value = true;
};

// Close Detail Modal
const closeDetail = () => {
  isDetailModalOpen.value = false;
  selectedAttempt.value = null;
};

// Export to CSV
const exportCSV = () => {
  if (!props.tableData || props.tableData.length === 0) {
    Swal.fire({
      icon: 'info',
      title: 'Tidak Ada Data',
      text: 'Tidak ada data laporan untuk di-export.',
    });
    return;
  }

  let csvContent = 'data:text/csv;charset=utf-8,';
  csvContent += 'Kode Attempt,Nama Peserta,Email,Kursus,Modul,Tipe Ujian,Skor (%),Status,Durasi,Tanggal Selesai\n';

  props.tableData.forEach((row) => {
    const statusText = row.is_flagged ? 'Anomali/Flagged' : (row.is_passed ? 'Lulus' : 'Tidak Lulus');
    const line = [
      `"${row.attempt_code}"`,
      `"${row.student_name}"`,
      `"${row.student_email}"`,
      `"${row.course_title}"`,
      `"${row.module_title}"`,
      `"${row.type}"`,
      `"${row.total_score}"`,
      `"${statusText}"`,
      `"${row.duration_formatted}"`,
      `"${row.completed_at_formatted}"`
    ].join(',');
    csvContent += line + '\n';
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `Report_Exam_Analytics_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// Print / PDF Export
const printReport = () => {
  window.print();
};

// Realtime WebSocket Listener (Broadcast Channel `exam-reports`)
let echoChannel = null;

onMounted(() => {
  if (typeof window !== 'undefined' && window.Echo) {
    try {
      echoChannel = window.Echo.channel('exam-reports')
        .listen('.ExamAttemptSubmitted', (e) => {
          realtimeNotificationCount.value++;
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Percobaan Ujian Baru!',
            text: `${e.studentName} menyelesaikan ${e.assessmentType} di ${e.courseTitle} (Skor: ${e.score}%)`,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
          });
          // Auto refresh report data seamlessly
          applyFilters();
        });
    } catch (err) {
      console.warn('Realtime WebSocket listener failed to initialize, falling back to static polling:', err);
    }
  }
});

onUnmounted(() => {
  if (echoChannel && window.Echo) {
    window.Echo.leaveChannel('exam-reports');
  }
});

// Helper for initials
const getInitials = (name) => {
  if (!name) return 'U';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

// Calculate SVG Trend Line Path
const maxTrendAttempts = computed(() => {
  if (!props.trendChart || props.trendChart.length === 0) return 10;
  const max = Math.max(...props.trendChart.map(t => t.attempts));
  return max > 0 ? max : 10;
});

const trendPoints = computed(() => {
  if (!props.trendChart || props.trendChart.length === 0) return '';
  const width = 600;
  const height = 150;
  const count = props.trendChart.length;

  return props.trendChart.map((t, idx) => {
    const x = count > 1 ? (idx / (count - 1)) * (width - 40) + 20 : width / 2;
    const y = height - 20 - (t.attempts / maxTrendAttempts.value) * (height - 40);
    return `${x},${y}`;
  }).join(' ');
});
</script>

<template>
  <Head title="Report Exam & Analytical Insights" />

  <DashboardWrapper>
    <div class="max-w-7xl mx-auto flex flex-col gap-8 print:p-0">

      <!-- HEADER & META INFORMASI -->
      <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 bg-gradient-to-r from-[#1A2B49] via-[#264790] to-[#1f3a76] p-6 lg:p-8 rounded-[2rem] text-white shadow-xl relative overflow-hidden print:shadow-none print:bg-white print:text-slate-900 print:p-0">
        <!-- Background Decorative Accents -->
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute right-1/3 -top-10 w-40 h-40 bg-blue-400/10 rounded-full blur-xl pointer-events-none"></div>

        <div class="flex flex-col gap-2 z-10">
          <div class="flex items-center gap-3 flex-wrap">
            <span class="px-3 py-1 bg-white/10 backdrop-blur-md text-blue-200 border border-white/15 rounded-full text-xs font-extrabold flex items-center gap-1.5 shadow-sm">
              <Sparkles :size="14" class="text-amber-300" /> Executive Analytics Report
            </span>
            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 rounded-full text-xs font-bold flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              Live Real-Time Active
            </span>
          </div>

          <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-white mt-1">
            Report Exam & Performance Insights
          </h1>
          
          <p class="text-xs lg:text-sm text-blue-100/90 font-medium max-w-2xl leading-relaxed">
            Analisis komprehensif tingkat partisipasi, tingkat kelulusan, skor rata-rata, serta pengenalan anomali pengerjaan ujian peserta secara real-time.
          </p>

          <!-- Meta Information Badges -->
          <div class="flex items-center gap-4 text-xs text-blue-100/80 mt-2 flex-wrap font-semibold">
            <div class="flex items-center gap-1.5">
              <Clock :size="14" class="text-blue-300" />
              <span>Periode Data: <strong class="text-white font-bold">{{ auditMetadata.period_label }}</strong></span>
            </div>
            <div class="flex items-center gap-1.5">
              <Activity :size="14" class="text-blue-300" />
              <span>Di-generate pada: <strong class="text-white font-bold">{{ auditMetadata.generated_at }}</strong></span>
            </div>
          </div>
        </div>

        <!-- QUICK ACTION EXPORT & PRINT BUTTONS -->
        <div class="flex items-center gap-3 z-10 w-full lg:w-auto print:hidden">
          <button 
            @click="isFilterOpen = !isFilterOpen" 
            class="px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-xl font-bold text-xs transition-all duration-300 flex items-center gap-2 cursor-pointer shadow-sm active:scale-95"
          >
            <Filter :size="16" /> Filter
          </button>
          
          <button 
            @click="exportCSV" 
            class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-extrabold text-xs transition-all duration-300 flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer active:scale-95"
          >
            <FileSpreadsheet :size="16" /> Export Excel/CSV
          </button>
          
          <button 
            @click="printReport" 
            class="px-4 py-2.5 bg-white text-[#1A2B49] hover:bg-slate-100 rounded-xl font-extrabold text-xs transition-all duration-300 flex items-center gap-2 shadow-md hover:shadow-lg cursor-pointer active:scale-95"
          >
            <Printer :size="16" /> Cetak / PDF
          </button>
        </div>
      </div>

      <!-- GLOBAL FILTER CONTROL PANEL -->
      <div v-show="isFilterOpen" class="bg-white p-6 rounded-2xl border border-slate-100 shadow-lg flex flex-col gap-5 transition-all duration-300 print:hidden">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-sm font-extrabold text-[#1A2B49] flex items-center gap-2">
            <Filter :size="16" class="text-[#264790]" /> Panel Filter Interaktif
          </h3>
          <button @click="resetFilters" class="text-xs font-bold text-red-500 hover:text-red-700 flex items-center gap-1 cursor-pointer">
            <RefreshCw :size="12" /> Reset Filter
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Preset Periode -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Rentang Periode</label>
            <select v-model="filterForm.date_preset" @change="applyFilters" class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50 outline-none focus:border-[#264790]">
              <option value="7_days">7 Hari Terakhir</option>
              <option value="30_days">30 Hari Terakhir</option>
              <option value="this_month">Bulan Ini</option>
              <option value="this_quarter">Triwulan Ini</option>
              <option value="all">Semua Data</option>
              <option value="custom">Kustom Tanggal</option>
            </select>
          </div>

          <!-- Filter Kursus -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Kursus</label>
            <select v-model="filterForm.course_id" @change="applyFilters" class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50 outline-none focus:border-[#264790]">
              <option value="">Semua Kursus</option>
              <option v-for="c in coursesList" :key="c.id" :value="c.id">{{ c.title }}</option>
            </select>
          </div>

          <!-- Tipe Tes -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Tipe Tes</label>
            <select v-model="filterForm.assessment_type" @change="applyFilters" class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50 outline-none focus:border-[#264790]">
              <option value="all">Semua Tipe Tes</option>
              <option value="pre_test">Pre-Test</option>
              <option value="post_test">Post-Test</option>
            </select>
          </div>

          <!-- Status Tes -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Kelulusan / Anomali</label>
            <select v-model="filterForm.status" @change="applyFilters" class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50 outline-none focus:border-[#264790]">
              <option value="all">Semua Status</option>
              <option value="passed">Lulus</option>
              <option value="failed">Tidak Lulus</option>
              <option value="flagged">Perlu Perhatian / Anomali</option>
            </select>
          </div>
        </div>

        <!-- Custom Date Range Pickers if preset is custom -->
        <div v-if="filterForm.date_preset === 'custom'" class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-slate-100 pt-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Mulai</label>
            <input type="date" v-model="filterForm.start_date" @change="applyFilters" class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50 outline-none focus:border-[#264790]" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Selesai</label>
            <input type="date" v-model="filterForm.end_date" @change="applyFilters" class="w-full border-2 border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 bg-slate-50 outline-none focus:border-[#264790]" />
          </div>
        </div>
      </div>

      <!-- EXECUTIVE SUMMARY (KPI METRIC CARDS) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Total Volume Percobaan -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between relative overflow-hidden transition-all hover:shadow-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Total Volume Ujian</span>
            <div class="p-3 bg-blue-50 text-[#264790] rounded-xl">
              <FileBarChart :size="20" />
            </div>
          </div>
          <div class="mt-4">
            <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight">{{ kpiMetrics.total_volume }}</h2>
            <div class="flex items-center gap-2 mt-2">
              <span v-if="kpiMetrics.prev_trend?.volume_percent" :class="kpiMetrics.prev_trend.volume_diff.startsWith('+') ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-rose-50 text-rose-600 border-rose-200'" class="px-2 py-0.5 border rounded-md text-[11px] font-black flex items-center gap-1">
                <TrendingUp v-if="kpiMetrics.prev_trend.volume_diff.startsWith('+')" :size="12" />
                <TrendingDown v-else :size="12" />
                {{ kpiMetrics.prev_trend.volume_percent }}
              </span>
              <span class="text-xs text-slate-400 font-semibold">vs periode lalu</span>
            </div>
          </div>
        </div>

        <!-- 2. Tingkat Kelulusan (Pass Rate) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between relative overflow-hidden transition-all hover:shadow-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Tingkat Kelulusan</span>
            <div :class="kpiMetrics.pass_rate >= 75 ? 'bg-emerald-50 text-emerald-600' : (kpiMetrics.pass_rate >= 50 ? 'bg-amber-50 text-amber-600' : 'bg-rose-50 text-rose-600')" class="p-3 rounded-xl">
              <Award :size="20" />
            </div>
          </div>
          <div class="mt-4">
            <div class="flex items-baseline gap-2">
              <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight">{{ kpiMetrics.pass_rate }}%</h2>
              <span :class="kpiMetrics.pass_rate >= 75 ? 'bg-emerald-100 text-emerald-700' : (kpiMetrics.pass_rate >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase">
                {{ kpiMetrics.pass_rate >= 75 ? 'Tinggi' : (kpiMetrics.pass_rate >= 50 ? 'Sedang' : 'Perlu Evaluasi') }}
              </span>
            </div>
            <div class="flex items-center gap-2 mt-2">
              <span v-if="kpiMetrics.prev_trend?.pass_rate_diff" :class="kpiMetrics.prev_trend.pass_rate_diff.startsWith('+') ? 'text-emerald-600' : 'text-rose-600'" class="text-xs font-bold flex items-center gap-1">
                {{ kpiMetrics.prev_trend.pass_rate_diff }} persentase
              </span>
              <span class="text-xs text-slate-400 font-semibold">vs periode lalu</span>
            </div>
          </div>
        </div>

        <!-- 3. Skor Rata-Rata -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between relative overflow-hidden transition-all hover:shadow-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Skor Rata-Rata</span>
            <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
              <Activity :size="20" />
            </div>
          </div>
          <div class="mt-4">
            <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight">{{ kpiMetrics.avg_score }} <span class="text-lg font-bold text-slate-400">/ 100</span></h2>
            <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden">
              <div class="bg-gradient-to-r from-purple-500 to-indigo-600 h-full rounded-full transition-all duration-500" :style="{ width: Math.min(100, kpiMetrics.avg_score) + '%' }"></div>
            </div>
          </div>
        </div>

        <!-- 4. Indikasi Anomali & Risiko -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between relative overflow-hidden transition-all hover:shadow-md">
          <div class="flex items-center justify-between">
            <span class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Flag Anomali / Risiko</span>
            <div :class="kpiMetrics.flagged_count > 0 ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-400'" class="p-3 rounded-xl">
              <AlertTriangle :size="20" />
            </div>
          </div>
          <div class="mt-4">
            <div class="flex items-baseline gap-2">
              <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight">{{ kpiMetrics.flagged_count }}</h2>
              <span class="text-xs font-bold text-slate-400">Kasus</span>
            </div>
            <p class="text-xs text-slate-400 font-medium mt-2">Durasi &lt;30 detik atau skor &lt;40%</p>
          </div>
        </div>
      </div>

      <!-- VISUAL ANALYTICS (GRAFIK TREND & DISTRIBUSI) -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Trend Chart (2 Columns) -->
        <div class="lg:col-span-2 bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-5">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
              <h3 class="text-lg font-bold text-[#1A2B49]">Tren Volume & Kelulusan Ujian</h3>
              <p class="text-xs text-slate-400 mt-0.5">Pergerakan akumulasi pengerjaan ujian per tanggal.</p>
            </div>
            <span class="text-xs font-bold text-[#264790] bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
              {{ trendChart.length }} Hari Data
            </span>
          </div>

          <!-- SVG Responsive Trend Chart -->
          <div v-if="trendChart && trendChart.length > 0" class="w-full overflow-x-auto">
            <div class="min-w-[500px] flex flex-col gap-2">
              <svg viewBox="0 0 600 160" class="w-full h-44 overflow-visible">
                <!-- Grid Lines -->
                <line x1="20" y1="20" x2="580" y2="20" stroke="#F1F5F9" stroke-width="1" />
                <line x1="20" y1="70" x2="580" y2="70" stroke="#F1F5F9" stroke-width="1" />
                <line x1="20" y1="120" x2="580" y2="120" stroke="#F1F5F9" stroke-width="1" stroke-dasharray="4 4" />

                <!-- Trend Line Path -->
                <polyline 
                  fill="none" 
                  stroke="#264790" 
                  stroke-width="3.5" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  :points="trendPoints" 
                />

                <!-- Trend Line Data Points -->
                <circle 
                  v-for="(t, idx) in trendChart" 
                  :key="idx"
                  :cx="trendChart.length > 1 ? (idx / (trendChart.length - 1)) * (560) + 20 : 300"
                  :cy="160 - 20 - (t.attempts / maxTrendAttempts) * 120"
                  r="5"
                  fill="#1A2B49"
                  stroke="#FFFFFF"
                  stroke-width="2.5"
                  class="cursor-pointer hover:r-7 transition-all"
                >
                  <title>{{ t.date }}: {{ t.attempts }} Percobaan (Pass: {{ t.pass_rate }}%)</title>
                </circle>
              </svg>

              <!-- X-Axis Labels -->
              <div class="flex justify-between text-[11px] font-bold text-slate-400 px-2 pt-2 border-t border-slate-100">
                <span v-for="(t, idx) in trendChart" :key="idx" class="truncate max-w-[50px] text-center">
                  {{ t.date }}
                </span>
              </div>
            </div>
          </div>

          <div v-else class="py-12 text-center text-slate-400 text-xs font-semibold">
            Belum ada data tren pengerjaan ujian pada periode ini.
          </div>
        </div>

        <!-- Distribution Chart & Grade Tiers (1 Column) -->
        <div class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="text-lg font-bold text-[#1A2B49]">Distribusi Kategori Skor</h3>
            <p class="text-xs text-slate-400 mt-0.5">Proporsi tingkat kelulusan dan tier performa.</p>
          </div>

          <!-- Grade Tier Progress Bars -->
          <div class="flex flex-col gap-4">
            <!-- Tier 1: Sangat Baik (>85) -->
            <div class="flex flex-col gap-1.5">
              <div class="flex justify-between items-center text-xs font-extrabold text-slate-700">
                <span class="flex items-center gap-1.5 text-emerald-600">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Sangat Baik (&gt;=85%)
                </span>
                <span>{{ distributionData.tiers?.excellent || 0 }} Siswa</span>
              </div>
              <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: (kpiMetrics.total_volume > 0 ? ((distributionData.tiers?.excellent || 0) / kpiMetrics.total_volume) * 100 : 0) + '%' }"></div>
              </div>
            </div>

            <!-- Tier 2: Baik (70-84) -->
            <div class="flex flex-col gap-1.5">
              <div class="flex justify-between items-center text-xs font-extrabold text-slate-700">
                <span class="flex items-center gap-1.5 text-blue-600">
                  <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Baik (70 - 84%)
                </span>
                <span>{{ distributionData.tiers?.good || 0 }} Siswa</span>
              </div>
              <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                <div class="bg-blue-500 h-full rounded-full transition-all duration-500" :style="{ width: (kpiMetrics.total_volume > 0 ? ((distributionData.tiers?.good || 0) / kpiMetrics.total_volume) * 100 : 0) + '%' }"></div>
              </div>
            </div>

            <!-- Tier 3: Remedial (<70) -->
            <div class="flex flex-col gap-1.5">
              <div class="flex justify-between items-center text-xs font-extrabold text-slate-700">
                <span class="flex items-center gap-1.5 text-rose-600">
                  <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Perlu Remedial (&lt;70%)
                </span>
                <span>{{ distributionData.tiers?.remedial || 0 }} Siswa</span>
              </div>
              <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                <div class="bg-rose-500 h-full rounded-full transition-all duration-500" :style="{ width: (kpiMetrics.total_volume > 0 ? ((distributionData.tiers?.remedial || 0) / kpiMetrics.total_volume) * 100 : 0) + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- Summary Badges Box -->
          <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex items-center justify-around text-center mt-2">
            <div>
              <span class="block text-xs font-bold text-slate-400">Total Lulus</span>
              <span class="text-xl font-black text-emerald-600">{{ distributionData.passed || 0 }}</span>
            </div>
            <div class="w-px h-8 bg-slate-200"></div>
            <div>
              <span class="block text-xs font-bold text-slate-400">Gagal</span>
              <span class="text-xl font-black text-rose-600">{{ distributionData.failed || 0 }}</span>
            </div>
            <div class="w-px h-8 bg-slate-200"></div>
            <div>
              <span class="block text-xs font-bold text-slate-400">Anomali</span>
              <span class="text-xl font-black text-amber-600">{{ distributionData.flagged || 0 }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ANOMALY HIGHLIGHTS & ACTIONABLE INSIGHTS -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Anomaly Flags Warning Panel -->
        <div class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-4">
          <h3 class="text-lg font-bold text-[#1A2B49] flex items-center gap-2 border-b border-slate-100 pb-3">
            <ShieldAlert :size="20" class="text-amber-500" /> Deteksi Anomali & Temuan Penting
          </h3>

          <div v-if="anomalyFlags && anomalyFlags.length > 0" class="flex flex-col gap-3">
            <div 
              v-for="(flag, idx) in anomalyFlags" 
              :key="idx"
              :class="flag.level === 'danger' ? 'bg-rose-50/70 border-rose-200 text-rose-900' : 'bg-amber-50/70 border-amber-200 text-amber-900'"
              class="p-4 rounded-xl border flex items-start gap-3 transition-all"
            >
              <AlertTriangle :size="18" :class="flag.level === 'danger' ? 'text-rose-600' : 'text-amber-600'" class="shrink-0 mt-0.5" />
              <div>
                <h4 class="text-xs font-black uppercase tracking-wider">{{ flag.title }}</h4>
                <p class="text-xs font-semibold leading-relaxed mt-1 opacity-90">{{ flag.message }}</p>
              </div>
            </div>
          </div>

          <div v-else class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/60 text-emerald-900 flex items-center gap-3">
            <CheckCircle2 :size="18" class="text-emerald-600 shrink-0" />
            <p class="text-xs font-bold">Seluruh aktivitas ujian berjalan normal. Tidak ditemukan indikasi anomali durasi atau skor.</p>
          </div>
        </div>

        <!-- Actionable Recommendations Panel -->
        <div class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-4">
          <h3 class="text-lg font-bold text-[#1A2B49] flex items-center gap-2 border-b border-slate-100 pb-3">
            <Sparkles :size="20" class="text-indigo-600" /> Rekomendasi Tindakan Sistem
          </h3>

          <div class="flex flex-col gap-3">
            <div 
              v-for="(insight, idx) in actionableInsights" 
              :key="idx"
              class="p-3.5 rounded-xl border border-indigo-100 bg-indigo-50/40 text-slate-800 text-xs font-semibold flex items-start gap-3"
            >
              <span class="w-5 h-5 rounded-full bg-indigo-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">{{ idx + 1 }}</span>
              <p class="leading-relaxed">{{ insight }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- GRANULAR BREAKDOWN DATA TABLE -->
      <div class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100 pb-4">
          <div>
            <h3 class="text-lg font-bold text-[#1A2B49]">Detail Data Percobaan Ujian (Granular Breakdown)</h3>
            <p class="text-xs text-slate-400 mt-0.5">Rincian individual pengerjaan pre-test & post-test peserta.</p>
          </div>

          <!-- Search Table Input -->
          <div class="relative w-full md:w-72 print:hidden">
            <Search :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input 
              type="text" 
              v-model="filterForm.search" 
              @keyup.enter="applyFilters"
              placeholder="Cari nama atau email..." 
              class="w-full pl-9 pr-4 py-2 border-2 border-slate-200 rounded-xl text-xs font-bold outline-none focus:border-[#264790] bg-slate-50"
            />
          </div>
        </div>

        <!-- Table Container -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse min-w-[750px]">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/60 text-slate-500 text-[11px] font-extrabold uppercase tracking-wider">
                <th class="py-3 px-4 rounded-l-xl">Kode Attempt</th>
                <th class="py-3 px-4">Nama Peserta</th>
                <th class="py-3 px-4">Kursus & Modul</th>
                <th class="py-3 px-4">Tipe Tes</th>
                <th class="py-3 px-4 text-center">Skor (%)</th>
                <th class="py-3 px-4 text-center">Durasi</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center rounded-r-xl print:hidden">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
              <tr v-for="row in tableData" :key="row.id" class="hover:bg-slate-50/70 transition-colors">
                <!-- Kode Attempt -->
                <td class="py-3.5 px-4 font-mono font-bold text-[#264790]">
                  {{ row.attempt_code }}
                </td>

                <!-- Nama Peserta -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-3">
                    <div v-if="row.student_photo" class="w-8 h-8 rounded-full bg-cover bg-center shrink-0 border border-slate-200" :style="{ backgroundImage: `url('${row.student_photo}')` }"></div>
                    <div v-else class="w-8 h-8 rounded-full bg-[#264790]/10 text-[#264790] font-black text-xs flex items-center justify-center shrink-0">
                      {{ getInitials(row.student_name) }}
                    </div>
                    <div class="flex flex-col">
                      <span class="font-bold text-slate-800">{{ row.student_name }}</span>
                      <span class="text-[11px] text-slate-400 font-medium">{{ row.student_email }}</span>
                    </div>
                  </div>
                </td>

                <!-- Kursus & Modul -->
                <td class="py-3.5 px-4 max-w-[220px]">
                  <div class="flex flex-col truncate">
                    <span class="font-bold text-slate-800 truncate" :title="row.course_title">{{ row.course_title }}</span>
                    <span class="text-[11px] text-slate-400 truncate" :title="row.module_title">{{ row.module_title }}</span>
                  </div>
                </td>

                <!-- Tipe Ujian -->
                <td class="py-3.5 px-4">
                  <span :class="row.type === 'pre_test' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-purple-50 text-purple-700 border-purple-200'" class="px-2.5 py-1 border rounded-lg text-[10px] font-black uppercase">
                    {{ row.type === 'pre_test' ? 'Pre-Test' : 'Post-Test' }}
                  </span>
                </td>

                <!-- Skor -->
                <td class="py-3.5 px-4 text-center font-bold text-sm">
                  <span :class="row.total_score >= 70 ? 'text-emerald-600' : 'text-rose-600'">
                    {{ row.total_score }}%
                  </span>
                </td>

                <!-- Durasi -->
                <td class="py-3.5 px-4 text-center text-slate-500 font-mono">
                  {{ row.duration_formatted }}
                </td>

                <!-- Status -->
                <td class="py-3.5 px-4 text-center">
                  <span 
                    v-if="row.is_flagged"
                    class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-black uppercase inline-flex items-center gap-1"
                  >
                    <AlertTriangle :size="10" /> Anomali
                  </span>
                  <span 
                    v-else-if="row.is_passed"
                    class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-black uppercase inline-flex items-center gap-1"
                  >
                    <CheckCircle2 :size="10" /> Lulus
                  </span>
                  <span 
                    v-else
                    class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[10px] font-black uppercase inline-flex items-center gap-1"
                  >
                    <XCircle :size="10" /> Gagal
                  </span>
                </td>

                <!-- Aksi -->
                <td class="py-3.5 px-4 text-center print:hidden">
                  <button 
                    @click="openDetail(row)" 
                    class="p-2 bg-slate-100 hover:bg-[#264790] text-slate-600 hover:text-white rounded-lg transition-colors cursor-pointer"
                    title="Lihat Rincian Jawaban"
                  >
                    <Eye :size="15" />
                  </button>
                </td>
              </tr>

              <tr v-if="!tableData || tableData.length === 0">
                <td colspan="8" class="py-12 text-center text-slate-400 font-bold">
                  Tidak ada data percobaan pengerjaan ujian yang ditemukan.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- AUDIT TRAIL FOOTER METADATA -->
      <div class="bg-slate-100 p-4 rounded-xl text-center text-xs font-semibold text-slate-500 border border-slate-200/60 print:bg-white print:border-none">
        Generated automatically by Drastha Learning Analytics Engine on <strong class="text-slate-700">{{ auditMetadata.generated_at }}</strong> for <strong class="text-slate-700">{{ auditMetadata.generated_by }}</strong> (IP: {{ auditMetadata.ip_address }}).
      </div>

    </div>

    <!-- DETAIL JAWABAN ATTEMPT MODAL -->
    <div v-if="isDetailModalOpen && selectedAttempt" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm print:hidden">
      <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-hidden flex flex-col border border-slate-100">
        <!-- Modal Header -->
        <div class="p-6 bg-slate-900 text-white flex items-center justify-between">
          <div>
            <span class="text-xs font-mono text-blue-300">{{ selectedAttempt.attempt_code }}</span>
            <h3 class="text-lg font-extrabold text-white">Detail Lembar Jawaban Peserta</h3>
            <p class="text-xs text-slate-300">{{ selectedAttempt.student_name }} ({{ selectedAttempt.student_email }})</p>
          </div>
          <button @click="closeDetail" class="text-slate-400 hover:text-white p-2 rounded-full hover:bg-white/10 transition-colors cursor-pointer">
            &times;
          </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto flex flex-col gap-6 text-xs text-slate-700">
          <!-- Summary Header Box -->
          <div class="grid grid-cols-3 gap-3 p-4 bg-slate-50 rounded-xl border border-slate-100 text-center font-bold">
            <div>
              <span class="block text-[11px] text-slate-400">Total Skor</span>
              <span class="text-lg font-black text-[#264790]">{{ selectedAttempt.total_score }}%</span>
            </div>
            <div>
              <span class="block text-[11px] text-slate-400">Durasi Pengerjaan</span>
              <span class="text-base font-black text-slate-800">{{ selectedAttempt.duration_formatted }}</span>
            </div>
            <div>
              <span class="block text-[11px] text-slate-400">Status</span>
              <span :class="selectedAttempt.is_passed ? 'text-emerald-600' : 'text-rose-600'" class="text-base font-black uppercase">
                {{ selectedAttempt.is_passed ? 'Lulus' : 'Gagal' }}
              </span>
            </div>
          </div>

          <!-- Question-by-Question Answers -->
          <div class="flex flex-col gap-4">
            <h4 class="font-extrabold text-slate-800 border-b border-slate-100 pb-2">Rincian Jawaban Soal:</h4>
            
            <div 
              v-for="(ans, idx) in selectedAttempt.answers_breakdown" 
              :key="idx"
              :class="ans.is_correct ? 'bg-emerald-50/50 border-emerald-200' : 'bg-rose-50/50 border-rose-200'"
              class="p-4 rounded-xl border flex flex-col gap-2"
            >
              <div class="flex items-start justify-between gap-3 font-bold">
                <span>Soal {{ idx + 1 }}: {{ ans.question_text }}</span>
                <span :class="ans.is_correct ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'" class="px-2 py-0.5 rounded text-[10px] font-black uppercase shrink-0">
                  {{ ans.is_correct ? 'Benar (+ ' + ans.points + ' Poin)' : 'Salah (0 Poin)' }}
                </span>
              </div>
              <div class="text-[11px] font-medium text-slate-600">
                Jawaban Peserta: <strong class="text-slate-900">{{ ans.selected_answer || 'Tidak Diisi' }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
          <button @click="closeDetail" class="px-6 py-2 bg-[#264790] hover:bg-[#1A2B49] text-white font-bold rounded-xl text-xs shadow-sm transition-all cursor-pointer">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </DashboardWrapper>
</template>
