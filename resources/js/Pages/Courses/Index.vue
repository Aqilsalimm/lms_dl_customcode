<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { 
  Search, Calendar, Clock, MapPin, Code, BookText, Globe, ChevronDown, Calculator 
} from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

// Leaflet.js setup
import 'leaflet/dist/leaflet.css';
import { LMap, LTileLayer, LMarker, LPopup } from '@vue-leaflet/vue-leaflet';

const zoom = ref(15);
const center = ref([-7.4008, 112.7592]);

const props = defineProps({
  courses: Array
});

// State for active search query
const searchQuery = ref('');

// Computed activeFilter dynamically listening to Ziggy query parameters
const activeFilter = computed({
  get() {
    return usePage().props.ziggy?.query?.filter || new URLSearchParams(window.location.search).get('filter') || 'Semua Kursus';
  },
  set(newValue) {
    if (newValue === 'Semua Kursus') {
      router.get('/courses');
    } else {
      router.get('/courses', { filter: newValue });
    }
  }
});

// Clear active filter query
const clearFilter = () => {
  activeFilter.value = 'Semua Kursus';
};

const formatPrice = (price) => {
  if (!price) return 'Rp 500.000';
  const num = parseFloat(price);
  if (isNaN(num)) return price;
  return 'Rp ' + Math.round(num).toLocaleString('id-ID');
};

// Course Fallback Data
const rawCourses = computed(() => {
  const data = props.courses?.data || props.courses;
  if (data && data.length > 0) {
    return data;
  }
  return [
    {
      id: 1, title: 'Python Class : Pemrograman dan Perkenalan Bahasa Python', level: 'Umum', slug: 'python-class',
      bg_color: '#FF4D4F', icon_type: 'code', sessions: 'Two Session per Week', duration: '1 Hour for 1 Session', type: 'Offline Class', price: 500000, period: '/ Bulan'
    },
    {
      id: 2, title: 'Website Class : Pemrograman Website dengan HTML dan CSS', level: 'SMA', slug: 'website-class',
      bg_color: '#44A6D9', icon_type: 'code', sessions: 'Two Session per Week', duration: '1 Hour for 1 Session', type: 'Offline Class', price: 500000, period: '/ Bulan'
    },
    {
      id: 3, title: 'Python Class : Pemrograman dan Perkenalan Bahasa Python', level: 'Umum', slug: 'python-class',
      bg_color: '#FF4D4F', icon_type: 'code', sessions: 'Two Session per Week', duration: '1 Hour for 1 Session', type: 'Offline Class', price: 500000, period: '/ Bulan'
    },
    {
      id: 4, title: 'Website Class : Pemrograman Website dengan HTML dan CSS', level: 'SMP', slug: 'website-class',
      bg_color: '#44A6D9', icon_type: 'code', sessions: 'Two Session per Week', duration: '1 Hour for 1 Session', type: 'Offline Class', price: 500000, period: '/ Bulan'
    },
    {
      id: 5, title: 'Website Class : Pemrograman Website dengan HTML dan CSS', level: 'SD', slug: 'website-class',
      bg_color: '#44A6D9', icon_type: 'code', sessions: 'Two Session per Week', duration: '1 Hour for 1 Session', type: 'Offline Class', price: 500000, period: '/ Bulan'
    },
    {
      id: 6, title: 'Python Class : Pemrograman dan Perkenalan Bahasa Python', level: 'Umum', slug: 'python-class',
      bg_color: '#FF4D4F', icon_type: 'code', sessions: 'Two Session per Week', duration: '1 Hour for 1 Session', type: 'Offline Class', price: 500000, period: '/ Bulan'
    }
  ];
});

// Reactively filter courses list based on header query and search query
const filteredCourses = computed(() => {
  let result = rawCourses.value;

  if (activeFilter.value !== 'Semua Kursus') {
    result = result.filter(course => course.level === activeFilter.value);
  }

  if (searchQuery.value) {
    result = result.filter(course => course.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
  }

  return result;
});

const gridColsClass = computed(() => {
  const cols = parseInt(usePage().props.settings?.course_columns || 3);
  if (cols === 1) return 'grid-cols-1';
  if (cols === 2) return 'grid-cols-1 sm:grid-cols-2';
  if (cols === 4) return 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4';
  return 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3';
});
</script>

<template>
  <Head title="Semua Kursus | Drastha Learning" />

  <GuestLayout>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      
      <!-- Filter and search bar section -->
      <div class="flex flex-col md:flex-row gap-4 mb-8">
        <div class="flex-grow bg-white rounded-full px-5 py-3.5 flex items-center gap-3 shadow-[0_4px_15px_rgb(0,0,0,0.02)] border border-slate-100/50">
          <Search :size="20" class="text-slate-400" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Mau Belajar apa?" 
            class="bg-transparent w-full outline-none text-[#1A2B49] placeholder-slate-400 font-semibold text-sm border-none focus:ring-0 py-0"
          >
        </div>

        <div class="w-full md:w-56 bg-white rounded-full px-5 py-3.5 flex items-center justify-between shadow-[0_4px_15px_rgb(0,0,0,0.02)] border border-slate-100/50 cursor-pointer">
          <div class="flex items-center gap-3">
            <BookText :size="20" class="text-[#1A2B49]" />
            <span class="text-[#1A2B49] font-bold text-sm">IT Class</span>
          </div>
          <ChevronDown :size="18" class="text-slate-400" />
        </div>

        <div class="w-full md:w-56 bg-white rounded-full px-5 py-3.5 flex items-center justify-between shadow-[0_4px_15px_rgb(0,0,0,0.02)] border border-slate-100/50 cursor-pointer">
          <div class="flex items-center gap-3">
            <Globe :size="20" class="text-[#1A2B49]" />
            <span class="text-[#1A2B49] font-bold text-sm">Mode Belajar</span>
          </div>
          <ChevronDown :size="18" class="text-slate-400" />
        </div>

        <button class="bg-[#264790] hover:bg-[#1a2b49] text-white font-extrabold py-3.5 px-8 rounded-full shadow-md transition-colors text-sm whitespace-nowrap">
          Find a Class
        </button>
      </div>

      <!-- Level Pill dynamic active tag -->
      <div v-if="activeFilter !== 'Semua Kursus'" class="mb-6 flex">
        <span class="bg-[#F9CC6B] text-[#1A2B49] text-xs font-extrabold px-4 py-2 rounded-full shadow-sm flex items-center gap-2">
          Menampilkan Kategori: Kelas {{ activeFilter }}
          <button @click="clearFilter" class="hover:text-red-500 font-black text-sm outline-none pl-1">&times;</button>
        </span>
      </div>

      <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- Courses Cards Grid -->
        <div :class="['w-full lg:w-[60%] xl:w-[65%] grid gap-6', gridColsClass]">
          
          <div v-if="filteredCourses.length === 0" class="col-span-1 sm:col-span-2 text-center py-20 bg-white rounded-3xl border border-slate-100/50 text-slate-500 font-bold text-sm">
            Tidak ada kelas yang ditemukan untuk filter ini.
          </div>

          <Link 
            v-for="course in filteredCourses" 
            :key="course.id"
            :href="'/courses/' + course.slug"
            class="bg-[#FFFFFF] rounded-2xl md:rounded-[1.5rem] overflow-hidden shadow-[0_4px_20px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] cursor-pointer"
          >
            <!-- Course Header Illustration -->
            <div 
              class="h-40 md:h-48 relative flex justify-center items-center overflow-hidden"
              :style="{ backgroundColor: course.bg_color || '#264790' }"
            >
              <!-- Render Cover Image if present in database course -->
              <img 
                v-if="course.thumbnail" 
                :src="course.thumbnail.startsWith('http') || course.thumbnail.startsWith('/') ? course.thumbnail : '/storage/' + course.thumbnail" 
                class="w-full h-full object-cover" 
                alt="Course Cover" 
              />
              
              <template v-else>
                <div class="text-black relative z-10">
                  <Code v-if="course.icon_type === 'code'" :size="64" :stroke-width="2.5" />
                  <Calculator v-else-if="course.icon_type === 'calculator'" :size="64" :stroke-width="2.5" />
                  <BookText v-else-if="course.icon_type === 'book'" :size="64" :stroke-width="2.5" />
                  <Code v-else :size="64" :stroke-width="2.5" />
                </div>

                <div class="absolute -bottom-4 -right-4 text-black opacity-10 transform rotate-12 scale-150">
                  <Code v-if="course.icon_type === 'code'" :size="100" />
                  <Calculator v-else-if="course.icon_type === 'calculator'" :size="100" />
                  <BookText v-else-if="course.icon_type === 'book'" :size="100" />
                  <Code v-else :size="100" />
                </div>
              </template>
            </div>

            <!-- Course Card Body info -->
            <div class="p-6 md:p-8 flex flex-col flex-grow text-left">
              <h3 class="text-[#1A2B49] font-extrabold text-lg md:text-xl leading-snug mb-5 line-clamp-2 min-h-[3rem]">
                {{ course.title }}
              </h3>

              <ul class="space-y-3 mb-6">
                <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                  <Calendar :size="16" class="text-slate-400" />
                  <span>{{ course.sessions || 'Two Session per Week' }}</span>
                </li>
                <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                  <Clock :size="16" class="text-slate-400" />
                  <span>{{ course.duration || '1 Hour for 1 Session' }}</span>
                </li>
                <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                  <MapPin :size="16" class="text-slate-400" />
                  <span>{{ course.type || 'Offline Class' }}</span>
                </li>
              </ul>

              <div class="flex-grow"></div>

              <div class="w-full h-px bg-slate-200 mb-4"></div>

              <div class="flex items-baseline gap-1">
                <span class="text-[#1A2B49] font-extrabold text-xl">
                  {{ formatPrice(course.price) }}
                </span>
                <span class="text-slate-400 font-medium text-xs">{{ course.period || '/ Bulan' }}</span>
              </div>
            </div>
          </Link>

          <!-- Pagination Buttons -->
          <div v-if="props.courses?.links && props.courses.last_page > 1" class="col-span-full flex justify-center gap-2 mt-8 w-full">
            <Link
              v-for="(link, lIdx) in props.courses.links"
              :key="lIdx"
              :href="link.url || '#'"
              v-html="link.label"
              :class="[
                'px-4 py-2 text-xs font-bold rounded-xl border transition-all',
                link.active 
                  ? 'bg-[#264790] text-white border-[#264790]' 
                  : 'bg-white hover:bg-slate-50 text-slate-600 border-slate-200',
                !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
              ]"
              :disabled="!link.url"
            />
          </div>

        </div>

        <!-- Google Maps Location Frame Sidebar (using Leaflet.js) -->
        <div class="w-full lg:w-[40%] xl:w-[35%] lg:sticky lg:top-28 h-[500px] bg-slate-200 rounded-[2rem] overflow-hidden shadow-sm border border-slate-100 relative z-0">
           
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
                   <p class="text-xs text-slate-500 mt-1 font-semibold">Pusat Kelas Offline</p>
                 </div>
               </l-popup>
             </l-marker>
           </l-map>
           
           <div class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-white/90 backdrop-blur-md px-6 py-3 rounded-full flex items-center gap-2 shadow-lg z-[1000] pointer-events-none">
             <MapPin class="text-[#FF4D4F]" :size="18" />
             <span class="font-bold text-[#1A2B49] text-sm">Lokasi Kelas Offline</span>
           </div>

        </div>

      </div>

    </main>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
