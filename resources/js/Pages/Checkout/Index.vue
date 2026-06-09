<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, GraduationCap, 
  Home, Newspaper, Calendar, Clock, MapPin, Code,
  Trash2, CreditCard, ChevronRight, CheckCircle2, Play
} from 'lucide-vue-next';
import axios from 'axios';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
  cartItems: Array,
  midtransClientKey: String,
  midtransSandboxMode: Boolean
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

// Remove item from checkout page (updates session cart and re-renders)
const handleRemove = (courseId) => {
  router.post('/cart/remove', {
    course_id: courseId
  }, {
    preserveScroll: true
  });
};

// Initialize Midtrans Snap script
onMounted(() => {
  const script = document.createElement('script');
  const isSandbox = props.midtransSandboxMode !== false;
  script.src = isSandbox 
    ? 'https://app.sandbox.midtrans.com/snap/snap.js'
    : 'https://app.midtrans.com/snap/snap.js';
  script.setAttribute('data-client-key', props.midtransClientKey || 'SB-Mid-client-placeholder');
  document.head.appendChild(script);
});

// Finalize Checkout and Trigger Payment Gateways
const handlePayNow = () => {
  if (props.cartItems.length === 0) {
    alert('Keranjang belanja Anda kosong.');
    router.get('/courses');
    return;
  }

  isProcessing.value = true;

  axios.post('/cart/checkout')
  .then(res => {
    // If checkout was auto-completed (auto_complete_ecommerce_orders is enabled)
    if (res.data.completed) {
      isProcessing.value = false;
      showSuccessOverlay.value = true;
      return;
    }

    const { snap_token, order_id } = res.data;
    
    if (snap_token && snap_token.includes('MOCK-SNAP-TOKEN')) {
      // Mock Completion
      axios.post(`/payment/mock-complete/${order_id}`)
        .then(() => {
          isProcessing.value = false;
          showSuccessOverlay.value = true;
        })
        .catch(err => {
          isProcessing.value = false;
          alert('Gagal memproses pendaftaran kustom.');
        });
    } else {
      // Real Midtrans Snap
      window.snap.pay(snap_token, {
        onSuccess: function(result) {
          showSuccessOverlay.value = true;
        },
        onPending: function(result) {
          alert('Pembayaran tertunda. Mohon selesaikan transfer.');
          router.get('/dashboard');
        },
        onError: function(result) {
          alert('Pembayaran gagal dilakukan.');
          isProcessing.value = false;
        },
        onClose: function() {
          isProcessing.value = false;
        }
      });
    }
  })
  .catch(err => {
    isProcessing.value = false;
    const errorMsg = err.response?.data?.message || 'Terjadi kendala saat memproses checkout.';
    alert(errorMsg);
  });
};

// Logo Helper
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
  <Head title="Checkout Pembelian | Drastha Learning" />

  <GuestLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <!-- Back to Cart Link -->
      <div class="mb-8">
        <Link 
          href="/cart" 
          class="inline-flex items-center gap-2 text-slate-400 hover:text-[#44A6D9] font-semibold text-sm transition-colors"
        >
          &lsaquo; Kembali ke Keranjang
        </Link>
      </div>

      <!-- Page Title -->
      <h1 class="text-3xl sm:text-4xl font-extrabold text-[#1A2B49] leading-tight mb-10">
        Kelas yang Dipilih
      </h1>

      <!-- Main Columns -->
      <div class="flex flex-col gap-10 items-stretch">
        
        <!-- TOP/LEFT COLUMN: Chosen Classes Detailed Card List -->
        <div class="w-full flex flex-col gap-8">
          
          <div 
            v-for="item in cartItems" 
            :key="item.id"
            class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col xl:flex-row items-stretch justify-between gap-8 transition-all duration-300 hover:shadow-[0_12px_40px_rgba(0,0,0,0.025)]"
          >
            
            <!-- Left Panel (Course general preview card matching layout) -->
            <div 
              class="w-full xl:max-w-[32%] rounded-[1.5rem] p-6 text-white flex flex-col justify-between shrink-0 relative overflow-hidden shadow-sm"
              :style="{ backgroundColor: item.bg_color || '#44A6D9' }"
            >
              <!-- Glassmorphism overlay icon -->
              <div class="absolute right-[-20px] bottom-[-20px] opacity-10 pointer-events-none scale-150">
                <Code :size="120" />
              </div>

              <div>
                <!-- Category badge -->
                <span class="inline-block bg-white/20 text-white font-extrabold text-[10px] tracking-wider uppercase px-3 py-1 rounded-md mb-6 backdrop-blur-md">
                  {{ item.category?.name || 'IT Class' }}
                </span>

                <!-- Thumbnail symbol -->
                <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-md">
                  <Code :size="28" :stroke-width="2.5" class="text-white" />
                </div>

                <!-- Title -->
                <h3 class="font-extrabold text-lg sm:text-xl leading-snug mb-8">
                  {{ item.title }}
                </h3>
              </div>

              <!-- Metadata specifications -->
              <div class="flex flex-col gap-2.5 text-xs font-bold text-white/90 border-t border-white/10 pt-4">
                <div class="flex items-center gap-2">
                  <Calendar :size="14" class="text-white/70" />
                  <span>Two Session per Week</span>
                </div>
                <div class="flex items-center gap-2">
                  <Clock :size="14" class="text-white/70" />
                  <span>1 Hour for 1 Session</span>
                </div>
                <div class="flex items-center gap-2">
                  <MapPin :size="14" class="text-white/70" />
                  <span>Offline Class</span>
                </div>
              </div>
            </div>

            <!-- Right Panel (Detailed descriptions & Usia & Benefits & Price action row) -->
            <div class="flex-grow flex flex-col justify-between gap-6 py-2">
              
              <div class="flex flex-col gap-5">
                <!-- Deskripsi Block -->
                <div>
                  <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49] mb-1.5">Deskripsi :</h4>
                  <p class="text-slate-500 font-medium text-xs sm:text-sm leading-relaxed">
                    {{ item.description || 'Materi kelas yang mengajarkan tentang dasar-dasar pemrograman dengan pendekatan interaktif, asyik, dan menyenangkan.' }}
                  </p>
                </div>

                <!-- Usia Block -->
                <div>
                  <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49] mb-1.5">Usia :</h4>
                  <p class="text-slate-500 font-medium text-xs sm:text-sm">
                    Umur 10 - 18 Tahun
                  </p>
                </div>

                <!-- Benefit Block -->
                <div>
                  <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49] mb-1.5">Benefit :</h4>
                  <p class="text-slate-500 font-medium text-xs sm:text-sm">
                    {{ item.about || 'Modul Lengkap, E-Certificate, Dokumentasi Belajar, Report Study Berkala' }}
                  </p>
                </div>
              </div>

              <!-- Price & Actions Row -->
              <div class="flex items-center gap-3 pt-6 border-t border-slate-50 mt-auto">
                <!-- Rounded Price Tag Pill -->
                <div class="bg-slate-50 text-[#1A2B49] font-extrabold px-6 py-2.5 rounded-full text-sm sm:text-base border border-slate-100/50">
                  Rp{{ formatPrice(item.price) }},-
                </div>

                <!-- Red Delete Button Pill -->
                <button 
                  @click="handleRemove(item.id)"
                  class="w-11 h-11 rounded-full bg-red-50 hover:bg-red-100 text-red-600 border border-red-100/30 transition-colors flex items-center justify-center shrink-0 shadow-sm"
                  title="Hapus dari Pilihan"
                >
                  <Trash2 :size="18" />
                </button>
              </div>

            </div>

          </div>

        </div>

        <!-- BOTTOM COLUMN: Purchase Summary Card aligned center/right -->
        <div class="w-full flex justify-end mt-4">
          
          <!-- Summary Box Card wrapper -->
          <div class="w-full md:max-w-md bg-white rounded-3xl p-8 border border-slate-50 shadow-[0_12px_40px_rgba(0,0,0,0.03)] flex flex-col gap-6">
            
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
                <span>Jumlah Siswa :</span>
                <span class="text-[#1A2B49] font-extrabold">1</span>
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

            <!-- Pay Now CTA Button -->
            <div class="flex flex-col gap-3 mt-2">
              <button 
                @click="handlePayNow"
                :disabled="isProcessing"
                class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-2"
              >
                <CreditCard :size="16" /> Bayar Sekarang
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
          Selamat! Seluruh paket kelas yang dipilih telah berhasil dibeli dan terdaftar. Silakan masuk ke Dashboard Siswa Anda untuk memulai belajar!
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
