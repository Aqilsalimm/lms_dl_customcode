<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { 
  BookOpen, Award, Clock, ArrowRight, Play,
  CreditCard, CheckCircle, HelpCircle, Calendar, Trophy, AlertCircle
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
    isProcessing.value = true;
    router.post(`/payment/mock-complete/${orderId}`, {}, {
      onFinish: () => {
        isProcessing.value = false;
        alert('Mock Payment Berhasil! Anda sekarang terdaftar.');
        window.location.reload();
      }
    });
  } else {
    window.snap.pay(snapToken, {
      onSuccess: function(result) { window.location.reload(); },
      onPending: function(result) { window.location.reload(); },
      onError: function(result) { window.location.reload(); }
    });
  }
};
</script>

<template>
  <Head title="Student Dashboard" />

  <GuestLayout>
    <DashboardWrapper>
      
      <!-- "Complete Your Profile" Card -->
      <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 mb-8 flex flex-col lg:flex-row gap-8 lg:items-center">
        <div class="flex-1">
          <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-6">Complete Your Profile</h3>
          
          <div class="flex gap-2 mb-4">
            <div class="h-2 flex-1 rounded-full bg-slate-200"></div>
            <div class="h-2 flex-1 rounded-full bg-slate-200"></div>
            <div class="h-2 flex-1 rounded-full bg-slate-200"></div>
          </div>
          
          <p class="text-slate-500 font-medium text-sm">Please complete profile: <b class="text-[#1A2B49]">0/3</b></p>
        </div>
        
        <div class="flex items-center gap-8 lg:w-1/2">
          <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex flex-col items-center justify-center shrink-0 shadow-inner">
            <Trophy :size="32" stroke-width="2.5" />
          </div>
          <div class="flex flex-col gap-3">
            <div class="flex items-center gap-2 text-slate-500 text-sm font-medium hover:text-[#44A6D9] cursor-pointer transition-colors">
              <AlertCircle :size="16" class="text-amber-500" /> Set Your Profile Photo
            </div>
            <div class="flex items-center gap-2 text-slate-500 text-sm font-medium hover:text-[#44A6D9] cursor-pointer transition-colors">
              <AlertCircle :size="16" class="text-amber-500" /> Set Your Bio
            </div>
            <div class="flex items-center gap-2 text-slate-500 text-sm font-medium hover:text-[#44A6D9] cursor-pointer transition-colors">
              <AlertCircle :size="16" class="text-amber-500" /> Configure Settings
            </div>
          </div>
        </div>
      </div>

      <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-6">Dashboard</h3>

      <!-- Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <!-- Metric 1: Registered Classes -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 flex flex-col items-center justify-center text-center transition-transform duration-300 hover:-translate-y-1">
          <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
            <BookOpen :size="28" stroke-width="2.5" />
          </div>
          <h3 class="text-4xl font-extrabold text-[#1A2B49] mb-2">{{ metrics.enrolled_count }}</h3>
          <p class="text-slate-500 text-sm font-medium">Kelas Terdaftar</p>
        </div>

        <!-- Metric 2: Completed Quizzes -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 flex flex-col items-center justify-center text-center transition-transform duration-300 hover:-translate-y-1">
          <div class="w-16 h-16 rounded-full bg-sky-50 text-sky-600 flex items-center justify-center mb-4">
            <Award :size="28" stroke-width="2.5" />
          </div>
          <h3 class="text-4xl font-extrabold text-[#1A2B49] mb-2">{{ metrics.completed_quizzes }}</h3>
          <p class="text-slate-500 text-sm font-medium">Kuis Dikerjakan</p>
        </div>

        <!-- Metric 3: Quizzes Passed -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 flex flex-col items-center justify-center text-center transition-transform duration-300 hover:-translate-y-1">
          <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4">
            <CheckCircle :size="28" stroke-width="2.5" />
          </div>
          <h3 class="text-4xl font-extrabold text-[#1A2B49] mb-2">{{ metrics.passed_quizzes }}</h3>
          <p class="text-slate-500 text-sm font-medium">Kuis Lulus (SKM &ge; 75)</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Enrolled Courses -->
        <div class="lg:col-span-2 bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
            <Play :size="20" class="text-indigo-600" /> Kelas Anda Sedang Berjalan
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div 
              v-for="course in enrolledCourses" 
              :key="course.id"
              class="rounded-[2rem] border border-slate-100 p-6 bg-slate-50/50 flex flex-col justify-between hover:bg-white hover:border-slate-200 hover:shadow-sm transition-all duration-300 group"
            >
              <div>
                <div class="flex items-center justify-between mb-5">
                  <span class="bg-[#F9CC6B] text-[#1A2B49] text-[10px] font-extrabold px-3 py-1.5 rounded-lg uppercase tracking-wider">
                    Kelas {{ course.level }}
                  </span>
                  <span class="text-slate-400 text-xs font-bold">{{ course.lessons_count }} Sesi</span>
                </div>
                
                <h4 class="font-extrabold text-[#1A2B49] text-base leading-tight mb-2 min-h-[2.5rem] line-clamp-2 group-hover:text-[#264790] transition-colors">
                  {{ course.title }}
                </h4>
                <p class="text-slate-400 text-xs font-medium mb-6">Oleh: <span class="font-bold text-slate-500">{{ course.instructor_name }}</span></p>
              </div>

              <div>
                <!-- Progress Bar -->
                <div class="mb-5">
                  <div class="flex items-center justify-between text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">
                     <span>Progres Belajar</span>
                     <span class="text-[#264790]">{{ getProgress(course) }}%</span>
                  </div>
                  <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                     <div class="h-full bg-gradient-to-r from-[#264790] to-[#44A6D9] rounded-full transition-all duration-1000 ease-out" :style="{ width: getProgress(course) + '%' }"></div>
                  </div>
                </div>

                <Link 
                  :href="`/courses/${course.slug}/learn`"
                  class="w-full bg-[#F4F7F9] hover:bg-[#264790] text-[#264790] hover:text-white border border-transparent py-3 rounded-2xl font-bold text-sm transition-colors flex items-center justify-center gap-1.5"
                >
                  Lanjutkan <ArrowRight :size="16" />
                </Link>
              </div>
            </div>

            <div v-if="enrolledCourses.length === 0" class="col-span-2 text-center py-12">
              <div class="w-20 h-20 bg-slate-100 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-4">
                <BookOpen :size="32" />
              </div>
              <p class="text-slate-400 font-bold mb-6">Anda belum mendaftar di kelas manapun.</p>
              <Link 
                href="/courses"
                class="bg-[#264790] hover:bg-[#44A6D9] text-white px-8 py-3 rounded-full font-bold text-sm shadow-md transition-colors inline-flex items-center gap-2"
              >
                Cari Kelas Sekarang <ArrowRight :size="16" />
              </Link>
            </div>
          </div>
        </div>

        <!-- Order & Invoice History -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col justify-between">
          <div>
            <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
              <CreditCard :size="20" class="text-indigo-600" /> Riwayat Pembelian
            </h3>
            
            <div class="flex flex-col gap-4">
              <div v-for="order in orders" :key="order.id" class="p-5 rounded-2xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                <div class="flex items-center justify-between mb-3">
                  <span class="font-extrabold text-[10px] text-slate-400 uppercase tracking-widest">INV-#{{ order.id }}</span>
                  <span 
                    :class="{
                      'bg-emerald-50 text-emerald-700 border-emerald-100': order.status === 'completed',
                      'bg-amber-50 text-amber-700 border-amber-100': order.status === 'pending',
                      'bg-rose-50 text-rose-700 border-rose-100': order.status === 'failed'
                    }"
                    class="px-2.5 py-1 text-[9px] font-extrabold rounded-full border uppercase tracking-wider"
                  >
                    {{ order.status }}
                  </span>
                </div>
                <h4 class="font-bold text-sm text-[#1A2B49] mb-2 line-clamp-2 leading-tight">
                  {{ order.buyable?.title || 'LMS Item' }}
                </h4>
                <div class="flex items-center justify-between mt-4">
                  <span class="font-extrabold text-base text-[#1A2B49]">Rp {{ parseFloat(order.amount).toLocaleString('id-ID') }}</span>
                  
                  <button 
                    v-if="order.status === 'pending' && order.snap_token"
                    @click="payOrder(order.snap_token, order.id)"
                    :disabled="isProcessing"
                    class="bg-[#264790] hover:bg-[#1A2B49] text-white px-4 py-2 rounded-xl font-bold text-xs shadow-sm transition-colors"
                  >
                    Bayar
                  </button>
                </div>
              </div>

              <div v-if="orders.length === 0" class="text-center py-10 text-slate-400 font-medium">
                Belum ada invoice pendaftaran.
              </div>
            </div>
          </div>
        </div>

      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>
