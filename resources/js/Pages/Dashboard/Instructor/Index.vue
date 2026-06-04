<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
  Users, BookOpen, TrendingUp, Trophy, ArrowRight,
  CheckCircle2, AlertCircle, Star
} from 'lucide-vue-next';

const props = defineProps({
  courses: Array,
  metrics: Object,
  recentEnrollments: Array
});
</script>

<template>
  <Head title="Instructor Dashboard" />

  <GuestLayout>
    <DashboardWrapper>
      
      <!-- "Complete Your Profile" Card -->
      <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 mb-8 flex flex-col lg:flex-row gap-8 lg:items-center">
        
        <div class="flex-1">
          <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-6">Complete Your Profile</h3>
          
          <!-- Progress Bar -->
          <div class="flex gap-2 mb-4">
            <div class="h-2 flex-1 rounded-full bg-slate-200"></div>
            <div class="h-2 flex-1 rounded-full bg-slate-200"></div>
            <div class="h-2 flex-1 rounded-full bg-slate-200"></div>
          </div>
          
          <p class="text-slate-500 font-medium text-sm">Please complete profile: <b class="text-[#1A2B49]">0/3</b></p>
        </div>
        
        <!-- Trophy Icon & Checklist -->
        <div class="flex items-center gap-8 lg:w-1/2">
          <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex flex-col items-center justify-center shrink-0 shadow-inner">
            <Trophy :size="32" stroke-width="2.5" />
          </div>
          <div class="flex flex-col gap-3">
            <div class="flex items-center gap-2 text-slate-500 text-sm font-medium hover:text-[#44A6D9] cursor-pointer transition-colors">
              <AlertCircle :size="16" class="text-amber-500" /> Set Your Profile Photo
            </div>
            <div class="flex items-center gap-2 text-slate-500 text-sm font-medium hover:text-[#44A6D9] cursor-pointer transition-colors">
              <AlertCircle :size="16" class="text-amber-500" /> Set Your Bio
            </div>
            <div class="flex items-center gap-2 text-slate-500 text-sm font-medium hover:text-[#44A6D9] cursor-pointer transition-colors">
              <AlertCircle :size="16" class="text-amber-500" /> Set Withdraw Method
            </div>
          </div>
        </div>
      </div>

      <h3 class="text-2xl font-extrabold text-[#1A2B49] mb-6">Dashboard</h3>

      <!-- Metrics Grid (3 cards as per screenshot) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        
        <!-- Metric 1 -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 flex flex-col items-center justify-center text-center transition-transform duration-300 hover:-translate-y-1">
          <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
            <Users :size="28" stroke-width="2.5" />
          </div>
          <h3 class="text-4xl font-extrabold text-[#1A2B49] mb-2">{{ metrics.total_students || 0 }}</h3>
          <p class="text-slate-500 text-sm font-medium">Total Students</p>
        </div>

        <!-- Metric 2 -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 flex flex-col items-center justify-center text-center transition-transform duration-300 hover:-translate-y-1">
          <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
            <BookOpen :size="28" stroke-width="2.5" />
          </div>
          <h3 class="text-4xl font-extrabold text-[#1A2B49] mb-2">{{ metrics.total_courses || 0 }}</h3>
          <p class="text-slate-500 text-sm font-medium">Total Courses</p>
        </div>

        <!-- Metric 3 -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 flex flex-col items-center justify-center text-center transition-transform duration-300 hover:-translate-y-1">
          <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4">
            <TrendingUp :size="28" stroke-width="2.5" />
          </div>
          <h3 class="text-4xl font-extrabold text-[#1A2B49] mb-2">{{ metrics.instructor_earnings || 'Rp0' }}</h3>
          <p class="text-slate-500 text-sm font-medium">Total Earnings</p>
        </div>

      </div>

      <!-- My Courses Table -->
      <div class="mb-6 flex items-center justify-between">
        <h3 class="text-2xl font-extrabold text-[#1A2B49]">My Courses</h3>
        <Link href="/course-builder" class="text-slate-500 hover:text-[#44A6D9] text-sm font-bold transition-colors">View All</Link>
      </div>

      <div class="bg-white rounded-[2rem] overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 mb-10">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-200 text-slate-500 text-[10px] font-extrabold uppercase tracking-widest bg-slate-50">
              <th class="py-4 px-6">COURSE NAME</th>
              <th class="py-4 px-6 text-center">ENROLLED</th>
              <th class="py-4 px-6 text-center">RATING</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="course in courses" :key="course.id" class="hover:bg-slate-50/50 transition-colors group">
              <td class="py-4 px-6 font-bold text-[#1A2B49] text-sm group-hover:text-[#264790] transition-colors">
                {{ course.title }}
              </td>
              <td class="py-4 px-6 text-center font-bold text-slate-500">
                {{ course.enrollments_count || 0 }}
              </td>
              <td class="py-4 px-6 text-center">
                <div class="flex items-center justify-center gap-1 text-slate-300">
                  <Star v-for="i in 5" :key="i" :size="14" />
                </div>
              </td>
            </tr>
            <tr v-if="courses.length === 0">
              <td colspan="3" class="py-10 text-center text-slate-400 font-medium">
                No courses found. Create one to get started!
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>
