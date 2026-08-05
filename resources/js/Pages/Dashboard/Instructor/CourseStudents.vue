<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
  ArrowLeft, Search, Users, User, Clock, CheckCircle, SearchX
} from 'lucide-vue-next';

const props = defineProps({
  course: Object,
  enrollments: Object,
  filters: Object,
  totalLessons: Number
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

let debounceTimeout = null;
const handleSearch = () => {
  if (debounceTimeout) clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    router.get(
      route('course-builder.students', props.course.id),
      { search: search.value, status: status.value },
      { preserveState: true, replace: true }
    );
  }, 300);
};

onUnmounted(() => {
  if (debounceTimeout) clearTimeout(debounceTimeout);
});

watch([search, status], () => {
  handleSearch();
});
</script>

<template>
  <Head :title="`Peserta: ${course.title}`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-4">
        <Link 
          :href="route('course-builder.index')"
          class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm"
        >
          <ArrowLeft :size="20" />
        </Link>
        <div>
          <h2 class="text-2xl font-extrabold text-[#1A2B49] leading-tight">
            Daftar Peserta
          </h2>
          <p class="text-sm text-slate-500 font-medium mt-1">
            Kelas: {{ course.title }} ({{ course.level }})
          </p>
        </div>
      </div>
    </template>

    <div class="py-12 bg-slate-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4 flex-1">
              <div class="relative max-w-md flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <Search class="h-5 w-5 text-slate-400" />
                </div>
                <input 
                  type="text" 
                  v-model="search"
                  placeholder="Cari nama atau email peserta..." 
                  class="block w-full pl-11 pr-4 py-2.5 border-slate-200 rounded-2xl text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                >
              </div>
              
              <select 
                v-model="status" 
                class="block w-48 py-2.5 px-4 border-slate-200 rounded-2xl text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-slate-700 font-medium"
              >
                <option value="">Semua Status</option>
                <option value="active">Aktif (Sedang Belajar)</option>
                <option value="completed">Lulus (Selesai)</option>
                <option value="expired">Kedaluwarsa</option>
              </select>
            </div>
            
            <div class="bg-indigo-50 px-4 py-2 rounded-2xl border border-indigo-100 flex items-center gap-3">
              <div class="bg-white p-1.5 rounded-xl text-indigo-600 shadow-sm">
                <Users :size="18" />
              </div>
              <div>
                <div class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Total Peserta</div>
                <div class="text-lg font-extrabold text-indigo-900 leading-none mt-0.5">
                  {{ enrollments.total }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider">
                  <th class="pb-4">Peserta</th>
                  <th class="pb-4">Tanggal Bergabung</th>
                  <th class="pb-4">Progress Belajar</th>
                  <th class="pb-4">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50 text-sm">
                <tr v-for="enrollment in enrollments.data" :key="enrollment.id" class="hover:bg-slate-50/50 transition-colors">
                  <td class="py-5 flex items-center gap-4">
                    <img 
                      :src="enrollment.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(enrollment.user?.name || 'Unknown User')}&background=random`"
                      alt="Avatar" 
                      class="w-12 h-12 rounded-full object-cover shadow-sm border-2 border-white"
                    />
                    <div>
                      <div class="font-bold text-[#1A2B49] text-base leading-tight mb-1">
                        {{ enrollment.user?.name || 'User Terhapus' }}
                      </div>
                      <div class="text-slate-500 text-xs">
                        {{ enrollment.user?.email || '-' }}
                      </div>
                    </div>
                  </td>
                  <td class="py-5 text-slate-600 font-medium">
                    <div class="flex items-center gap-2">
                      <Clock :size="14" class="text-slate-400" />
                      {{ new Date(enrollment.enrolled_at || enrollment.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </div>
                  </td>
                  <td class="py-5">
                    <div class="w-48">
                      <div class="flex justify-between text-xs mb-1.5 font-bold">
                        <span class="text-slate-600">
                          {{ (enrollment.completed_lessons || []).length }} / {{ totalLessons }} Materi
                        </span>
                        <span class="text-indigo-600">
                          {{ totalLessons > 0 ? Math.round(((enrollment.completed_lessons || []).length / totalLessons) * 100) : 0 }}%
                        </span>
                      </div>
                      <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div 
                          class="bg-indigo-500 h-2 rounded-full transition-all duration-500"
                          :style="{ width: `${totalLessons > 0 ? Math.round(((enrollment.completed_lessons || []).length / totalLessons) * 100) : 0}%` }"
                        ></div>
                      </div>
                    </div>
                  </td>
                  <td class="py-5">
                    <span 
                      v-if="enrollment.status === 'completed'"
                      class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                    >
                      <CheckCircle :size="12" /> Lulus
                    </span>
                    <span 
                      v-else-if="enrollment.status === 'expired'"
                      class="inline-flex items-center gap-1 bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                    >
                      Kedaluwarsa
                    </span>
                    <span 
                      v-else
                      class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                    >
                      Aktif
                    </span>
                  </td>
                </tr>
                <tr v-if="enrollments.data.length === 0">
                  <td colspan="4" class="py-16 text-center">
                    <div class="flex flex-col items-center justify-center">
                      <div class="bg-slate-100 p-4 rounded-full mb-3 text-slate-400">
                        <SearchX :size="32" />
                      </div>
                      <h3 class="text-slate-700 font-bold text-base mb-1">Peserta Tidak Ditemukan</h3>
                      <p class="text-slate-400 text-sm max-w-sm">
                        Belum ada peserta yang mendaftar di kelas ini atau kata kunci pencarian Anda tidak cocok dengan data manapun.
                      </p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="enrollments.links && enrollments.links.length > 3" class="mt-8 flex justify-center">
            <div class="flex items-center gap-1 bg-white p-1 rounded-2xl border border-slate-200 shadow-sm">
              <template v-for="(link, i) in enrollments.links" :key="i">
                <Link
                  v-if="link.url"
                  :href="link.url"
                  class="px-4 py-2 text-sm font-bold rounded-xl transition-colors duration-200"
                  :class="link.active 
                    ? 'bg-indigo-600 text-white shadow-md' 
                    : 'text-slate-600 hover:bg-slate-50 hover:text-indigo-600'"
                  v-html="link.label"
                />
                <span
                  v-else
                  class="px-4 py-2 text-sm font-medium text-slate-400 cursor-not-allowed"
                  v-html="link.label"
                />
              </template>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
