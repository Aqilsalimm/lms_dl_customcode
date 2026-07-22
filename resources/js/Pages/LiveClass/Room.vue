<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  Video, 
  ArrowLeft, 
  Calendar, 
  Clock, 
  ExternalLink,
  BookOpen,
  Award,
  Users
} from 'lucide-vue-next';

const props = defineProps({
  course: {
    type: Object,
    required: true
  },
  zoom_link: {
    type: String,
    default: null
  },
  materials: {
    type: Array,
    default: () => []
  }
});

const formatDate = (dateRaw) => {
  if (!dateRaw) return '';
  const date = new Date(dateRaw);
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(date);
};

const formatTime = (dateRaw) => {
  if (!dateRaw) return '';
  const date = new Date(dateRaw);
  return new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }).format(date);
};
</script>

<template>
  <Head>
    <title>Ruang Live Class - {{ course.title }}</title>
  </Head>

  <div class="min-h-screen bg-slate-900 text-slate-100 font-sans flex flex-col selection:bg-emerald-500 selection:text-white">
    <!-- Navbar / Header -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link 
          :href="route('courses.learn', course.slug)"
          class="p-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-all active:scale-95 border border-slate-700/50"
        >
          <ArrowLeft :size="18" />
        </Link>
        <div>
          <h1 class="text-sm font-semibold text-slate-400 uppercase tracking-wider">Live Class Room</h1>
          <p class="text-base font-extrabold text-white line-clamp-1">{{ course.title }}</p>
        </div>
      </div>
      
      <div class="hidden sm:flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
        Sesi Aktif
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-6 py-10 flex flex-col lg:flex-row gap-8">
      
      <!-- Left Column: Video Link and Course Info -->
      <div class="flex-grow flex flex-col gap-8 lg:w-2/3">
        
        <!-- Live Stream Access Card -->
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-800 bg-gradient-to-br from-slate-950 to-slate-900 p-8 shadow-2xl">
          <!-- Background glow effect -->
          <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
          <div class="absolute -left-20 -bottom-20 w-80 h-80 rounded-full bg-blue-500/10 blur-3xl pointer-events-none"></div>

          <div class="relative z-10 flex flex-col items-center text-center max-w-lg mx-auto py-8">
            <div class="mb-6 p-5 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 shadow-inner">
              <Video :size="48" class="animate-pulse" />
            </div>

            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight mb-3">Gabung Sesi Live Pertemuan</h2>
            <p class="text-slate-400 text-sm md:text-base leading-relaxed mb-8">
              Gunakan tombol di bawah untuk bergabung dengan sesi tatap muka langsung bersama Instruktur via platform video conference (Zoom / Google Meet).
            </p>

            <!-- Conditional Link Button -->
            <template v-if="zoom_link">
              <a 
                :href="zoom_link"
                target="_blank"
                class="inline-flex items-center gap-3 px-8 py-4.5 bg-emerald-500 hover:bg-emerald-600 active:scale-98 text-white rounded-2xl font-black text-sm transition-all shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/35 tracking-wide group"
              >
                Masuk Ruang Pertemuan <ExternalLink :size="18" class="group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
              </a>
              <span class="mt-4 text-xs text-slate-500 font-medium">Link Alternatif: <a :href="zoom_link" target="_blank" class="text-emerald-400 underline hover:text-emerald-300">{{ zoom_link }}</a></span>
            </template>
            <template v-else>
              <div class="w-full p-6 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 font-semibold text-sm">
                ⚠️ Tautan pertemuan online belum dirilis oleh instruktur. Silakan kembali dalam beberapa saat atau hubungi admin.
              </div>
            </template>
          </div>
        </div>

        <!-- Course Meta Info Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800/80 flex items-start gap-4">
            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-400">
              <Calendar :size="20" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tanggal Mulai</p>
              <p class="text-sm font-semibold text-white">{{ course.start_date ? formatDate(course.start_date) : 'Jadwal fleksibel' }}</p>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800/80 flex items-start gap-4">
            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-400">
              <Clock :size="20" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Waktu Sesi</p>
              <p class="text-sm font-semibold text-white">{{ course.start_date ? formatTime(course.start_date) : 'Akan ditentukan' }} {{ course.timezone || 'WIB' }}</p>
            </div>
          </div>
        </div>

        <!-- Instructor Information -->
        <div v-if="course.instructor" class="p-6 rounded-2xl bg-slate-950 border border-slate-800/80">
          <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4">Instruktur Anda</h3>
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-slate-800 border border-slate-700 overflow-hidden flex items-center justify-center text-slate-300 font-bold uppercase">
              {{ course.instructor.name.substring(0, 2) }}
            </div>
            <div>
              <p class="text-base font-extrabold text-white">{{ course.instructor.name }}</p>
              <p class="text-xs text-slate-500">{{ course.instructor.email }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Materials & Lessons -->
      <div class="lg:w-1/3 flex flex-col gap-6">
        <div class="p-6 rounded-[2rem] border border-slate-800 bg-slate-950 flex flex-col flex-grow">
          <h3 class="text-base font-black text-white tracking-tight mb-4 flex items-center gap-2">
            <BookOpen :size="18" class="text-emerald-400" /> Materi & Modul Kelas
          </h3>
          <p class="text-slate-500 text-xs leading-relaxed mb-6">
            Materi pendukung, modul presentasi, atau silabus lengkap yang relevan untuk sesi ini.
          </p>

          <!-- List of lessons / materials -->
          <div v-if="materials && materials.length > 0" class="flex flex-col gap-3 overflow-y-auto max-h-[350px] pr-2">
            <div 
              v-for="(item, idx) in materials" 
              :key="idx"
              class="p-4 rounded-xl bg-slate-900 hover:bg-slate-900/80 border border-slate-800/60 transition-all flex items-center justify-between"
            >
              <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-400">{{ idx + 1 }}</span>
                <span class="text-sm font-semibold text-slate-300">{{ item.title || item.name }}</span>
              </div>
            </div>
          </div>
          <div v-else class="flex flex-col items-center justify-center text-center p-8 border border-dashed border-slate-800 rounded-2xl flex-grow">
            <BookOpen :size="28" class="text-slate-700 mb-2" />
            <p class="text-slate-500 text-xs font-bold">Materi belum diunggah.</p>
          </div>
        </div>
      </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/60 bg-slate-950 py-6 px-6 text-center text-xs text-slate-600 font-semibold">
      &copy; 2026 Drastha Learning. All rights reserved.
    </footer>
  </div>
</template>

<style scoped>
/* Gradient styles */
</style>
