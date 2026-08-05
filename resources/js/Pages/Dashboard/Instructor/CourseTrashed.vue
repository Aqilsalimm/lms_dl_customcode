<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  ArrowLeft, RefreshCcw, Trash2, AlertCircle, Search
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
  courses: Object,
  filters: Object
});

const search = ref(props.filters.search || '');

const handleSearch = () => {
  router.get(route('course-builder.trashed'), { search: search.value }, {
    preserveState: true,
    preserveScroll: true
  });
};

const restoreCourse = (course) => {
  Swal.fire({
    title: 'Pulihkan Kelas?',
    text: `Kelas "${course.title}" akan dipulihkan dan bisa diakses kembali.`,
    icon: 'info',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Pulihkan!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(route('course-builder.restore', course.id), {}, {
        onSuccess: () => {
          Swal.fire('Berhasil!', 'Kelas telah dipulihkan.', 'success');
        }
      });
    }
  });
};

const forceDeleteCourse = (course) => {
  Swal.fire({
    title: 'Hapus Permanen?',
    text: `Data kelas "${course.title}" beserta seluruh materi, kuis, dan data nilai akan dihapus PERMANEN dan tidak dapat dipulihkan lagi!`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Hapus Permanen!',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('course-builder.force-delete', course.id), {
        onSuccess: () => {
          Swal.fire('Terhapus!', 'Kelas telah dihapus secara permanen.', 'success');
        }
      });
    }
  });
};
</script>

<template>
  <Head title="Tempat Sampah Kelas" />

  <AuthenticatedLayout>
    <div class="py-8 bg-slate-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
          <div class="flex items-center space-x-4">
            <Link :href="route('course-builder.index')" class="p-2 bg-white rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors shadow-sm">
              <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
              <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center">
                <Trash2 class="w-6 h-6 mr-3 text-red-500" />
                Tempat Sampah (Recycle Bin)
              </h1>
              <p class="text-sm text-slate-500 mt-1 font-medium">Pulihkan kelas yang terhapus atau hapus secara permanen.</p>
            </div>
          </div>
          
          <div class="flex items-center space-x-3 w-full md:w-auto">
            <div class="relative w-full md:w-64">
              <input 
                v-model="search" 
                @keyup.enter="handleSearch"
                type="text" 
                placeholder="Cari kelas terhapus..." 
                class="w-full pl-10 pr-4 py-2 bg-white border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm"
              >
              <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" />
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-50/50">
                  <th class="py-4 px-6 font-semibold whitespace-nowrap">Kelas</th>
                  <th class="py-4 px-6 font-semibold whitespace-nowrap">Kategori</th>
                  <th class="py-4 px-6 font-semibold whitespace-nowrap">Dihapus Pada</th>
                  <th class="py-4 px-6 font-semibold text-right whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100/80">
                <tr v-for="course in courses.data" :key="course.id" class="hover:bg-slate-50/50 transition-colors group">
                  <td class="py-4 px-6 align-middle">
                    <div class="flex items-center space-x-4">
                      <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                        <img v-if="course.thumbnail" :src="course.thumbnail" alt="Thumbnail" class="w-full h-full object-cover grayscale opacity-60" />
                        <BookOpen v-else class="w-5 h-5 text-slate-400" />
                      </div>
                      <div class="min-w-[200px]">
                        <h3 class="font-bold text-slate-700 text-base leading-tight mb-1 truncate max-w-xs">{{ course.title }}</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-600">
                          {{ course.course_type === 'live_class' ? 'Live Class' : 'Async' }}
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-6 align-middle">
                    <span class="text-sm text-slate-600 font-medium whitespace-nowrap">
                      {{ course.category?.name || '-' }}
                    </span>
                  </td>
                  <td class="py-4 px-6 align-middle">
                    <div class="text-sm text-slate-500 whitespace-nowrap">
                      {{ new Date(course.deleted_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                    </div>
                  </td>
                  <td class="py-4 px-6 align-middle text-right">
                    <div class="flex items-center justify-end space-x-2">
                      <button 
                        @click="restoreCourse(course)"
                        class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all tooltip"
                        title="Pulihkan Kelas"
                      >
                        <RefreshCcw class="w-4 h-4" />
                      </button>
                      <button 
                        @click="forceDeleteCourse(course)"
                        class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all tooltip"
                        title="Hapus Permanen"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="courses.data.length === 0">
                  <td colspan="4" class="py-16 text-center">
                    <div class="flex flex-col items-center justify-center">
                      <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <Trash2 class="w-8 h-8 text-slate-300" />
                      </div>
                      <h3 class="text-lg font-bold text-slate-700 mb-1">Tempat Sampah Kosong</h3>
                      <p class="text-slate-500 text-sm">Tidak ada kelas yang dihapus saat ini.</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div v-if="courses.links && courses.links.length > 3" class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <span class="text-sm text-slate-500 font-medium">Menampilkan {{ courses.from }} - {{ courses.to }} dari {{ courses.total }} kelas</span>
            <div class="flex space-x-1">
              <Link 
                v-for="(link, i) in courses.links" 
                :key="i"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
                :class="[
                  link.active 
                    ? 'bg-blue-600 text-white shadow-sm' 
                    : 'text-slate-500 hover:bg-slate-200 bg-white border border-slate-200',
                  !link.url ? 'opacity-50 cursor-not-allowed' : ''
                ]"
              />
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
