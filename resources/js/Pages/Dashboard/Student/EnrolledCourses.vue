<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ChevronDown, Play, ArrowRight, BookOpen, Search, RotateCcw, Calendar, Video } from 'lucide-vue-next';

const props = defineProps({
  enrolledCourses: Array
});

const activeTab = ref('enrolled'); // 'enrolled', 'active', 'completed'
const selectedType = ref('async'); // 'async', 'live_class'
const showTypeDropdown = ref(false);
const searchQuery = ref('');

const getProgress = (course) => {
  const savedCompletions = localStorage.getItem('drastha_course_completions');
  if (!savedCompletions) return 0;
  try {
    const completionsMap = JSON.parse(savedCompletions);
    const completed = completionsMap[course.id] || [];
    if (!course.lessons_count || course.lessons_count === 0) return 0;
    const pct = Math.round((completed.length / course.lessons_count) * 100);
    return Math.min(pct, 100);
  } catch (e) {
    return 0;
  }
};

const filteredCourses = computed(() => {
  let list = props.enrolledCourses;
  
  // Filter by Type
  list = list.filter(c => c.course_type === selectedType.value);

  // Filter by Tab
  if (activeTab.value === 'active') {
    list = list.filter(c => getProgress(c) > 0 && getProgress(c) < 100);
  } else if (activeTab.value === 'completed') {
    list = list.filter(c => getProgress(c) === 100);
  }

  // Filter by Search (especially for Live Class)
  if (searchQuery.value) {
    list = list.filter(c => c.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
  }

  return list;
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
  <Head title="Enrolled Courses" />

  <GuestLayout>
    <DashboardWrapper>
      
      <div class="mb-8">
        <div class="flex flex-col gap-1 mb-6">
            <h2 class="text-3xl font-extrabold text-[#1A2B49]">{{ selectedType === 'async' ? 'Enrolled Courses' : 'My Live Classes' }}</h2>
            <p v-if="selectedType === 'live_class'" class="text-slate-500 text-sm font-medium">Ikuti sesi live via Zoom bersama mentor berpengalaman untuk belajar secara interaktif</p>
        </div>
        
        <!-- Live Class Search Bar -->
        <div v-if="selectedType === 'live_class'" class="flex flex-wrap items-center gap-4 mb-8">
            <div class="relative flex-1 min-w-[280px]">
                <input 
                    v-model="searchQuery"
                    type="text" 
                    placeholder="Cari live class..." 
                    class="w-full bg-white border border-slate-200 rounded-full py-3.5 pl-12 pr-4 text-sm font-medium text-[#1A2B49] focus:outline-none focus:ring-2 focus:ring-[#264790]/20 focus:border-[#264790] transition-all shadow-sm"
                />
                <Search class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
            </div>
            <button class="bg-[#264790] hover:bg-[#1A2B49] text-white px-8 py-3.5 rounded-full font-bold text-sm transition-all shadow-md active:scale-95">
                Search
            </button>
            <button @click="searchQuery = ''" class="bg-[#EF4444] hover:bg-red-700 text-white px-8 py-3.5 rounded-full font-bold text-sm transition-all shadow-md active:scale-95 flex items-center gap-2">
                <RotateCcw :size="16" /> Reset
            </button>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <!-- Tabs -->
          <div class="flex items-center gap-3">
            <button 
              @click="activeTab = 'enrolled'"
              :class="activeTab === 'enrolled' ? 'bg-[#1A2B49] text-white shadow-md' : 'bg-white text-[#1A2B49] hover:bg-slate-50 border border-slate-200'"
              class="px-6 py-2.5 rounded-full font-bold text-sm transition-all duration-300"
            >
              {{ selectedType === 'async' ? 'Enrolled Courses' : 'All Live Classes' }}
            </button>
            <button 
              v-if="selectedType === 'async'"
              @click="activeTab = 'active'"
              :class="activeTab === 'active' ? 'bg-[#1A2B49] text-white shadow-md' : 'bg-white text-[#1A2B49] hover:bg-slate-50 border border-slate-200'"
              class="px-6 py-2.5 rounded-full font-bold text-sm transition-all duration-300"
            >
              Active Courses
            </button>
            <button 
              v-if="selectedType === 'async'"
              @click="activeTab = 'completed'"
              :class="activeTab === 'completed' ? 'bg-[#1A2B49] text-white shadow-md' : 'bg-white text-[#1A2B49] hover:bg-slate-50 border border-slate-200'"
              class="px-6 py-2.5 rounded-full font-bold text-sm transition-all duration-300"
            >
              Completed Courses
            </button>
          </div>

          <!-- Filter Type Dropdown -->
          <div class="flex items-center gap-2 relative">
            <span class="text-slate-500 font-medium text-sm">Type:</span>
            <button 
                @click="showTypeDropdown = !showTypeDropdown"
                class="bg-white border border-slate-200 px-5 py-2.5 rounded-full font-extrabold text-sm text-[#1A2B49] flex items-center gap-2 hover:bg-slate-50 transition-colors shadow-sm min-w-[140px] justify-between"
            >
              {{ selectedType === 'async' ? 'Courses' : 'Live Class' }} 
              <ChevronDown :size="16" :class="{'rotate-180': showTypeDropdown}" class="transition-transform" />
            </button>
            
            <div v-if="showTypeDropdown" class="absolute right-0 top-full mt-2 w-48 bg-white border border-slate-100 rounded-2xl shadow-xl z-50 overflow-hidden py-1">
                <button 
                    @click="selectedType = 'async'; showTypeDropdown = false"
                    :class="selectedType === 'async' ? 'bg-slate-50 text-[#264790]' : 'text-slate-600 hover:bg-slate-50'"
                    class="w-full text-left px-5 py-3 text-sm font-bold transition-colors flex items-center gap-2"
                >
                    <BookOpen :size="16" /> Courses
                </button>
                <button 
                    @click="selectedType = 'live_class'; showTypeDropdown = false; activeTab = 'enrolled'"
                    :class="selectedType === 'live_class' ? 'bg-slate-50 text-[#264790]' : 'text-slate-600 hover:bg-slate-50'"
                    class="w-full text-left px-5 py-3 text-sm font-bold transition-colors flex items-center gap-2"
                >
                    <Video :size="16" /> Live Class
                </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredCourses.length === 0" class="flex flex-col items-center justify-center py-20 bg-transparent">
        <div class="relative w-64 h-64 mb-6 opacity-40">
          <svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
            <path d="M100 350 L300 350" stroke="#E2E8F0" stroke-width="2" stroke-linecap="round"/>
            <path d="M130 180 C130 130 270 130 270 180 L270 250 L130 250 Z" fill="#E2E8F0"/>
            <path d="M270 180 C270 130 300 130 300 180 L300 250 L270 250 Z" fill="#CBD5E1"/>
            <path d="M130 180 C130 130 100 130 100 180 L100 250 C100 300 130 300 130 250 Z" fill="#CBD5E1"/>
          </svg>
        </div>
        <h3 class="text-xl font-extrabold text-[#1A2B49] mb-2">{{ selectedType === 'async' ? 'Belum ada course' : 'Belum ada live class' }}</h3>
        <p class="text-slate-400 font-medium text-sm text-center max-w-sm mb-8">
            {{ selectedType === 'async' ? 'Kamu belum terdaftar di course manapun. Yuk temukan course yang sesuai!' : 'Kamu belum terdaftar di live class manapun. Yuk temukan live class yang sesuai!' }}
        </p>
        <Link 
            :href="selectedType === 'async' ? '/courses' : '/live-classes'"
            class="bg-[#264790] text-white px-10 py-4 rounded-full font-black text-sm shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all active:scale-95"
        >
            {{ selectedType === 'async' ? 'Jelajahi Course' : 'Jelajahi Live Class' }}
        </Link>
      </div>

      <!-- Courses Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="course in filteredCourses" 
          :key="course.id"
          class="bg-white rounded-[2.5rem] border border-slate-100 p-8 flex flex-col justify-between hover:border-[#264790]/20 hover:shadow-[0_20px_50px_rgba(38,71,144,0.08)] transition-all duration-500 group relative overflow-hidden"
        >
          <!-- Live Class Decoration -->
          <div v-if="selectedType === 'live_class'" class="absolute -right-4 -top-4 w-24 h-24 bg-[#264790]/5 rounded-full blur-2xl group-hover:bg-[#264790]/10 transition-colors"></div>

          <div>
            <div class="flex items-center justify-between mb-6">
              <span class="bg-[#F9CC6B] text-[#1A2B49] text-[10px] font-black px-4 py-2 rounded-xl uppercase tracking-widest shadow-sm">
                {{ selectedType === 'async' ? 'Kelas Umum' : 'Workshop' }}
              </span>
              <div class="flex items-center gap-2">
                <span class="text-slate-400 text-xs font-bold">{{ course.lessons_count }} Sesi</span>
              </div>
            </div>
            
            <h4 class="font-black text-[#1A2B49] text-lg leading-tight mb-3 min-h-[3rem] line-clamp-2 group-hover:text-[#264790] transition-colors">
              {{ course.title }}
            </h4>
            
            <div class="flex items-center gap-2 mb-8">
                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[#264790] font-black text-xs border border-white shadow-sm">
                    {{ course.instructor_name.charAt(0) }}
                </div>
                <p class="text-slate-500 text-xs font-semibold">Oleh: <span class="text-[#1A2B49]">{{ course.instructor_name }}</span></p>
            </div>

            <!-- Live Class Info Section -->
            <div v-if="selectedType === 'live_class'" class="space-y-4 mb-8 bg-slate-50/80 p-5 rounded-[1.5rem] border border-slate-100/50">
                <div class="flex items-center gap-3 text-slate-600">
                    <Calendar :size="18" class="text-[#264790]" />
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Jadwal Sesi</span>
                        <span class="text-xs font-extrabold text-[#1A2B49]">{{ formatDate(course.start_date) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-slate-600">
                    <Video :size="18" class="text-[#264790]" />
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Metode</span>
                        <span class="text-xs font-extrabold text-[#1A2B49]">Zoom Meeting</span>
                    </div>
                </div>
            </div>
          </div>

          <div>
            <!-- Progress Bar (Only for Courses) -->
            <div v-if="selectedType === 'async'" class="mb-8 bg-slate-50/50 p-4 rounded-2xl border border-slate-100/50">
              <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">
                 <span>Progres Belajar</span>
                 <span class="text-[#264790]">{{ getProgress(course) }}%</span>
              </div>
              <div class="w-full h-3 bg-white border border-slate-100 rounded-full overflow-hidden shadow-inner">
                 <div class="h-full bg-gradient-to-r from-[#264790] via-[#44A6D9] to-[#264790] bg-[length:200%_100%] animate-shimmer rounded-full transition-all duration-1000 ease-out" :style="{ width: getProgress(course) + '%' }"></div>
              </div>
            </div>

            <Link 
              v-if="selectedType === 'async'"
              :href="`/courses/${course.slug}/learn`"
              target="_blank"
              class="w-full bg-[#F4F7F9] hover:bg-[#264790] text-[#264790] hover:text-white border border-transparent py-4 rounded-[1.5rem] font-black text-sm transition-all flex items-center justify-center gap-2 group/btn shadow-sm hover:shadow-lg active:scale-95"
            >
              Lanjutkan <ArrowRight :size="18" class="group-hover/btn:translate-x-1 transition-transform" />
            </Link>
            
            <a 
              v-else
              :href="course.meeting_url || '#'"
              target="_blank"
              class="w-full bg-[#264790] hover:bg-[#1A2B49] text-white py-4 rounded-[1.5rem] font-black text-sm transition-all flex items-center justify-center gap-2 group/btn shadow-lg hover:shadow-[#264790]/25 active:scale-95"
            >
              <Video :size="18" /> Gabung Sesi Live <ArrowRight :size="18" class="group-hover/btn:translate-x-1 transition-transform" />
            </a>
          </div>
        </div>
      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>

<style scoped>
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.animate-shimmer {
  animation: shimmer 3s linear infinite;
}
</style>
