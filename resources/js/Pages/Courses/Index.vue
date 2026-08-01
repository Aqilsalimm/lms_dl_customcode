<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { 
  Search, Calendar, Clock, MapPin, Code, BookText, Globe, ChevronDown, Calculator, Video, Loader2 
} from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
  courses: [Array, Object],
  categories: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

// Reactive state for courses list & loading
const coursesList = ref(Array.isArray(props.courses?.data) ? props.courses.data : (Array.isArray(props.courses) ? props.courses : []));
const paginationData = ref(props.courses?.links ? props.courses : null);
const isLoading = ref(false);

// Active filters state
const searchQuery = ref(props.filters?.search || '');
const courseTypeFilter = ref(props.filters?.type || 'Semua Mode');
const selectedCategorySlug = ref(props.filters?.category || '');
const activeLevel = ref(props.filters?.level || 'Semua Kursus');

const showCourseTypeDropdown = ref(false);
const showCategoryDropdown = ref(false);

const selectedCategoryLabel = computed(() => {
  if (!selectedCategorySlug.value) return 'Semua Kategori';
  const found = props.categories?.find(c => c.slug === selectedCategorySlug.value);
  return found ? found.name : selectedCategorySlug.value;
});

const courseTypeFilterLabel = computed(() => {
  if (courseTypeFilter.value === 'Semua Mode' || !courseTypeFilter.value) {
    return usePage().props.translations?.all_modes || 'Semua Mode';
  }
  if (courseTypeFilter.value === 'live_class' || courseTypeFilter.value === 'Kelas Kursus / Live Class') {
    return usePage().props.translations?.live_class || 'Kelas Kursus / Live Class';
  }
  if (courseTypeFilter.value === 'async' || courseTypeFilter.value === 'Kursus Mandiri') {
    return 'Kursus Mandiri';
  }
  return courseTypeFilter.value;
});
const hierarchicalCategories = computed(() => {
  const rawCats = props.categories || [];
  const list = [];
  const parents = rawCats.filter(c => !c.parent_id);
  const children = rawCats.filter(c => c.parent_id);

  parents.forEach(parent => {
    list.push({ ...parent, depth: 0 });
    const subcats = children.filter(c => c.parent_id === parent.id);
    subcats.forEach(sub => {
      list.push({ ...sub, depth: 1 });
    });
  });

  // Handle remaining orphans
  const addedIds = new Set(list.map(c => c.id));
  rawCats.forEach(c => {
    if (!addedIds.has(c.id)) {
      list.push({ ...c, depth: c.parent_id ? 1 : 0 });
    }
  });

  return list;
});
// Centralized AJAX Fetcher Function
const fetchCoursesAjax = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = {
      page: page,
      search: searchQuery.value || undefined,
      category: selectedCategorySlug.value || undefined,
      type: courseTypeFilter.value !== 'Semua Mode' ? courseTypeFilter.value : undefined,
      level: activeLevel.value !== 'Semua Kursus' ? activeLevel.value : undefined
    };

    const res = await axios.get('/api/courses/search', { params });
    if (res.data && res.data.success) {
      coursesList.value = res.data.data;
      if (res.data.pagination) {
        paginationData.value = res.data.pagination;
      }

      // Update URL query string silently without reloading page or crashing Inertia
      const urlParams = new URLSearchParams();
      if (params.search) urlParams.set('search', params.search);
      if (params.category) urlParams.set('category', params.category);
      if (params.type) urlParams.set('type', params.type);
      if (params.level) urlParams.set('level', params.level);
      if (page > 1) urlParams.set('page', page);

      const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
      window.history.pushState({ path: newUrl }, '', newUrl);
    }
  } catch (err) {
    console.error('Error fetching courses via AJAX:', err);
  } finally {
    isLoading.value = false;
  }
};

const handleFilterCoursesEvent = (e) => {
  if (e.detail) {
    selectedCategorySlug.value = e.detail.category || '';
    if (e.detail.level) {
      activeLevel.value = e.detail.level;
    }
    fetchCoursesAjax();
  }
};

onMounted(() => {
  window.addEventListener('filter-courses-ajax', handleFilterCoursesEvent);
});

onUnmounted(() => {
  window.removeEventListener('filter-courses-ajax', handleFilterCoursesEvent);
});

const selectCategory = (slug) => {
  selectedCategorySlug.value = slug;
  showCategoryDropdown.value = false;
  fetchCoursesAjax();
};

const selectCourseType = (typeValue) => {
  courseTypeFilter.value = typeValue;
  showCourseTypeDropdown.value = false;
  fetchCoursesAjax();
};

const submitSearch = () => {
  fetchCoursesAjax();
};

const clearFilter = () => {
  selectedCategorySlug.value = '';
  courseTypeFilter.value = 'Semua Mode';
  searchQuery.value = '';
  activeLevel.value = 'Semua Kursus';
  fetchCoursesAjax();
};

const formatPrice = (price) => {
  if (!price) return 'Rp 500.000';
  const num = parseFloat(price);
  if (isNaN(num)) return price;
  return 'Rp ' + Math.round(num).toLocaleString('id-ID');
};

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
            @keyup.enter="submitSearch"
            type="text" 
            :placeholder="$t('search_placeholder') || 'Mau Belajar apa?'" 
            class="bg-transparent w-full outline-none text-[#1A2B49] placeholder-slate-400 font-semibold text-sm border-none focus:ring-0 py-0"
          >
        </div>

        <!-- Dynamic Category Dropdown -->
        <div class="relative w-full md:w-56">
          <div 
            @click="showCategoryDropdown = !showCategoryDropdown"
            class="bg-white rounded-full px-5 py-3.5 flex items-center justify-between shadow-[0_4px_15px_rgb(0,0,0,0.02)] border border-slate-100/50 cursor-pointer"
          >
            <div class="flex items-center gap-3">
              <BookText :size="20" class="text-[#1A2B49]" />
              <span class="text-[#1A2B49] font-bold text-sm line-clamp-1">{{ selectedCategoryLabel }}</span>
            </div>
            <ChevronDown :size="18" class="text-slate-400" />
          </div>

          <div v-if="showCategoryDropdown" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-50 max-h-60 overflow-y-auto">
            <div @click="selectCategory('')" class="px-4 py-2 hover:bg-slate-50 rounded-xl cursor-pointer text-sm font-semibold text-[#1A2B49] border-b border-slate-100 mb-1">
              Semua Kategori
            </div>
            <div 
              v-for="cat in hierarchicalCategories" 
              :key="cat.id"
              @click="selectCategory(cat.slug)" 
              class="px-4 py-2 hover:bg-slate-50 rounded-xl cursor-pointer text-sm text-[#1A2B49] flex items-center gap-1.5"
              :class="cat.depth > 0 ? 'pl-8 text-slate-500 font-medium' : 'font-bold'"
            >
              <span v-if="cat.depth > 0" class="text-slate-300">↳</span>
              {{ cat.name }}
            </div>
          </div>
        </div>

        <!-- Course Type Dropdown -->
        <div class="relative w-full md:w-56">
          <div 
            @click="showCourseTypeDropdown = !showCourseTypeDropdown"
            class="bg-white rounded-full px-5 py-3.5 flex items-center justify-between shadow-[0_4px_15px_rgb(0,0,0,0.02)] border border-slate-100/50 cursor-pointer"
          >
            <div class="flex items-center gap-3">
              <Globe :size="20" class="text-[#1A2B49]" />
              <span class="text-[#1A2B49] font-bold text-sm line-clamp-1">{{ courseTypeFilterLabel }}</span>
            </div>
            <ChevronDown :size="18" class="text-slate-400" />
          </div>
          
          <div v-if="showCourseTypeDropdown" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-50">
            <div @click="selectCourseType('Semua Mode')" class="px-4 py-2 hover:bg-slate-50 rounded-xl cursor-pointer text-sm font-semibold text-[#1A2B49]">{{ $t('all_modes') || 'Semua Mode' }}</div>
            <div @click="selectCourseType('async')" class="px-4 py-2 hover:bg-slate-50 rounded-xl cursor-pointer text-sm font-semibold text-[#1A2B49]">Kursus Mandiri</div>
            <div @click="selectCourseType('live_class')" class="px-4 py-2 hover:bg-slate-50 rounded-xl cursor-pointer text-sm font-semibold text-[#1A2B49]">{{ $t('live_class') || 'Kelas Kursus / Live Class' }}</div>
          </div>
        </div>

        <button 
          @click="submitSearch"
          class="bg-[#264790] hover:bg-[#1a2b49] text-white font-extrabold py-3.5 px-8 rounded-full shadow-md transition-colors text-sm whitespace-nowrap cursor-pointer"
        >
          {{ $t('find_class') || 'Cari Kelas' }}
        </button>
      </div>

      <!-- Level Pill dynamic active tag -->
      <div v-if="activeFilter !== 'Semua Kursus'" class="mb-6 flex">
        <span class="bg-[#F9CC6B] text-[#1A2B49] text-xs font-extrabold px-4 py-2 rounded-full shadow-sm flex items-center gap-2">
          {{ $t('showing_category') || 'Menampilkan Kategori:' }} Kelas {{ activeFilter }}
          <button @click="clearFilter" class="hover:text-red-500 font-black text-sm outline-none pl-1">&times;</button>
        </span>
      </div>

      <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- Courses Cards Grid -->
        <div :class="['w-full lg:w-[60%] xl:w-[65%] grid gap-6 relative', gridColsClass]">
          
          <!-- AJAX Loading Spinner Overlay -->
          <div v-if="isLoading" class="col-span-full py-20 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm rounded-3xl z-30 min-h-[300px]">
            <Loader2 :size="36" class="text-[#264790] animate-spin mb-3" />
            <span class="text-xs font-bold text-slate-500">Memuat data kursus...</span>
          </div>

          <template v-else>
            <div v-if="coursesList.length === 0" class="col-span-full text-center py-20 bg-white rounded-3xl border border-slate-100/50 text-slate-500 font-bold text-sm">
              {{ $t('no_courses_found') || 'Tidak ada kelas yang ditemukan untuk filter ini.' }}
            </div>

            <Link 
              v-for="course in coursesList" 
              :key="course.id"
              :href="'/courses/' + course.slug"
              class="bg-[#FFFFFF] rounded-2xl md:rounded-[1.5rem] overflow-hidden shadow-[0_4px_20px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] cursor-pointer"
            >
              <!-- Course Header Illustration -->
              <div 
                class="h-40 md:h-48 relative flex justify-center items-center overflow-hidden"
                :style="{ backgroundColor: course.bg_color || '#264790' }"
              >
                <!-- Live Class Badge -->
                <div 
                  v-if="course.course_type === 'live_class'"
                  class="absolute top-3 left-3 z-20 bg-gradient-to-r from-rose-500 to-amber-500 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow-lg flex items-center gap-1.5 border border-white/20 backdrop-blur-sm"
                >
                  <Video :size="12" /> Live Workshop
                </div>
                <!-- Render Cover Image if present in database course -->
                <img 
                  v-if="course.thumbnail" 
                  :src="course.thumbnail.startsWith('http') || course.thumbnail.startsWith('/') ? course.thumbnail : '/storage/' + course.thumbnail" 
                  class="w-full h-full object-cover" 
                  alt="Course Cover" 
                  loading="lazy"
                  decoding="async"
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
                    <span>{{ course.sessions || ($t('two_sessions') || 'Two Session per Week') }}</span>
                  </li>
                  <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                    <Clock :size="16" class="text-slate-400" />
                    <span>{{ course.access_duration_months ? course.access_duration_months + ' ' + ($t('months') || 'Bulan') : (course.duration || ($t('one_session_duration') || '1 Hour for 1 Session')) }}</span>
                  </li>
                  <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                    <MapPin :size="16" class="text-slate-400" />
                    <span>{{ course.type || ($t('offline_class') || 'Offline Class') }}</span>
                  </li>
                </ul>

                <div class="flex-grow"></div>

                <div class="w-full h-px bg-slate-200 mb-4"></div>

                <div class="flex items-baseline gap-1">
                  <span class="text-[#1A2B49] font-extrabold text-xl">
                    {{ formatPrice(course.price) }}
                  </span>
                  <span v-if="course.payment_type !== 'one-time'" class="text-slate-400 font-medium text-xs">{{ $t('price_per_month') }}</span>
                </div>
              </div>
            </Link>
          </template>

          <!-- Pagination Buttons -->
          <div v-if="paginationData?.links && paginationData.last_page > 1" class="col-span-full flex justify-center gap-2 mt-8 w-full">
            <button
              v-for="(link, lIdx) in paginationData.links"
              :key="lIdx"
              @click="link.url ? fetchCoursesAjax(link.url.includes('page=') ? link.url.split('page=')[1].split('&')[0] : 1) : null"
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

        <!-- Google Maps Location Frame Sidebar -->
        <div class="w-full lg:w-[40%] xl:w-[35%] lg:sticky lg:top-28 h-[500px] rounded-2xl overflow-hidden shadow-md relative z-0">
           
           <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15826.115904033626!2d112.75402035!3d-7.40082715!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7e42d764b8a21%3A0xc3f8373b8cb4005b!2sPerum%20Citra%20Surya%20Mas%20Sidoarjo!5e0!3m2!1sen!2sid!4v1718451151608!5m2!1sen!2sid" 
            width="100%" 
            height="100%" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
           </iframe>
           
        </div>

      </div>

    </main>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
