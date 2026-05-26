<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, GraduationCap, 
  Home, Newspaper, Calendar, Clock, MapPin, Code,
  Trash2, CreditCard, ChevronRight, CheckCircle2, ArrowRight
} from 'lucide-vue-next';
import axios from 'axios';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
  cartItems: Array,
});

const isProcessing = ref(false);
const showSuccessOverlay = ref(false);

// Formatting helpers
const formatPrice = (val) => {
  return parseFloat(val).toLocaleString('id-ID');
};

const totalItem = computed(() => props.cartItems.length);

const totalHargaRaw = computed(() => {
  return props.cartItems.reduce((sum, item) => sum + parseFloat(item.price), 0);
});

const totalHargaFormatted = computed(() => {
  return formatPrice(totalHargaRaw.value);
});

// Remove item from cart
const handleRemove = (courseId) => {
  router.post('/cart/remove', {
    course_id: courseId
  }, {
    preserveScroll: true
  });
};

// Clear entire cart
const handleClearCart = () => {
  if (confirm('Apakah Anda yakin ingin mengosongkan seluruh isi keranjang belanja?')) {
    router.post('/cart/clear', {}, {
      preserveScroll: true
    });
  }
};

// Initialize Midtrans Snap script
onMounted(() => {
  const script = document.createElement('script');
  script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
  script.setAttribute('data-client-key', 'SB-Mid-client-placeholder');
  document.head.appendChild(script);
});

// Checkout action (Redirect to checkout page)
const handleCheckout = () => {
  // Check if logged in
  if (!usePage().props.auth.user) {
    alert('Silakan login terlebih dahulu untuk melakukan checkout pembelian.');
    router.get('/login');
    return;
  }

  if (props.cartItems.length === 0) {
    alert('Keranjang belanja Anda kosong.');
    return;
  }

  router.get('/checkout');
};

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
  <Head title="Keranjang Belanja | Drastha Learning" />

  <GuestLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <!-- Back to Courses Link -->
      <div class="mb-8">
        <Link 
          href="/courses" 
          class="inline-flex items-center gap-2 text-slate-400 hover:text-[#44A6D9] font-semibold text-sm transition-colors"
        >
          &lsaquo; Kembali ke Daftar Kelas
        </Link>
      </div>

      <!-- Page Title -->
      <h1 class="text-3xl sm:text-4xl font-extrabold text-[#1A2B49] leading-tight mb-8">
        Keranjang Belanja
      </h1>

      <!-- Empty Cart Layout -->
      <div v-if="cartItems.length === 0" class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm flex flex-col items-center max-w-xl mx-auto mt-10">
        <div class="w-20 h-20 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mb-6">
          <ShoppingCart :size="38" />
        </div>
        <h3 class="text-xl font-extrabold text-[#1A2B49] mb-2">Keranjang Belanja Kosong</h3>
        <p class="text-slate-400 text-sm font-semibold mb-8 max-w-sm leading-relaxed">
          Sepertinya Anda belum menambahkan kelas belajar apa pun ke dalam keranjang. Mari temukan kelas pemrograman terbaik untuk masa depan Anda!
        </p>
        <Link 
          href="/courses"
          class="inline-flex items-center gap-2 bg-[#264790] hover:bg-[#44A6D9] text-white font-bold py-3.5 px-8 rounded-2xl shadow-md transition-colors text-sm"
        >
          Cari Kelas Belajar <ArrowRight :size="16" />
        </Link>
      </div>

      <!-- Main Cart Layout -->
      <div v-else class="flex flex-col lg:flex-row gap-10 items-start">
        
        <!-- LEFT COLUMN: Cart Items list -->
        <div class="flex-grow w-full lg:max-w-[65%] flex flex-col gap-6">
          
          <div 
            v-for="item in cartItems" 
            :key="item.id"
            class="bg-white rounded-[2rem] p-6 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 transition-all duration-300 hover:shadow-[0_12px_40px_rgba(0,0,0,0.025)] relative"
          >
            <!-- Thumbnail & Main Info Block -->
            <div class="flex items-start sm:items-center gap-5 w-full sm:w-auto">
              <!-- Visual Card Graphics Thumbnail -->
              <div 
                class="w-20 h-20 rounded-2xl flex items-center justify-center shrink-0 shadow-sm"
                :style="{ backgroundColor: item.bg_color || '#FF4D4F' }"
              >
                <div class="text-black scale-110">
                  <Code :size="24" :stroke-width="2.5" />
                </div>
              </div>

              <!-- Metadata content -->
              <div class="flex-grow">
                <span class="text-[#264790] bg-[#264790]/5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider block w-fit mb-1.5">
                  {{ item.category?.name || 'IT Class' }}
                </span>
                
                <h3 class="text-[#1A2B49] font-extrabold text-sm sm:text-base leading-snug mb-3">
                  {{ item.title }}
                </h3>

                <!-- Specs row -->
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] text-slate-400 font-bold">
                  <div class="flex items-center gap-1.5">
                    <Calendar :size="13" class="text-slate-300" />
                    <span>Two Session per Week</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <Clock :size="13" class="text-slate-300" />
                    <span>1 Hour for 1 Session</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <MapPin :size="13" class="text-slate-300" />
                    <span>Offline Class</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Price & Action block -->
            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center w-full sm:w-auto border-t sm:border-t-0 border-slate-50 pt-4 sm:pt-0 shrink-0 gap-4">
              <span class="text-[#1A2B49] font-extrabold text-lg sm:text-xl">
                Rp{{ formatPrice(item.price) }},-
              </span>
              
              <!-- Red Trash Delete Button -->
              <button 
                @click="handleRemove(item.id)"
                class="w-10 h-10 rounded-full bg-red-50 hover:bg-red-100 text-red-600 transition-colors flex items-center justify-center shrink-0 shadow-sm"
                title="Hapus dari Keranjang"
              >
                <Trash2 :size="18" />
              </button>
            </div>

          </div>

        </div>

        <!-- RIGHT COLUMN: Purchase Summary Card -->
        <div class="w-full lg:max-w-[35%] lg:sticky lg:top-28">
          
          <!-- Summary Box Card wrapper -->
          <div class="bg-white rounded-3xl p-8 border border-slate-50 shadow-[0_12px_40px_rgba(0,0,0,0.03)] flex flex-col gap-6">
            
            <h3 class="text-lg font-extrabold text-[#1A2B49]">
              Ringkasan Belanja
            </h3>

            <!-- Specs items details -->
            <div class="flex flex-col gap-3.5 text-xs sm:text-sm font-semibold text-slate-500">
              <div class="flex items-center justify-between">
                <span>Total Item :</span>
                <span class="text-[#1A2B49] font-extrabold">{{ totalItem }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span>Harga Kelas :</span>
                <span class="text-[#1A2B49] font-extrabold">Rp{{ totalHargaFormatted }}</span>
              </div>
            </div>

            <div class="h-px bg-slate-100"></div>

            <!-- Total Harga dynamic display -->
            <div class="flex items-center justify-between">
              <span class="text-slate-400 text-xs sm:text-sm font-semibold">Total Harga :</span>
              <span class="text-[#1A2B49] font-extrabold text-lg sm:text-xl">
                Rp{{ totalHargaFormatted }},-
              </span>
            </div>

            <!-- Checkout and clear actions -->
            <div class="flex flex-col gap-3 mt-2">
              <button 
                @click="handleCheckout"
                :disabled="isProcessing"
                class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-2"
              >
                <CreditCard :size="16" /> Checkout Paket
              </button>

              <button 
                @click="handleClearCart"
                class="w-full bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-100 py-4 rounded-2xl font-bold text-sm transition-colors flex items-center justify-center gap-2"
              >
                <Trash2 :size="16" /> Hapus Keranjang
              </button>
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

    <!-- Payment Success Overlay Modal -->
    <div 
      v-if="showSuccessOverlay" 
      class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl border border-slate-100 relative text-center flex flex-col items-center">
        
        <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5">
          <CheckCircle2 :size="36" />
        </div>

        <h3 class="text-xl font-extrabold text-[#1A2B49] mb-2">Pembelian Berhasil!</h3>
        <p class="text-slate-400 text-sm font-semibold mb-6">
          Selamat! Seluruh paket kelas di dalam keranjang belanja Anda telah berhasil dibeli dan terdaftar. Silakan masuk ke Dashboard Siswa Anda untuk memulai belajar!
        </p>

        <Link 
          href="/dashboard"
          class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-md transition-colors"
        >
          Masuk ke Dashboard
        </Link>
      </div>
    </div>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
