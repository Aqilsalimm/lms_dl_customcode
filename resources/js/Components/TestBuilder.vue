<script setup>
import { ref, watch } from 'vue';
import { Plus, Trash2, Save, HelpCircle, AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  courseId: {
    type: [Number, String],
    required: true
  },
  moduleId: {
    type: [Number, String],
    default: null
  },
  initialPreTest: {
    type: Object,
    default: () => null
  },
  initialPostTest: {
    type: Object,
    default: () => null
  }
});

const activeTab = ref('pre_test'); // 'pre_test' or 'post_test'
const isSaving = ref(false);

// Helper to initialize assessment state
const initAssessmentForm = (data, defaultTitle) => {
  return {
    id: data?.id || null,
    title: data?.title || defaultTitle,
    description: data?.description || '',
    duration_minutes: data?.duration_minutes || 30,
    passing_score: data?.passing_score || 60,
    max_attempts: data?.max_attempts !== undefined ? data.max_attempts : 1,
    questions: data?.questions?.map(q => ({
      id: q.id || null,
      question_text: q.question_text || '',
      options: Array.isArray(q.options) ? [...q.options] : ['', '', '', ''],
      correct_answer: q.correct_answer !== undefined ? String(q.correct_answer) : '0',
      points: q.points !== undefined && q.points !== null ? Number(q.points) : 10
    })) || []
  };
};

// Form states
const preTestForm = ref(initAssessmentForm(props.initialPreTest, 'Workshop Pre-Test'));
const postTestForm = ref(initAssessmentForm(props.initialPostTest, 'Workshop Post-Test'));

// Sync forms when props change
watch(() => props.initialPreTest, (newVal) => {
  preTestForm.value = initAssessmentForm(newVal, 'Workshop Pre-Test');
}, { deep: true });

watch(() => props.initialPostTest, (newVal) => {
  postTestForm.value = initAssessmentForm(newVal, 'Workshop Post-Test');
}, { deep: true });

// Get the active form based on selected tab
const getActiveForm = () => {
  return activeTab.value === 'pre_test' ? preTestForm.value : postTestForm.value;
};

// Add a new question to the active test
const addQuestion = () => {
  const form = getActiveForm();
  form.questions.push({
    id: null,
    question_text: '',
    options: ['', '', '', ''],
    correct_answer: '0',
    points: 10
  });
};

// Remove a question
const removeQuestion = (index) => {
  const form = getActiveForm();
  form.questions.splice(index, 1);
};

// Submit/Save the active test configuration and its questions
const saveConfiguration = () => {
  // 1. Client-side validations for Pre-test
  if (!preTestForm.value.title.trim()) {
    alert('Judul Pre-test tidak boleh kosong.');
    return;
  }
  for (let i = 0; i < preTestForm.value.questions.length; i++) {
    const q = preTestForm.value.questions[i];
    if (!q.question_text.trim()) {
      alert(`Pertanyaan ke-${i + 1} pada Pre-test tidak boleh kosong.`);
      return;
    }
    for (let j = 0; j < q.options.length; j++) {
      if (!q.options[j].trim()) {
        alert(`Opsi ke-${j + 1} pada Pertanyaan ke-${i + 1} di Pre-test tidak boleh kosong.`);
        return;
      }
    }
  }
  
  // 2. Client-side validations for Post-test
  if (!postTestForm.value.title.trim()) {
    alert('Judul Post-test tidak boleh kosong.');
    return;
  }
  for (let i = 0; i < postTestForm.value.questions.length; i++) {
    const q = postTestForm.value.questions[i];
    if (!q.question_text.trim()) {
      alert(`Pertanyaan ke-${i + 1} pada Post-test tidak boleh kosong.`);
      return;
    }
    for (let j = 0; j < q.options.length; j++) {
      if (!q.options[j].trim()) {
        alert(`Opsi ke-${j + 1} pada Pertanyaan ke-${i + 1} di Post-test tidak boleh kosong.`);
        return;
      }
    }
  }

  isSaving.value = true;
  
  router.post(route('course-builder.assessments.bulk-store', props.courseId), {
    module_id: props.moduleId,
    assessments: [
      {
        type: 'pre_test',
        module_id: props.moduleId,
        title: preTestForm.value.title,
        description: preTestForm.value.description,
        duration_minutes: preTestForm.value.duration_minutes,
        passing_score: preTestForm.value.passing_score,
        max_attempts: preTestForm.value.max_attempts,
        questions: preTestForm.value.questions
      },
      {
        type: 'post_test',
        module_id: props.moduleId,
        title: postTestForm.value.title,
        description: postTestForm.value.description,
        duration_minutes: postTestForm.value.duration_minutes,
        passing_score: postTestForm.value.passing_score,
        max_attempts: postTestForm.value.max_attempts,
        questions: postTestForm.value.questions
      }
    ]
  }, {
    onSuccess: () => {
      alert('Konfigurasi Pre-test & Post-test berhasil disimpan!');
    },
    onError: (errors) => {
      alert('Gagal menyimpan evaluasi. Silakan periksa kembali formulir Anda.');
      console.error(errors);
    },
    onFinish: () => {
      isSaving.value = false;
    }
  });
};
</script>

<template>
  <div class="mt-6 pt-6 border-t border-slate-100 flex flex-col gap-5">
    <div class="flex flex-col gap-6">

      <!-- 1. Sleek Tab Navigation Toggle -->
      <div class="bg-white p-1.5 rounded-2xl border border-slate-100 shadow-sm flex gap-2">
        <button
          type="button"
          @click="activeTab = 'pre_test'"
          :class="activeTab === 'pre_test' ? 'bg-[#264790] text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 bg-transparent'"
          class="flex-1 py-3 px-4 rounded-xl font-extrabold text-xs sm:text-sm transition-all text-center cursor-pointer"
        >
          Pre-Test Setup
        </button>
        <button
          type="button"
          @click="activeTab = 'post_test'"
          :class="activeTab === 'post_test' ? 'bg-[#264790] text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 bg-transparent'"
          class="flex-1 py-3 px-4 rounded-xl font-extrabold text-xs sm:text-sm transition-all text-center cursor-pointer"
        >
          Post-Test Setup
        </button>
      </div>

      <!-- 2. Assessment Configuration Card -->
      <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-100 shadow-sm flex flex-col gap-6">
        <div>
          <h2 class="text-base sm:text-lg font-black text-[#1A2B49] flex items-center gap-2">
            <span class="w-2.5 h-6 rounded bg-[#44A6D9]"></span>
            Konfigurasi {{ activeTab === 'pre_test' ? 'Pre-Test' : 'Post-Test' }}
          </h2>
          <p class="text-slate-400 text-[11px] sm:text-xs font-semibold mt-1">Konfigurasikan judul, deskripsi pengerjaan, KKM, serta durasi pengerjaan evaluasi.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <!-- Title Input -->
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Judul Evaluasi</label>
            <input
              type="text"
              v-model="getActiveForm().title"
              placeholder="Masukkan judul evaluasi..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
            />
          </div>

          <!-- Description Input -->
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Petunjuk Pengerjaan</label>
            <textarea
              v-model="getActiveForm().description"
              rows="3"
              placeholder="Tulis petunjuk pengerjaan bagi peserta..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all resize-none"
            ></textarea>
          </div>

          <!-- Duration Input -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Durasi (Menit)</label>
            <input
              type="number"
              v-model.number="getActiveForm().duration_minutes"
              min="1"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
            />
          </div>

          <!-- Passing Score Input -->
          <div class="flex flex-col gap-1.5">
            <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Passing Score / KKM (0 - 100)</label>
            <input
              type="number"
              v-model.number="getActiveForm().passing_score"
              min="0"
              max="100"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
            />
          </div>

          <!-- Max Attempts Input -->
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Batas Maksimal Percobaan (0 = Unlimited)</label>
            <input
              type="number"
              v-model.number="getActiveForm().max_attempts"
              min="0"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
            />
          </div>
        </div>
      </div>

      <!-- 3. Question Builder Section -->
      <div class="flex flex-col gap-4">
        <div class="flex justify-between items-center px-1">
          <div>
            <h3 class="text-sm sm:text-base font-black text-[#1A2B49] uppercase tracking-wider">Bank Pertanyaan</h3>
            <p class="text-slate-400 text-[10px] sm:text-[11px] font-semibold mt-0.5">Kelola butir-butir soal pilihan ganda di bawah ini.</p>
          </div>
          <span class="text-xs bg-slate-100 text-[#1A2B49] px-3 py-1 rounded-full font-bold">
            Total: {{ getActiveForm().questions.length }} Soal
          </span>
        </div>

        <!-- Render Questions List -->
        <div class="flex flex-col gap-5">
          <div
            v-for="(question, qIndex) in getActiveForm().questions"
            :key="qIndex"
            class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-100 shadow-sm relative flex flex-col gap-4"
          >
            <!-- Delete Question Icon Button -->
            <button
              type="button"
              @click="removeQuestion(qIndex)"
              class="absolute top-5 right-5 text-slate-400 hover:text-rose-500 transition-colors p-1.5 hover:bg-rose-50 rounded-xl cursor-pointer"
              title="Hapus Pertanyaan"
            >
              <Trash2 :size="16" />
            </button>

            <!-- Question Header & Point Input -->
            <div class="flex flex-wrap items-center justify-between gap-3 pr-8">
              <span class="text-xs font-black text-[#264790]">PERTANYAAN #{{ qIndex + 1 }}</span>
              <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl">
                <label class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Bobot Nilai:</label>
                <input
                  type="number"
                  min="1"
                  v-model.number="question.points"
                  class="w-16 bg-white border border-slate-200 rounded-lg px-2 py-0.5 text-xs text-[#1A2B49] font-black text-center focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                />
                <span class="text-[10px] font-extrabold text-slate-400">Poin</span>
              </div>
            </div>

            <!-- Question Textarea -->
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Pertanyaan</label>
              <textarea
                v-model="question.question_text"
                rows="2"
                placeholder="Tuliskan teks pertanyaan..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all resize-none"
              ></textarea>
            </div>

            <!-- Stacked Options inputs with Radio buttons -->
            <div class="flex flex-col gap-3">
              <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Opsi Jawaban & Jawaban Benar</label>
              <div class="grid grid-cols-1 gap-3.5">
                <div
                  v-for="(option, optIdx) in question.options"
                  :key="optIdx"
                  class="flex items-center gap-3"
                >
                  <!-- Radio Button to mark correct answer -->
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input
                      type="radio"
                      :name="`correct_${qIndex}`"
                      :value="String(optIdx)"
                      v-model="question.correct_answer"
                      class="w-4 h-4 text-blue-600 focus:ring-blue-500 cursor-pointer"
                    />
                    <span class="text-[10px] font-black text-slate-400 w-5 text-center">{{ String.fromCharCode(65 + optIdx) }}</span>
                  </label>
                  
                  <!-- Option text input -->
                  <input
                    type="text"
                    v-model="question.options[optIdx]"
                    :placeholder="`Masukkan opsi jawaban ${String.fromCharCode(65 + optIdx)}...`"
                    class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Prominent Add New Question Button -->
        <button
          type="button"
          @click="addQuestion"
          class="w-full bg-white hover:bg-slate-50 border-2 border-dashed border-slate-300 hover:border-blue-500 py-5 rounded-2xl font-extrabold text-xs sm:text-sm transition-all text-center flex items-center justify-center gap-2 cursor-pointer text-slate-500 hover:text-blue-600 shadow-sm"
        >
          <Plus :size="16" /> + Add New Question
        </button>
      </div>

      <!-- 4. Global Action Buttons -->
      <div class="flex justify-end gap-3 mt-4">
        <button
          type="button"
          @click="saveConfiguration"
          :disabled="isSaving"
          class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center gap-2 cursor-pointer disabled:cursor-not-allowed"
        >
          <Save :size="16" />
          <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Konfigurasi Ujian' }}</span>
        </button>
      </div>

    </div>
  </div>
</template>
