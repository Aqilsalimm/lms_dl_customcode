<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  BarChart3, 
  AlertTriangle, 
  CheckCircle2, 
  XCircle, 
  HelpCircle, 
  Users, 
  GraduationCap, 
  ArrowLeft, 
  Award, 
  TrendingUp, 
  Clock,
  ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
  course: Object,
  preTest: Object,
  postTest: Object,
  atRiskStudents: Array,
  studentScores: Array,
});

const activeTab = ref('overview'); // 'overview', 'at_risk', 'item_analysis', 'all_students'
</script>

<template>
  <GuestLayout>
    <Head :title="`Analitik Penilaian - ${course.title}`" />

    <DashboardWrapper>
      <!-- Header Banner -->
      <div class="mb-8 bg-gradient-to-r from-[#1A2B49] via-[#264790] to-[#1A2B49] rounded-[2.5rem] p-8 md:p-10 text-white shadow-2xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
          <div>
            <Link 
              :href="`/courses/${course.slug}`" 
              class="inline-flex items-center gap-2 text-xs font-bold text-blue-200 hover:text-white mb-4 transition-colors bg-white/10 px-4 py-2 rounded-full backdrop-blur-md border border-white/10"
            >
              <ArrowLeft :size="14" /> Kembali ke Kursus
            </Link>
            <h1 class="text-2xl md:text-3xl font-black tracking-tight mb-2">
              📊 Dasbor Rekapitulasi & Analitik Penilaian
            </h1>
            <p class="text-blue-100/80 text-sm font-medium">
              Monitoring performa Pre-test, Post-test, serta peserta yang membutuhkan bantuan (At-Risk).
            </p>
          </div>

          <div class="flex items-center gap-3 bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-2xl">
            <GraduationCap :size="32" class="text-blue-300" />
            <div>
              <div class="text-[10px] font-extrabold uppercase tracking-widest text-blue-200">Total Peserta</div>
              <div class="text-xl font-black">{{ studentScores ? studentScores.length : 0 }} Siswa</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Aggregate Cards (Segment 1) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Pre-Test Average Card -->
        <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-rata Pre-Test</span>
            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-black">
              <TrendingUp :size="20" />
            </div>
          </div>
          <div class="text-3xl font-black text-[#1A2B49] mb-1">
            {{ preTest ? preTest.avg_score : '-' }} <span class="text-xs text-slate-400 font-semibold">/ 100</span>
          </div>
          <div class="text-xs text-slate-500 font-semibold">
            KKM: <span class="font-extrabold text-[#1A2B49]">{{ preTest ? preTest.passing_score : '-' }}</span>
          </div>
        </div>

        <!-- Post-Test Average Card -->
        <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-rata Post-Test</span>
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black">
              <Award :size="20" />
            </div>
          </div>
          <div class="text-3xl font-black text-[#1A2B49] mb-1">
            {{ postTest ? postTest.avg_score : '-' }} <span class="text-xs text-slate-400 font-semibold">/ 100</span>
          </div>
          <div class="text-xs text-slate-500 font-semibold">
            KKM: <span class="font-extrabold text-[#1A2B49]">{{ postTest ? postTest.passing_score : '-' }}</span>
          </div>
        </div>

        <!-- Pass Rate Metric Card -->
        <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Tingkat Kelulusan</span>
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#264790] flex items-center justify-center font-black">
              <CheckCircle2 :size="20" />
            </div>
          </div>
          <div class="text-3xl font-black text-[#1A2B49] mb-1">
            {{ postTest && postTest.total_attempts > 0 ? Math.round((postTest.pass_count / postTest.total_attempts) * 100) : (preTest && preTest.total_attempts > 0 ? Math.round((preTest.pass_count / preTest.total_attempts) * 100) : 0) }}%
          </div>
          <div class="text-xs text-slate-500 font-semibold">
            Lulus: <span class="font-extrabold text-emerald-600">{{ postTest ? postTest.pass_count : (preTest ? preTest.pass_count : 0) }}</span> Peserta
          </div>
        </div>

        <!-- At-Risk Count Card -->
        <div class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all">
          <div class="flex items-center justify-between mb-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Peserta Perlu Bantuan</span>
            <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-black">
              <AlertTriangle :size="20" />
            </div>
          </div>
          <div class="text-3xl font-black text-rose-600 mb-1">
            {{ atRiskStudents ? atRiskStudents.length : 0 }} <span class="text-xs text-slate-400 font-semibold">Siswa</span>
          </div>
          <div class="text-xs text-slate-500 font-semibold">
            Butuh Pendampingan Khusus
          </div>
        </div>
      </div>

      <!-- Segment 2: At-Risk Student Flagging & Item Analysis Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- At-Risk Flagging Panel (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center font-black shadow-inner">
                <AlertTriangle :size="20" />
              </div>
              <div>
                <h3 class="text-lg font-black text-[#1A2B49]">⚠️ Peserta Berisiko (At-Risk Flagging)</h3>
                <p class="text-xs text-slate-400 font-semibold">Peserta yang gagal berulang kali atau mencapai batas maksimal retake</p>
              </div>
            </div>
            <span class="text-xs font-extrabold px-3 py-1 bg-rose-50 text-rose-600 rounded-full border border-rose-100">
              {{ atRiskStudents ? atRiskStudents.length : 0 }} Terdeteksi
            </span>
          </div>

          <div v-if="!atRiskStudents || atRiskStudents.length === 0" class="text-center py-12 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
            <CheckCircle2 :size="40" class="mx-auto text-emerald-500 mb-2" />
            <h4 class="text-sm font-bold text-[#1A2B49]">Tidak ada peserta At-Risk</h4>
            <p class="text-xs text-slate-400 mt-1">Semua peserta berjalan lancar tanpa kendala berulang.</p>
          </div>

          <div v-else class="space-y-3">
            <div 
              v-for="student in atRiskStudents" 
              :key="student.user_id"
              class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-rose-50/50 hover:bg-rose-50 rounded-2xl border border-rose-100/80 transition-all gap-4"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-rose-600 text-white font-black text-xs flex items-center justify-center shadow-md">
                  {{ student.name.charAt(0) }}
                </div>
                <div>
                  <div class="text-sm font-black text-[#1A2B49]">{{ student.name }}</div>
                  <div class="text-xs text-slate-500 font-medium">{{ student.email }}</div>
                </div>
              </div>

              <div class="flex items-center gap-4 text-xs">
                <div class="text-right">
                  <span class="font-extrabold text-rose-700 bg-rose-100 px-2.5 py-1 rounded-full text-[11px]">
                    Gagal {{ student.failed_attempts }}x (Skor: {{ student.last_score }})
                  </span>
                  <div class="text-[10px] text-slate-400 font-bold mt-1 uppercase">{{ student.assessment_title }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Item Analysis / Top Hardest Questions (1 Col) -->
        <div class="bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-black shadow-inner">
              <HelpCircle :size="20" />
            </div>
            <div>
              <h3 class="text-lg font-black text-[#1A2B49]">🎯 Analisis Butir Soal</h3>
              <p class="text-xs text-slate-400 font-semibold">Top 5 Soal Tersulit (Paling Banyak Salah)</p>
            </div>
          </div>

          <div v-if="preTest && preTest.hardest_questions && preTest.hardest_questions.length > 0" class="space-y-4">
            <div 
              v-for="(q, idx) in preTest.hardest_questions" 
              :key="q.id"
              class="p-4 bg-slate-50 rounded-2xl border border-slate-100"
            >
              <div class="flex items-start justify-between gap-2 mb-2">
                <span class="text-xs font-black text-[#1A2B49] line-clamp-2">
                  #{{ idx + 1 }} {{ q.question_text }}
                </span>
                <span class="text-[10px] font-black bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full flex-shrink-0">
                  {{ q.wrong_answers_count }} Salah
                </span>
              </div>
              <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div 
                  class="bg-rose-500 h-full rounded-full transition-all" 
                  :style="{ width: (q.total_answers_count > 0 ? (q.wrong_answers_count / q.total_answers_count) * 100 : 0) + '%' }"
                ></div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-8 text-xs text-slate-400 font-semibold">
            Belum ada data butir soal yang terkumpul.
          </div>
        </div>

      </div>

      <!-- Segment 3: All Enrolled Students Rekapitulasi Table -->
      <div class="bg-white rounded-[2.5rem] p-6 md:p-8 border border-slate-100 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div>
            <h3 class="text-xl font-black text-[#1A2B49]">📋 Rekapitulasi Nilai Seluruh Peserta</h3>
            <p class="text-xs text-slate-400 font-semibold">Daftar nilai Pre-test dan Post-test per individu peserta</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-wider text-slate-400 bg-slate-50/50">
                <th class="py-4 px-4 rounded-l-2xl">Peserta</th>
                <th class="py-4 px-4">Nilai Pre-Test</th>
                <th class="py-4 px-4">Status Pre-Test</th>
                <th class="py-4 px-4">Nilai Post-Test</th>
                <th class="py-4 px-4">Status Post-Test</th>
                <th class="py-4 px-4 rounded-r-2xl">Tanggal Daftar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs font-semibold">
              <tr v-for="student in studentScores" :key="student.user_id" class="hover:bg-slate-50/60 transition-colors">
                <td class="py-4 px-4">
                  <div class="font-black text-[#1A2B49]">{{ student.name }}</div>
                  <div class="text-[11px] text-slate-400 font-normal">{{ student.email }}</div>
                </td>
                <td class="py-4 px-4 font-black text-sm">
                  {{ student.pre_test_score !== null ? student.pre_test_score : '-' }}
                </td>
                <td class="py-4 px-4">
                  <span 
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-black',
                      student.pre_test_status === 'Lulus' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : (student.pre_test_status === 'Gagal' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-slate-100 text-slate-500')
                    ]"
                  >
                    {{ student.pre_test_status }}
                  </span>
                </td>
                <td class="py-4 px-4 font-black text-sm">
                  {{ student.post_test_score !== null ? student.post_test_score : '-' }}
                </td>
                <td class="py-4 px-4">
                  <span 
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-black',
                      student.post_test_status === 'Lulus' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : (student.post_test_status === 'Gagal' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-slate-100 text-slate-500')
                    ]"
                  >
                    {{ student.post_test_status }}
                  </span>
                </td>
                <td class="py-4 px-4 text-slate-400">
                  {{ student.enrolled_at }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>
