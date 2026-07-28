<script>
import GuestLayout from '@/Layouts/GuestLayout.vue';

export default {
  // Set Inertia persistent layout with spotlight mode active
  layout: (h, page) => h(GuestLayout, { spotlightMode: true }, () => page),
};
</script>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
  Clock, Award, AlertCircle, CheckCircle2, 
  ChevronLeft, ChevronRight, BookOpen, Presentation, Calendar, Check
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  course: Object,
  assessment: Object,
  existingAttempt: Object,
  pastAttempts: Array,
});

// Test progress state
const attempt = ref(props.existingAttempt);
const hasStarted = ref(!!props.existingAttempt);
const isSubmitted = ref(false);
const quizScore = ref(0);
const quizResults = ref([]);
const isSubmitting = ref(false);

// Active question navigation
const currentQuestionIndex = ref(0);
const selectedAnswers = ref({});

// Countdown timer state
const timeLeftFormatted = ref('00:00');
let timerInterval = null;

// Persisted Draft logic
const storageKey = computed(() => attempt.value ? `assessment_attempt_${attempt.value.id}` : null);
const loadPersistedAnswers = () => {
  if (storageKey.value) {
    const saved = localStorage.getItem(storageKey.value);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        selectedAnswers.value = { ...selectedAnswers.value, ...parsed };
      } catch (e) {
        console.error('Failed to parse saved answers', e);
      }
    }
  }
};

watch(selectedAnswers, (newVal) => {
  if (storageKey.value && !isSubmitted.value) {
    localStorage.setItem(storageKey.value, JSON.stringify(newVal));
  }
}, { deep: true });

// Multiple Tab Prevention
const channel = new BroadcastChannel('drastha_assessment_channel');
channel.onmessage = (event) => {
  if (event.data.type === 'START' && event.data.assessmentId === props.assessment.id) {
    if (hasStarted.value && !isSubmitted.value) {
      alert('Perhatian: Anda telah membuka kuis ini di tab lain. Untuk mencegah konflik, sesi di tab ini tidak dilanjutkan.');
      window.location.reload();
    }
  }
};

// Start the assessment session
const startAssessment = async () => {
  try {
    const res = await axios.post(route('assessments.start', props.assessment.id));
    if (res.data && res.data.attempt) {
      attempt.value = res.data.attempt;
      hasStarted.value = true;
      initTimer(res.data.attempt);
      loadPersistedAnswers();
      channel.postMessage({ type: 'START', assessmentId: props.assessment.id });
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal memulai tes. Silakan coba lagi.');
  }
};

// Initialize countdown timer
const initTimer = (attemptObj) => {
  if (!props.assessment.duration_minutes) return;

  const durationMs = props.assessment.duration_minutes * 60 * 1000;
  const startedAt = new Date(attemptObj.started_at).getTime();
  
  const updateTimer = () => {
    const now = new Date().getTime();
    const elapsed = now - startedAt;
    const remaining = durationMs - elapsed;

    if (remaining <= 0) {
      clearInterval(timerInterval);
      timeLeftFormatted.value = '00:00';
      autoSubmit();
    } else {
      const minutes = Math.floor(remaining / 60000);
      const seconds = Math.floor((remaining % 60000) / 1000);
      timeLeftFormatted.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
  };

  updateTimer();
  timerInterval = setInterval(updateTimer, 1000);
};

// Automatic submit when timer hits 0
const autoSubmit = () => {
  submitAnswers(true);
};

// Submit the test answers
const submitAnswers = async (isAuto = false) => {
  if (!attempt.value || isSubmitting.value) return;

  if (!isAuto && !confirm('Apakah Anda yakin ingin mengirim semua jawaban Anda sekarang?')) {
    return;
  }

  isSubmitting.value = true;
  clearInterval(timerInterval);

  let retries = 3;
  while (retries > 0) {
    try {
      const res = await axios.post(route('attempts.submit', attempt.value.id), {
        answers: selectedAnswers.value
      });

      if (res.data) {
        quizScore.value = res.data.score;
        quizResults.value = res.data.results;
        isSubmitted.value = true;
        
        // Cleanup storage on success
        if (storageKey.value) localStorage.removeItem(storageKey.value);
        
        isSubmitting.value = false;
        return; // Success, exit
      }
    } catch (err) {
      retries--;
      if (retries === 0) {
        alert(err.response?.data?.message || 'Koneksi terputus: Gagal mengirim jawaban setelah beberapa percobaan. Harap periksa jaringan Anda.');
        isSubmitting.value = false;
        
        // Re-init timer if we failed (unless it was auto-submit, then it stays at 0)
        if (!isAuto) {
          initTimer(attempt.value);
        }
        return;
      }
      // Wait 2 seconds before retrying
      await new Promise(r => setTimeout(r, 2000));
    }
  }
};

// Check if all questions are answered
const allQuestionsAnswered = computed(() => {
  if (!props.assessment.questions) return false;
  return props.assessment.questions.every(q => selectedAnswers.value[q.id] !== undefined && selectedAnswers.value[q.id] !== '');
});

// Setup on mount
onMounted(() => {
  // Prep answers structure
  if (props.assessment.questions) {
    props.assessment.questions.forEach(q => {
      selectedAnswers.value[q.id] = undefined;
    });
  }
  
  if (props.existingAttempt) {
    loadPersistedAnswers();
    initTimer(props.existingAttempt);
  }
});

onBeforeUnmount(() => {
  if (timerInterval) clearInterval(timerInterval);
  channel.close();
});

// Format timestamp Helper
const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', { 
    day: 'numeric', 
    month: 'short', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <Head :title="`${assessment.title} | ${course.title}`" />

  <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      
      <!-- Back to Course Breadcrumb -->
      <div class="mb-6 flex justify-between items-center">
        <Link 
          :href="route('courses.learn', course.slug)"
          class="inline-flex items-center gap-2 text-slate-400 hover:text-[#44A6D9] font-semibold text-xs sm:text-sm transition-colors"
        >
          &lsaquo; Kembali ke Halaman Kelas
        </Link>
      </div>

      <!-- Split Screen Workspace -->
      <div class="flex flex-col lg:flex-row gap-8 items-start relative">
        
        <!-- LEFT SIDEBAR (25% width) -->
        <div class="w-full lg:w-1/4 shrink-0 lg:sticky lg:top-8 flex flex-col gap-5">
          
          <!-- Syllabus navigation panel -->
          <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
              <div class="w-8 h-8 rounded-full bg-[#264790]/5 text-[#264790] flex items-center justify-center">
                <BookOpen :size="16" />
              </div>
              <span class="text-[#1A2B49] font-extrabold text-sm">Silabus Belajar</span>
            </div>

            <!-- Modules List -->
            <div class="flex flex-col gap-3 max-h-[250px] overflow-y-auto pr-1">
              <div 
                v-for="mod in course.modules" 
                :key="mod.id" 
                class="flex flex-col gap-1.5"
              >
                <span class="font-extrabold text-[11px] text-[#1A2B49] opacity-80 block truncate">{{ mod.title }}</span>
                <div class="flex flex-col gap-1 pl-2">
                  <div 
                    v-for="les in mod.lessons" 
                    :key="les.id"
                    class="flex items-center gap-2 text-[10px] text-slate-400 font-semibold truncate"
                  >
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-300 shrink-0"></div>
                    <span>{{ les.title }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Assessments List -->
            <div class="flex flex-col gap-2 pt-3 border-t border-slate-100">
              <span class="font-extrabold text-[11px] text-[#264790] uppercase tracking-wider">Instrumen Evaluasi</span>
              
              <!-- Pre-test Link / Status -->
              <div 
                :class="assessment.type === 'pre_test' ? 'bg-[#264790] text-white' : 'bg-slate-50 text-[#1A2B49]'"
                class="p-3 rounded-xl flex items-center justify-between font-bold text-xs shadow-sm transition-all"
              >
                <div class="flex items-center gap-2">
                  <Presentation :size="14" />
                  <span>Pre-test Evaluasi</span>
                </div>
                <CheckCircle2 v-if="assessment.type === 'pre_test' && pastAttempts.some(a => a.is_passed)" :size="14" class="text-emerald-400" />
              </div>

              <!-- Post-test Link / Status -->
              <div 
                :class="assessment.type === 'post_test' ? 'bg-[#264790] text-white' : 'bg-slate-50 text-[#1A2B49]'"
                class="p-3 rounded-xl flex items-center justify-between font-bold text-xs shadow-sm transition-all"
              >
                <div class="flex items-center gap-2">
                  <Award :size="14" />
                  <span>Post-test Kelulusan</span>
                </div>
                <CheckCircle2 v-if="assessment.type === 'post_test' && pastAttempts.some(a => a.is_passed)" :size="14" class="text-emerald-400" />
              </div>
            </div>
          </div>

          <!-- Live Event Card -->
          <div class="bg-gradient-to-br from-[#1A2B49] to-[#264790] p-5 rounded-2xl text-white shadow-md border border-[#44A6D9]/10">
            <span class="bg-amber-500 text-amber-950 text-[8px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider inline-block mb-3">Live Class Event</span>
            <h4 class="font-extrabold text-xs leading-snug mb-2">{{ course.title }}</h4>
            <div class="flex flex-col gap-1 opacity-90 text-[10px] font-medium">
              <div class="flex items-center gap-1.5">
                <Calendar :size="12" class="text-[#44A6D9]" />
                <span>{{ formatDate(course.start_date) }}</span>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT MAIN CONTENT (75% width) -->
        <div class="w-full lg:w-3/4 flex flex-col gap-6">

          <!-- 1. NOT STARTED INTRODUCTORY CARD -->
          <div v-if="!hasStarted && !isSubmitted" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-md flex flex-col gap-6">
            <div class="border-b border-slate-100 pb-5">
              <span class="text-[10px] bg-[#264790]/10 text-[#264790] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">
                {{ assessment.type === 'pre_test' ? 'Pre-Test' : 'Post-Test' }}
              </span>
              <h1 class="text-2xl font-extrabold text-[#1A2B49] mt-3 leading-snug">{{ assessment.title }}</h1>
              <p class="text-slate-400 text-xs sm:text-sm font-semibold mt-1">Evaluasi kelas live untuk menguji pemahaman materi Anda.</p>
            </div>

            <!-- Parameters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/50 flex flex-col gap-1">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Batas Waktu</span>
                <span class="font-extrabold text-sm text-[#1A2B49] flex items-center gap-1.5">
                  <Clock :size="16" class="text-[#264790]" />
                  {{ assessment.duration_minutes ? `${assessment.duration_minutes} Menit` : 'Tanpa Batas' }}
                </span>
              </div>
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/50 flex flex-col gap-1">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">KKM Kelulusan</span>
                <span class="font-extrabold text-sm text-[#1A2B49] flex items-center gap-1.5">
                  <Award :size="16" class="text-amber-500" />
                  {{ assessment.passing_score }} / 100
                </span>
              </div>
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/50 flex flex-col gap-1">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Maksimal Percobaan</span>
                <span class="font-extrabold text-sm text-[#1A2B49] flex items-center gap-1.5">
                  <AlertCircle :size="16" class="text-rose-500" />
                  {{ assessment.max_attempts > 0 ? `${assessment.max_attempts} Kali` : 'Tanpa Batas' }}
                </span>
              </div>
            </div>

            <div class="bg-amber-50 border border-amber-100 p-4 rounded-2xl flex gap-3 text-amber-700 text-xs font-semibold leading-relaxed">
              <AlertCircle :size="18" class="text-amber-500 shrink-0" />
              <p>Perhatian: Sesi pengerjaan akan dimulai setelah Anda menekan tombol "Mulai Pengerjaan Tes". Pastikan koneksi internet Anda stabil selama tes berlangsung.</p>
            </div>

            <!-- Attempts History Table if available -->
            <div v-if="pastAttempts && pastAttempts.length > 0" class="flex flex-col gap-3">
              <span class="font-extrabold text-xs text-[#1A2B49] uppercase tracking-wider">Riwayat Percobaan</span>
              <div class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                <table class="w-full text-left text-xs">
                  <thead class="bg-slate-50 font-extrabold text-slate-500 border-b border-slate-100">
                    <tr>
                      <th class="p-3.5">Ke</th>
                      <th class="p-3.5">Nilai</th>
                      <th class="p-3.5">Status</th>
                      <th class="p-3.5">Tanggal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                    <tr v-for="att in pastAttempts" :key="att.id">
                      <td class="p-3.5">Percobaan #{{ att.attempt_number }}</td>
                      <td class="p-3.5 text-[#1A2B49]">{{ att.total_score }} / 100</td>
                      <td class="p-3.5">
                        <span 
                          :class="att.is_passed ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                          class="px-2 py-0.5 rounded-full text-[9px] uppercase tracking-wider font-extrabold"
                        >
                          {{ att.is_passed ? 'Lulus' : 'Gagal' }}
                        </span>
                      </td>
                      <td class="p-3.5 text-slate-400">{{ formatDate(att.completed_at) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Start Action Button -->
            <button 
              @click="startAssessment"
              data-testid="start-pretest-btn"
              class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-sm shadow-md transition-colors cursor-pointer text-center"
            >
              Mulai Pengerjaan Tes
            </button>
          </div>

          <!-- 2. TEST EXECUTION INTERFACE (Modern Card-Based Design) -->
          <div v-else-if="hasStarted && !isSubmitted" class="flex flex-col gap-6">
            
            <!-- Header Card with Title & Countdown Timer -->
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-md flex items-center justify-between">
              <div>
                <h2 class="text-lg font-extrabold text-[#1A2B49]">{{ assessment.title }}</h2>
                <span class="text-[10px] text-slate-400 font-bold">Menjawab Soal {{ currentQuestionIndex + 1 }} dari {{ assessment.questions?.length }}</span>
              </div>
              <div 
                v-if="assessment.duration_minutes" 
                class="px-4 py-2 bg-rose-50 text-rose-600 border border-rose-100 text-xs font-black rounded-2xl flex items-center gap-2 select-none"
              >
                <Clock :size="15" /> 
                <span class="font-mono tracking-wider">{{ timeLeftFormatted }}</span>
              </div>
            </div>

            <!-- Question & Answers with Fade Transition -->
            <Transition name="fade" mode="out-in">
              <div :key="currentQuestionIndex" class="flex flex-col gap-6">
                
                <!-- Main Question Area enclosed in an elevated card -->
                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-100 shadow-md">
                  <div class="text-[#1A2B49] font-extrabold text-sm sm:text-base leading-relaxed flex gap-2">
                    <span class="text-[#264790]">{{ currentQuestionIndex + 1 }}.</span>
                    <p class="flex-1 whitespace-pre-wrap">{{ assessment.questions[currentQuestionIndex]?.question_text }}</p>
                  </div>
                </div>

                <!-- 4 Stacked Clickable Option Cards -->
                <div class="flex flex-col gap-3">
                  <label 
                    v-for="(opt, idx) in assessment.questions[currentQuestionIndex]?.options" 
                    :key="idx"
                    :class="selectedAnswers[assessment.questions[currentQuestionIndex].id] === idx 
                      ? 'border-blue-500 bg-blue-50 text-blue-900 shadow-md ring-2 ring-blue-500/20' 
                      : 'bg-white hover:bg-slate-50 border-slate-200 text-slate-700 hover:shadow-sm'"
                    class="flex items-center gap-3 p-4.5 rounded-2xl border-2 cursor-pointer transition-all duration-200 select-none shadow-sm"
                  >
                    <input 
                      type="radio" 
                      :value="idx" 
                      v-model="selectedAnswers[assessment.questions[currentQuestionIndex].id]" 
                      class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer" 
                    />
                    <span class="font-bold">{{ opt }}</span>
                  </label>
                </div>

              </div>
            </Transition>

            <!-- Navigation Control Footer Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-md flex justify-between items-center gap-4">
              <button 
                type="button"
                @click="currentQuestionIndex = Math.max(0, currentQuestionIndex - 1)"
                :disabled="currentQuestionIndex === 0"
                class="px-5 py-3 bg-white hover:bg-slate-100 border border-slate-200 disabled:opacity-30 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer disabled:cursor-not-allowed shadow-sm text-slate-700"
              >
                <ChevronLeft :size="14" /> Sebelumnya
              </button>

              <div class="flex items-center gap-2.5">
                <button 
                  v-if="currentQuestionIndex < assessment.questions.length - 1"
                  type="button"
                  @click="currentQuestionIndex++"
                  class="px-6 py-3 bg-[#264790] text-white hover:bg-[#44A6D9] rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer shadow-sm"
                >
                  Berikutnya <ChevronRight :size="14" />
                </button>
                
                <button 
                  v-else
                  type="button"
                  @click="submitAnswers(false)"
                  data-testid="submit-pretest-btn"
                  :disabled="isSubmitting"
                  class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all cursor-pointer shadow-md flex items-center gap-1.5"
                >
                  <Check :size="14" /> Submit Test
                </button>
              </div>
            </div>

          </div>

          <!-- 3. SCORE RESULT CARD & ANSWERS REVIEW -->
          <div v-else-if="isSubmitted" class="flex flex-col gap-6">
            
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-md flex flex-col items-center justify-center text-center gap-5">
              <span class="text-xs font-extrabold text-slate-400 uppercase tracking-widest">Hasil Evaluasi Pengerjaan</span>
              
              <!-- Circular Progress Score -->
              <div class="relative flex items-center justify-center animate-bounce-slow">
                <div 
                  :class="quizScore >= assessment.passing_score ? 'text-emerald-500 bg-emerald-50 border-emerald-200' : 'text-rose-500 bg-rose-50 border-rose-200'"
                  class="w-36 h-36 rounded-full border-4 flex flex-col items-center justify-center shadow-sm"
                >
                  <span class="text-4xl font-black leading-none mb-1">{{ quizScore }}</span>
                  <span class="text-[9px] font-bold text-slate-400">SKOR AKHIR</span>
                </div>
              </div>

              <div>
                <h2 v-if="quizScore >= assessment.passing_score" class="text-xl font-extrabold text-emerald-600">Selamat, Anda Lulus Evaluasi!</h2>
                <h2 v-else class="text-xl font-extrabold text-rose-500">Nilai Belum Mencukupi Kelulusan</h2>
                <p class="text-slate-400 text-xs sm:text-sm font-semibold max-w-sm mt-1">
                  {{ quizScore >= assessment.passing_score 
                    ? 'Hasil ini telah tersimpan dalam rekam progress belajar Anda. Silakan lanjutkan belajar Anda.' 
                    : `Nilai minimum kelulusan yang dipersyaratkan adalah ${assessment.passing_score}. Silakan pelajari kembali materi dan coba lagi.` 
                  }}
                </p>
              </div>

              <!-- Retake or Return buttons -->
              <div class="flex flex-col sm:flex-row gap-3 w-full max-w-sm mt-3">
                <button 
                  v-if="quizScore < assessment.passing_score && (assessment.max_attempts === 0 || pastAttempts.length + 1 < assessment.max_attempts)"
                  @click="
                    hasStarted.value = false;
                    isSubmitted.value = false;
                    router.reload();
                  "
                  class="flex-1 bg-white hover:bg-slate-50 border border-slate-200 text-[#1A2B49] py-3 rounded-xl font-extrabold text-xs shadow-sm transition-colors cursor-pointer"
                >
                  Coba Ulangi Tes
                </button>
                <Link 
                  :href="route('courses.learn', course.slug)"
                  class="flex-1 bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-xl font-extrabold text-xs shadow-sm transition-colors text-center block cursor-pointer"
                >
                  Kembali ke Kelas
                </Link>
              </div>
            </div>

            <!-- Detailed Answers Review Summary -->
            <div class="flex flex-col gap-4">
              <h3 class="text-xs font-extrabold text-[#1A2B49] uppercase tracking-wider pl-1">Koreksi Lembar Jawaban</h3>
              
              <div 
                v-for="(res, idx) in quizResults" 
                :key="idx"
                :class="res.is_correct ? 'border-emerald-200 bg-emerald-50/10' : 'border-rose-200 bg-rose-50/10'"
                class="p-5 rounded-2xl border bg-white flex flex-col gap-3 shadow-md"
              >
                <div class="flex justify-between items-start gap-4">
                  <span class="text-xs sm:text-sm font-extrabold text-[#1A2B49] leading-snug">{{ idx + 1 }}. {{ res.question_text }}</span>
                  <span 
                    :class="res.is_correct ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'"
                    class="px-2.5 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider shrink-0"
                  >
                    {{ res.is_correct ? 'Benar' : 'Salah' }}
                  </span>
                </div>
                
                <div class="text-[11px] font-bold flex flex-col gap-1 text-slate-500 border-t border-slate-100/50 pt-2.5">
                  <div>Jawaban Anda: <span :class="res.is_correct ? 'text-emerald-600' : 'text-rose-600'">{{ res.student_answer !== null && res.student_answer !== undefined && res.student_answer !== '' ? res.student_answer : '(Tidak Diisi)' }}</span></div>
                </div>
              </div>
            </div>

          </div>

        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }

.animate-bounce-slow {
  animation: bounce 3s infinite;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(-4%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}

/* Fade Transition Effects */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(4px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
