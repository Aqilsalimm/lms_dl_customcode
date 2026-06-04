<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ChevronDown, Play, ArrowRight, BookOpen } from 'lucide-vue-next';

const props = defineProps({
  enrolledCourses: Array
});

const activeTab = ref('enrolled'); // 'enrolled', 'active', 'completed'

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
  if (activeTab.value === 'enrolled') return props.enrolledCourses;
  if (activeTab.value === 'active') {
    return props.enrolledCourses.filter(c => getProgress(c) > 0 && getProgress(c) < 100);
  }
  if (activeTab.value === 'completed') {
    return props.enrolledCourses.filter(c => getProgress(c) === 100);
  }
  return props.enrolledCourses;
});

</script>

<template>
  <Head title="Enrolled Courses" />

  <GuestLayout>
    <DashboardWrapper>
      
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-[#1A2B49] mb-6">Enrolled Courses</h2>
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <!-- Tabs -->
          <div class="flex items-center gap-3">
            <button 
              @click="activeTab = 'enrolled'"
              :class="activeTab === 'enrolled' ? 'bg-[#1A2B49] text-white shadow-md' : 'bg-white text-[#1A2B49] hover:bg-slate-50 border border-slate-200'"
              class="px-6 py-2.5 rounded-full font-bold text-sm transition-all duration-300"
            >
              Enrolled Courses
            </button>
            <button 
              @click="activeTab = 'active'"
              :class="activeTab === 'active' ? 'bg-[#1A2B49] text-white shadow-md' : 'bg-white text-[#1A2B49] hover:bg-slate-50 border border-slate-200'"
              class="px-6 py-2.5 rounded-full font-bold text-sm transition-all duration-300"
            >
              Active Courses
            </button>
            <button 
              @click="activeTab = 'completed'"
              :class="activeTab === 'completed' ? 'bg-[#1A2B49] text-white shadow-md' : 'bg-white text-[#1A2B49] hover:bg-slate-50 border border-slate-200'"
              class="px-6 py-2.5 rounded-full font-bold text-sm transition-all duration-300"
            >
              Completed Courses
            </button>
          </div>

          <!-- Filter -->
          <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium text-sm">Type:</span>
            <button class="bg-white border border-slate-200 px-4 py-2 rounded-full font-bold text-sm text-[#1A2B49] flex items-center gap-2 hover:bg-slate-50 transition-colors shadow-sm">
              Courses <ChevronDown :size="16" />
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredCourses.length === 0" class="flex flex-col items-center justify-center py-20 bg-transparent">
        <div class="relative w-64 h-64 mb-6">
          <svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
            <!-- Background Elements -->
            <path d="M100 350 L300 350" stroke="#E2E8F0" stroke-width="2" stroke-linecap="round"/>
            <path d="M80 320 C80 320 90 280 110 320" fill="#F1F5F9"/>
            <path d="M300 330 C300 330 310 290 330 330" fill="#F1F5F9"/>
            <!-- Mailbox Post -->
            <rect x="230" y="220" width="16" height="130" fill="#CBD5E1" rx="2"/>
            <rect x="234" y="220" width="8" height="130" fill="#E2E8F0"/>
            <!-- Mailbox Body -->
            <path d="M130 180 C130 130 270 130 270 180 L270 250 L130 250 Z" fill="#E2E8F0"/>
            <path d="M270 180 C270 130 300 130 300 180 L300 250 L270 250 Z" fill="#CBD5E1"/>
            <!-- Mailbox Door / Front -->
            <path d="M130 180 C130 130 100 130 100 180 L100 250 C100 300 130 300 130 250 Z" fill="#CBD5E1"/>
            <path d="M130 180 C130 130 110 130 110 180 L110 250 C110 280 130 280 130 250 Z" fill="#94A3B8"/>
            <!-- Mailbox Flag -->
            <path d="M260 170 L260 140 L280 140 L280 155 L264 155 L264 170 Z" fill="#3B82F6"/>
            <!-- Spider Web -->
            <path d="M110 160 L140 160 M120 150 L130 170 M115 155 L135 165" stroke="#F8FAFC" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M115 155 Q125 160 135 165 Q130 170 120 170 Q115 165 110 160" stroke="#F8FAFC" stroke-width="1" fill="none"/>
            <circle cx="125" cy="160" r="1.5" fill="#94A3B8"/>
            <!-- Fly -->
            <path d="M220 100 Q225 95 230 100 Q225 105 220 100" fill="#CBD5E1"/>
            <path d="M222 100 Q215 90 220 85 Q225 90 222 100" fill="#E2E8F0"/>
            <path d="M228 100 Q235 90 230 85 Q225 90 228 100" fill="#E2E8F0"/>
            <path d="M200 110 C190 120 200 140 210 130" stroke="#E2E8F0" stroke-width="1" stroke-dasharray="2 2" fill="none"/>
          </svg>
        </div>
        <p class="text-slate-400 font-medium text-sm">No Data Available in this Section</p>
      </div>

      <!-- Courses Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="course in filteredCourses" 
          :key="course.id"
          class="bg-white rounded-[2rem] border border-slate-100 p-6 flex flex-col justify-between hover:border-slate-200 hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-300 group"
        >
          <div>
            <div class="flex items-center justify-between mb-5">
              <span class="bg-[#F9CC6B] text-[#1A2B49] text-[10px] font-extrabold px-3 py-1.5 rounded-lg uppercase tracking-wider">
                Kelas {{ course.level }}
              </span>
              <span class="text-slate-400 text-xs font-bold">{{ course.lessons_count }} Sesi</span>
            </div>
            
            <h4 class="font-extrabold text-[#1A2B49] text-base leading-tight mb-2 min-h-[2.5rem] line-clamp-2 group-hover:text-[#264790] transition-colors">
              {{ course.title }}
            </h4>
            <p class="text-slate-400 text-xs font-medium mb-6">Oleh: <span class="font-bold text-slate-500">{{ course.instructor_name }}</span></p>
          </div>

          <div>
            <!-- Progress Bar -->
            <div class="mb-5">
              <div class="flex items-center justify-between text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">
                 <span>Progres Belajar</span>
                 <span class="text-[#264790]">{{ getProgress(course) }}%</span>
              </div>
              <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                 <div class="h-full bg-gradient-to-r from-[#264790] to-[#44A6D9] rounded-full transition-all duration-1000 ease-out" :style="{ width: getProgress(course) + '%' }"></div>
              </div>
            </div>

            <Link 
              :href="`/courses/${course.slug}/learn`"
              class="w-full bg-[#F4F7F9] hover:bg-[#264790] text-[#264790] hover:text-white border border-transparent py-3 rounded-2xl font-bold text-sm transition-colors flex items-center justify-center gap-1.5"
            >
              Lanjutkan <ArrowRight :size="16" />
            </Link>
          </div>
        </div>
      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>
