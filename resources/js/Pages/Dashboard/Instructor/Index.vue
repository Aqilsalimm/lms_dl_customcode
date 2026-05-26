<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
  Users, BookOpen, TrendingUp, Shield, ArrowRight,
  Plus, Calendar, Award
} from 'lucide-vue-next';

const props = defineProps({
  courses: Array,
  metrics: Object,
  recentEnrollments: Array
});
</script>

<template>
  <Head title="Instructor Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-extrabold text-[#1A2B49] leading-tight">
          Dashboard Pengajar
        </h2>
        <Link 
          :href="route('course-builder.index')"
          class="bg-[#264790] hover:bg-[#44A6D9] text-white px-5 py-2.5 rounded-full font-bold text-sm shadow-md transition-colors flex items-center gap-2"
        >
          <Plus :size="16" /> Kelola & Buat Kelas
        </Link>
      </div>
    </template>

    <div class="py-12 bg-slate-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
          
          <!-- Metric 1: Active Courses -->
          <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
              <BookOpen :size="28" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-0.5">Kelas Aktif</p>
              <h3 class="text-xl font-extrabold text-[#1A2B49]">{{ metrics.total_courses }} Kelas</h3>
            </div>
          </div>

          <!-- Metric 2: Total Students -->
          <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-sky-50 flex items-center justify-center text-sky-600">
              <Users :size="28" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-0.5">Total Siswa Anda</p>
              <h3 class="text-xl font-extrabold text-[#1A2B49]">{{ metrics.total_students }} Siswa</h3>
            </div>
          </div>

          <!-- Metric 3: Gross Revenue -->
          <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
              <TrendingUp :size="28" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-0.5">Total Omset</p>
              <h3 class="text-xl font-extrabold text-[#1A2B49]">{{ metrics.gross_revenue }}</h3>
            </div>
          </div>

          <!-- Metric 4: Instructor Earnings -->
          <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center gap-5 transition-transform duration-300 hover:-translate-y-1">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600">
              <Award :size="28" />
            </div>
            <div>
              <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-0.5">Bagi Hasil Anda (70%)</p>
              <h3 class="text-xl font-extrabold text-[#1A2B49]">{{ metrics.instructor_earnings }}</h3>
            </div>
          </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          <!-- Course List -->
          <div class="lg:col-span-2 bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
            <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
              <BookOpen :size="20" class="text-indigo-600" /> Daftar Kelas Anda
            </h3>
            
            <div class="flex flex-col gap-5">
              <div v-for="course in courses" :key="course.id" class="p-5 rounded-3xl bg-slate-50/50 border border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:border-slate-200 hover:bg-white transition-all duration-300">
                <div class="flex items-center gap-4">
                  <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white" :style="{ backgroundColor: course.bg_color || '#44A6D9' }">
                    <BookOpen :size="24" />
                  </div>
                  <div>
                    <h4 class="font-extrabold text-[#1A2B49] text-base leading-tight mb-1">{{ course.title }}</h4>
                    <div class="flex items-center gap-3 text-xs font-medium text-slate-400">
                      <span>{{ course.level }}</span>
                      <span>•</span>
                      <span>{{ course.modules_count }} Bab</span>
                      <span>•</span>
                      <span>{{ course.lessons_count }} Sesi</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center justify-between sm:justify-end gap-6">
                  <div class="text-right">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Siswa Enrolled</div>
                    <div class="text-lg font-extrabold text-[#1A2B49]">{{ course.enrollments_count }} Siswa</div>
                  </div>
                  <Link 
                    :href="route('course-builder.build', course.id)"
                    class="bg-white hover:bg-[#264790] hover:text-white border border-slate-200 hover:border-[#264790] p-2.5 rounded-2xl text-[#264790] transition-colors"
                  >
                    <ArrowRight :size="18" />
                  </Link>
                </div>
              </div>

              <div v-if="courses.length === 0" class="text-center py-10 text-slate-400 font-medium">
                Belum ada kelas yang dibuat. Silakan klik "Kelola & Buat Kelas" untuk memulai!
              </div>
            </div>
          </div>

          <!-- Recent Enrollments -->
          <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
            <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
              <Users :size="20" class="text-sky-600" /> Pendaftaran Terbaru
            </h3>
            
            <div class="flex flex-col gap-4">
              <div v-for="en in recentEnrollments" :key="en.id" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <div class="font-bold text-sm text-[#1A2B49]">{{ en.user?.name }}</div>
                <div class="text-slate-400 text-xs mb-1.5">{{ en.user?.email }}</div>
                <div class="text-slate-500 font-medium text-[11px] leading-tight flex items-center gap-1.5 mt-1">
                  <Calendar :size="12" class="text-indigo-600" />
                  <span>Enrolled in: <b class="text-[#1A2B49]">{{ en.course?.title }}</b></span>
                </div>
              </div>

              <div v-if="recentEnrollments.length === 0" class="text-center py-6 text-slate-400 font-medium">
                Belum ada pendaftaran siswa.
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
