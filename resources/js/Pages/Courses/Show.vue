<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, GraduationCap, 
  Home, Newspaper, Calendar, Clock, MapPin, Code,
  Share2, CheckCircle2, Map, CreditCard, Play,
  BarChart3, RotateCw, Award, Image as ImageIcon,
  Instagram, Twitter, Facebook, Linkedin
} from 'lucide-vue-next';
import axios from 'axios';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

// Leaflet.js setup
import 'leaflet/dist/leaflet.css';
import { LMap, LTileLayer, LMarker, LPopup } from '@vue-leaflet/vue-leaflet';

const zoom = ref(15);
const center = ref([-7.4008, 112.7592]);

const props = defineProps({
  course: Object,
  isEnrolled: Boolean,
  showContentSummary: {
    type: Boolean,
    default: true
  }
});

const isProcessing = ref(false);
const showSuccessOverlay = ref(false);

const activeMediaTab = ref('image');

const introVideoUrl = computed(() => {
  if (props.course.about && props.course.about.startsWith('{') && props.course.about.endsWith('}')) {
    try {
      const parsed = JSON.parse(props.course.about);
      return parsed.intro_video_url || '';
    } catch (e) {
      return '';
    }
  }
  return '';
});

const courseBenefits = computed(() => {
  if (props.course.about && props.course.about.startsWith('{') && props.course.about.endsWith('}')) {
    try {
      const parsed = JSON.parse(props.course.about);
      const learn = parsed.what_will_learn;
      if (Array.isArray(learn) && learn.length > 0) {
        return learn.join(', ');
      } else if (typeof learn === 'string' && learn.trim() !== '') {
        return learn;
      }
      
      const overview = parsed.overview;
      if (typeof overview === 'string' && overview.trim() !== '') {
        return overview;
      }
      
      return '';
    } catch (e) {
      return '';
    }
  }
  return props.course.about;
});

const youtubeEmbedUrl = computed(() => {
  const url = introVideoUrl.value;
  if (!url) return '';
  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
  const match = url.match(regExp);
  return (match && match[2].length === 11) ? `https://www.youtube.com/embed/${match[2]}` : '';
});

// Accordion Collapsible State for Syllabus Modules
const expandedModules = ref({});

// Automatically expand first module by default
if (props.course.modules && props.course.modules.length > 0) {
  expandedModules.value[props.course.modules[0].id] = true;
}

const toggleModule = (id) => {
  expandedModules.value[id] = !expandedModules.value[id];
};

const getModuleDuration = (mod) => {
  if (!mod.lessons) return 0;
  return mod.lessons.reduce((acc, l) => acc + parseInt(l.duration_minutes || 0), 0);
};

// Computed Meta Values for Right Sidebar matching revised mockup
// Computed Meta Values for Right Sidebar matching revised mockup
const levelLabel = computed(() => {
  const map = {
    'SD': usePage().props.translations['level_beginner'] || 'Beginner',
    'SMP': usePage().props.translations['level_intermediate'] || 'Intermediate',
    'SMA': usePage().props.translations['level_advanced'] || 'Advanced',
    'Umum': usePage().props.translations['level_intermediate'] || 'Intermediate'
  };
  return map[props.course.level] || 'Intermediate';
});

const enrolledCountLabel = computed(() => {
  const text = usePage().props.translations['total_enrolled'] || 'Total Terdaftar';
  return `${props.course.enrollments_count || 0} ${text}`;
});

const ageLabel = computed(() => {
  const map = {
    'SD': usePage().props.translations['age_sd'] || 'Umur 7 - 12 Tahun',
    'SMP': usePage().props.translations['age_smp'] || 'Umur 12 - 15 Tahun',
    'SMA': usePage().props.translations['age_sma'] || 'Umur 15 - 18 Tahun',
    'Umum': usePage().props.translations['age_umum'] || 'Semua Umur'
  };
  return map[props.course.level] || 'Semua Umur';
});

const durationLabel = computed(() => {
  let totalMinutes = 0;
  if (props.course.modules) {
    props.course.modules.forEach(mod => {
      if (mod.lessons) {
        mod.lessons.forEach(les => {
          totalMinutes += parseInt(les.duration_minutes || 0);
        });
      }
    });
  }
  const t = (key, fallback) => usePage().props.translations[key] || fallback;
  if (totalMinutes === 0) {
    return `5 ${t('hours', 'jam')} 30 ${t('minutes', 'menit')} ${t('duration', 'Durasi')}`;
  }
  const hrs = Math.floor(totalMinutes / 60);
  const mins = totalMinutes % 60;
  let parts = [];
  if (hrs > 0) parts.push(`${hrs} ${t('hours', 'jam')}`);
  if (mins > 0) parts.push(`${mins} ${t('minutes', 'menit')}`);
  return parts.join(' ') + ' ' + t('duration', 'Durasi');
});

const lastUpdatedLabel = computed(() => {
  const date = new Date(props.course.updated_at || props.course.created_at);
  const formatted = date.toLocaleDateString(usePage().props.locale === 'id' ? 'id-ID' : 'en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  const text = usePage().props.translations['last_updated'] || 'Terakhir Diperbarui';
  return `${formatted} ${text}`;
});

const instructorListLayout = computed(() => {
  return usePage().props.settings?.instructor_list_layout || 'cover';
});

// Format Price
const formatPrice = (val) => {
  return parseFloat(val).toLocaleString('id-ID');
};

// Initialize Midtrans Snap script
onMounted(() => {
  const script = document.createElement('script');
  script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
  script.setAttribute('data-client-key', 'SB-Mid-client-placeholder');
  document.head.appendChild(script);
});

// Checkout action (Add to cart and go to Cart page)
const handleRegister = () => {
  if (!usePage().props.auth.user) {
    alert(usePage().props.translations['alert_login_register'] || 'Silakan login terlebih dahulu untuk mendaftar kelas.');
    router.get('/login');
    return;
  }
  isProcessing.value = true;
  
  router.post('/cart/add', {
    course_id: props.course.id
  }, {
    onSuccess: () => {
      isProcessing.value = false;
      router.get('/cart');
    },
    onError: () => {
      isProcessing.value = false;
      alert(usePage().props.translations['alert_failed_cart'] || 'Gagal menambahkan kelas ke keranjang belanja.');
    }
  });
};

const handleAccessLesson = (lesson) => {
  if (!usePage().props.auth.user) {
    alert(usePage().props.translations['alert_login_access'] || 'Silakan login terlebih dahulu untuk mengakses kelas.');
    router.get('/login');
    return;
  }
  if (!props.isEnrolled) {
    alert(usePage().props.translations['alert_not_enrolled'] || 'Anda belum terdaftar di kelas ini. Silakan daftar terlebih dahulu.');
    return;
  }
  router.get(`/courses/${props.course.slug}/learn`);
};

const handleShare = () => {
  navigator.clipboard.writeText(window.location.href);
  alert(usePage().props.translations['alert_share_success'] || 'Link rincian kelas berhasil disalin ke clipboard!');
};

// Logo Helper
const Logo = () => {
  const settings = usePage().props.settings;
  const customLogo = settings?.course_logo;
  if (customLogo && customLogo !== '/images/logo-placeholder.png') {
    return `<div class="flex items-center gap-2">
      <img src="${customLogo}" alt="Drastha Learning Logo" class="h-10 w-auto object-contain" />
    </div>`;
  }
  return `<div class="flex items-center gap-2">
    <svg width="32" height="32" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M50 20 L20 35 L50 50 L80 35 Z" fill="#264790"/>
      <path d="M30 40 L30 65 C30 75 70 75 70 65 L70 40" stroke="#44A6D9" stroke-width="6" fill="none"/>
      <path d="M15 45 C15 75 40 90 50 90 C60 90 85 75 85 45" stroke="#264790" stroke-width="4" stroke-dasharray="4 4" fill="none"/>
      <circle cx="75" cy="25" r="3" fill="#44A6D9"/>
      <circle cx="85" cy="15" r="2" fill="#F9CC6B"/>
    </svg>
    <div class="flex flex-col justify-center">
      <span class="font-bold text-[10px] tracking-widest text-[#264790] uppercase leading-tight">Drastha</span>
      <span class="font-bold text-[10px] tracking-widest text-[#44A6D9] uppercase leading-tight">Learning</span>
    </div>
  </div>`;
};
</script>

<script>
// For usePage import access inside setup
import { usePage } from '@inertiajs/vue3';
</script>

<template>
  <Head :title="course.title + ' | Drastha Learning'" />

  <GuestLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <!-- Back to Courses Link -->
      <div class="mb-8">
        <Link 
          href="/courses" 
          class="inline-flex items-center gap-2 text-slate-400 hover:text-[#44A6D9] font-semibold text-sm transition-colors"
        >
          &lsaquo; {{ $t('back_to_courses') || 'Kembali ke Daftar Kelas' }}
        </Link>
      </div>

      <div class="flex flex-col lg:flex-row gap-10 items-start">
        
        <!-- LEFT COLUMN: Main Course Info -->
        <div class="flex-1 lg:max-w-[65%] w-full">
          
          <!-- Badges -->
          <div class="flex items-center gap-3 mb-4">
            <span class="bg-[#F9CC6B]/15 text-[#264790] border border-[#264790]/20 rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wider">
              {{ course.category?.name || 'Coding IT Class' }}
            </span>
            <span class="bg-[#1A2B49] text-white rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wider">
              {{ $t('open_registration') || 'Pendaftaran Dibuka' }}
            </span>
          </div>

          <!-- Course Title -->
          <h1 class="text-3xl sm:text-4xl font-extrabold text-[#1A2B49] leading-tight mb-5">
            {{ course.title }}
          </h1>

          <!-- Top Meta -->
          <div class="flex flex-wrap items-center gap-6 text-sm text-slate-500 font-semibold mb-8">
            <div class="flex items-center gap-2">
              <Calendar :size="16" class="text-slate-400" />
              <span>{{ course.sessions || $t('two_sessions_week') || 'Two Session per Week' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Clock :size="16" class="text-slate-400" />
              <span>{{ course.duration || $t('one_hour_session') || '1 Hour for 1 Session' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <MapPin :size="16" class="text-slate-400" />
              <span>{{ course.type || $t('offline_class') || 'Offline Class' }}</span>
            </div>
          </div>

          <!-- Media Card Wrapper -->
          <div class="bg-white p-5 rounded-3xl border border-slate-100/80 shadow-[0_8px_30px_rgba(0,0,0,0.015)] mb-10">
            
            <!-- Media Switcher Tabs (Segmented Control Pill Style) -->
            <div class="flex items-center bg-slate-100/80 p-1.5 rounded-2xl w-full mb-5 border border-slate-200/20">
              <button 
                @click="activeMediaTab = 'image'"
                :class="activeMediaTab === 'image' ? 'bg-white text-[#1A2B49] shadow-sm' : 'text-slate-500 hover:text-[#1A2B49]'"
                class="flex-1 py-3 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 outline-none cursor-pointer"
              >
                <ImageIcon :size="16" /> {{ $t('course_cover') || 'Sampul Kursus' }}
              </button>
              <button 
                @click="activeMediaTab = 'video'"
                :class="activeMediaTab === 'video' ? 'bg-white text-[#1A2B49] shadow-sm' : 'text-slate-500 hover:text-[#1A2B49]'"
                class="flex-1 py-3 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-2 outline-none cursor-pointer"
              >
                <Play :size="16" fill="currentColor" /> {{ $t('intro_video') || 'Intro Video' }}
              </button>
            </div>

            <!-- Banner Visual Panel -->
            <div 
              class="w-full h-80 sm:h-96 rounded-2xl overflow-hidden relative shadow-sm border border-slate-100/50"
            >
              <!-- Image Tab -->
              <template v-if="activeMediaTab === 'image'">
                <div v-if="course.thumbnail" class="w-full h-full">
                  <img :src="`/storage/${course.thumbnail}`" class="w-full h-full object-cover" :alt="course.title" />
                </div>
                
                <!-- Default HTML/CSS Placeholder -->
                <div 
                  v-else
                  class="w-full h-full flex items-center justify-center relative"
                  :style="{ backgroundColor: course.bg_color || '#44A6D9' }"
                >
                  <div class="text-black scale-125 sm:scale-150 transform">
                    <!-- Render code icon default as per screenshot -->
                    <Code :size="80" :stroke-width="2.5" />
                  </div>
                  
                  <!-- Glassmorphism corner design from screenshot -->
                  <div class="absolute -bottom-6 -right-6 text-black opacity-10 transform -rotate-45">
                     <div class="w-32 h-4 bg-black rounded-full mb-3"></div>
                     <div class="w-20 h-4 bg-black rounded-full"></div>
                  </div>
                </div>
              </template>

              <!-- Video Tab -->
              <template v-else-if="activeMediaTab === 'video'">
                <div v-if="youtubeEmbedUrl" class="w-full h-full relative z-0">
                  <iframe 
                    :src="youtubeEmbedUrl"
                    class="w-full h-full border-0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                  ></iframe>
                </div>
                
                <!-- Fallback: No Video Intro -->
                <div 
                  v-else
                  class="w-full h-full flex flex-col items-center justify-center bg-slate-50 p-8 text-center"
                >
                  <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4 text-slate-400">
                    <Play :size="24" class="text-slate-400 ml-0.5" />
                  </div>
                  <h4 class="font-extrabold text-base text-[#1A2B49] mb-1">{{ $t('no_intro_video') || 'Tidak ada Video intro' }}</h4>
                  <p class="text-slate-400 font-semibold text-xs sm:text-sm max-w-xs leading-relaxed">
                    {{ $t('no_intro_video_desc') || 'Kelas ini belum memiliki video cuplikan atau pengenalan. Anda tetap bisa langsung mendaftar kelas ini.' }}
                  </p>
                </div>
              </template>
            </div>
          </div>

          <!-- Section: Tentang Kelas -->
          <div class="mb-10">
            <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-5">{{ $t('about_class') || 'Tentang Kelas' }}</h3>
            
            <div class="flex flex-col gap-6 text-[#1A2B49] leading-relaxed">
              <div>
                <h4 class="font-extrabold text-base mb-1.5">{{ $t('description_label') || 'Deskripsi' }} :</h4>
                <p class="text-slate-500 font-medium text-sm sm:text-base">
                  {{ course.description || $t('course_desc_fallback') || 'Materi kelas yang mengajarkan tentang pemrograman dasar secara asyik, interaktif, dan menyenangkan.' }}
                </p>
              </div>

              <div>
                <h4 class="font-extrabold text-base mb-1.5">{{ $t('age_label') || 'Usia' }} :</h4>
                <p class="text-slate-500 font-medium text-sm sm:text-base">
                  {{ ageLabel }}
                </p>
              </div>

              <div>
                <h4 class="font-extrabold text-base mb-1.5">{{ $t('benefit_label') || 'Benefit' }} :</h4>
                <p class="text-slate-500 font-medium text-sm sm:text-base">
                  {{ courseBenefits || $t('course_benefit_fallback') || 'Modul Lengkap, E-Certificate, Dokumentasi Belajar, Report Study Berkala' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Section: Lokasi -->
          <div class="mb-10">
            <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-5">{{ $t('location_label') || 'Lokasi' }}</h3>
            
            <!-- Map Card Wrapper (Leaflet.js) -->
            <div class="rounded-3xl overflow-hidden border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] mb-5 h-[320px] relative z-0">
              <l-map ref="map" v-model:zoom="zoom" :center="center" :use-global-leaflet="false" style="height: 100%; width: 100%;">
                <l-tile-layer
                  url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                  layer-type="base"
                  name="OpenStreetMap"
                ></l-tile-layer>
                
                <l-marker :lat-lng="center">
                  <l-popup>
                    <div class="text-center font-montserrat">
                      <h4 class="font-extrabold text-[#1A2B49] text-sm">Drastha Learning</h4>
                      <p class="text-xs text-slate-500 mt-1 font-semibold">{{ $t('offline_class_center') || 'Pusat Kelas Offline' }}</p>
                    </div>
                  </l-popup>
                </l-marker>
              </l-map>
            </div>

            <div class="flex items-start gap-3">
              <MapPin :size="20" class="text-[#FF4D4F] shrink-0 mt-0.5" />
              <p class="text-slate-500 font-semibold text-xs sm:text-sm leading-relaxed">
                {{ $t('drastha_address') || 'Drastha Learning, Jl. Budi Luhur Wagir Indah No.B-2, Wagir, Kwangsan, Kec. Sedati, Kabupaten Sidoarjo, Jawa Timur 61253' }}
              </p>
            </div>
          </div>

          <!-- Section: Kurikulum Kelas Accordion collapsible -->
          <div v-if="showContentSummary" class="mb-10">
            <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-5">{{ $t('course_curriculum') || 'Kurikulum Kelas' }} :</h3>
            
            <div class="flex flex-col gap-4">
              <div 
                v-for="(mod, modIdx) in course.modules" 
                :key="mod.id"
                class="bg-white rounded-[1.25rem] border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.015)] overflow-hidden"
              >
                <!-- Accordion Header -->
                <button 
                  @click="toggleModule(mod.id)"
                  class="w-full flex items-center justify-between p-5 text-left hover:bg-slate-50/50 transition-colors outline-none"
                >
                  <div>
                    <h4 class="font-extrabold text-base text-[#1A2B49] mb-1">{{ mod.title }}</h4>
                    <span class="text-xs text-slate-400 font-bold">
                      {{ mod.lessons?.length || 0 }} {{ $t('video_label') || 'Video' }} ({{ getModuleDuration(mod) }} {{ $t('minutes_label') || 'Menit' }})
                    </span>
                  </div>
                  <ChevronDown 
                    :size="18" 
                    :class="{'rotate-180': expandedModules[mod.id]}"
                    class="text-slate-400 transition-transform duration-200" 
                  />
                </button>

                <!-- Accordion Content -->
                <div 
                  v-show="expandedModules[mod.id]"
                  class="p-5 border-t border-slate-50 bg-slate-50/20 flex flex-col gap-3"
                >
                  <div 
                    v-for="(les, lesIdx) in mod.lessons" 
                    :key="les.id"
                    @click="handleAccessLesson(les)"
                    :class="[
                      lesIdx === 0 ? 'bg-[#264790] hover:bg-[#1C356E] text-white shadow-sm' : 'bg-[#F4F7F9] hover:bg-slate-200/70 text-[#1A2B49]',
                      'flex items-center justify-between p-4 rounded-xl transition-all cursor-pointer hover:shadow-sm'
                    ]"
                  >
                    <div class="flex items-center gap-3">
                      <div 
                        :class="lesIdx === 0 ? 'bg-white/10 text-white' : 'bg-white text-[#264790] border border-slate-100'"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 shadow-sm"
                      >
                        <Play :size="12" :stroke-width="3" />
                      </div>
                      <span class="font-bold text-xs sm:text-sm">{{ les.title }}</span>
                    </div>
                    <span 
                      :class="lesIdx === 0 ? 'text-white/80' : 'text-slate-400'"
                      class="text-xs font-bold shrink-0"
                    >
                      {{ les.duration_minutes }} {{ $t('minutes_label') || 'Menit' }}
                    </span>
                  </div>

                  <div v-if="!mod.lessons || mod.lessons.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">
                    {{ $t('no_lessons_module') || 'Belum ada materi pelajaran dalam bab ini.' }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section: Instruktur Kelas -->
          <div v-if="course.instructor" class="mb-10">
            <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-5">{{ $t('course_instructor') || 'Instruktur Kelas' }}</h3>
            
            <!-- 1. Layout PORTRAIT -->
            <div v-if="instructorListLayout === 'portrait'" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col items-center text-center max-w-sm">
              <div class="w-24 h-24 rounded-full bg-slate-100 border-4 border-slate-50 overflow-hidden mb-4 shrink-0 shadow-sm">
                <img :src="course.instructor.avatar || '/images/avatars/default.png'" class="w-full h-full object-cover" :alt="course.instructor.name" />
              </div>
              <h4 class="font-extrabold text-lg text-[#1A2B49] mb-1">{{ course.instructor.name }}</h4>
              <span class="text-xs font-bold text-[#264790] uppercase tracking-wider bg-blue-50 px-3 py-1 rounded-full mb-3">{{ $t('main_instructor') || 'Instruktur Utama' }}</span>
              <p class="text-slate-400 font-medium text-xs leading-relaxed">
                {{ course.instructor.bio || $t('instructor_bio_fallback') || 'Pengajar profesional di bidangnya dengan pengalaman bertahun-tahun membimbing siswa untuk mencapai impian mereka.' }}
              </p>
            </div>

            <!-- 2. Layout COVER -->
            <div v-else-if="instructorListLayout === 'cover'" class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] overflow-hidden max-w-sm flex flex-col relative">
              <div class="h-24 bg-gradient-to-r from-[#264790] to-[#44A6D9] w-full"></div>
              <div class="px-6 pb-6 flex flex-col items-center text-center -mt-12">
                <div class="w-24 h-24 rounded-full bg-white border-4 border-white overflow-hidden mb-3 shrink-0 shadow-md">
                  <img :src="course.instructor.avatar || '/images/avatars/default.png'" class="w-full h-full object-cover" :alt="course.instructor.name" />
                </div>
                <h4 class="font-extrabold text-lg text-[#1A2B49] mb-1">{{ course.instructor.name }}</h4>
                <span class="text-xs font-bold text-[#264790] uppercase tracking-wider mb-2">{{ $t('main_instructor') || 'Instruktur Utama' }}</span>
                <p class="text-slate-400 font-medium text-xs leading-relaxed">
                  {{ course.instructor.bio || $t('instructor_bio_fallback_short') || 'Pengajar profesional di bidangnya dengan pengalaman bertahun-tahun membimbing siswa.' }}
                </p>
              </div>
            </div>

            <!-- 3. Layout MINIMAL -->
            <div v-else-if="instructorListLayout === 'minimal'" class="flex flex-col items-center text-center p-4 max-w-sm">
              <div class="w-20 h-20 rounded-full bg-slate-100 overflow-hidden mb-3 shrink-0 border border-slate-200">
                <img :src="course.instructor.avatar || '/images/avatars/default.png'" class="w-full h-full object-cover" :alt="course.instructor.name" />
              </div>
              <h4 class="font-extrabold text-base text-[#1A2B49] mb-0.5">{{ course.instructor.name }}</h4>
              <span class="text-[11px] font-bold text-slate-400">{{ $t('instructor') || 'Instruktur' }}</span>
            </div>

            <!-- 4. Layout PORTRAIT_HORIZONTAL -->
            <div v-else-if="instructorListLayout === 'portrait_horizontal'" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex gap-5 items-center w-full max-w-xl">
              <div class="w-24 h-24 rounded-full bg-slate-100 border-4 border-slate-50 overflow-hidden shrink-0 shadow-sm">
                <img :src="course.instructor.avatar || '/images/avatars/default.png'" class="w-full h-full object-cover" :alt="course.instructor.name" />
              </div>
              <div class="flex-1">
                <span class="text-[10px] font-bold text-[#264790] uppercase tracking-wider mb-1 block">{{ $t('main_instructor') || 'Instruktur Utama' }}</span>
                <h4 class="font-extrabold text-lg text-[#1A2B49] mb-1.5">{{ course.instructor.name }}</h4>
                <p class="text-slate-400 font-medium text-xs sm:text-sm leading-relaxed">
                  {{ course.instructor.bio || $t('instructor_bio_fallback') || 'Pengajar profesional di bidangnya dengan pengalaman bertahun-tahun membimbing siswa untuk mencapai impian mereka.' }}
                </p>
              </div>
            </div>

            <!-- 5. Layout MINIMAL_HORIZONTAL -->
            <div v-else-if="instructorListLayout === 'minimal_horizontal'" class="flex gap-4 items-center p-3 border border-slate-150 rounded-2xl max-w-md bg-slate-50/50">
              <div class="w-12 h-12 rounded-full bg-slate-100 overflow-hidden shrink-0 border border-slate-200">
                <img :src="course.instructor.avatar || '/images/avatars/default.png'" class="w-full h-full object-cover" :alt="course.instructor.name" />
              </div>
              <div>
                <h4 class="font-extrabold text-sm text-[#1A2B49]">{{ course.instructor.name }}</h4>
                <span class="text-[10px] font-bold text-slate-400">{{ $t('instructor') || 'Instruktur' }}</span>
              </div>
            </div>

          </div>

        </div>

        <!-- RIGHT COLUMN: Sticky Purchase Sidebar -->
        <div class="w-full lg:max-w-[35%] lg:sticky lg:top-28">
          
          <!-- Sticky Box Card -->
          <div class="bg-white rounded-3xl p-8 border border-slate-50 shadow-[0_12px_40px_rgba(0,0,0,0.03)] flex flex-col gap-6">
            
            <div>
              <span class="text-slate-400 text-xs font-bold uppercase tracking-wider block mb-1">{{ $t('registration_fee') || 'BIAYA PENDAFTARAN' }}</span>
              <div class="flex items-baseline gap-1">
                <span class="text-3xl font-extrabold text-[#1A2B49]">Rp{{ formatPrice(course.price) }}</span>
                <span v-if="course.payment_type !== 'one-time'" class="text-slate-400 font-bold text-xs">/ {{ $t('month_label') || 'Bulan' }}</span>
              </div>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col gap-3">
              <!-- If Enrolled, show Start Learning button -->
              <Link 
                v-if="isEnrolled"
                href="/dashboard"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-4 rounded-2xl font-bold text-sm shadow-md transition-colors text-center flex items-center justify-center gap-1.5"
              >
                <Play :size="16" /> {{ $t('start_learning') || 'Mulai Belajar' }}
              </Link>
              
              <!-- Otherwise, show dynamic Register button -->
              <button 
                v-else
                @click="handleRegister"
                :disabled="isProcessing"
                class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-1.5"
              >
                <ShoppingCart :size="16" /> {{ $t('add_to_cart') || 'Tambah ke Keranjang' }}
              </button>

              <button 
                @click="handleShare"
                class="w-full bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-100 py-4 rounded-2xl font-bold text-sm transition-colors flex items-center justify-center gap-1.5"
              >
                <Share2 :size="16" /> {{ $t('share_label') || 'Bagikan' }}
              </button>
            </div>

            <div class="h-px bg-slate-100"></div>

            <!-- Revised Meta attributes matching revised screenshot -->
            <div class="flex flex-col gap-3.5 text-slate-500 font-semibold text-xs sm:text-sm">
              <div class="flex items-center gap-3">
                <BarChart3 :size="18" class="text-slate-400 shrink-0" />
                <span>{{ levelLabel }}</span>
              </div>
              <div class="flex items-center gap-3">
                <GraduationCap :size="18" class="text-slate-400 shrink-0" />
                <span>{{ enrolledCountLabel }}</span>
              </div>
              <div class="flex items-center gap-3">
                <User :size="18" class="text-slate-400 shrink-0" />
                <span>{{ $t('capacity_label') || 'Kapasitas' }}: {{ course.capacity || 20 }} {{ $t('students_class') || 'Siswa / Kelas' }}</span>
              </div>
              <div class="flex items-center gap-3">
                <Clock :size="18" class="text-slate-400 shrink-0" />
                <span>{{ durationLabel }}</span>
              </div>
              <div class="flex items-center gap-3">
                <RotateCw :size="18" class="text-slate-400 shrink-0" />
                <span>{{ lastUpdatedLabel }}</span>
              </div>
              <div class="flex items-center gap-3">
                <Award :size="18" class="text-slate-400 shrink-0" />
                <span>{{ $t('certificate_completion') || 'Certificate of completion' }}</span>
              </div>
            </div>

          </div>

        </div>

      </div>

    </div>

    <!-- CLEAN FOOTER -->
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-32 md:pb-12 mt-16">
      
      <div class="bg-[#FFFFFF] rounded-[2.5rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-50">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-8 mb-12">
          
          <div class="md:col-span-5 flex flex-col gap-6">
            
            <div class="flex items-center gap-2">
              <ApplicationLogo />
            </div>

            <p class="text-[#264790] text-sm md:text-base font-medium leading-relaxed max-w-md">
              {{ $t('hero_subtitle') }}
            </p>

            <div class="flex items-center gap-4">
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Instagram :size="20" :stroke-width="2.5" />
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Twitter :size="20" :stroke-width="2.5" />
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Facebook :size="20" :stroke-width="2.5" />
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Linkedin :size="20" :stroke-width="2.5" />
              </a>
            </div>
          </div>

          <div class="md:col-span-3 flex flex-col gap-5">
            <h4 class="font-extrabold text-[#1A2B49] text-lg">{{ $t('quick_links') || 'Tautan Cepat' }}</h4>
            <ul class="flex flex-col gap-3">
              <li><Link href="/" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('home') || 'Home' }}</Link></li>
              <li><Link href="/courses" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('sub_class') || 'Kelas Kami' }}</Link></li>
              <li><Link href="/contact" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('contact_us_title') || 'Hubungi Kami' }}</Link></li>
              <li><Link href="/about" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('about_us') || 'Tentang Kami' }}</Link></li>
              <li><Link href="/clients" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('client_our') || 'Klien Kami' }}</Link></li>
              <li><Link href="/blogs" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('blog') || 'Blog Kami' }}</Link></li>
            </ul>
          </div>

          <div class="md:col-span-4 flex flex-col gap-5">
            <h4 class="font-extrabold text-[#1A2B49] text-lg">{{ $t('contact_label') || 'Kontak' }}</h4>
            <ul class="flex flex-col gap-3 text-[#264790] text-sm md:text-base font-medium leading-relaxed">
              <li class="font-bold text-[#264790] uppercase tracking-wide">
                PT. DRASTHA BERKAH SENTOSA
              </li>
              <li>031-9960-5068 (Pulsa)</li>
              <li>0812-3485-9768 (WhatsApp)</li>
              <li class="max-w-xs">
                Jl Budi Luhur B/2 Wagir Indah Kwangsan, Sedati Sidoarjo Jawa Timur 61253
              </li>
            </ul>
          </div>

        </div>

        <div class="w-full h-px bg-slate-300/50 mb-6"></div>

        <div class="flex flex-col-reverse md:flex-row justify-between items-center gap-4 text-[#264790] text-xs md:text-sm font-semibold">
          <p>&copy; 2026 Drastha Learning. {{ $t('all_rights_reserved') || 'All Rights Reserved' }}</p>
          
          <div class="flex flex-wrap justify-center gap-4 md:gap-8">
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Privacy Policy</Link>
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Terms of Service</Link>
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Cookies Settings</Link>
          </div>
        </div>

      </div>
    </footer>

    <!-- Payment Success Overlay Modal -->
    <div 
      v-if="showSuccessOverlay" 
      class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl border border-slate-100 relative text-center flex flex-col items-center">
        
        <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5">
          <CheckCircle2 :size="36" />
        </div>

        <h3 class="text-xl font-extrabold text-[#1A2B49] mb-2">{{ $t('registration_success') || 'Pendaftaran Berhasil!' }}</h3>
        <p class="text-slate-400 text-sm font-semibold mb-6">
          {{ $t('registration_success_desc_1') || 'Selamat! Anda telah resmi terdaftar di kelas' }} <b class="text-[#1A2B49]">{{ course.title }}</b>. {{ $t('registration_success_desc_2') || 'Silakan masuk ke Dashboard Siswa Anda untuk memulai pelajaran.' }}
        </p>

        <Link 
          href="/dashboard"
          class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-md transition-colors"
        >
          {{ $t('go_to_dashboard') || 'Masuk ke Dashboard' }}
        </Link>
      </div>
    </div>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
