<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { 
  Folder, Tag, Plus, Trash2, Edit, Sliders, 
  UploadCloud, Settings, Info, Save, X, Layers, Search
} from 'lucide-vue-next';

const props = defineProps({
  categories: Array,
  tags: Array
});

// Tab management
const activeTab = ref(typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('tab') || 'categories') : 'categories');
const tabs = [
  { id: 'categories', label: 'Kategori Kursus', icon: Folder },
  { id: 'tags', label: 'Tag Kursus', icon: Tag },
  { id: 'course-importer', label: 'Course Importer', icon: UploadCloud },
  { id: 'quiz-importer', label: 'Quiz Importer', icon: Sliders }
];

// Search filters
const categorySearch = ref('');
const tagSearch = ref('');

// Modals
const showCategoryModal = ref(false);
const editingCategory = ref(null);
const categoryForm = useForm({
  name: '',
  description: '',
  parent_id: ''
});

const showTagModal = ref(false);
const editingTag = ref(null);
const tagForm = useForm({
  name: ''
});

// Course Importer State
const importFile = ref(null);
const importLogs = ref([]);
const isImporting = ref(false);
const dragActive = ref(false);
const fileInput = ref(null);

// Quiz Importer State
const coursesList = ref([]);
const isCoursesLoading = ref(false);
const quizTitle = ref('');
const selectedCourseId = ref('');
const selectedModuleId = ref('');
const quizImportFile = ref(null);
const quizDragActive = ref(false);
const quizFileInput = ref(null);
const isImportingQuiz = ref(false);
const quizLogs = ref([]);

// Computed parent categories candidate list (exclude current category to prevent circular dependency)
const parentCategoryOptions = computed(() => {
  return props.categories.filter(cat => {
    // Cannot be self
    if (editingCategory.value && cat.id === editingCategory.value.id) return false;
    // Keep only top-level categories as parents to keep hierarchy clean (1 level deep)
    return !cat.parent_id;
  });
});

// Filtered categories
const filteredCategories = computed(() => {
  if (!categorySearch.value) return props.categories;
  const q = categorySearch.value.toLowerCase();
  return props.categories.filter(cat => 
    cat.name.toLowerCase().includes(q) || 
    (cat.description && cat.description.toLowerCase().includes(q))
  );
});

// Filtered tags
const filteredTags = computed(() => {
  if (!tagSearch.value) return props.tags;
  const q = tagSearch.value.toLowerCase();
  return props.tags.filter(t => t.name.toLowerCase().includes(q));
});

// Category methods
const openAddCategory = () => {
  editingCategory.value = null;
  categoryForm.reset();
  categoryForm.clearErrors();
  showCategoryModal.value = true;
};

const openEditCategory = (category) => {
  editingCategory.value = category;
  categoryForm.name = category.name;
  categoryForm.description = category.description || '';
  categoryForm.parent_id = category.parent_id || '';
  categoryForm.clearErrors();
  showCategoryModal.value = true;
};

const saveCategory = () => {
  if (editingCategory.value) {
    categoryForm.put(route('dashboard.settings.course-builder.categories.update', editingCategory.value.id), {
      onSuccess: () => {
        showCategoryModal.value = false;
        categoryForm.reset();
      }
    });
  } else {
    categoryForm.post(route('dashboard.settings.course-builder.categories.store'), {
      onSuccess: () => {
        showCategoryModal.value = false;
        categoryForm.reset();
      }
    });
  }
};

const deleteCategory = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus kategori ini? Sub-kategori yang berelasi juga akan terpengaruh.')) {
    router.delete(route('dashboard.settings.course-builder.categories.destroy', id));
  }
};

// Tag methods
const openAddTag = () => {
  editingTag.value = null;
  tagForm.reset();
  tagForm.clearErrors();
  showTagModal.value = true;
};

const openEditTag = (tag) => {
  editingTag.value = tag;
  tagForm.name = tag.name;
  tagForm.clearErrors();
  showTagModal.value = true;
};

const saveTag = () => {
  if (editingTag.value) {
    tagForm.put(route('dashboard.settings.course-builder.tags.update', editingTag.value.id), {
      onSuccess: () => {
        showTagModal.value = false;
        tagForm.reset();
      }
    });
  } else {
    tagForm.post(route('dashboard.settings.course-builder.tags.store'), {
      onSuccess: () => {
        showTagModal.value = false;
        tagForm.reset();
      }
    });
  }
};

const deleteTag = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus tag ini?')) {
    router.delete(route('dashboard.settings.course-builder.tags.destroy', id));
  }
};

const isValidExcelOrCsv = (file) => {
  if (!file) return false;
  const name = file.name.toLowerCase();
  return name.endsWith('.xlsx') || name.endsWith('.xls') || name.endsWith('.csv');
};

// Importer methods
const handleFileSelect = (e) => {
  const file = e.target.files[0];
  if (file) {
    if (!isValidExcelOrCsv(file)) {
      alert('Format berkas tidak valid. Harap unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).');
      if (fileInput.value) fileInput.value.value = '';
      importFile.value = null;
      return;
    }
    importFile.value = file;
  }
};

const handleFileDrop = (e) => {
  dragActive.value = false;
  const file = e.dataTransfer.files[0];
  if (file) {
    if (!isValidExcelOrCsv(file)) {
      alert('Format berkas tidak valid. Harap unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).');
      importFile.value = null;
      return;
    }
    importFile.value = file;
  }
};

const startImport = () => {
  if (!importFile.value) {
    alert('Harap pilih file terlebih dahulu.');
    return;
  }

  isImporting.value = true;
  importLogs.value = ['<span class="text-blue-400 font-bold">[UPLOAD] Mengunggah file ke server...</span>'];

  const formData = new FormData();
  formData.append('import_file', importFile.value);

  axios.post(route('dashboard.settings.course-builder.import'), formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  .then(res => {
    isImporting.value = false;
    if (res.data.success) {
      importLogs.value = res.data.logs;
    } else {
      importLogs.value = res.data.logs || ['<span class="text-red-500 font-bold">Gagal memproses impor berkas.</span>'];
    }
  })
  .catch(err => {
    isImporting.value = false;
    const msg = err.response?.data?.message || 'Gagal terhubung ke server.';
    importLogs.value = [`<span class="text-red-500 font-bold">Error: ${msg}</span>`];
  });
};

// Quiz Importer methods
const loadCourses = () => {
  isCoursesLoading.value = true;
  axios.get(route('dashboard.settings.course-builder.courses'))
    .then(res => {
      coursesList.value = res.data;
      isCoursesLoading.value = false;
    })
    .catch(err => {
      isCoursesLoading.value = false;
      console.error(err);
    });
};

const filteredModules = computed(() => {
  const course = coursesList.value.find(c => c.id === selectedCourseId.value);
  return course ? course.modules : [];
});

watch(selectedCourseId, () => {
  selectedModuleId.value = '';
});

watch(activeTab, (newTab) => {
  if (newTab === 'quiz-importer' && coursesList.value.length === 0) {
    loadCourses();
  }
});

const handleQuizFileSelect = (e) => {
  const file = e.target.files[0];
  if (file) {
    if (!isValidExcelOrCsv(file)) {
      alert('Format berkas tidak valid. Harap unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).');
      if (quizFileInput.value) quizFileInput.value.value = '';
      quizImportFile.value = null;
      return;
    }
    quizImportFile.value = file;
  }
};

const handleQuizFileDrop = (e) => {
  quizDragActive.value = false;
  const file = e.dataTransfer.files[0];
  if (file) {
    if (!isValidExcelOrCsv(file)) {
      alert('Format berkas tidak valid. Harap unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).');
      quizImportFile.value = null;
      return;
    }
    quizImportFile.value = file;
  }
};

const startQuizImport = () => {
  if (!quizTitle.value) {
    alert('Harap masukkan judul kuis.');
    return;
  }
  if (!selectedModuleId.value) {
    alert('Harap pilih bab target.');
    return;
  }
  if (!quizImportFile.value) {
    alert('Harap pilih berkas kuis.');
    return;
  }

  isImportingQuiz.value = true;
  quizLogs.value = ['<span class="text-blue-400 font-bold">[UPLOAD] Mengunggah file kuis ke server...</span>'];

  const formData = new FormData();
  formData.append('quiz_title', quizTitle.value);
  formData.append('target_module', selectedModuleId.value);
  formData.append('import_file', quizImportFile.value);

  axios.post(route('dashboard.settings.course-builder.import-quiz'), formData, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  .then(res => {
    isImportingQuiz.value = false;
    if (res.data.success) {
      quizLogs.value = res.data.logs;
    } else {
      quizLogs.value = res.data.logs || ['<span class="text-red-500 font-bold">Gagal memproses impor kuis.</span>'];
    }
  })
  .catch(err => {
    isImportingQuiz.value = false;
    const msg = err.response?.data?.message || 'Gagal terhubung ke server.';
    quizLogs.value = [`<span class="text-red-500 font-bold">Error: ${msg}</span>`];
  });
};
</script>

<template>
  <Head title="Course Builder Settings" />

  <DashboardWrapper>
    <div class="max-w-6xl mx-auto flex flex-col gap-8">
      <!-- HEADER -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <h1 class="text-2xl font-extrabold text-[#1A2B49] tracking-tight">Course Builder Settings</h1>
          <p class="text-sm text-slate-500 mt-1">Kelola Kategori (Parent/Child), Tag Kelas, dan Importer Kursus Drastha Learning.</p>
        </div>
        <div class="flex items-center gap-3">
          <button 
            v-if="activeTab === 'categories'" 
            @click="openAddCategory" 
            class="bg-[#264790] text-white hover:bg-[#1f3a76] px-5 py-2.5 rounded-full font-bold text-xs shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2 cursor-pointer"
          >
            <Plus :size="14" /> Tambah Kategori
          </button>
          <button 
            v-if="activeTab === 'tags'" 
            @click="openAddTag" 
            class="bg-[#264790] text-white hover:bg-[#1f3a76] px-5 py-2.5 rounded-full font-bold text-xs shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2 cursor-pointer"
          >
            <Plus :size="14" /> Tambah Tag
          </button>
        </div>
      </div>

      <!-- TAB CONTENTS -->
      <div class="flex flex-col gap-6">
        
        <!-- 1. CATEGORIES TAB -->
        <div v-if="activeTab === 'categories'" class="flex flex-col gap-6">
          <!-- Search Bar -->
          <div class="relative max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
              <Search :size="16" />
            </span>
            <input 
              v-model="categorySearch"
              type="text" 
              placeholder="Cari kategori..." 
              class="w-full bg-white border border-slate-200 rounded-full pl-10 pr-4 py-2.5 outline-none text-xs text-slate-700 font-semibold focus:border-blue-500 transition-colors shadow-sm"
            />
          </div>

          <div v-if="filteredCategories.length === 0" class="py-16 text-center bg-white border border-slate-100 rounded-[2rem] shadow-sm flex flex-col items-center justify-center p-6">
            <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mb-4">
              <Folder :size="24" />
            </div>
            <h3 class="text-sm font-bold text-slate-800">Belum ada Kategori</h3>
            <p class="text-xs text-slate-450 mt-1 max-w-xs leading-relaxed">Mulai tambahkan kategori kursus baru untuk mengelompokkan kurikulum Anda.</p>
            <button @click="openAddCategory" class="mt-4 bg-[#264790]/10 hover:bg-[#264790]/20 text-[#264790] px-4 py-2 rounded-full font-bold text-xs transition-colors cursor-pointer">
              Tambah Kategori Baru
            </button>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div 
              v-for="category in filteredCategories" 
              :key="category.id" 
              class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:shadow-[0_12px_40px_rgb(0,0,0,0.04)] p-6 transition-all duration-300 flex flex-col justify-between gap-4 group"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-[#264790] shrink-0">
                    <Folder :size="20" />
                  </div>
                  <!-- Category hierarchy badge -->
                  <div class="flex flex-col gap-0.5">
                    <span v-if="category.parent" class="bg-indigo-50 text-indigo-600 border border-indigo-150 text-[9px] font-extrabold px-2 py-0.5 rounded-full w-max">
                      Sub: {{ category.parent.name }}
                    </span>
                    <span v-else class="bg-[#EBF3FF] text-[#1E40AF] border border-[#BFDBFE] text-[9px] font-extrabold px-2 py-0.5 rounded-full w-max">
                      Induk (Parent)
                    </span>
                  </div>
                </div>

                <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <button 
                    @click="openEditCategory(category)" 
                    class="p-2 bg-slate-50 hover:bg-blue-50 text-slate-600 hover:text-[#264790] rounded-xl transition-all"
                    title="Edit Kategori"
                  >
                    <Edit :size="14" />
                  </button>
                  <button 
                    @click="deleteCategory(category.id)" 
                    class="p-2 bg-slate-50 hover:bg-red-50 text-slate-600 hover:text-red-500 rounded-xl transition-all"
                    title="Hapus Kategori"
                  >
                    <Trash2 :size="14" />
                  </button>
                </div>
              </div>

              <div>
                <h3 class="text-base font-extrabold text-[#1A2B49] truncate">{{ category.name }}</h3>
                <p class="text-xs text-slate-400 font-medium mt-1">Slug: <span class="bg-slate-50 px-1.5 py-0.5 rounded font-mono text-[10px] text-slate-600">{{ category.slug }}</span></p>
                <p class="text-xs text-slate-500 line-clamp-2 mt-3 leading-relaxed">{{ category.description || 'Tidak ada deskripsi.' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. TAGS TAB -->
        <div v-else-if="activeTab === 'tags'" class="flex flex-col gap-6">
          <!-- Search Bar -->
          <div class="relative max-w-sm">
            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
              <Search :size="16" />
            </span>
            <input 
              v-model="tagSearch"
              type="text" 
              placeholder="Cari tag..." 
              class="w-full bg-white border border-slate-200 rounded-full pl-10 pr-4 py-2.5 outline-none text-xs text-slate-700 font-semibold focus:border-blue-500 transition-colors shadow-sm"
            />
          </div>

          <div v-if="filteredTags.length === 0" class="py-16 text-center bg-white border border-slate-100 rounded-[2rem] shadow-sm flex flex-col items-center justify-center p-6">
            <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mb-4">
              <Tag :size="24" />
            </div>
            <h3 class="text-sm font-bold text-slate-800">Belum ada Tag</h3>
            <p class="text-xs text-slate-450 mt-1 max-w-xs leading-relaxed">Mulai tambahkan tag kelas baru untuk melabeli kurikulum Anda.</p>
            <button @click="openAddTag" class="mt-4 bg-[#264790]/10 hover:bg-[#264790]/20 text-[#264790] px-4 py-2 rounded-full font-bold text-xs transition-colors cursor-pointer">
              Tambah Tag Baru
            </button>
          </div>

          <div v-else class="bg-white border border-slate-100 rounded-[2rem] shadow-sm overflow-hidden p-6">
            <div class="flex flex-wrap gap-3">
              <div 
                v-for="tag in filteredTags" 
                :key="tag.id" 
                class="flex items-center gap-2 px-3.5 py-2 bg-slate-50 hover:bg-[#EBF3FF] border border-slate-200 hover:border-[#BFDBFE] rounded-full transition-all duration-300 group cursor-default"
              >
                <Tag :size="12" class="text-slate-400 group-hover:text-[#264790]" />
                <span class="text-xs font-bold text-slate-700 group-hover:text-[#1E40AF]">{{ tag.name }}</span>
                <span class="text-[9px] font-mono text-slate-400 font-medium">({{ tag.slug }})</span>
                
                <div class="flex items-center gap-0.5 ml-2 border-l border-slate-200 pl-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                  <button 
                    @click="openEditTag(tag)" 
                    class="p-1 text-slate-400 hover:text-[#264790] hover:bg-white rounded-full transition-colors"
                    title="Edit Tag"
                  >
                    <Edit :size="10" />
                  </button>
                  <button 
                    @click="deleteTag(tag.id)" 
                    class="p-1 text-slate-400 hover:text-red-500 hover:bg-white rounded-full transition-colors"
                    title="Hapus Tag"
                  >
                    <Trash2 :size="10" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. COURSE IMPORTER TAB -->
        <div v-else-if="activeTab === 'course-importer'" class="flex flex-col gap-6">
          <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm flex flex-col md:flex-row gap-8">
            <!-- Upload Panel -->
            <div class="flex-1 flex flex-col gap-5">
              <div>
                <h3 class="text-lg font-extrabold text-[#1A2B49]">Unggah File Data Kelas</h3>
                <p class="text-xs text-slate-500 mt-1">Impor Kurikulum Kelas (Course, Bab/Module, Lesson) secara otomatis via CSV / Excel (.xlsx).</p>
              </div>

              <!-- Dropzone -->
              <div 
                @dragover.prevent="dragActive = true"
                @dragleave.prevent="dragActive = false"
                @drop.prevent="handleFileDrop"
                @click="fileInput.click()"
                :class="[
                  dragActive ? 'border-[#264790] bg-[#264790]/5' : 'border-slate-200 bg-slate-50/30 hover:border-[#264790]/40 hover:bg-[#264790]/5'
                ]"
                class="border-2 border-dashed rounded-[2rem] p-10 text-center cursor-pointer transition-all duration-300 flex flex-col items-center justify-center gap-3 group"
              >
                <input 
                  type="file" 
                  ref="fileInput" 
                  @change="handleFileSelect" 
                  accept=".csv,.xlsx" 
                  class="hidden" 
                />
                
                <div class="w-16 h-16 rounded-2xl bg-blue-50 text-[#264790] flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <UploadCloud :size="28" />
                </div>

                <div v-if="!importFile">
                  <p class="text-sm font-bold text-slate-700">Klik untuk memilih file atau seret kemari</p>
                  <p class="text-xs text-slate-450 mt-1">Mendukung format Excel (.xlsx) dan CSV (.csv)</p>
                </div>
                <div v-else class="flex flex-col items-center">
                  <p class="text-sm font-extrabold text-emerald-600">Berkas Terpilih:</p>
                  <p class="text-xs font-mono font-bold text-slate-700 mt-1 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-full">
                    {{ importFile.name }} ({{ (importFile.size / 1024).toFixed(1) }} KB)
                  </p>
                </div>
              </div>

              <button 
                @click="startImport"
                :disabled="isImporting || !importFile"
                class="w-full bg-[#264790] hover:bg-[#1f3a76] text-white py-3.5 rounded-2xl font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <Plus :size="16" /> {{ isImporting ? 'Memproses Impor...' : 'Mulai Proses Impor Otomatis' }}
              </button>
            </div>

            <!-- Template Panel -->
            <div class="w-full md:w-80 shrink-0 bg-slate-50/50 border border-slate-100 rounded-[2rem] p-6 flex flex-col justify-between gap-6">
              <div class="flex flex-col gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#264790] flex items-center justify-center">
                  <Settings :size="20" />
                </div>
                <div>
                  <h4 class="text-sm font-extrabold text-[#1A2B49]">Format & Template Data</h4>
                  <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Gunakan template Excel resmi kami untuk memastikan kolom data kursus terpetakan secara presisi oleh sistem pembaca kami.
                  </p>
                </div>
              </div>

              <a 
                href="/dashboard/settings/course-builder/download-template" 
                class="bg-[#264790]/15 hover:bg-[#264790]/25 text-[#264790] py-3 rounded-2xl font-bold text-xs text-center transition-colors flex items-center justify-center gap-2 cursor-pointer"
              >
                <Plus :size="14" class="rotate-45" /> Unduh Template Excel (.xlsx)
              </a>
            </div>
          </div>

          <!-- Log Box Section -->
          <div v-if="importLogs.length > 0" class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-sm flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h4 class="text-sm font-extrabold text-[#1A2B49]">Log Hasil Proses Impor</h4>
              <button 
                @click="importLogs = []" 
                class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors"
              >
                Bersihkan Log
              </button>
            </div>

            <!-- Terminal log box style -->
            <div class="bg-slate-900 rounded-2xl p-5 font-mono text-xs text-blue-400 max-h-[350px] overflow-y-auto leading-relaxed flex flex-col gap-1.5 border border-slate-800 shadow-inner">
              <div 
                v-for="(log, idx) in importLogs" 
                :key="idx" 
                v-html="log"
                class="border-b border-slate-800 pb-1.5 last:border-0 last:pb-0"
              ></div>
            </div>
          </div>
        </div>

        <!-- 4. QUIZ IMPORTER TAB -->
        <div v-else-if="activeTab === 'quiz-importer'" class="flex flex-col gap-6">
          <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm flex flex-col md:flex-row gap-8">
            <!-- Upload Panel -->
            <div class="flex-1 flex flex-col gap-5">
              <div>
                <h3 class="text-lg font-extrabold text-[#1A2B49]">Konfigurasi & Impor Kuis</h3>
                <p class="text-xs text-slate-500 mt-1">Impor soal kuis beserta pilihan jawabannya secara instan menggunakan template Excel.</p>
              </div>

              <!-- Quiz Form inputs -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-slate-650 text-xs font-bold uppercase tracking-wider text-[#1A2B49]">Judul Kuis Baru</label>
                  <input 
                    v-model="quizTitle" 
                    type="text" 
                    placeholder="Contoh: Kuis Evaluasi Pekan 1" 
                    class="w-full border-2 border-slate-200 hover:border-[#264790]/55 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                    required
                  />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-slate-650 text-xs font-bold uppercase tracking-wider text-[#1A2B49]">Pilih Course Target</label>
                  <select 
                    v-model="selectedCourseId" 
                    class="w-full border-2 border-slate-200 hover:border-[#264790]/55 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm bg-white cursor-pointer"
                  >
                    <option value="">-- Pilih Kelas --</option>
                    <option v-for="course in coursesList" :key="course.id" :value="course.id">
                      {{ course.title }}
                    </option>
                  </select>
                </div>

                <div class="flex flex-col gap-1.5 md:col-span-2">
                  <label class="text-slate-650 text-xs font-bold uppercase tracking-wider text-[#1A2B49]">Pilih Bab / Modul Target</label>
                  <select 
                    v-model="selectedModuleId" 
                    :disabled="!selectedCourseId || isCoursesLoading"
                    class="w-full border-2 border-slate-200 hover:border-[#264790]/55 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm bg-white cursor-pointer disabled:opacity-50"
                  >
                    <option value="">-- Pilih Bab / Modul --</option>
                    <option v-for="mod in filteredModules" :key="mod.id" :value="mod.id">
                      {{ mod.title }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Dropzone -->
              <div 
                @dragover.prevent="quizDragActive = true"
                @dragleave.prevent="quizDragActive = false"
                @drop.prevent="handleQuizFileDrop"
                @click="quizFileInput.click()"
                :class="[
                  quizDragActive ? 'border-[#264790] bg-[#264790]/5' : 'border-slate-200 bg-slate-50/30 hover:border-[#264790]/40 hover:bg-[#264790]/5'
                ]"
                class="border-2 border-dashed rounded-[2rem] p-8 text-center cursor-pointer transition-all duration-300 flex flex-col items-center justify-center gap-2 group"
              >
                <input 
                  type="file" 
                  ref="quizFileInput" 
                  @change="handleQuizFileSelect" 
                  accept=".csv,.xlsx" 
                  class="hidden" 
                />
                
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#264790] flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <UploadCloud :size="22" />
                </div>

                <div v-if="!quizImportFile">
                  <p class="text-xs font-bold text-slate-700">Klik untuk memilih file kuis atau seret kemari</p>
                  <p class="text-[10px] text-slate-400 mt-0.5">Mendukung format Excel (.xlsx) dan CSV (.csv)</p>
                </div>
                <div v-else class="flex flex-col items-center">
                  <p class="text-xs font-extrabold text-emerald-600">Berkas Terpilih:</p>
                  <p class="text-[10px] font-mono font-bold text-slate-700 mt-0.5 bg-emerald-50 border border-emerald-100 px-3 py-1 rounded-full">
                    {{ quizImportFile.name }} ({{ (quizImportFile.size / 1024).toFixed(1) }} KB)
                  </p>
                </div>
              </div>

              <button 
                @click="startQuizImport"
                :disabled="isImportingQuiz || !quizImportFile || !selectedModuleId || !quizTitle"
                class="w-full bg-[#264790] hover:bg-[#1f3a76] text-white py-3.5 rounded-2xl font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <Plus :size="16" /> {{ isImportingQuiz ? 'Memproses Impor Kuis...' : 'Mulai Impor Kuis' }}
              </button>
            </div>

            <!-- Template Panel -->
            <div class="w-full md:w-80 shrink-0 bg-slate-50/50 border border-slate-100 rounded-[2rem] p-6 flex flex-col justify-between gap-6">
              <div class="flex flex-col gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-[#264790] flex items-center justify-center">
                  <Settings :size="20" />
                </div>
                <div>
                  <h4 class="text-sm font-extrabold text-[#1A2B49]">Format & Template Soal</h4>
                  <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                    Unduh dan sesuaikan templat Excel resmi kami untuk memastikan susunan jenis pertanyaan, poin kuis, pilihan jawaban, dan kunci jawaban terbaca sempurna oleh sistem.
                  </p>
                </div>
              </div>

              <a 
                href="/dashboard/settings/course-builder/download-quiz-template" 
                class="bg-[#264790]/15 hover:bg-[#264790]/25 text-[#264790] py-3 rounded-2xl font-bold text-xs text-center transition-colors flex items-center justify-center gap-2 cursor-pointer"
              >
                <Plus :size="14" class="rotate-45" /> Unduh Template Soal (.xlsx)
              </a>
            </div>
          </div>

          <!-- Log Box Section -->
          <div v-if="quizLogs.length > 0" class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-sm flex flex-col gap-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <h4 class="text-sm font-extrabold text-[#1A2B49]">Log Hasil Proses Impor Kuis</h4>
              <button 
                @click="quizLogs = []" 
                class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors"
              >
                Bersihkan Log
              </button>
            </div>

            <!-- Terminal log box style -->
            <div class="bg-slate-900 rounded-2xl p-5 font-mono text-xs text-blue-400 max-h-[350px] overflow-y-auto leading-relaxed flex flex-col gap-1.5 border border-slate-800 shadow-inner">
              <div 
                v-for="(log, idx) in quizLogs" 
                :key="idx" 
                v-html="log"
                class="border-b border-slate-800 pb-1.5 last:border-0 last:pb-0"
              ></div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL CATEGORY -->
    <div v-if="showCategoryModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
      <div class="bg-white rounded-[2rem] max-w-md w-full shadow-2xl relative border border-slate-100 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <h3 class="text-base font-extrabold text-[#1A2B49]">
            {{ editingCategory ? 'Edit Kategori Kursus' : 'Tambah Kategori Kursus' }}
          </h3>
          <button @click="showCategoryModal = false" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="saveCategory" class="p-6 flex flex-col gap-5">
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Kategori Induk (Parent Category)</label>
            <select 
              v-model="categoryForm.parent_id" 
              class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm bg-white cursor-pointer"
            >
              <option value="">-- Tanpa Induk (Top Level Parent) --</option>
              <option v-for="cat in parentCategoryOptions" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Nama Kategori</label>
            <input 
              v-model="categoryForm.name" 
              type="text" 
              placeholder="Contoh: Pemrograman Python" 
              class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              required
            />
            <span v-if="categoryForm.errors.name" class="text-xs text-red-500 mt-1 font-bold">{{ categoryForm.errors.name }}</span>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Deskripsi</label>
            <textarea 
              v-model="categoryForm.description" 
              rows="4" 
              placeholder="Jelaskan secara singkat materi kursus yang masuk dalam kelompok kategori ini..." 
              class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm resize-none"
            ></textarea>
            <span v-if="categoryForm.errors.description" class="text-xs text-red-500 mt-1 font-bold">{{ categoryForm.errors.description }}</span>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="showCategoryModal = false" 
              class="px-5 py-2.5 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 font-bold text-xs transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="categoryForm.processing"
              class="bg-[#264790] text-white hover:bg-[#1f3a76] px-6 py-2.5 rounded-full font-bold text-xs shadow-md transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <Save :size="14" /> Simpan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL TAG -->
    <div v-if="showTagModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
      <div class="bg-white rounded-[2rem] max-w-md w-full shadow-2xl relative border border-slate-100 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <h3 class="text-base font-extrabold text-[#1A2B49]">
            {{ editingTag ? 'Edit Tag' : 'Tambah Tag Baru' }}
          </h3>
          <button @click="showTagModal = false" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="saveTag" class="p-6 flex flex-col gap-5">
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Nama Tag</label>
            <input 
              v-model="tagForm.name" 
              type="text" 
              placeholder="Contoh: Laravel" 
              class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              required
            />
            <span v-if="tagForm.errors.name" class="text-xs text-red-500 mt-1 font-bold">{{ tagForm.errors.name }}</span>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="showTagModal = false" 
              class="px-5 py-2.5 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 font-bold text-xs transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="tagForm.processing"
              class="bg-[#264790] text-white hover:bg-[#1f3a76] px-6 py-2.5 rounded-full font-bold text-xs shadow-md transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <Save :size="14" /> Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </DashboardWrapper>
</template>
