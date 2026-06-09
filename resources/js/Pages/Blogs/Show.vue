<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Calendar, User as UserIcon, ArrowLeft, Clock, BookOpen } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
  blog: Object,
});

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
  <Head :title="`${blog.title} | Blog | Drastha Learning`" />

  <GuestLayout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      
      <!-- Back button -->
      <div class="mb-8">
        <Link 
          href="/blogs" 
          class="inline-flex items-center gap-2 text-slate-400 hover:text-[#44A6D9] font-extrabold text-xs sm:text-sm transition-colors"
        >
          <ArrowLeft :size="16" /> Kembali ke Daftar Artikel
        </Link>
      </div>

      <!-- Main Header Area -->
      <div class="flex flex-col gap-6 mb-10">
        
        <!-- Category Badge -->
        <span class="self-start bg-[#F9CC6B] text-[#1A2B49] text-xs font-bold px-4 py-1.5 rounded-lg uppercase tracking-wider shadow-sm">
          {{ blog.category }}
        </span>

        <!-- Article Title -->
        <h1 class="text-2xl sm:text-4xl font-extrabold text-[#1A2B49] leading-snug">
          {{ blog.title }}
        </h1>

        <!-- Publication Info -->
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-xs sm:text-sm font-bold text-slate-400 border-b border-slate-100 pb-6">
          <div class="flex items-center gap-2">
            <Calendar :size="16" class="text-[#264790]" />
            <span>
              {{ new Date(blog.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <UserIcon :size="16" class="text-[#264790]" />
            <span>Oleh: {{ blog.user?.name || 'Admin Drastha Learning' }}</span>
          </div>
          <div class="flex items-center gap-2">
            <Clock :size="16" class="text-[#264790]" />
            <span>Baca: 5 Menit</span>
          </div>
        </div>

      </div>

      <!-- Hero Cover Image -->
      <div class="rounded-3xl overflow-hidden bg-slate-100 mb-10 border border-slate-100 shadow-md aspect-[16/9] w-full">
        <img 
          :src="blog.image || '/images/pages/login_or_signup/bg-login-signup.png'" 
          class="w-full h-full object-cover" 
          alt="Article Cover Image" 
        />
      </div>

      <!-- Article Content Body -->
      <div class="bg-white rounded-3xl p-8 sm:p-12 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.01)]">
        <div class="prose prose-indigo max-w-none text-slate-600 font-medium text-xs sm:text-base leading-relaxed whitespace-pre-line">
          {{ blog.content }}
        </div>
      </div>

    </div>

    <!-- CLEAN FOOTER -->
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mt-16 border-t border-slate-100">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
          <!-- Inline SVG Logo -->
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
              <Link href="/blogs" class="hover:text-[#44A6D9] transition-colors">Blog Berita</Link>
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
