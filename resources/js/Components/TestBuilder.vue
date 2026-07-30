<script setup>
import { ref, watch, computed } from 'vue';
import { Plus, Trash2, Save, HelpCircle, AlertCircle, CheckCircle2, Sliders, Globe, Lock, Unlock, Settings2, Image, UploadCloud, X, Calculator } from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';

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

const page = usePage();

// Computed global LMS test builder settings from page props
const globalSettings = computed(() => {
  return {
    default_duration: Number(page.props.settings?.test_builder_default_duration || 30),
    pre_passing_score: Number(page.props.settings?.test_builder_pre_passing_score || 70),
    post_passing_score: Number(page.props.settings?.test_builder_post_passing_score || 70),
    default_max_attempts: Number(page.props.settings?.test_builder_default_max_attempts || 3),
  };
});

const activeTab = ref('pre_test'); // 'pre_test' or 'post_test'
const isSaving = ref(false);

// Helper to initialize assessment state
const initAssessmentForm = (data, defaultTitle, defaultType) => {
  const useGlobal = data?.use_global_settings !== undefined ? Boolean(data.use_global_settings) : true;
  const defaultPassing = defaultType === 'pre_test' ? globalSettings.value.pre_passing_score : globalSettings.value.post_passing_score;

  return {
    id: data?.id || null,
    title: data?.title || defaultTitle,
    description: data?.description || '',
    use_global_settings: useGlobal,
    duration_minutes: data?.duration_minutes !== undefined && data?.duration_minutes !== null ? Number(data.duration_minutes) : globalSettings.value.default_duration,
    passing_score: data?.passing_score !== undefined && data?.passing_score !== null ? Number(data.passing_score) : defaultPassing,
    max_attempts: data?.max_attempts !== undefined && data?.max_attempts !== null ? Number(data.max_attempts) : globalSettings.value.default_max_attempts,
    questions: data?.questions?.map(q => ({
      id: q.id || null,
      question_text: q.question_text || '',
      image_url: q.image_url || null,
      options: Array.isArray(q.options) ? [...q.options] : ['', '', '', ''],
      correct_answer: q.correct_answer !== undefined ? String(q.correct_answer) : '0',
      points: q.points !== undefined && q.points !== null ? Number(q.points) : 10
    })) || []
  };
};

// Form states
const preTestForm = ref(initAssessmentForm(props.initialPreTest, 'Workshop Pre-Test', 'pre_test'));
const postTestForm = ref(initAssessmentForm(props.initialPostTest, 'Workshop Post-Test', 'post_test'));

// Sync forms when props change
watch(() => props.initialPreTest, (newVal) => {
  preTestForm.value = initAssessmentForm(newVal, 'Workshop Pre-Test', 'pre_test');
}, { deep: true });

watch(() => props.initialPostTest, (newVal) => {
  postTestForm.value = initAssessmentForm(newVal, 'Workshop Post-Test', 'post_test');
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
    image_url: null,
    options: ['', '', '', ''],
    correct_answer: '0',
    points: 10
  });
};

// Handle Question Image Upload with strict 500KB client-side validation
const handleQuestionImageUpload = (event, question) => {
  const file = event.target.files[0];
  if (!file) return;

  const maxSizeBytes = 500 * 1024; // 500 KB
  if (file.size > maxSizeBytes) {
    alert(`Ukuran gambar melebihi batas maksimal 500 KB! (Ukuran berkas Anda: ${(file.size / 1024).toFixed(1)} KB)`);
    event.target.value = '';
    return;
  }

  if (!file.type.startsWith('image/')) {
    alert('Berkas yang diunggah harus berupa gambar (JPG, PNG, WebP, GIF).');
    event.target.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = (e) => {
    question.image_url = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeQuestionImage = (question) => {
  question.image_url = null;
};

// Math Formula Snippet Insertion
const insertMathFormula = (question, formulaPattern) => {
  question.question_text = (question.question_text || '') + (question.question_text ? ' ' : '') + formulaPattern;
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
        use_global_settings: preTestForm.value.use_global_settings,
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
        use_global_settings: postTestForm.value.use_global_settings,
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
          class="flex-1 py-3 px-4 rounded-xl font-extrabold text-xs sm:text-sm transition-all text-center cursor-pointer flex items-center justify-center gap-2"
        >
          <span>Pre-Test Setup</span>
          <span v-if="preTestForm.use_global_settings" class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full font-bold">Global</span>
          <span v-else class="text-[9px] bg-amber-400 text-slate-900 px-2 py-0.5 rounded-full font-black">Kustom</span>
        </button>
        <button
          type="button"
          @click="activeTab = 'post_test'"
          :class="activeTab === 'post_test' ? 'bg-[#264790] text-white shadow-sm' : 'text-slate-500 hover:text-slate-800 bg-transparent'"
          class="flex-1 py-3 px-4 rounded-xl font-extrabold text-xs sm:text-sm transition-all text-center cursor-pointer flex items-center justify-center gap-2"
        >
          <span>Post-Test Setup</span>
          <span v-if="postTestForm.use_global_settings" class="text-[9px] bg-white/20 px-2 py-0.5 rounded-full font-bold">Global</span>
          <span v-else class="text-[9px] bg-amber-400 text-slate-900 px-2 py-0.5 rounded-full font-black">Kustom</span>
        </button>
      </div>

      <!-- 2. Assessment Configuration Card -->
      <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-100 shadow-sm flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4 border-b border-slate-100 pb-5">
          <div>
            <h2 class="text-base sm:text-lg font-black text-[#1A2B49] flex items-center gap-2">
              <span class="w-2.5 h-6 rounded bg-[#44A6D9]"></span>
              Konfigurasi {{ activeTab === 'pre_test' ? 'Pre-Test' : 'Post-Test' }}
            </h2>
            <p class="text-slate-400 text-[11px] sm:text-xs font-semibold mt-1">
              Atur parameter durasi, KKM, serta batas percobaan untuk evaluasi ini.
            </p>
          </div>

          <!-- TOGGLE SWITCH KONFIGURASI KUSTOM / GLOBAL -->
          <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-200/80 shadow-inner">
            <div class="flex flex-col text-right">
              <span class="text-xs font-black text-[#1A2B49]">Konfigurasi Kustom Test Builder</span>
              <span class="text-[10px] font-bold" :class="getActiveForm().use_global_settings ? 'text-blue-600' : 'text-amber-600'">
                {{ getActiveForm().use_global_settings ? 'OFF (Mengikuti Setting Global LMS)' : 'ON (Pengaturan Khusus Evaluasi Ini)' }}
              </span>
            </div>

            <!-- Sleek Modern Switch -->
            <button
              type="button"
              @click="getActiveForm().use_global_settings = !getActiveForm().use_global_settings"
              :class="!getActiveForm().use_global_settings ? 'bg-amber-500' : 'bg-slate-300'"
              class="relative inline-flex h-7 w-13 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
              title="Toggle Konfigurasi Kustom"
            >
              <span
                :class="!getActiveForm().use_global_settings ? 'translate-x-6 bg-white' : 'translate-x-0 bg-white'"
                class="pointer-events-none inline-block h-6 w-6 transform rounded-full shadow-md ring-0 transition duration-200 ease-in-out flex items-center justify-center"
              >
                <Sliders v-if="!getActiveForm().use_global_settings" :size="12" class="text-amber-600 font-bold" />
                <Globe v-else :size="12" class="text-slate-500" />
              </span>
            </button>
          </div>
        </div>

        <!-- CALLOUT BANNER STATUS KONFIGURASI GLOBAL / KUSTOM -->
        <div v-if="getActiveForm().use_global_settings" class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 flex items-start gap-3 text-blue-900">
          <div class="p-2 bg-blue-500 text-white rounded-xl shrink-0 mt-0.5">
            <Globe :size="18" />
          </div>
          <div class="flex-1 text-xs leading-relaxed">
            <span class="font-extrabold text-blue-950 block text-sm">🔒 Menggunakan Konfigurasi Global LMS Admin</span>
            <span>
              Evaluasi ini saat ini mengikuti parameter standar platform dari halaman **Admin LMS Settings**. 
              KKM: <strong class="text-blue-900 font-black">{{ activeTab === 'pre_test' ? globalSettings.pre_passing_score : globalSettings.post_passing_score }}%</strong> | 
              Durasi: <strong class="text-blue-900 font-black">{{ globalSettings.default_duration }} Menit</strong> | 
              Batas Percobaan: <strong class="text-blue-900 font-black">{{ globalSettings.default_max_attempts }}x</strong>.
              <br/>
              <em>Aktifkan toggle <strong>"Konfigurasi Kustom"</strong> di atas jika Anda ingin mengubah KKM & durasi khusus untuk silabus ini.</em>
            </span>
          </div>
        </div>

        <div v-else class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 flex items-start gap-3 text-amber-900">
          <div class="p-2 bg-amber-500 text-white rounded-xl shrink-0 mt-0.5">
            <Sliders :size="18" />
          </div>
          <div class="flex-1 text-xs leading-relaxed">
            <span class="font-extrabold text-amber-950 block text-sm">⚡ Konfigurasi Kustom Aktif</span>
            <span>
              Anda menggunakan pengaturan khusus untuk evaluasi ini. Nilai KKM, durasi, dan batas percobaan di bawah ini akan menggantikan (*override*) nilai global LMS.
            </span>
          </div>
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
            <div class="flex items-center justify-between">
              <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Durasi (Menit)</label>
              <span v-if="getActiveForm().use_global_settings" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Global: {{ globalSettings.default_duration }} Min</span>
            </div>
            <input
              type="number"
              v-model.number="getActiveForm().duration_minutes"
              :disabled="getActiveForm().use_global_settings"
              :placeholder="getActiveForm().use_global_settings ? String(globalSettings.default_duration) : 'Masukkan durasi...'"
              min="1"
              :class="getActiveForm().use_global_settings ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed select-none' : 'bg-slate-50 border-slate-200 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:bg-white'"
              class="w-full border rounded-xl px-4 py-3 text-xs sm:text-sm font-semibold focus:outline-none transition-all"
            />
          </div>

          <!-- Passing Score Input -->
          <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between">
              <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Passing Score / KKM (0 - 100)</label>
              <span v-if="getActiveForm().use_global_settings" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">
                Global: {{ activeTab === 'pre_test' ? globalSettings.pre_passing_score : globalSettings.post_passing_score }}%
              </span>
            </div>
            <input
              type="number"
              v-model.number="getActiveForm().passing_score"
              :disabled="getActiveForm().use_global_settings"
              :placeholder="getActiveForm().use_global_settings ? String(activeTab === 'pre_test' ? globalSettings.pre_passing_score : globalSettings.post_passing_score) : 'Masukkan KKM...'"
              min="0"
              max="100"
              :class="getActiveForm().use_global_settings ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed select-none' : 'bg-slate-50 border-slate-200 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:bg-white'"
              class="w-full border rounded-xl px-4 py-3 text-xs sm:text-sm font-semibold focus:outline-none transition-all"
            />
          </div>

          <!-- Max Attempts Input -->
          <div class="flex flex-col gap-1.5 sm:col-span-2">
            <div class="flex items-center justify-between">
              <label class="text-[11px] sm:text-xs font-extrabold text-[#264790] uppercase tracking-wider">Batas Maksimal Percobaan (0 = Unlimited)</label>
              <span v-if="getActiveForm().use_global_settings" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Global: {{ globalSettings.default_max_attempts }} Percobaan</span>
            </div>
            <input
              type="number"
              v-model.number="getActiveForm().max_attempts"
              :disabled="getActiveForm().use_global_settings"
              :placeholder="getActiveForm().use_global_settings ? String(globalSettings.default_max_attempts) : 'Masukkan batas percobaan...'"
              min="0"
              :class="getActiveForm().use_global_settings ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed select-none' : 'bg-slate-50 border-slate-200 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:bg-white'"
              class="w-full border rounded-xl px-4 py-3 text-xs sm:text-sm font-semibold focus:outline-none transition-all"
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

            <!-- Question Textarea & Math Snippet Toolbar -->
            <div class="flex flex-col gap-2">
              <div class="flex items-center justify-between">
                <label class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Pertanyaan Soal</label>
                <span class="text-[10px] text-slate-400 font-semibold">Dukung LaTeX & Rumus Matematika</span>
              </div>

              <!-- Math Formula Quick Insert Toolbar -->
              <div class="flex flex-wrap items-center gap-1.5 p-2 bg-slate-100/80 rounded-xl border border-slate-200 text-xs">
                <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1 px-1">
                  <Calculator :size="12" class="text-[#264790]" /> Rumus/Formula:
                </span>
                <button type="button" @click="insertMathFormula(question, '\\frac{a}{b}')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Sisipkan Pecahan">
                  Pecahan \(\frac{a}{b}\)
                </button>
                <button type="button" @click="insertMathFormula(question, '\\sqrt{x}')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Sisipkan Akar">
                  Akar \(\sqrt{x}\)
                </button>
                <button type="button" @click="insertMathFormula(question, 'x^{n}')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Sisipkan Pangkat">
                  Pangkat \(x^n\)
                </button>
                <button type="button" @click="insertMathFormula(question, '\\times')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Perkalian">
                  \(\times\)
                </button>
                <button type="button" @click="insertMathFormula(question, '\\div')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Pembagian">
                  \(\div\)
                </button>
                <button type="button" @click="insertMathFormula(question, '\\pm')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Plus/Minus">
                  \(\pm\)
                </button>
                <button type="button" @click="insertMathFormula(question, '\\pi')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Pi">
                  \(\pi\)
                </button>
                <button type="button" @click="insertMathFormula(question, '\\le')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Kurang Dari Sama Dengan">
                  \(\le\)
                </button>
                <button type="button" @click="insertMathFormula(question, '\\ge')" class="px-2 py-1 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-[11px] font-bold text-slate-700 hover:text-blue-700 transition-all cursor-pointer shadow-2xs" title="Lebih Dari Sama Dengan">
                  \(\ge\)
                </button>
              </div>

              <textarea
                v-model="question.question_text"
                rows="2"
                placeholder="Tuliskan teks pertanyaan atau rumus (misal: Hitunglah nilai dari \sqrt{16} + \frac{4}{2})..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all resize-none"
              ></textarea>
            </div>

            <!-- Question Image Attachment (Max 500 KB) -->
            <div class="flex flex-col gap-2 bg-slate-50/60 p-3.5 rounded-2xl border border-slate-200/80">
              <div class="flex items-center justify-between">
                <label class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                  <Image :size="14" class="text-[#264790]" /> Lampiran Gambar Soal (Maksimal 500 KB)
                </label>
                <span v-if="question.image_url" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">✓ Gambar Terlampir</span>
              </div>

              <!-- Image Preview Container if present -->
              <div v-if="question.image_url" class="relative group w-fit mt-1">
                <img :src="question.image_url" alt="Pratinjau Gambar Soal" class="max-h-48 rounded-xl border border-slate-200 object-contain bg-white p-1.5 shadow-xs" />
                <button 
                  type="button" 
                  @click="removeQuestionImage(question)"
                  class="absolute -top-2 -right-2 bg-rose-500 hover:bg-rose-600 text-white p-1 rounded-full shadow-md transition-transform hover:scale-110 cursor-pointer"
                  title="Hapus Gambar"
                >
                  <X :size="14" />
                </button>
              </div>

              <!-- Image Upload Input -->
              <div v-else class="flex flex-wrap items-center gap-3 mt-0.5">
                <label class="flex items-center gap-2 px-4 py-2 bg-white hover:bg-blue-50/80 border border-dashed border-slate-300 hover:border-blue-400 rounded-xl cursor-pointer text-xs font-bold text-slate-600 hover:text-blue-700 transition-all shadow-2xs">
                  <UploadCloud :size="15" class="text-[#264790]" />
                  <span>Pilih Gambar (Maks 500KB)</span>
                  <input type="file" accept="image/*" @change="(e) => handleQuestionImageUpload(e, question)" class="hidden" />
                </label>
                <span class="text-[10px] font-semibold text-slate-400">Format: JPG, PNG, WebP, GIF (Ukuran maks: 500 KB)</span>
              </div>
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
