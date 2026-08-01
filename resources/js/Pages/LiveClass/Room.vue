<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  Video, 
  ArrowLeft, 
  Calendar, 
  Clock, 
  ExternalLink,
  BookOpen,
  MapPin,
  QrCode,
  Globe,
  Layers,
  CheckCircle2,
  Navigation
} from 'lucide-vue-next';
import HybridClassBadge from '@/Components/HybridClassBadge.vue';
import AttendanceTypeSelector from '@/Components/AttendanceTypeSelector.vue';

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
  },
  live_classes: {
    type: Array,
    default: () => []
  }
});

const activeTab = ref('online'); // 'online' | 'location'

const currentLiveClass = computed(() => {
  if (props.live_classes && props.live_classes.length > 0) {
    return props.live_classes[0];
  }
  return null;
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

const refreshData = () => {
  router.reload({ preserveScroll: true });
};
</script>

<template>
  <Head>
    <title>Ruang Live Class - {{ course.title }}</title>
  </Head>

  <div class="min-h-screen bg-slate-900 text-slate-100 font-sans flex flex-col selection:bg-[#264790] selection:text-white">
    <!-- Navbar / Header -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur-md sticky top-0 z-50 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link 
          :href="route('courses.learn', course.slug)"
          class="p-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-all active:scale-95 border border-slate-700/50"
        >
          <ArrowLeft :size="18" />
        </Link>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ruang Pertemuan Kelas</h1>
            <HybridClassBadge :mode="currentLiveClass?.mode || course.delivery_mode || 'online'" size="sm" />
          </div>
          <p class="text-base font-extrabold text-white line-clamp-1 mt-0.5">{{ course.title }}</p>
        </div>
      </div>
      
      <div class="hidden sm:flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
        Sesi Aktif
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-6 py-8 flex flex-col lg:flex-row gap-8">
      
      <!-- Left Column: Attendance Selector & Main Interactive Card -->
      <div class="flex-grow flex flex-col gap-6 lg:w-2/3">

        <!-- Self-Service Attendance Type Selector (For Hybrid Mode) -->
        <div v-if="currentLiveClass && (currentLiveClass.mode === 'hybrid' || currentLiveClass.mode === 'offline')">
          <AttendanceTypeSelector :live-class="currentLiveClass" @updated="refreshData" />
        </div>

        <!-- Navigation Tabs Bar -->
        <div class="flex items-center gap-2 border-b border-slate-800 pb-2">
          <button 
            @click="activeTab = 'online'"
            class="px-5 py-2.5 rounded-xl font-extrabold text-xs transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'online' ? 'bg-[#264790] text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
          >
            <Video :size="16" /> TAB 1: Akses Link Online
          </button>

          <button 
            @click="activeTab = 'location'"
            class="px-5 py-2.5 rounded-xl font-extrabold text-xs transition-all flex items-center gap-2 cursor-pointer"
            :class="activeTab === 'location' ? 'bg-[#264790] text-white shadow-md' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
          >
            <MapPin :size="16" /> TAB 2: Lokasi & Tiket Presensi
          </button>
        </div>

        <!-- TAB 1 CONTENT: Online Link Streaming -->
        <div v-if="activeTab === 'online'" class="relative overflow-hidden rounded-[2rem] border border-slate-800 bg-gradient-to-br from-slate-950 to-slate-900 p-8 shadow-2xl">
          <!-- Glow effect -->
          <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>

          <div class="relative z-10 flex flex-col items-center text-center max-w-lg mx-auto py-6">
            <div class="mb-6 p-5 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 shadow-inner">
              <Video :size="48" class="animate-pulse" />
            </div>

            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight mb-3">Tautan Sesi Online</h2>
            <p class="text-slate-400 text-sm leading-relaxed mb-6">
              Link streaming video conference resmi (Zoom / Google Meet) yang selalu ter-update secara otomatis per silabus.
            </p>

            <template v-if="currentLiveClass?.meeting_link || zoom_link">
              <a 
                :href="currentLiveClass?.meeting_link || zoom_link"
                target="_blank"
                class="inline-flex items-center gap-3 px-8 py-4 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white rounded-2xl font-black text-sm transition-all shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/35 tracking-wide group cursor-pointer"
              >
                Masuk Ruang Pertemuan Online <ExternalLink :size="18" class="group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" />
              </a>
              <span class="mt-4 text-xs text-slate-500 font-medium">URL Direct: <a :href="currentLiveClass?.meeting_link || zoom_link" target="_blank" class="text-emerald-400 underline hover:text-emerald-300">{{ currentLiveClass?.meeting_link || zoom_link }}</a></span>
            </template>
            <template v-else>
              <div class="w-full p-6 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-300 font-semibold text-sm">
                ⚠️ Tautan pertemuan online belum dirilis oleh instruktur untuk sesi ini.
              </div>
            </template>
          </div>
        </div>

        <!-- TAB 2 CONTENT: Location & Onsite Ticket -->
        <div v-else-if="activeTab === 'location'" class="space-y-6">
          
          <!-- Onsite Venue & GMaps Info Card -->
          <div class="rounded-[2rem] border border-slate-800 bg-slate-950 p-6 sm:p-8 space-y-6">
            <div class="flex items-start justify-between">
              <div>
                <span class="text-xs font-bold text-amber-500 uppercase tracking-wider">Lokasi Pelaksanaan Tatap Muka</span>
                <h3 class="text-xl font-extrabold text-white mt-1">{{ currentLiveClass?.venue_name || course.location_venue || 'Gedung Utama LMS Drastha' }}</h3>
                <p class="text-sm text-slate-400 mt-1 font-medium">{{ currentLiveClass?.venue_address || 'Jl. Raya Citra Surya Mas No. 12, Sidoarjo, Jawa Timur' }}</p>
              </div>

              <a 
                v-if="currentLiveClass?.gmaps_url"
                :href="currentLiveClass.gmaps_url"
                target="_blank"
                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-extrabold flex items-center gap-2 transition-all shadow-md shrink-0 cursor-pointer"
              >
                <Navigation :size="14" /> Petunjuk Rute GMaps
              </a>
            </div>

            <!-- Embedded Google Maps -->
            <div class="h-64 rounded-2xl overflow-hidden border border-slate-800 bg-slate-900 relative">
              <iframe 
                v-if="currentLiveClass?.gmaps_embed_url"
                :src="currentLiveClass.gmaps_embed_url"
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
              ></iframe>
              <iframe 
                v-else
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15826.115904033626!2d112.75402035!3d-7.40082715!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7e42d764b8a21%3A0xc3f8373b8cb4005b!2sPerum%20Citra%20Surya%20Mas%20Sidoarjo!5e0!3m2!1sen!2sid!4v1718451151608!5m2!1sen!2sid" 
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
              ></iframe>
            </div>
          </div>

          <!-- Presensi QR Ticket Card (For Onsite Attendees) -->
          <div class="rounded-[2rem] border border-amber-500/30 bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950/20 p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-xl">
            <div class="space-y-2 text-center sm:text-left">
              <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 text-xs font-bold border border-amber-500/20">
                🎟️ Tiket Presensi Onsite
              </div>
              <h4 class="text-lg font-black text-white">QR Code Presensi Fisik</h4>
              <p class="text-xs text-slate-400 max-w-md font-medium">Tunjukkan kode QR ini kepada panitia di lokasi acara untuk konfirmasi kehadiran tatap muka.</p>
            </div>

            <!-- Visual QR Code Badge Representation -->
            <div class="p-4 bg-white rounded-2xl shadow-lg border border-slate-200 text-center shrink-0">
              <div class="w-32 h-32 bg-slate-900 rounded-xl p-2 flex flex-col items-center justify-center text-white relative">
                <QrCode :size="80" class="text-slate-100" />
                <span class="text-[9px] font-mono text-emerald-400 mt-1 uppercase tracking-widest font-bold">
                  {{ currentLiveClass?.checkin_qr_code ? currentLiveClass.checkin_qr_code.substring(0, 10) : 'ONSITE-PASS' }}
                </span>
              </div>
              <p class="text-[10px] font-extrabold text-slate-600 mt-2">Scan di Lokasi Venue</p>
            </div>
          </div>

        </div>

        <!-- Session Schedule Meta -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800/80 flex items-start gap-4">
            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-400">
              <Calendar :size="20" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tanggal Pertemuan</p>
              <p class="text-sm font-semibold text-white">{{ currentLiveClass?.start_time ? formatDate(currentLiveClass.start_time) : (course.start_date ? formatDate(course.start_date) : 'Jadwal Fleksibel') }}</p>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-slate-950 border border-slate-800/80 flex items-start gap-4">
            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-slate-400">
              <Clock :size="20" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Waktu Sesi</p>
              <p class="text-sm font-semibold text-white">{{ currentLiveClass?.start_time ? formatTime(currentLiveClass.start_time) : (course.start_date ? formatTime(course.start_date) : 'Akan ditentukan') }} {{ course.timezone || 'WIB' }}</p>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Materials & Syllabus -->
      <div class="lg:w-1/3 flex flex-col gap-6">
        <div class="p-6 rounded-[2rem] border border-slate-800 bg-slate-950 flex flex-col flex-grow">
          <h3 class="text-base font-black text-white tracking-tight mb-4 flex items-center gap-2">
            <BookOpen :size="18" class="text-emerald-400" /> Silabus & Materi Kelas
          </h3>
          <p class="text-slate-500 text-xs leading-relaxed mb-6">
            Materi pendukung, modul presentasi, atau silabus lengkap yang relevan untuk sesi ini.
          </p>

          <!-- List of lessons / materials -->
          <div v-if="materials && materials.length > 0" class="flex flex-col gap-3 overflow-y-auto max-h-[400px] pr-2">
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
