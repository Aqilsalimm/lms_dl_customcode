<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  BookOpen, Plus, Trash2, ArrowLeft, Settings, 
  ChevronRight, Save, Layout, Edit, Video, HelpCircle,
  PlusCircle, Trash, Check, X, CheckSquare
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  course: Object,
  categories: Array,
  tags: Array
});

// Current active tab in course builder settings vs content
const activeTab = ref('curriculum'); // 'curriculum' or 'details'

// Settings Form
const form = ref({
  title: props.course.title,
  price: parseFloat(props.course.price),
  level: props.course.level,
  status: props.course.status,
  description: props.course.description || '',
  about: props.course.about || '',
  bg_color: props.course.bg_color || '#44A6D9',
  icon_type: props.course.icon_type || 'code',
  category_id: props.course.category_id || '',
  capacity: props.course.capacity || 20,
});

// Dynamic Local Content Lists for instantaneous updates
const modules = ref([...props.course.modules]);

// Modal State Management
const currentModal = ref(''); // 'module', 'lesson', 'quiz', 'question'
const selectedModule = ref(null);
const selectedQuiz = ref(null);

// Temporary model states for creating/editing
const moduleForm = ref({ id: null, title: '' });
const lessonForm = ref({ id: null, title: '', content: '', video_url: '', duration_minutes: 30 });
const quizForm = ref({ id: null, title: '', description: '', time_limit_minutes: 15 });
const questionForm = ref({
  id: null,
  question_text: '',
  options: ['', '', '', ''],
  correct_option_index: 0
});

// --- SAVE MAIN COURSE DETAILS ---
const saveDetails = () => {
  axios.put(`/course-builder/courses/${props.course.id}`, form.value)
    .then(res => {
      alert('Informasi kelas berhasil diperbarui!');
    })
    .catch(err => {
      alert('Error updating details. Mohon cek data Anda.');
    });
};

// --- MODULE CRUD ---
const openModuleModal = (module = null) => {
  if (module) {
    moduleForm.value = { id: module.id, title: module.title };
  } else {
    moduleForm.value = { id: null, title: '' };
  }
  currentModal.value = 'module';
};

const saveModule = () => {
  if (moduleForm.value.id) {
    // Edit Mode
    axios.put(`/course-builder/modules/${moduleForm.value.id}`, { title: moduleForm.value.title })
      .then(res => {
        const idx = modules.value.findIndex(m => m.id === moduleForm.value.id);
        modules.value[idx].title = res.data.module.title;
        currentModal.value = '';
      });
  } else {
    // Create Mode
    axios.post(`/course-builder/courses/${props.course.id}/modules`, { title: moduleForm.value.title })
      .then(res => {
        modules.value.push({ ...res.data.module, lessons: [], quizzes: [] });
        currentModal.value = '';
      });
  }
};

const deleteModule = (moduleId) => {
  if (confirm('Hapus bab ini beserta seluruh isinya?')) {
    axios.delete(`/course-builder/modules/${moduleId}`)
      .then(() => {
        modules.value = modules.value.filter(m => m.id !== moduleId);
      });
  }
};

// --- LESSON CRUD ---
const openLessonModal = (module, lesson = null) => {
  selectedModule.value = module;
  if (lesson) {
    lessonForm.value = { 
      id: lesson.id, 
      title: lesson.title, 
      content: lesson.content || '', 
      video_url: lesson.video_url || '', 
      duration_minutes: lesson.duration_minutes 
    };
  } else {
    lessonForm.value = { id: null, title: '', content: '', video_url: '', duration_minutes: 30 };
  }
  currentModal.value = 'lesson';
};

const saveLesson = () => {
  if (lessonForm.value.id) {
    // Edit
    axios.put(`/course-builder/lessons/${lessonForm.value.id}`, lessonForm.value)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        const lesIdx = modules.value[modIdx].lessons.findIndex(l => l.id === lessonForm.value.id);
        modules.value[modIdx].lessons[lesIdx] = res.data.lesson;
        currentModal.value = '';
      });
  } else {
    // Create
    axios.post(`/course-builder/modules/${selectedModule.value.id}/lessons`, lessonForm.value)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        modules.value[modIdx].lessons.push(res.data.lesson);
        currentModal.value = '';
      });
  }
};

const deleteLesson = (module, lessonId) => {
  if (confirm('Hapus sesi materi ini?')) {
    axios.delete(`/course-builder/lessons/${lessonId}`)
      .then(() => {
        const modIdx = modules.value.findIndex(m => m.id === module.id);
        modules.value[modIdx].lessons = modules.value[modIdx].lessons.filter(l => l.id !== lessonId);
      });
  }
};

// --- QUIZ CRUD ---
const openQuizModal = (module, quiz = null) => {
  selectedModule.value = module;
  if (quiz) {
    quizForm.value = { 
      id: quiz.id, 
      title: quiz.title, 
      description: quiz.description || '', 
      time_limit_minutes: quiz.time_limit_minutes 
    };
  } else {
    quizForm.value = { id: null, title: '', description: '', time_limit_minutes: 15 };
  }
  currentModal.value = 'quiz';
};

const saveQuiz = () => {
  if (quizForm.value.id) {
    axios.put(`/course-builder/quizzes/${quizForm.value.id}`, quizForm.value)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        const qIdx = modules.value[modIdx].quizzes.findIndex(q => q.id === quizForm.value.id);
        // Preserve questions
        const questionsTemp = modules.value[modIdx].quizzes[qIdx].questions || [];
        modules.value[modIdx].quizzes[qIdx] = { ...res.data.quiz, questions: questionsTemp };
        currentModal.value = '';
      });
  } else {
    axios.post(`/course-builder/modules/${selectedModule.value.id}/quizzes`, quizForm.value)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        if (!modules.value[modIdx].quizzes) modules.value[modIdx].quizzes = [];
        modules.value[modIdx].quizzes.push({ ...res.data.quiz, questions: [] });
        currentModal.value = '';
      });
  }
};

const deleteQuiz = (module, quizId) => {
  if (confirm('Hapus kuis ini?')) {
    axios.delete(`/course-builder/quizzes/${quizId}`)
      .then(() => {
        const modIdx = modules.value.findIndex(m => m.id === module.id);
        modules.value[modIdx].quizzes = modules.value[modIdx].quizzes.filter(q => q.id !== quizId);
      });
  }
};

// --- QUIZ QUESTIONS ---
const openQuestionModal = (quiz, question = null) => {
  selectedQuiz.value = quiz;
  if (question) {
    questionForm.value = {
      id: question.id,
      question_text: question.question_text,
      options: [...question.options],
      correct_option_index: question.correct_option_index
    };
  } else {
    questionForm.value = {
      id: null,
      question_text: '',
      options: ['', '', '', ''],
      correct_option_index: 0
    };
  }
  currentModal.value = 'question';
};

const saveQuestion = () => {
  if (questionForm.value.id) {
    axios.put(`/course-builder/questions/${questionForm.value.id}`, questionForm.value)
      .then(res => {
        // Find module index and quiz index
        for (let m = 0; m < modules.value.length; m++) {
          const qIdx = (modules.value[m].quizzes || []).findIndex(q => q.id === selectedQuiz.value.id);
          if (qIdx !== -1) {
            const qstIdx = modules.value[m].quizzes[qIdx].questions.findIndex(q => q.id === questionForm.value.id);
            modules.value[m].quizzes[qIdx].questions[qstIdx] = res.data.question;
            break;
          }
        }
        currentModal.value = '';
      });
  } else {
    axios.post(`/course-builder/quizzes/${selectedQuiz.value.id}/questions`, questionForm.value)
      .then(res => {
        for (let m = 0; m < modules.value.length; m++) {
          const qIdx = (modules.value[m].quizzes || []).findIndex(q => q.id === selectedQuiz.value.id);
          if (qIdx !== -1) {
            if (!modules.value[m].quizzes[qIdx].questions) {
              modules.value[m].quizzes[qIdx].questions = [];
            }
            modules.value[m].quizzes[qIdx].questions.push(res.data.question);
            break;
          }
        }
        currentModal.value = '';
      });
  }
};

const deleteQuestion = (quiz, questionId) => {
  if (confirm('Hapus pertanyaan ini?')) {
    axios.delete(`/course-builder/questions/${questionId}`)
      .then(() => {
        for (let m = 0; m < modules.value.length; m++) {
          const qIdx = (modules.value[m].quizzes || []).findIndex(q => q.id === quiz.id);
          if (qIdx !== -1) {
            modules.value[m].quizzes[qIdx].questions = modules.value[m].quizzes[qIdx].questions.filter(q => q.id !== questionId);
            break;
          }
        }
      });
  }
};
</script>

<template>
  <Head :title="'Workspace Builder: ' + course.title" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link 
            :href="route('course-builder.index')"
            class="bg-slate-100 hover:bg-slate-200 p-2 rounded-2xl text-slate-600 transition-colors"
          >
            <ArrowLeft :size="16" />
          </Link>
          <div>
            <h2 class="text-xl font-extrabold text-[#1A2B49] leading-tight line-clamp-1 max-w-lg">
              {{ course.title }}
            </h2>
            <p class="text-slate-400 text-xs font-semibold">Workspace Kurikulum & Pengaturan Kelas</p>
          </div>
        </div>

        <div class="flex gap-3">
          <button 
            @click="activeTab = 'curriculum'"
            :class="activeTab === 'curriculum' ? 'bg-[#264790] text-white' : 'bg-slate-100 text-slate-600'"
            class="px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all duration-300 flex items-center gap-1.5"
          >
            <Layout :size="14" /> Kurikulum
          </button>
          
          <button 
            @click="activeTab = 'details'"
            :class="activeTab === 'details' ? 'bg-[#264790] text-white' : 'bg-slate-100 text-slate-600'"
            class="px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all duration-300 flex items-center gap-1.5"
          >
            <Settings :size="14" /> Pengaturan Kelas
          </button>
        </div>
      </div>
    </template>

    <div class="py-12 bg-slate-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Tab 1: CURRICULUM BUILDER -->
        <div v-if="activeTab === 'curriculum'">
          
          <!-- CTA Header -->
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-extrabold text-[#1A2B49] flex items-center gap-2">
              <Layout :size="20" class="text-indigo-600" /> Struktur Silabus Kelas
            </h3>
            <button 
              @click="openModuleModal()"
              class="bg-white hover:bg-slate-50 text-indigo-600 border border-indigo-200 px-4.5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-colors flex items-center gap-1.5"
            >
              <PlusCircle :size="16" /> Tambah Bab Baru
            </button>
          </div>

          <!-- Modules Outline -->
          <div class="flex flex-col gap-6">
            <div 
              v-for="(mod, modIndex) in modules" 
              :key="mod.id" 
              class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100"
            >
              
              <!-- Module Header -->
              <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <div class="flex items-center gap-3">
                  <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold">
                    {{ modIndex + 1 }}
                  </span>
                  <h4 class="font-extrabold text-base text-[#1A2B49]">{{ mod.title }}</h4>
                </div>
                <div class="flex items-center gap-2">
                  <button @click="openModuleModal(mod)" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-600 transition-colors">
                    <Edit :size="16" />
                  </button>
                  <button @click="deleteModule(mod.id)" class="p-2 hover:bg-rose-50 rounded-xl text-slate-400 hover:text-rose-600 transition-colors">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </div>

              <!-- Module Content Grid (Lessons & Quizzes) -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Lessons Column -->
                <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100/50">
                  <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Materi & Video ({{ mod.lessons?.length || 0 }})</span>
                    <button @click="openLessonModal(mod)" class="text-xs font-bold text-[#264790] hover:text-[#44A6D9] flex items-center gap-1">
                      <Plus :size="12" /> Tambah Sesi
                    </button>
                  </div>

                  <div class="flex flex-col gap-3">
                    <div 
                      v-for="les in mod.lessons" 
                      :key="les.id"
                      class="bg-white p-3.5 rounded-2xl border border-slate-100 flex items-center justify-between hover:border-slate-200 transition-colors"
                    >
                      <div class="flex items-center gap-3">
                        <Video :size="16" class="text-indigo-500" />
                        <div>
                          <div class="font-bold text-xs text-[#1A2B49]">{{ les.title }}</div>
                          <div class="text-[10px] text-slate-400 font-medium">{{ les.duration_minutes }} Menit</div>
                        </div>
                      </div>
                      <div class="flex items-center gap-1.5">
                        <button @click="openLessonModal(mod, les)" class="p-1 hover:bg-slate-50 rounded-lg text-slate-400 hover:text-slate-600">
                          <Edit :size="14" />
                        </button>
                        <button @click="deleteLesson(mod, les.id)" class="p-1 hover:bg-rose-50 rounded-lg text-slate-400 hover:text-rose-600">
                          <Trash :size="14" />
                        </button>
                      </div>
                    </div>
                    
                    <div v-if="!mod.lessons || mod.lessons.length === 0" class="text-center py-4 text-xs font-semibold text-slate-400">
                      Belum ada materi pembelajaran.
                    </div>
                  </div>
                </div>

                <!-- Quizzes Column -->
                <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100/50">
                  <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Evaluasi Kuis ({{ mod.quizzes?.length || 0 }})</span>
                    <button @click="openQuizModal(mod)" class="text-xs font-bold text-[#264790] hover:text-[#44A6D9] flex items-center gap-1">
                      <Plus :size="12" /> Tambah Kuis
                    </button>
                  </div>

                  <div class="flex flex-col gap-3">
                    <div 
                      v-for="qz in mod.quizzes" 
                      :key="qz.id"
                      class="bg-white p-3.5 rounded-2xl border border-slate-100 flex flex-col gap-3 hover:border-slate-200 transition-colors"
                    >
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                          <HelpCircle :size="16" class="text-amber-500" />
                          <div>
                            <div class="font-bold text-xs text-[#1A2B49]">{{ qz.title }}</div>
                            <div class="text-[10px] text-slate-400 font-medium">{{ qz.time_limit_minutes }} Menit • {{ qz.questions?.length || 0 }} Pertanyaan</div>
                          </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                          <button @click="openQuizModal(mod, qz)" class="p-1 hover:bg-slate-50 rounded-lg text-slate-400 hover:text-slate-600">
                            <Edit :size="14" />
                          </button>
                          <button @click="deleteQuiz(mod, qz.id)" class="p-1 hover:bg-rose-50 rounded-lg text-slate-400 hover:text-rose-600">
                            <Trash :size="14" />
                          </button>
                        </div>
                      </div>

                      <!-- Sub-question list inside quiz -->
                      <div class="bg-slate-50 p-3 rounded-xl flex flex-col gap-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 mb-1.5">
                          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pertanyaan Kuis</span>
                          <button @click="openQuestionModal(qz)" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5">
                            <Plus :size="10" /> Tambah Soal
                          </button>
                        </div>
                        
                        <div v-for="(qst, qstIdx) in qz.questions" :key="qst.id" class="flex items-center justify-between text-xs text-[#1A2B49] font-medium p-1.5 bg-white rounded-lg border border-slate-100">
                          <span class="line-clamp-1 flex-1">{{ qstIdx + 1 }}. {{ qst.question_text }}</span>
                          <div class="flex items-center gap-1">
                            <button @click="openQuestionModal(qz, qst)" class="p-0.5 hover:bg-slate-50 text-slate-400 hover:text-slate-600"><Edit :size="10" /></button>
                            <button @click="deleteQuestion(qz, qst.id)" class="p-0.5 hover:bg-rose-50 text-slate-400 hover:text-rose-600"><Trash :size="10" /></button>
                          </div>
                        </div>
                        <div v-if="!qz.questions || qz.questions.length === 0" class="text-center py-2 text-[10px] text-slate-400 font-semibold">
                          Belum ada pertanyaan dibuat.
                        </div>
                      </div>
                    </div>

                    <div v-if="!mod.quizzes || mod.quizzes.length === 0" class="text-center py-4 text-xs font-semibold text-slate-400">
                      Belum ada evaluasi kuis.
                    </div>
                  </div>
                </div>

              </div>

            </div>
            
            <div v-if="modules.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-100">
              <p class="text-slate-400 font-medium mb-4">Silabus kelas masih kosong.</p>
              <button 
                @click="openModuleModal()"
                class="bg-[#264790] hover:bg-[#44A6D9] text-white px-6 py-2.5 rounded-full font-bold text-xs shadow-sm transition-colors"
              >
                Mulai dengan Membuat Bab Pertama
              </button>
            </div>
          </div>

        </div>

        <!-- Tab 2: DETAILS SETTINGS -->
        <div v-if="activeTab === 'details'" class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <div class="flex items-center justify-between border-b border-slate-100 pb-5 mb-8">
            <h3 class="text-lg font-extrabold text-[#1A2B49] flex items-center gap-2">
              <Settings :size="20" class="text-[#264790]" /> Pengaturan Utama Kelas
            </h3>
            <button 
              @click="saveDetails"
              class="bg-[#264790] hover:bg-[#44A6D9] text-white px-5 py-2.5 rounded-full font-bold text-xs shadow-md transition-colors flex items-center gap-1.5"
            >
              <Save :size="14" /> Simpan Pengaturan
            </button>
          </div>

          <div class="flex flex-col gap-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Kelas</label>
                <input v-model="form.title" type="text" class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors" />
              </div>
              
              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Kategori</label>
                <select v-model="form.category_id" class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold cursor-pointer transition-colors">
                  <option value="">Pilih Kategori</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Harga (Rupiah)</label>
                <input v-model.number="form.price" type="number" class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold transition-colors" />
              </div>
              
              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Level Sasaran</label>
                <select v-model="form.level" class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold cursor-pointer transition-colors">
                  <option value="SD">Sekolah Dasar (SD)</option>
                  <option value="SMP">SMP</option>
                  <option value="SMA">SMA</option>
                  <option value="Umum">Umum / Profesional</option>
                </select>
              </div>

              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Kapasitas Kelas (Siswa)</label>
                <input v-model.number="form.capacity" type="number" class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold transition-colors" />
              </div>

              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Status Publikasi</label>
                <select v-model="form.status" class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold cursor-pointer transition-colors">
                  <option value="draft">Draft (Disembunyikan)</option>
                  <option value="published">Published (Aktif/Terbuka)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Warna Background Kartu</label>
                <input v-model="form.bg_color" type="text" placeholder="#FF4D4F" class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold transition-colors" />
              </div>
              
              <div>
                <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Tipe Ikon Lucide</label>
                <input v-model="form.icon_type" type="text" placeholder="code, calculator, book-open, dll." class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors" />
              </div>
            </div>

            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Ringkasan Singkat (Short Description)</label>
              <textarea v-model="form.description" rows="3" placeholder="Masukkan ringkasan materi untuk card info kelas..." class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors"></textarea>
            </div>

            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Tentang Kelas (About Course)</label>
              <textarea v-model="form.about" rows="5" placeholder="Tuliskan deskripsi lengkap, keuntungan belajar, tools, dan prasyarat kelas..." class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors"></textarea>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- 1. MODULE MODAL -->
    <div v-if="currentModal === 'module'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl relative border border-slate-100">
        <button @click="currentModal = ''" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        <h3 class="text-lg font-extrabold text-[#1A2B49] mb-5">{{ moduleForm.id ? 'Edit Bab' : 'Tambah Bab Baru' }}</h3>
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Bab</label>
            <input v-model="moduleForm.title" type="text" placeholder="Contoh: Pengenalan Sintaks Dasar" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium" />
          </div>
          <button @click="saveModule" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-sm transition-colors">
            <Check :size="16" class="inline mr-1" /> Simpan Bab
          </button>
        </div>
      </div>
    </div>

    <!-- 2. LESSON MODAL -->
    <div v-if="currentModal === 'lesson'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl relative border border-slate-100">
        <button @click="currentModal = ''" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        <h3 class="text-lg font-extrabold text-[#1A2B49] mb-5">{{ lessonForm.id ? 'Edit Sesi Materi' : 'Tambah Sesi Materi Baru' }}</h3>
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Sesi</label>
            <input v-model="lessonForm.title" type="text" placeholder="Contoh: Belajar Operator Aritmatika" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium" />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Durasi (Menit)</label>
              <input v-model.number="lessonForm.duration_minutes" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold" />
            </div>
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Link Video (YouTube/Vimeo)</label>
              <input v-model="lessonForm.video_url" type="text" placeholder="https://youtube.com/..." class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium" />
            </div>
          </div>

          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Isi Ringkasan Materi</label>
            <textarea v-model="lessonForm.content" rows="4" placeholder="Tuliskan rangkuman teori, catatan, atau tugas sesi ini..." class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium"></textarea>
          </div>

          <button @click="saveLesson" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-sm transition-colors">
            <Check :size="16" class="inline mr-1" /> Simpan Materi
          </button>
        </div>
      </div>
    </div>

    <!-- 3. QUIZ MODAL -->
    <div v-if="currentModal === 'quiz'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl relative border border-slate-100">
        <button @click="currentModal = ''" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        <h3 class="text-lg font-extrabold text-[#1A2B49] mb-5">{{ quizForm.id ? 'Edit Kuis' : 'Tambah Kuis Baru' }}</h3>
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Kuis</label>
            <input v-model="quizForm.title" type="text" placeholder="Contoh: Evaluasi Aritmatika" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium" />
          </div>

          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Batas Waktu (Menit)</label>
            <input v-model.number="quizForm.time_limit_minutes" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold" />
          </div>

          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Instruksi Singkat</label>
            <textarea v-model="quizForm.description" rows="3" placeholder="Tuliskan petunjuk pengerjaan kuis..." class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium"></textarea>
          </div>

          <button @click="saveQuiz" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-sm transition-colors">
            <Check :size="16" class="inline mr-1" /> Simpan Kuis
          </button>
        </div>
      </div>
    </div>

    <!-- 4. QUESTION MODAL -->
    <div v-if="currentModal === 'question'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl relative border border-slate-100">
        <button @click="currentModal = ''" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        <h3 class="text-lg font-extrabold text-[#1A2B49] mb-5">{{ questionForm.id ? 'Edit Pertanyaan' : 'Tambah Pertanyaan Kuis' }}</h3>
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Teks Soal / Pertanyaan</label>
            <textarea v-model="questionForm.question_text" rows="3" placeholder="Contoh: Sintaks apakah yang digunakan untuk menampilkan tulisan di layar?" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium"></textarea>
          </div>

          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Pilihan Jawaban (Beri Centang pada Pilihan yang Benar)</label>
            <div class="flex flex-col gap-3">
              <div v-for="(opt, idx) in questionForm.options" :key="idx" class="flex items-center gap-3">
                <input 
                  type="radio" 
                  name="correct_index" 
                  :value="idx" 
                  v-model="questionForm.correct_option_index"
                  class="w-5 h-5 text-indigo-600 cursor-pointer"
                />
                <input 
                  v-model="questionForm.options[idx]" 
                  type="text" 
                  :placeholder="'Pilihan ' + (idx + 1)"
                  class="flex-1 bg-slate-55 border border-slate-200 hover:border-slate-300 rounded-xl px-3 py-2 outline-none text-xs text-[#1A2B49] font-medium"
                />
              </div>
            </div>
          </div>

          <button @click="saveQuestion" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-sm transition-colors">
            <Check :size="16" class="inline mr-1" /> Simpan Pertanyaan
          </button>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
