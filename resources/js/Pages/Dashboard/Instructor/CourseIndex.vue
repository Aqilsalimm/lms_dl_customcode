<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  BookOpen, Plus, Trash2, ArrowRight, Settings, 
  X, Check, AlertCircle, Upload
} from 'lucide-vue-next';

const props = defineProps({
  courses: Array
});

const isModalOpen = ref(false);
const newCourseTitle = ref('');
const newCourseLevel = ref('Umum');
const newCoursePrice = ref(0);
const isSubmitting = ref(false);

const fileInput = ref(null);
const isUploading = ref(false);

const openCreateModal = () => {
  isModalOpen.value = true;
};

const closeCreateModal = () => {
  isModalOpen.value = false;
  newCourseTitle.value = '';
  newCourseLevel.value = 'Umum';
  newCoursePrice.value = 0;
};

const triggerFileInput = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  isUploading.value = true;
  router.post(route('course-builder.import'), {
    file: file
  }, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isUploading.value = false;
      event.target.value = null;
      alert('Berhasil mengimpor kelas dari Excel/CSV!');
    },
    onError: (errors) => {
      isUploading.value = false;
      event.target.value = null;
      alert('Gagal mengimpor: ' + Object.values(errors).join(', '));
    }
  });
};

const createCourse = () => {
  if (!newCourseTitle.value) {
    alert('Judul kelas wajib diisi.');
    return;
  }

  isSubmitting.value = true;

  router.post(route('course-builder.store'), {
    title: newCourseTitle.value,
    level: newCourseLevel.value,
    price: newCoursePrice.value,
  }, {
    onSuccess: (page) => {
      closeCreateModal();
      isSubmitting.value = false;
      // Inertia redirects automatically or we reload
    },
    onError: () => {
      isSubmitting.value = false;
    }
  });
};

const deleteCourse = (courseId) => {
  if (confirm('Apakah Anda yakin ingin menghapus kelas ini secara permanen beserta semua bab & sesi di dalamnya?')) {
    router.delete(route('course-builder.destroy', courseId));
  }
};
</script>

<template>
  <Head title="Kelola Kelas | Course Builder" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-extrabold text-[#1A2B49] leading-tight">
          Kelola Kelas (Course Builder)
        </h2>
        <div class="flex items-center gap-3">
          <input 
            type="file" 
            ref="fileInput" 
            @change="handleFileUpload" 
            class="hidden" 
            accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" 
          />
          <button 
            @click="triggerFileInput"
            :disabled="isUploading"
            class="bg-[#F9CC6B] hover:bg-[#e5bc62] text-[#1A2B49] px-5 py-2.5 rounded-full font-bold text-sm shadow-md transition-colors flex items-center gap-2"
          >
            <Upload :size="16" /> {{ isUploading ? 'Uploading...' : 'Import Excel/CSV' }}
          </button>
          <button 
            @click="openCreateModal"
            class="bg-[#264790] hover:bg-[#44A6D9] text-white px-5 py-2.5 rounded-full font-bold text-sm shadow-md transition-colors flex items-center gap-2"
          >
            <Plus :size="16" /> Tambah Kelas Baru
          </button>
        </div>
      </div>
    </template>

    <div class="py-12 bg-slate-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Table Card -->
        <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider">
                  <th class="pb-4">Kelas</th>
                  <th class="pb-4">Level</th>
                  <th class="pb-4">Harga</th>
                  <th class="pb-4">Status</th>
                  <th class="pb-4 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50 text-sm">
                <tr v-for="course in courses" :key="course.id" class="hover:bg-slate-50/50 transition-colors">
                  <td class="py-5 flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold" :style="{ backgroundColor: course.bg_color || '#44A6D9' }">
                      <BookOpen :size="20" />
                    </div>
                    <div>
                      <div class="font-extrabold text-[#1A2B49] text-base leading-tight mb-1">
                        {{ course.title }}
                      </div>
                      <div class="text-slate-400 text-xs font-medium">
                        Dibuat pada: {{ new Date(course.created_at).toLocaleDateString('id-ID') }}
                      </div>
                    </div>
                  </td>
                  <td class="py-5 font-bold text-[#1A2B49]">
                    Kelas {{ course.level }}
                  </td>
                  <td class="py-5 font-bold text-[#1A2B49]">
                    Rp {{ parseFloat(course.price).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-5">
                    <span 
                      :class="{
                        'bg-emerald-50 text-emerald-700 border-emerald-100': course.status === 'published',
                        'bg-slate-50 text-slate-600 border-slate-100': course.status === 'draft',
                      }"
                      class="px-2.5 py-1 text-xs font-bold rounded-full border uppercase tracking-wider"
                    >
                      {{ course.status }}
                    </span>
                  </td>
                  <td class="py-5 text-right">
                    <div class="flex items-center justify-end gap-3">
                      <Link 
                        :href="route('course-builder.build', course.id)"
                        class="bg-indigo-50 hover:bg-indigo-600 text-indigo-600 hover:text-white px-3.5 py-2 rounded-2xl font-bold text-xs shadow-sm transition-all duration-200 flex items-center gap-1.5"
                      >
                        <Settings :size="14" /> Builder
                      </Link>
                      <button 
                        @click="deleteCourse(course.id)"
                        class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white p-2 rounded-2xl transition-all duration-200"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="courses.length === 0">
                  <td colspan="5" class="py-10 text-center text-slate-400 font-medium">
                    Belum ada kelas yang dibuat. Silakan tambah kelas baru untuk mulai mendesain silabus Anda!
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>

    <!-- Create Course Modal -->
    <div 
      v-if="isModalOpen" 
      class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl border border-slate-100 relative animate-in fade-in zoom-in duration-200">
        
        <button 
          @click="closeCreateModal"
          class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors"
        >
          <X :size="20" />
        </button>

        <h3 class="text-xl font-extrabold text-[#1A2B49] mb-6">Tambah Kelas Baru</h3>

        <div class="flex flex-col gap-5">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Kelas</label>
            <input 
              v-model="newCourseTitle" 
              type="text" 
              placeholder="Contoh: Pemrograman JavaScript dari Nol"
              class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Level</label>
              <select 
                v-model="newCourseLevel"
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold cursor-pointer transition-colors"
              >
                <option value="SD">Sekolah Dasar (SD)</option>
                <option value="SMP">SMP</option>
                <option value="SMA">SMA</option>
                <option value="Umum">Umum / Profesional</option>
              </select>
            </div>
            
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Harga (Rupiah)</label>
              <input 
                v-model.number="newCoursePrice" 
                type="number" 
                placeholder="500000"
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold transition-colors"
              />
            </div>
          </div>

          <div class="h-px bg-slate-100 my-2"></div>

          <div class="flex gap-4">
            <button 
              @click="closeCreateModal"
              class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-2xl font-bold text-sm transition-colors"
            >
              Batal
            </button>
            <button 
              @click="createCourse"
              :disabled="isSubmitting"
              class="flex-1 bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-1.5"
            >
              <Check :size="16" /> Buat Kelas
            </button>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
