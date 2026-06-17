<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, GraduationCap, 
  Home, Newspaper, Calendar, Clock, MapPin, Code,
  Trash2, CreditCard, ChevronRight, CheckCircle2, Play
} from 'lucide-vue-next';
import axios from 'axios';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
  cartItems: Array,
  midtransClientKey: String,
  midtransSandboxMode: Boolean
});

const isProcessing = ref(false);
const showSuccessOverlay = ref(false);

// Coupon states
const couponCode = ref('');
const appliedCoupon = ref(null);
const couponError = ref('');
const isCheckingCoupon = ref(false);

const handleApplyCoupon = () => {
  if (!couponCode.value) return;
  couponError.value = '';
  isCheckingCoupon.value = true;

  const items = props.cartItems.map(item => ({
    id: item.id,
    type: 'course'
  }));

  axios.post('/payment/validate-coupon', {
    code: couponCode.value.trim().toUpperCase(),
    items: items
  })
  .then(res => {
    isCheckingCoupon.value = false;
    appliedCoupon.value = res.data;
  })
  .catch(err => {
    isCheckingCoupon.value = false;
    appliedCoupon.value = null;
    couponError.value = err.response?.data?.message || 'Gagal memvalidasi kupon.';
  });
};

const handleRemoveCoupon = () => {
  couponCode.value = '';
  appliedCoupon.value = null;
  couponError.value = '';
};

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

const finalTotalHarga = computed(() => {
  if (appliedCoupon.value) {
    return Math.max(0, totalHargaRaw.value - appliedCoupon.value.discount_amount);
  }
  return totalHargaRaw.value;
});

const finalTotalHargaFormatted = computed(() => {
  return formatPrice(finalTotalHarga.value);
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

  axios.post('/cart/checkout', {
    coupon_code: appliedCoupon.value?.code || null
  })
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

// Logo Helper removed, using ApplicationLogo component directly.
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
                  <ul class="text-slate-500 font-medium text-xs sm:text-sm list-disc pl-4 space-y-1">
                    <template v-if="Array.isArray(item.about)">
                      <li v-for="(benefit, index) in item.about" :key="index">{{ benefit }}</li>
                    </template>
                    <template v-else-if="typeof item.about === 'string' && item.about.startsWith('{')">
                      <div v-html="JSON.parse(item.about).what_will_learn || 'Materi komprehensif.'"></div>
                    </template>
                    <template v-else>
                      <li v-if="item.about">{{ item.about }}</li>
                      <li v-else>Modul Lengkap, E-Certificate, Dokumentasi Belajar, Report Study Berkala</li>
                    </template>
                  </ul>
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
              <div v-if="appliedCoupon" class="flex items-center justify-between text-emerald-600">
                <span>Diskon Promo:</span>
                <span class="font-extrabold">-Rp{{ formatPrice(appliedCoupon.discount_amount) }}</span>
              </div>
            </div>

            <div class="h-px bg-slate-100"></div>

            <!-- Coupon Code Field -->
            <div class="flex flex-col gap-2.5">
              <label class="text-xs font-bold text-[#1A2B49] uppercase tracking-wider">Punya Kode Promo?</label>
              
              <div v-if="!appliedCoupon" class="flex gap-2">
                <input 
                  v-model="couponCode"
                  type="text"
                  placeholder="Masukkan kode promo"
                  class="flex-grow border border-slate-200 hover:border-slate-350 focus:border-[#264790] rounded-xl px-4 py-2.5 text-xs outline-none text-[#1A2B49] uppercase font-mono font-bold"
                  :disabled="isCheckingCoupon"
                  @keyup.enter="handleApplyCoupon"
                />
                <button 
                  type="button"
                  @click="handleApplyCoupon"
                  :disabled="isCheckingCoupon || !couponCode"
                  class="bg-[#264790]/10 hover:bg-[#264790]/25 text-[#264790] px-4 rounded-xl font-bold text-xs transition-colors cursor-pointer disabled:opacity-50 shrink-0"
                >
                  Terapkan
                </button>
              </div>

              <div v-else class="flex items-center justify-between bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-xs">
                <div class="flex flex-col gap-0.5">
                  <span class="font-bold text-emerald-800 font-mono text-sm uppercase">{{ appliedCoupon.code }}</span>
                  <span class="text-emerald-600 font-medium">Potongan -Rp{{ formatPrice(appliedCoupon.discount_amount) }}</span>
                </div>
                <button 
                  type="button"
                  @click="handleRemoveCoupon"
                  class="text-red-500 hover:text-red-700 font-bold transition-colors cursor-pointer"
                >
                  Hapus
                </button>
              </div>

              <span v-if="couponError" class="text-xs text-red-500 font-bold mt-1">{{ couponError }}</span>
            </div>

            <div class="h-px bg-slate-100"></div>

            <!-- Total Harga dynamic display -->
            <div class="flex items-center justify-between">
              <span class="text-slate-400 text-xs sm:text-sm font-semibold">Total Harga :</span>
              <span class="text-[#1A2B49] font-extrabold text-lg sm:text-xl">
                Rp{{ finalTotalHargaFormatted }},-
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

    <!-- FOOTER SECTION (Matches Welcome.vue Footer) -->
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-32 md:pb-12 mt-16">
      
      <div class="bg-[#FFFFFF] rounded-[2.5rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-50">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-8 mb-12">
          
          <div class="md:col-span-5 flex flex-col gap-6">
            
            <div class="flex items-center gap-2 mb-4">
            <ApplicationLogo />
          </div>

            <p class="text-[#264790] text-sm md:text-base font-medium leading-relaxed max-w-md">
              Platform Learning Management System (LMS) yang dirancang untuk mendukung pembelajaran modern, interaktif, dan berkelanjutan.
            </p>

            <div class="flex items-center gap-4">
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-instagram"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-twitter"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg>
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-linkedin"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>
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
          <p>&copy; 2026 Drastha Learning. All Rights Reserved</p>
          
          <div class="flex flex-wrap justify-center gap-4 md:gap-8">
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Privacy Policy</Link>
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Terms of Service</Link>
          </div>
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
