<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
  CheckCircle, HelpCircle, Activity, PlayCircle, MessageSquare
} from 'lucide-vue-next';

const props = defineProps({
  telemetry: Object
});

const maxWatchMinutes = computed(() => {
  if (!props.telemetry?.watch_time) return 60;
  const max = Math.max(...props.telemetry.watch_time.map(d => d.minutes), 0);
  return max > 0 ? max : 60;
});

const chartPoints = computed(() => {
  if (!props.telemetry?.watch_time) return [];
  const width = 500;
  const height = 180;
  const paddingX = 40;
  const paddingY = 25;
  const maxVal = maxWatchMinutes.value;
  
  return props.telemetry.watch_time.map((d, i) => {
    const x = paddingX + (i * (width - 2 * paddingX) / 6);
    const y = height - paddingY - (d.minutes / maxVal * (height - 2 * paddingY));
    return { x, y, minutes: d.minutes, date: d.date };
  });
});

const linePath = computed(() => {
  const points = chartPoints.value;
  if (points.length === 0) return '';
  return points.reduce((path, p, i) => {
    return path + `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`;
  }, '');
});

const areaPath = computed(() => {
  const points = chartPoints.value;
  if (points.length === 0) return '';
  const height = 180;
  const paddingY = 25;
  const first = points[0];
  const last = points[points.length - 1];
  let path = `M ${first.x} ${height - paddingY}`;
  points.forEach(p => {
    path += ` L ${p.x} ${p.y}`;
  });
  path += ` L ${last.x} ${height - paddingY} Z`;
  return path;
});
</script>

<template>
  <Head title="Learning Progress" />

  <GuestLayout>
    <DashboardWrapper>
      
      <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-[#1A2B49] mb-2">Learning Progress</h2>
        <p class="text-slate-500 text-sm font-medium">Pantau statistik aktivitas belajar, waktu menonton video, dan topik utama Anda.</p>
      </div>

      <div v-if="telemetry" class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        <!-- Latest Progress Card -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#264790]"></span>
                <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49]">Latest Progress</h4>
              </div>
              <span class="text-[10px] bg-slate-50 text-slate-400 font-bold px-2.5 py-1 rounded-lg">Last 7 Days</span>
            </div>
            
            <div class="flex flex-col gap-4">
              <div 
                v-for="log in telemetry.latest_progress" 
                :key="log.id" 
                class="flex items-start gap-3 p-3 rounded-2xl hover:bg-slate-50/50 transition-all duration-200"
              >
                <div class="w-8 h-8 rounded-full bg-[#264790]/5 flex items-center justify-center text-[#264790] shrink-0 mt-0.5">
                  <PlayCircle v-if="log.activity_type === 'video_progress'" :size="16" />
                  <CheckCircle v-else :size="16" class="text-emerald-600" />
                </div>
                <div class="flex-grow min-w-0">
                  <h5 class="font-bold text-xs text-[#1A2B49] truncate leading-tight">{{ log.lesson_title }}</h5>
                  <p class="text-[10px] text-slate-400 font-bold truncate mt-0.5">{{ log.course_title }}</p>
                  <p class="text-[9px] text-[#44A6D9] font-extrabold mt-1">
                    {{ log.activity_type === 'video_progress' ? `Belajar ${log.watch_minutes} Menit` : 'Selesai diakses' }} • {{ log.created_at_formatted }}
                  </p>
                </div>
              </div>
              <div v-if="!telemetry.latest_progress || telemetry.latest_progress.length === 0" class="text-center py-8 text-xs font-bold text-slate-400">
                Belum ada catatan aktivitas belajar terbaru.
              </div>
            </div>
          </div>
          
          <!-- Forum & QnA Panel -->
          <div class="mt-6 pt-5 border-t border-slate-100/80 flex flex-col gap-2">
            <h5 class="text-[10px] uppercase tracking-wider font-extrabold text-slate-450 mb-1.5">Akses Cepat Panel Belajar</h5>
            <div class="grid grid-cols-2 gap-3">
              <Link 
                href="/blogs" 
                class="flex items-center justify-center gap-1.5 bg-[#264790]/5 hover:bg-[#264790]/10 text-[#264790] py-3 px-3 rounded-xl text-[11px] font-extrabold transition-all"
              >
                <MessageSquare :size="13" /> Forum Diskusi
              </Link>
              <Link 
                :href="route('dashboard.qa')" 
                class="flex items-center justify-center gap-1.5 bg-[#44A6D9]/5 hover:bg-[#44A6D9]/10 text-[#44A6D9] py-3 px-3 rounded-xl text-[11px] font-extrabold transition-all"
              >
                <HelpCircle :size="13" /> Kolom QnA
              </Link>
            </div>
          </div>
        </div>

        <!-- Most Topics & Watch Time Charts -->
        <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8 bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          
          <!-- Most Topics -->
          <div class="flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#F9CC6B]"></span>
                  <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49]">Most Topics</h4>
                </div>
                <span class="text-[10px] bg-slate-50 text-slate-400 font-bold px-2.5 py-1 rounded-lg">Last 7 Days</span>
              </div>
              
              <div class="flex flex-col gap-4 mt-2">
                <div 
                  v-for="(t, idx) in telemetry.most_topics" 
                  :key="t.topic" 
                  class="flex flex-col gap-2"
                >
                  <div class="flex justify-between items-center text-xs">
                    <div class="flex items-center gap-2 min-w-0">
                      <span 
                        :class="idx === 0 ? 'bg-[#264790]' : (idx === 1 ? 'bg-[#F9CC6B]' : 'bg-[#44A6D9]')"
                        class="w-2 h-2 rounded-full shrink-0"
                      ></span>
                      <span class="font-bold text-[#1A2B49] truncate">{{ t.topic }}</span>
                    </div>
                    <span class="font-extrabold text-slate-500 shrink-0">{{ t.percentage }}%</span>
                  </div>
                  
                  <!-- Progress Bar -->
                  <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div 
                      :class="idx === 0 ? 'bg-[#264790]' : (idx === 1 ? 'bg-[#F9CC6B]' : 'bg-[#44A6D9]')"
                      :style="{ width: `${t.percentage}%` }"
                      class="h-full rounded-full transition-all duration-500"
                    ></div>
                  </div>
                  <span class="text-[10px] text-slate-400 font-bold">Total waktu tonton: {{ t.minutes }} menit</span>
                </div>
                <div v-if="!telemetry.most_topics || telemetry.most_topics.length === 0" class="text-center py-8 text-xs font-bold text-slate-400">
                  Belum ada pembagian topik belajar.
                </div>
              </div>
            </div>
            
            <div class="bg-[#264790]/5 p-4 rounded-2xl border border-slate-100 flex items-start gap-3 text-slate-500 text-xs font-semibold leading-relaxed mt-4 md:mt-0">
              <Activity :size="16" class="text-[#264790] shrink-0 mt-0.5 animate-pulse" />
              <p>Topik yang paling sering Anda pelajari membantu pembentukan keahlian terfokus Anda secara terarah.</p>
            </div>
          </div>

          <!-- Watch Time Chart -->
          <div class="flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-[#44A6D9]"></span>
                  <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49]">Watch Time (m)</h4>
                </div>
                <span class="text-[10px] bg-slate-50 text-slate-400 font-bold px-2.5 py-1 rounded-lg">Last 7 Days</span>
              </div>

              <!-- SVG Line Chart -->
              <div class="w-full relative">
                <svg viewBox="0 0 500 180" class="w-full h-auto overflow-visible">
                  <defs>
                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="#44A6D9" stop-opacity="0.3"/>
                      <stop offset="100%" stop-color="#44A6D9" stop-opacity="0"/>
                    </linearGradient>
                  </defs>
                  
                  <!-- Grid lines -->
                  <line x1="40" y1="25" x2="460" y2="25" stroke="#f1f5f9" stroke-width="1.5" />
                  <line x1="40" y1="77.5" x2="460" y2="77.5" stroke="#f1f5f9" stroke-width="1.5" />
                  <line x1="40" y1="130" x2="460" y2="130" stroke="#f1f5f9" stroke-width="1.5" />
                  <line x1="40" y1="155" x2="460" y2="155" stroke="#cbd5e1" stroke-width="1.5" />

                  <!-- Area Fill -->
                  <path :d="areaPath" fill="url(#chartGrad)" />

                  <!-- Line Path -->
                  <path :d="linePath" fill="none" stroke="#44A6D9" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />

                  <!-- Nodes/Dots -->
                  <g v-for="(p, i) in chartPoints" :key="i">
                    <circle :cx="p.x" :cy="p.y" r="8" fill="#44A6D9" fill-opacity="0.15" />
                    <circle :cx="p.x" :cy="p.y" r="5" fill="#ffffff" stroke="#44A6D9" stroke-width="2.5" />
                    <text :x="p.x" :y="p.y - 12" text-anchor="middle" class="text-[9px] font-extrabold fill-slate-700 select-none">{{ p.minutes }}m</text>
                    <text :x="p.x" y="172" text-anchor="middle" class="text-[9px] font-bold fill-slate-400 select-none">{{ p.date }}</text>
                  </g>
                </svg>
              </div>
            </div>
            
            <div class="mt-4 border-t border-slate-100 pt-3 flex justify-between items-center text-[10px] text-slate-400 font-extrabold uppercase">
              <span>Total Waktu Belajar:</span>
              <span class="text-[#264790] text-xs font-black">
                {{ telemetry.watch_time.reduce((acc, d) => acc + d.minutes, 0) }} Menit
              </span>
            </div>
          </div>

        </div>
      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>
