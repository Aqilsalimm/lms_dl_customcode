<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  BookOpen, Award, Clock, ArrowRight, Play,
  CreditCard, CheckCircle, HelpCircle, Calendar
} from 'lucide-vue-next';

const props = defineProps({
  enrolledCourses: Array,
  quizAttempts: Array,
  orders: Array,
  metrics: Object
});

const isProcessing = ref(false);
const completionsMap = ref({});

onMounted(() => {
  const savedCompletions = localStorage.getItem('drastha_course_completions');
  if (savedCompletions) {
    try {
      completionsMap.value = JSON.parse(savedCompletions);
    } catch (e) {}
  }
});

const getProgress = (course) => {
  const completed = completionsMap.value[course.id] || [];
  if (!course.lessons_count || course.lessons_count === 0) return 0;
  const pct = Math.round((completed.length / course.lessons_count) * 100);
  return Math.min(pct, 100);
};

const payOrder = (snapToken, orderId) => {
  if (snapToken.includes('MOCK-SNAP-TOKEN')) {
    // Development Mock mode! Proactively complete order using custom local API.
    isProcessing.value = true;
    router.post(`/payment/mock-complete/${orderId}`, {}, {
      onFinish: () => {
        isProcessing.value = false;
        alert('Mock Payment Berhasil! Anda sekarang terdaftar.');
        window.location.reload();
      }
    });
  } else {
    // Real Snap midtrans payment popup!
    window.snap.pay(snapToken, {
      onSuccess: function(result) {
        window.location.reload();
      },
      onPending: function(result) {
        window.location.reload();
      },
      onError: function(result) {
        window.location.reload();
      }
    });
  }
};
</script>

<template>
  <Head title="Student Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-extrabold text-[#1A2B49] leading-tight">
          Dashboard Siswa
        </h2>
        <span class="bg-[#F9CC6B] text-[#1A2B49] text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1 shadow-sm">
          <Award :size="12" /> Level: Siswa Aktif
        </span>
      </div>
    </template>

    <div class="py-12 bg-slate-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
          
          <!-- Metric 1: Registered Classes -->
          <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
              <BookOpen :size="28" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-0.5">Kelas Terdaftar</p>
              <h3 class="text-xl font-extrabold text-[#1A2B49]">{{ metrics.enrolled_count }} Kelas</h3>
            </div>
          </div>

          <!-- Metric 2: Completed Quizzes -->
          <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-sky-50 flex items-center justify-center text-sky-600">
              <Award :size="28" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-0.5">Kuis Dikerjakan</p>
              <h3 class="text-xl font-extrabold text-[#1A2B49]">{{ metrics.completed_quizzes }} Kuis</h3>
            </div>
          </div>

          <!-- Metric 3: Quizzes Passed -->
          <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
              <CheckCircle :size="28" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-0.5">Kuis Lulus (SKM &ge; 75)</p>
              <h3 class="text-xl font-extrabold text-[#1A2B49]">{{ metrics.passed_quizzes }} Kuis</h3>
            </div>
          </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          <!-- Enrolled Courses -->
          <div class="lg:col-span-2 bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
            <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
              <Play :size="20" class="text-indigo-600" /> Kelas Anda Sedang Berjalan
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div 
                v-for="course in enrolledCourses" 
                :key="course.id"
                class="rounded-3xl border border-slate-100 p-5 bg-slate-50/50 flex flex-col justify-between hover:bg-white hover:border-slate-200 transition-all duration-300"
              >
                <div>
                  <div class="flex items-center justify-between mb-4">
                    <span class="bg-[#F9CC6B] text-[#1A2B49] text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">
                      Kelas {{ course.level }}
                    </span>
                    <span class="text-slate-400 text-xs font-medium">{{ course.lessons_count }} Sesi</span>
                  </div>
                  
                  <h4 class="font-extrabold text-[#1A2B49] text-base leading-tight mb-2 min-h-[2.5rem] line-clamp-2">
                    {{ course.title }}
                  </h4>
                  <p class="text-slate-400 text-xs font-medium mb-4">Oleh: {{ course.instructor_name }}</p>
                </div>

                <div>
                  <!-- Progress Bar -->
                  <div class="mb-4">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 mb-1.5">
                       <span>Progres Belajar</span>
                       <span>{{ getProgress(course) }}%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                       <div class="h-full bg-indigo-600 rounded-full" :style="{ width: getProgress(course) + '%' }"></div>
                    </div>
                  </div>

                  <Link 
                    :href="`/courses/${course.slug}/learn`"
                    class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-2.5 rounded-2xl font-bold text-xs transition-colors flex items-center justify-center gap-1.5"
                  >
                    Mulai Belajar <ArrowRight :size="14" />
                  </Link>
                </div>
              </div>

              <div v-if="enrolledCourses.length === 0" class="col-span-2 text-center py-10">
                <p class="text-slate-400 font-medium mb-4">Anda belum mendaftar di kelas manapun.</p>
                <Link 
                  href="/courses"
                  class="bg-[#264790] hover:bg-[#44A6D9] text-white px-6 py-2.5 rounded-full font-bold text-xs shadow-sm transition-colors"
                >
                  Cari Kelas Sekarang
                </Link>
              </div>
            </div>
          </div>

          <!-- Order & Invoice History -->
          <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col justify-between">
            <div>
              <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
                <CreditCard :size="20" class="text-indigo-600" /> Riwayat Pembelian
              </h3>
              
              <div class="flex flex-col gap-4">
                <div v-for="order in orders" :key="order.id" class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-xs text-[#1A2B49]">INV-#{{ order.id }}</span>
                    <span 
                      :class="{
                        'bg-emerald-50 text-emerald-700 border-emerald-100': order.status === 'completed',
                        'bg-amber-50 text-amber-700 border-amber-100': order.status === 'pending',
                        'bg-rose-50 text-rose-700 border-rose-100': order.status === 'failed'
                      }"
                      class="px-2 py-0.5 text-[10px] font-bold rounded-full border"
                    >
                      {{ order.status }}
                    </span>
                  </div>
                  <h4 class="font-bold text-xs text-[#1A2B49] mb-1 line-clamp-1">
                    {{ order.buyable?.title || 'LMS Item' }}
                  </h4>
                  <div class="flex items-center justify-between mt-2">
                    <span class="font-extrabold text-sm text-[#1A2B49]">Rp {{ parseFloat(order.amount).toLocaleString('id-ID') }}</span>
                    
                    <button 
                      v-if="order.status === 'pending' && order.snap_token"
                      @click="payOrder(order.snap_token, order.id)"
                      :disabled="isProcessing"
                      class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-xl font-bold text-[10px] shadow-sm transition-colors"
                    >
                      Bayar Sekarang
                    </button>
                  </div>
                </div>

                <div v-if="orders.length === 0" class="text-center py-6 text-slate-400 font-medium">
                  Belum ada invoice pendaftaran.
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
