<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { 
  Search, Calendar, User as UserIcon, Plus, X, 
  ChevronLeft, ChevronRight, PenTool, BookOpen, AlertCircle,
  Instagram, Twitter, Facebook, Linkedin
} from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
  blogs: Object, // Laravel Paginated Object
  filters: Object,
  auth: Object
});

const searchQuery = ref(props.filters.search || '');
const isCreateModalOpen = ref(false);

// Check if user has permission to manage blogs (admin or superadmin role)
const isAdmin = computed(() => {
  return props.auth?.user?.role === 'admin';
});

// Search trigger
const handleSearch = () => {
  router.get(route('blogs.index'), { search: searchQuery.value }, {
    preserveState: true,
    replace: true
  });
};

// Create Blog Form model
const createForm = useForm({
  title: '',
  content: '',
  category: 'Coding IT Class',
  excerpt: '',
});

// Submit new blog article
const submitBlog = () => {
  createForm.post(route('blogs.store'), {
    onSuccess: () => {
      isCreateModalOpen.value = false;
      createForm.reset();
      alert('Artikel blog berhasil dipublikasikan!');
    },
    onError: (errors) => {
      let errMsg = Object.values(errors).join('\n');
      alert('Gagal menerbitkan artikel:\n' + errMsg);
    }
  });
};

// Delete blog article (Admin only)
const deleteBlog = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus artikel blog ini?')) {
    router.delete(route('blogs.destroy', id), {
      onSuccess: () => {
        alert('Artikel blog berhasil dihapus.');
      }
    });
  }
};

// Logo SVG Helper
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

<template>
  <Head title="Berita & Artikel | Drastha Learning" />

  <GuestLayout>
    <!-- HERO SECTION WITH CUSTOM GRADIENT & GRAPHICS -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
      <div 
        class="rounded-[2.5rem] bg-gradient-to-r from-sky-400 via-blue-500 to-[#264790] text-white p-8 sm:p-16 text-center relative overflow-hidden shadow-lg min-h-[350px] flex flex-col justify-center items-center gap-6"
      >
        <!-- Background Illustration Image Overlay if present -->
        <div class="absolute inset-0 opacity-10 mix-blend-overlay">
          <img src="/images/pages/blog/bg-login-signup.png" class="w-full h-full object-cover" alt="" />
        </div>

        <div class="relative z-10 flex flex-col items-center gap-4 max-w-2xl">
          <!-- Small Floating Micro Icons Graphic -->
          <div class="flex gap-3 mb-2">
            <span class="text-3xl animate-bounce">📰</span>
            <span class="text-3xl animate-pulse">💡</span>
          </div>

          <h1 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight">
            Discover Insights.<br />
            Fuel Your Curiosity
          </h1>
          <p class="text-white/85 text-xs sm:text-base font-semibold max-w-lg leading-relaxed">
            Dive into a world of insightful articles, expert opinions, and inspiring stories.
          </p>

          <!-- Search Input Box matching mockup exactly -->
          <div class="w-full max-w-md bg-white rounded-full p-1.5 shadow-xl flex items-center gap-2 mt-4">
            <input 
              type="text" 
              v-model="searchQuery"
              @keyup.enter="handleSearch"
              placeholder="Search Article"
              class="w-full bg-transparent border-none text-slate-700 text-xs sm:text-sm font-semibold pl-5 focus:outline-none focus:ring-0 placeholder-slate-400 py-2.5"
            />
            <button 
              @click="handleSearch"
              class="w-10 h-10 rounded-full bg-[#264790] hover:bg-[#44A6D9] text-white flex items-center justify-center transition-colors shrink-0 outline-none"
            >
              <Search :size="16" />
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- MAIN ARTICLES CONTENT SECTION -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      
      <!-- Section Title and Subtitle -->
      <div class="text-center mb-16 flex flex-col items-center gap-3">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-[#1A2B49] tracking-tight">
          Blog <span class="text-[#264790]">News</span>
        </h2>
        <p class="text-slate-400 text-xs sm:text-sm font-semibold max-w-sm">
          Get Started today and take your reading experience wherever you go!
        </p>

        <!-- Admin Add Article Button -->
        <button 
          v-if="isAdmin"
          @click="isCreateModalOpen = true"
          class="mt-6 inline-flex items-center gap-2 bg-[#264790] hover:bg-[#44A6D9] text-white px-6 py-3 rounded-2xl font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all"
        >
          <Plus :size="16" /> Tambah Artikel Baru
        </button>
      </div>

      <!-- Blogs Grid: 12 cards columns -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        
        <div 
          v-for="blog in blogs.data" 
          :key="blog.id"
          class="bg-white rounded-3xl overflow-hidden border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
        >
          <!-- Thumbnail card -->
          <div class="aspect-[4/3] bg-slate-100 relative overflow-hidden shrink-0">
            <img 
              :src="blog.image || '/images/pages/login_or_signup/bg-login-signup.png'" 
              class="w-full h-full object-cover" 
              alt="Article Thumbnail" 
            />
            
            <!-- Category Badge (orange/yellow theme matching mockup) -->
            <span class="absolute bottom-4 left-4 bg-[#F9CC6B] text-[#1A2B49] text-[9px] font-bold px-3 py-1 rounded-md uppercase tracking-wider shadow-sm">
              {{ blog.category }}
            </span>
          </div>

          <!-- Body card info -->
          <div class="p-6 flex-grow flex flex-col justify-between gap-5">
            <div>
              <!-- Date publication -->
              <span class="text-[10px] text-slate-400 font-bold block mb-2">
                {{ new Date(blog.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
              </span>
              
              <!-- Title links to detail -->
              <Link 
                :href="`/blogs/${blog.slug}`"
                class="font-extrabold text-[#1A2B49] text-sm sm:text-base leading-snug hover:text-[#44A6D9] transition-colors block line-clamp-2"
              >
                {{ blog.title }}
              </Link>
            </div>

            <!-- Author signature & optional delete action -->
            <div class="flex items-center justify-between border-t border-slate-50 pt-4 mt-auto">
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center shrink-0">
                  <UserIcon :size="12" />
                </div>
                <span class="text-[10px] font-bold text-slate-500 truncate max-w-[120px]">
                  {{ blog.user?.name || 'Admin Drastha' }}
                </span>
              </div>

              <!-- Trash Delete Button for Admin only -->
              <button 
                v-if="isAdmin"
                @click="deleteBlog(blog.id)"
                class="text-rose-500 hover:text-rose-700 transition-colors p-1"
                title="Hapus Artikel"
              >
                <X :size="14" />
              </button>
            </div>

          </div>

        </div>

      </div>

      <!-- If Empty States -->
      <div v-if="blogs.data.length === 0" class="text-center py-20 bg-slate-50 rounded-3xl border border-slate-100 flex flex-col items-center gap-3">
        <AlertCircle :size="40" class="text-slate-400 animate-pulse" />
        <p class="text-slate-500 font-semibold text-sm">Belum ada artikel berita yang diterbitkan.</p>
      </div>

      <!-- PAGINATION NAVIGATION MATCHING MOCKUP: < Blog Berita > -->
      <div v-if="blogs.data.length > 0" class="flex items-center justify-center gap-8 mt-16 border-t border-slate-100 pt-8">
        <Link 
          v-if="blogs.prev_page_url" 
          :href="blogs.prev_page_url" 
          class="w-10 h-10 rounded-full border border-slate-200 text-slate-600 hover:text-[#264790] hover:border-[#264790] flex items-center justify-center transition-all shadow-sm"
        >
          <ChevronLeft :size="16" />
        </Link>
        <span v-else class="w-10 h-10 rounded-full border border-slate-100 text-slate-300 flex items-center justify-center cursor-not-allowed">
          <ChevronLeft :size="16" />
        </span>

        <span class="text-sm font-bold text-[#1A2B49] uppercase tracking-widest">
          Blog <span class="text-[#264790]">Berita</span>
        </span>

        <Link 
          v-if="blogs.next_page_url" 
          :href="blogs.next_page_url" 
          class="w-10 h-10 rounded-full border border-slate-200 text-slate-600 hover:text-[#264790] hover:border-[#264790] flex items-center justify-center transition-all shadow-sm"
        >
          <ChevronRight :size="16" />
        </Link>
        <span v-else class="w-10 h-10 rounded-full border border-slate-100 text-slate-300 flex items-center justify-center cursor-not-allowed">
          <ChevronRight :size="16" />
        </span>
      </div>

    </div>

    <!-- ADMIN CREATE BLOG MODAL FORM -->
    <div 
      v-if="isCreateModalOpen" 
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all"
    >
      <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-8 border border-slate-100 shadow-2xl flex flex-col gap-6 max-h-[90vh] overflow-y-auto">
        
        <!-- Header Modal -->
        <div class="flex justify-between items-center pb-4 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-[#264790]/5 text-[#264790] flex items-center justify-center">
              <PenTool :size="16" />
            </div>
            <h3 class="text-lg font-extrabold text-[#1A2B49]">Tulis Artikel Baru</h3>
          </div>
          <button 
            @click="isCreateModalOpen = false" 
            class="text-slate-400 hover:text-slate-600 transition-colors outline-none"
          >
            <X :size="20" />
          </button>
        </div>

        <!-- Form content -->
        <form @submit.prevent="submitBlog" class="flex flex-col gap-5">
          <!-- Title Input -->
          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Judul Artikel</label>
            <input 
              type="text" 
              v-model="createForm.title"
              placeholder="Masukkan judul artikel" 
              required
              class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3.5 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
            />
          </div>

          <!-- Category Select -->
          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kategori Kelas</label>
            <select 
              v-model="createForm.category"
              class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3.5 text-xs sm:text-sm text-slate-700 font-bold focus:outline-none cursor-pointer"
            >
              <option value="Coding IT Class">Coding IT Class</option>
              <option value="Design Class">Design Class</option>
              <option value="Finance Class">Finance Class</option>
              <option value="Sains Class">Sains Class</option>
              <option value="Umum Class">Umum Class</option>
            </select>
          </div>

          <!-- Excerpt Input -->
          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ringkasan Singkat (Excerpt)</label>
            <textarea 
              v-model="createForm.excerpt"
              placeholder="Masukkan kutipan ringkasan artikel..." 
              rows="2"
              class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3.5 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
            ></textarea>
          </div>

          <!-- Content Body -->
          <div class="flex flex-col gap-2">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Isi Lengkap Artikel</label>
            <textarea 
              v-model="createForm.content"
              placeholder="Tulis materi artikel berita Anda secara detail disini..." 
              rows="6"
              required
              class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3.5 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
            ></textarea>
          </div>

          <!-- Action CTA -->
          <button 
            type="submit"
            :disabled="createForm.processing"
            class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-2"
          >
            Terbitkan Artikel
          </button>
        </form>

      </div>
    </div>

    <!-- FOOTER SECTION -->
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-32 md:pb-12 mt-16 select-none">
      
      <div class="bg-[#FFFFFF] rounded-[2.5rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-50">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-8 mb-12 text-left">
          
          <div class="md:col-span-5 flex flex-col gap-6">
            
            <div class="flex items-center gap-2">
              <ApplicationLogo />
            </div>

            <p class="text-[#264790] text-sm md:text-base font-medium leading-relaxed max-w-md">
              Platform Learning Management System (LMS) yang dirancang untuk mendukung pembelajaran modern, interaktif, dan berkelanjutan.
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
            <h4 class="font-extrabold text-[#1A2B49] text-lg">Tautan Cepat</h4>
            <ul class="flex flex-col gap-3">
              <li><Link href="/" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Home</Link></li>
              <li><Link href="/courses" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Kelas Kami</Link></li>
              <li><Link href="/contact" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Hubungi Kami</Link></li>
              <li><Link href="/about" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Tentang Kami</Link></li>
              <li><Link href="/clients" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Klien Kami</Link></li>
              <li><Link href="/blog" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Blog Kami</Link></li>
            </ul>
          </div>

          <div class="md:col-span-4 flex flex-col gap-5">
            <h4 class="font-extrabold text-[#1A2B49] text-lg">Kontak</h4>
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
          <p>&copy; 2026 Drastha Learning, All Rights Reserved</p>
          
          <div class="flex flex-wrap justify-center gap-4 md:gap-8">
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Privacy Policy</Link>
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Terms of Service</Link>
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Cookies Settings</Link>
          </div>
        </div>

      </div>
    </footer>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
