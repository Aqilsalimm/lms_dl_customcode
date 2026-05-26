<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, GraduationCap, 
  Home, Newspaper, Calendar, Clock, MapPin, Code,
  Play, CheckCircle2, ArrowLeft, Download, Info, Menu, X
} from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
  course: Object,
});

// State Management
const activeTab = ref('resources'); // 'resources' or 'about'
const expandedModules = ref({});
const isSidebarOpen = ref(true);

// State for active lesson (default: very first lesson of first module)
const activeLesson = ref(null);
const activeModule = ref(null);

// Completed lessons state (synced with LocalStorage)
const completedLessons = ref([]);

// Set initial active lesson
if (props.course.modules && props.course.modules.length > 0) {
  // Expand first module by default
  expandedModules.value[props.course.modules[0].id] = true;
  
  if (props.course.modules[0].lessons && props.course.modules[0].lessons.length > 0) {
    activeLesson.value = props.course.modules[0].lessons[0];
    activeModule.value = props.course.modules[0];
  }
}

// Expand/collapse module accordion
const toggleModule = (id) => {
  expandedModules.value[id] = !expandedModules.value[id];
};

// Select a lesson
const selectLesson = (lesson, moduleObj) => {
  activeLesson.value = lesson;
  activeModule.value = moduleObj;
};

// Calculate total module duration
const getModuleDuration = (mod) => {
  if (!mod.lessons) return 0;
  return mod.lessons.reduce((acc, l) => acc + parseInt(l.duration_minutes || 0), 0);
};

// Parse YouTube / Vimeo url into iframe embed url
const getEmbedUrl = computed(() => {
  const url = activeLesson.value?.video_url;
  if (!url) return '';

  // YouTube match
  let youtubeRegExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
  let ytMatch = url.match(youtubeRegExp);
  if (ytMatch && ytMatch[2].length === 11) {
    return `https://www.youtube.com/embed/${ytMatch[2]}?autoplay=1&rel=0`;
  }

  // Vimeo match
  let vimeoRegExp = /vimeo\.com\/(\d+)/;
  let vimeoMatch = url.match(vimeoRegExp);
  if (vimeoMatch) {
    return `https://player.vimeo.com/video/${vimeoMatch[1]}?autoplay=1`;
  }

  return url;
});

// Load completed state from LocalStorage on mount
onMounted(() => {
  const savedCompletions = localStorage.getItem('drastha_course_completions');
  if (savedCompletions) {
    try {
      const completionsMap = JSON.parse(savedCompletions);
      completedLessons.value = completionsMap[props.course.id] || [];
    } catch (e) {
      completedLessons.value = [];
    }
  }
});

// Check if lesson is completed
const isCompleted = (lessonId) => {
  return completedLessons.value.includes(lessonId);
};

// Toggle completion state
const toggleComplete = () => {
  if (!activeLesson.value) return;

  const lId = activeLesson.value.id;
  let list = [...completedLessons.value];

  if (list.includes(lId)) {
    // Mark as incomplete
    list = list.filter(id => id !== lId);
  } else {
    // Mark as complete
    list.push(lId);
  }

  completedLessons.value = list;

  // Save to unified completions map in LocalStorage
  const savedCompletions = localStorage.getItem('drastha_course_completions');
  let completionsMap = {};
  if (savedCompletions) {
    try {
      completionsMap = JSON.parse(savedCompletions);
    } catch (e) {}
  }
  completionsMap[props.course.id] = list;
  localStorage.setItem('drastha_course_completions', JSON.stringify(completionsMap));
};

// Tool tag mapper based on course name
const toolsList = computed(() => {
  const title = props.course.title.toLowerCase();
  if (title.includes('python')) {
    return [
      { name: 'Python', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg' },
      { name: 'VS Code', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vscode/vscode-original.svg' },
      { name: 'Terminal', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/bash/bash-original.svg' }
    ];
  }
  
  // Default stack matching mockup
  return [
    { name: 'Visual Studio Code', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vscode/vscode-original.svg' },
    { name: 'HTML', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/html5/html5-original.svg' },
    { name: 'CSS', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/css3/css3-original.svg' },
    { name: 'Javascript', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/javascript/javascript-original.svg' },
    { name: 'Supabase', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/supabase/supabase-original.svg' },
    { name: 'NodeJs', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg' }
  ];
});

// Logo Helper
const Logo = () => (
  `<div class="flex items-center gap-2">
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
  </div>`
);
</script>

<template>
  <Head :title="`${course.title} | Kelas Belajar | Drastha Learning`" />

  <GuestLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Back to Courses Breadcrumbs -->
      <div class="mb-6 flex justify-between items-center">
        <Link 
          href="/dashboard" 
          class="inline-flex items-center gap-2 text-slate-400 hover:text-[#44A6D9] font-semibold text-xs sm:text-sm transition-colors"
        >
          &lsaquo; Kembali ke Daftar Kelas
        </Link>
      </div>

      <!-- Main Two Column Workspace -->
      <div class="flex flex-col lg:flex-row gap-8 items-start relative">
        
        <!-- SIDEBAR SYLLABUS: Left Column -->
        <div 
          :class="isSidebarOpen ? 'w-full lg:max-w-[32%] flex' : 'hidden lg:hidden'"
          class="flex-col gap-5 shrink-0 transition-all duration-300 w-full"
        >
          
          <!-- Sidebar Toggle Header -->
          <div class="flex items-center gap-3 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm justify-between">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-[#264790]/5 text-[#264790] flex items-center justify-center">
                <Code :size="16" />
              </div>
              <span class="text-[#1A2B49] font-extrabold text-sm">Silabus Belajar</span>
            </div>
            <button 
              @click="isSidebarOpen = false"
              class="lg:hidden text-slate-400 hover:text-slate-600 transition-colors"
            >
              <X :size="18" />
            </button>
          </div>

          <!-- Modules Accordion List -->
          <div class="flex flex-col gap-4 max-h-[80vh] overflow-y-auto pr-1">
            
            <div 
              v-for="(mod, modIdx) in course.modules" 
              :key="mod.id"
              class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
            >
              <!-- Accordion Module Header -->
              <button 
                @click="toggleModule(mod.id)"
                class="w-full flex items-center justify-between p-4 sm:p-5 text-left hover:bg-slate-50/50 transition-colors outline-none"
              >
                <div>
                  <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49] mb-1.5 leading-snug">{{ mod.title }}</h4>
                  <span class="text-[11px] text-slate-400 font-bold block">
                    {{ mod.lessons?.length || 0 }} Video ({{ getModuleDuration(mod) }} Menit)
                  </span>
                </div>
                <ChevronDown 
                  :size="16" 
                  :class="{'rotate-180': expandedModules[mod.id]}"
                  class="text-slate-400 transition-transform duration-200 shrink-0" 
                />
              </button>

              <!-- Accordion Module Lessons List -->
              <div 
                v-show="expandedModules[mod.id]"
                class="p-4 sm:p-5 border-t border-slate-50 bg-slate-50/20 flex flex-col gap-3.5"
              >
                <button 
                  v-for="(les, lesIdx) in mod.lessons" 
                  :key="les.id"
                  @click="selectLesson(les, mod)"
                  :class="activeLesson?.id === les.id ? 'bg-[#264790] text-white shadow-md' : 'bg-[#F4F7F9] text-[#1A2B49] hover:bg-slate-100'"
                  class="w-full flex items-center justify-between p-4 rounded-xl text-left transition-all duration-200 outline-none"
                >
                  <div class="flex items-center gap-3">
                    <!-- Icon badge inside circle -->
                    <div 
                      :class="activeLesson?.id === les.id ? 'bg-white/10 text-white' : (isCompleted(les.id) ? 'bg-emerald-100 text-emerald-600' : 'bg-white text-[#264790] border border-slate-100')"
                      class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 shadow-sm"
                    >
                      <CheckCircle2 v-if="isCompleted(les.id)" :size="14" />
                      <Play v-else :size="11" :stroke-width="3" />
                    </div>
                    <div>
                      <span class="font-bold text-xs leading-snug block">{{ les.title }}</span>
                      <span 
                        :class="activeLesson?.id === les.id ? 'text-white/60' : 'text-slate-400'"
                        class="text-[10px] font-bold mt-0.5 block"
                      >
                        {{ les.duration_minutes }} Menit
                      </span>
                    </div>
                  </div>
                </button>

                <div v-if="!mod.lessons || mod.lessons.length === 0" class="text-center py-4 text-xs font-bold text-slate-400">
                  Belum ada materi pelajaran.
                </div>

              </div>

            </div>

          </div>

        </div>

        <!-- RIGHT WORKSPACE: Video Player & Material Details -->
        <div class="flex-grow w-full lg:max-w-[65%] flex flex-col gap-6">
          
          <!-- Toggle Sidebar Button (when sidebar is hidden) -->
          <div v-if="!isSidebarOpen" class="mb-2 self-start">
            <button 
              @click="isSidebarOpen = true"
              class="inline-flex items-center gap-2 bg-white border border-slate-100 shadow-sm px-4 py-2.5 rounded-xl font-extrabold text-xs text-[#264790] hover:text-[#44A6D9] transition-colors"
            >
              <Menu :size="16" /> Tampilkan Silabus
            </button>
          </div>

          <!-- Video Player Iframe Frame -->
          <div class="rounded-3xl overflow-hidden bg-slate-900 border border-slate-950 shadow-lg aspect-video w-full relative">
            <iframe 
              v-if="getEmbedUrl"
              :src="getEmbedUrl"
              class="w-full h-full absolute inset-0"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
            ></iframe>
            <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-3">
              <Play :size="48" class="text-slate-500 animate-pulse" />
              <span class="text-sm font-semibold">Memuat video materi belajar...</span>
            </div>
          </div>

          <!-- Lesson Title, Subtitle, & Complete Action Row -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
              <h1 class="text-xl sm:text-2xl font-extrabold text-[#1A2B49] leading-snug mb-1">
                {{ activeLesson?.title || 'Materi Belajar' }}
              </h1>
              <p class="text-slate-400 text-xs sm:text-sm font-semibold">
                Materi bagian : {{ activeModule?.title || 'Introduction' }}
              </p>
            </div>

            <!-- "Selesai" Mark Complete Button -->
            <button 
              @click="toggleComplete"
              :class="isCompleted(activeLesson?.id) ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/10' : 'bg-[#264790] hover:bg-[#44A6D9] text-white shadow-[#264790]/10'"
              class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md transition-all shrink-0 select-none outline-none"
            >
              <CheckCircle2 :size="16" /> 
              <span>{{ isCompleted(activeLesson?.id) ? 'Sudah Selesai' : 'Selesai' }}</span>
            </button>
          </div>

          <!-- Tabs Section -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col gap-6">
            
            <!-- Tabs Navigation -->
            <div class="flex gap-4 border-b border-slate-100 pb-3">
              <button 
                @click="activeTab = 'resources'"
                :class="activeTab === 'resources' ? 'text-[#264790] border-b-2 border-[#264790]' : 'text-slate-400 hover:text-slate-600'"
                class="pb-2 font-extrabold text-sm transition-colors outline-none"
              >
                Resources
              </button>
              <button 
                @click="activeTab = 'about'"
                :class="activeTab === 'about' ? 'text-[#264790] border-b-2 border-[#264790]' : 'text-slate-400 hover:text-slate-600'"
                class="pb-2 font-extrabold text-sm transition-colors outline-none"
              >
                About
              </button>
            </div>

            <!-- Tab Content: Resources -->
            <div v-if="activeTab === 'resources'">
              <!-- File downloader card -->
              <a 
                href="#" 
                @click.prevent="alert('Mengunduh paket sumber belajar / assets kelas...')"
                class="inline-flex items-center gap-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/50 p-4 rounded-2xl font-bold text-xs sm:text-sm text-slate-700 transition-colors shadow-sm"
              >
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-[#264790] shadow-sm shrink-0">
                  <Download :size="16" />
                </div>
                <div>
                  <span class="block text-slate-800">Download Asset / Tools Belajar</span>
                  <span class="block text-[10px] text-slate-400 font-bold mt-0.5">Format: ZIP / PDF (Drastha Learning)</span>
                </div>
              </a>
            </div>

            <!-- Tab Content: About -->
            <div v-else class="text-slate-500 font-medium text-xs sm:text-sm leading-relaxed">
              {{ activeLesson?.content || 'Materi video panduan untuk mempersiapkan tools pemrograman yang mendukung pembelajaran secara terstruktur.' }}
            </div>

          </div>

          <!-- Tools Kelas Section -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col gap-5">
            <h3 class="text-lg font-extrabold text-[#1A2B49]">
              Tools Kelas
            </h3>
            
            <div class="flex flex-wrap gap-3">
              <div 
                v-for="tool in toolsList" 
                :key="tool.name"
                class="flex items-center gap-2 bg-slate-50 border border-slate-200/40 px-4 py-2.5 rounded-2xl font-bold text-xs text-slate-600 shadow-sm"
              >
                <img :src="tool.icon" class="w-4 h-4 object-contain shrink-0" :alt="tool.name" />
                <span>{{ tool.name }}</span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- CLEAN FOOTER -->
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mt-16 border-t border-slate-100">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
          <!-- Inline SVG DL Logo -->
          <div class="flex items-center gap-2 mb-4" v-html="Logo()"></div>
          <p class="text-slate-500 font-medium text-xs max-w-sm">
            Platform Learning Management System (LMS) yang dirancang untuk mendukung pembelajaran modern, interaktif, dan berkelanjutan.
          </p>
        </div>

        <div class="flex flex-wrap gap-x-12 gap-y-6">
          <div>
            <h4 class="font-bold text-xs text-[#1A2B49] uppercase tracking-wider mb-3">Tautan Cepat</h4>
            <div class="flex flex-col gap-2 text-xs font-semibold text-slate-400">
              <Link href="/" class="hover:text-[#44A6D9] transition-colors">Home</Link>
              <Link href="/courses" class="hover:text-[#44A6D9] transition-colors">Kelas Kami</Link>
              <Link href="/#hubungi-kami" class="hover:text-[#44A6D9] transition-colors">Hubungi Kami</Link>
            </div>
          </div>
          <div>
            <h4 class="font-bold text-xs text-[#1A2B49] uppercase tracking-wider mb-3">Kontak</h4>
            <p class="text-xs font-semibold text-slate-400 mb-1">PT. DRASTHA BERKAH SENTOSA</p>
            <p class="text-xs font-semibold text-slate-400">Jl. Budi Luhur B/2, Wagir, Kwangsan, Sedati</p>
          </div>
        </div>
      </div>

      <div class="h-px bg-slate-100 my-8"></div>

      <div class="flex flex-col sm:flex-row justify-between items-center text-[10px] font-bold text-slate-400 gap-4">
        <span>&copy; 2026 Drastha Learning. All Rights Reserved</span>
        <div class="flex gap-6">
          <a href="#" class="hover:text-[#44A6D9] transition-colors">Privacy Policy</a>
          <a href="#" class="hover:text-[#44A6D9] transition-colors">Terms of Service</a>
        </div>
      </div>
    </footer>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
