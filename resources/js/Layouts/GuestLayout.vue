<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, 
  GraduationCap, Home, Newspaper, X, Eye, EyeOff
} from 'lucide-vue-next';
import FloatingChat from '@/Components/FloatingChat.vue';
import Swal from 'sweetalert2';

const props = defineProps({
  spotlightMode: {
    type: Boolean,
    default: false
  }
});

// --- STATE UNTUK LAYOUT & MODAL ---
const isLayananOpen = ref(false);
const isTentangKamiOpen = ref(false);
const isLoginModalOpen = ref(false);
const showPassword = ref(false);

const isScrolled = ref(false);
const isLangOpen = ref(false);
const page = usePage();
const currentLocale = computed(() => page.props.locale || 'id');

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Trigger initial check
    
    const params = new URLSearchParams(window.location.search);
    if (params.get('login') === 'true') {
      isLoginModalOpen.value = true;
      const newUrl = window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
    } else if (params.get('auth_timeout') === '1') {
      isLoginModalOpen.value = true;
      const newUrl = window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
      
      Swal.fire({
        title: 'Sesi Berakhir',
        text: 'Sistem telah keluar secara otomatis karena tidak adanya aktivitas. Silakan login kembali.',
        icon: 'warning',
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 shadow-[0_20px_50px_rgba(0,0,0,0.15)] bg-white text-slate-800 font-sans select-none',
          title: 'text-xl font-extrabold text-[#1A2B49] mb-2',
          htmlContainer: 'text-sm font-semibold text-slate-500 leading-relaxed my-4',
          confirmButton: 'bg-[#F9CC6B] hover:bg-[#e5bc62] text-[#1A2B49] font-black px-8 py-3 rounded-full text-xs shadow-md transition-all outline-none focus:ring-4 focus:ring-[#F9CC6B]/20 active:scale-95 cursor-pointer',
        }
      });
    }
  }
});

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', handleScroll);
  }
});

// --- JUMP / ANCHOR LINK NAVIGATION FOR TENTANG KAMI MEGA MENU ---
const navigateAndScroll = (id) => {
  isTentangKamiOpen.value = false;
  
  if (typeof window !== 'undefined') {
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    } else {
      router.visit('/', {
        data: { scrollTo: id },
        onSuccess: () => {
          setTimeout(() => {
            const el = document.getElementById(id);
            if (el) el.scrollIntoView({ behavior: 'smooth' });
          }, 300);
        }
      });
    }
  }
};

watch(
  () => usePage().url,
  (newUrl) => {
    if (typeof window !== 'undefined') {
      const urlObj = new URL(newUrl, window.location.origin);
      const scrollToId = urlObj.searchParams.get('scrollTo');
      if (scrollToId) {
        setTimeout(() => {
          const el = document.getElementById(scrollToId);
          if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
            // Clean up the query parameter from URL
            const cleanUrl = urlObj.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
          }
        }, 400); // 400ms delay to let the DOM settle
      }
    }
  },
  { immediate: true }
);

watch(
  () => usePage().props.flash?.logout_message,
  (msg) => {
    if (msg) {
      Swal.fire({
        title: 'Sudah Keluar',
        text: msg,
        icon: 'success',
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 shadow-[0_20px_50px_rgba(0,0,0,0.15)] bg-white text-slate-800 font-sans select-none',
          title: 'text-xl font-extrabold text-[#1A2B49] mb-2',
          htmlContainer: 'text-sm font-semibold text-slate-500 leading-relaxed my-4',
          confirmButton: 'bg-[#264790] hover:bg-[#1A2B49] text-white font-black px-8 py-3 rounded-full text-xs shadow-md transition-all outline-none focus:ring-4 focus:ring-[#264790]/20 active:scale-95 cursor-pointer',
        }
      });
    }
  },
  { immediate: true }
);

// Google Sign-In Simulation States
const isGooglePromptOpen = ref(false);

// Get auth details from Inertia page props
const pageProps = usePage().props;
const isLoggedIn = computed(() => !!pageProps.auth?.user);

// --- DATA MEGA MENU LAYANAN ---
const layananMenu = [
  { id: 'semua', title: 'Semua Kursus', desc: 'Seluruh Layanan Course, baik Offline atau Online', filter: 'Semua Kursus' },
  { id: 'smp', title: 'Kelas SMP', desc: 'Seluruh Layanan Course khusus untuk anak kelas SMP', filter: 'SMP' },
  { id: 'sd', title: 'Kelas SD', desc: 'Seluruh Layanan Course khusus untuk anak kelas Sekolah Dasar', filter: 'SD' },
  { id: 'sma', title: 'Kelas SMA', desc: 'Seluruh Layanan Course khusus untuk anak kelas SMA', filter: 'SMA' },
  { id: 'umum', title: 'Kelas Umum', desc: 'Seluruh Layanan Course khusus untuk Profesi atau Seminar Umum', filter: 'Umum' },
];

const handleLayananClick = (filterValue) => {
  isLayananOpen.value = false;
  if (filterValue === 'Semua Kursus') {
    router.get('/courses'); 
  } else {
    router.get('/courses', { filter: filterValue });
  }
};

// --- LOGIN FORM HANDLING ---
const loginForm = useForm({
  email: '',
  password: '',
  remember: true
});

const handleSignIn = () => {
  if (!loginForm.email || !loginForm.password) {
    alert('Silakan masukkan alamat email dan password Anda.');
    return;
  }

  loginForm.post(route('login'), {
    onSuccess: () => {
      isLoginModalOpen.value = false;
      loginForm.reset();
      alert('Selamat datang kembali!');
    },
    onError: (errors) => {
      let errMsg = Object.values(errors).join('\n');
      alert('Gagal Masuk:\n' + errMsg);
    }
  });
};

// --- GOOGLE SIGN IN / UP SIMULATOR ---
const triggerGoogleOAuth = () => {
  isGooglePromptOpen.value = true;
};

const selectGoogleAccount = (email) => {
  isGooglePromptOpen.value = false;
  
  if (email === 'student@drastha.com' || email === 'admin@drastha.com') {
    // If account already exists/signed up, log in automatically
    loginForm.email = email;
    loginForm.password = 'password'; // Seed password
    
    loginForm.post(route('login'), {
      onSuccess: () => {
        isLoginModalOpen.value = false;
        loginForm.reset();
        alert('Masuk dengan Akun Google: ' + email + ' Berhasil!');
      }
    });
  } else {
    // If account is new, close modal and redirect to our beautiful 3-step registration form pre-filled!
    isLoginModalOpen.value = false;
    alert('Akun Google (' + email + ') belum terdaftar. Anda akan diarahkan ke laman pendaftaran 3 langkah kami untuk melengkapi profil Anda.');
    router.get(route('register'), { email: email });
  }
};

const handleUserIconClick = () => {
  if (isLoggedIn.value) {
    router.get('/dashboard');
  } else {
    isLoginModalOpen.value = true;
  }
};

// Logo Helper referencing DL logo file or uploaded custom course builder logo
const Logo = () => {
  const customLogo = pageProps.settings?.course_logo;
  const logoUrl = (customLogo && customLogo !== '/images/logo-placeholder.png') 
    ? customLogo 
    : '/images/logo/logo_dl.png';
  return `<div class="flex items-center gap-2">
    <img src="${logoUrl}" alt="Drastha Learning Logo" class="h-10 w-auto object-contain" />
  </div>`;
};
</script>

<template>
  <div class="min-h-screen bg-[#F4F7F9] font-montserrat text-[#1A2B49] pb-24 md:pb-0 relative selection:bg-[#44A6D9] selection:text-white">
    
    <!-- 1. FLOATING HEADER DENGAN MEGA MENU -->
    <div 
      v-if="!spotlightMode"
      class="sticky z-40 w-full mx-auto transition-all duration-500 ease-in-out"
      :class="[
        isScrolled 
          ? 'top-4 px-4 sm:px-6 lg:px-8 max-w-7xl mb-8' 
          : 'top-4 px-4 mb-8 md:top-0 md:px-0 md:max-w-full md:mb-8'
      ]"
    >
      <header 
        class="w-full flex justify-between items-center relative transition-all duration-500 ease-in-out mx-auto"
        :class="[
          isScrolled 
            ? 'bg-[#FFFFFF] rounded-full px-8 py-3 shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-slate-100/50' 
            : 'bg-transparent border-transparent shadow-none px-8 py-3 md:rounded-t-none md:rounded-b-[30px] md:px-12 lg:px-16 md:py-6'
        ]"
      >
        
        <!-- Logo -->
        <Link href="/" class="hidden md:block cursor-pointer" v-html="Logo()"></Link>

        <!-- Navigasi Tengah -->
        <nav class="hidden md:flex items-center gap-8 font-medium text-[#1A2B49]/80">
          <Link href="/" class="hover:text-[#44A6D9] transition-colors">{{ $t('home') }}</Link>
          
          <!-- MENU: LAYANAN (MEGA MENU) -->
          <div 
            class="py-4 cursor-pointer"
            @mouseenter="isLayananOpen = true" 
            @mouseleave="isLayananOpen = false"
          >
            <button class="flex items-center gap-1 hover:text-[#44A6D9] text-[#264790] font-semibold transition-colors">
              <span>{{ $t('services') }}</span>
              <ChevronDown :size="16" :class="{'rotate-180': isLayananOpen}" class="transition-transform duration-300" />
            </button>
            
            <!-- Mega Menu Card (Layanan) -->
            <transition 
              enter-active-class="transition ease-out duration-200" 
              enter-from-class="opacity-0 translate-y-3" 
              enter-to-class="opacity-100 translate-y-0" 
              leave-active-class="transition ease-in duration-150" 
              leave-from-class="opacity-100 translate-y-0" 
              leave-to-class="opacity-0 translate-y-3"
            >
              <div 
                v-show="isLayananOpen" 
                class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-[650px] bg-white rounded-3xl shadow-[0_15px_40px_rgb(0,0,0,0.08)] border border-slate-100 p-8 z-50 cursor-default"
              >
                <!-- Grid 2 Kolom untuk layout Mega Menu -->
                <div class="grid grid-cols-2 gap-x-10 gap-y-8">
                  <button 
                    v-for="item in layananMenu" 
                    :key="item.id"
                    @click="handleLayananClick(item.filter)"
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-colors w-full"
                  >
                    <h4 class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">{{ item.title }}</h4>
                    <p class="text-slate-500 text-xs font-medium leading-relaxed">{{ item.desc }}</p>
                  </button>
                </div>
              </div>
            </transition>
          </div>

          <!-- MENU: TENTANG KAMI (MEGA MENU PLACEHOLDER) -->
          <div 
            class="py-4 cursor-pointer"
            @mouseenter="isTentangKamiOpen = true" 
            @mouseleave="isTentangKamiOpen = false"
          >
            <button class="flex items-center gap-1 hover:text-[#44A6D9] text-[#264790] font-semibold transition-colors">
              <span>{{ $t('about_us') }}</span>
              <ChevronDown :size="16" :class="{'rotate-180': isTentangKamiOpen}" class="transition-transform duration-300" />
            </button>
            
            <!-- Mega Menu Card (Tentang Kami) -->
            <transition 
              enter-active-class="transition ease-out duration-200" 
              enter-from-class="opacity-0 translate-y-3" 
              enter-to-class="opacity-100 translate-y-0" 
              leave-active-class="transition ease-in duration-150" 
              leave-from-class="opacity-100 translate-y-0" 
              leave-to-class="opacity-0 translate-y-3"
            >
              <div 
                v-show="isTentangKamiOpen" 
                class="absolute top-full left-1/2 -translate-x-1/2 mt-2 w-[650px] bg-white rounded-3xl shadow-[0_15px_40px_rgb(0,0,0,0.08)] border border-slate-100 p-8 z-50 cursor-default"
              >
                <!-- Grid 2 Kolom untuk layout Mega Menu -->
                <div class="grid grid-cols-2 gap-x-10 gap-y-8">
                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('tentang-kami')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">Tentang</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">Pelajari lebih lanjut tentang Drastha Learning</span>
                  </a>

                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('blog-aktivitas')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">Blog</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">Artikel Berita, Wawasan, dan Rekomendasi terbaru dari kami</span>
                  </a>

                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('tim-kami')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">Tim</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">Seluruh Tim yang ada di dalam struktural Drastha Learning</span>
                  </a>

                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('pilihan-kelas')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">Kelas</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">Seluruh Layanan Course khusus untuk Online/Offline Course</span>
                  </a>

                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('hubungi-kami')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">Kontak</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">Customer Service yang menyediakan layanan di Drastha Learning</span>
                  </a>
                </div>
              </div>
            </transition>
          </div>

          <Link href="/blogs" class="hover:text-[#44A6D9] transition-colors py-4">{{ $t('blog') }}</Link>
        </nav>

        <!-- Ikon Kanan Desktop -->
        <div class="hidden md:flex items-center gap-6 text-[#1A2B49]">
          <button @click="handleUserIconClick" class="hover:text-[#44A6D9] transition-colors outline-none" aria-label="Akun Pengguna atau Login">
            <User :size="22" />
          </button>
          <Link href="/cart" class="hover:text-[#44A6D9] transition-colors" aria-label="Keranjang Belanja">
            <ShoppingCart :size="22" />
          </Link>
          <div class="h-6 w-px bg-slate-200"></div>
          <div class="relative">
            <button 
              @click="isLangOpen = !isLangOpen" 
              class="flex items-center gap-1.5 hover:text-[#44A6D9] transition-colors font-semibold text-sm outline-none"
              aria-label="Pilih Bahasa / Select Language"
            >
              <Globe :size="20" />
              <span>{{ currentLocale === 'en' ? 'English' : 'Indonesia' }}</span>
              <ChevronDown :size="14" class="transition-transform duration-200" :class="{ 'rotate-180': isLangOpen }" />
            </button>
            
            <div 
              v-if="isLangOpen" 
              class="absolute right-0 mt-2 w-36 bg-white border border-slate-100 rounded-2xl shadow-xl py-2 z-50 animate-fadeIn"
            >
              <Link 
                href="/language/en" 
                @click="isLangOpen = false"
                class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-[#264790] transition-colors"
                :class="{ 'font-bold text-[#264790]': currentLocale === 'en' }"
              >
                English
              </Link>
              <Link 
                href="/language/id" 
                @click="isLangOpen = false"
                class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-[#264790] transition-colors"
                :class="{ 'font-bold text-[#264790]': currentLocale === 'id' }"
              >
                Indonesia
              </Link>
            </div>
          </div>
        </div>

        <!-- Header Mobile -->
        <div class="flex md:hidden justify-between items-center w-full px-2 gap-3">
          <div class="flex items-center gap-3">
            <Link href="/cart" class="text-[#1A2B49] hover:text-[#44A6D9] transition-colors" aria-label="Keranjang Belanja">
              <ShoppingCart :size="22" />
            </Link>
            <Link 
              :href="'/language/' + (currentLocale === 'en' ? 'id' : 'en')" 
              class="text-[#1A2B49] hover:text-[#44A6D9] transition-colors outline-none"
              aria-label="Ubah Bahasa / Switch Language"
            >
              <Globe :size="22" />
            </Link>
          </div>
          <div class="flex-grow flex justify-center">
            <Link href="/">
              <div v-html="Logo()"></div>
            </Link>
          </div>
          <button @click="handleUserIconClick" class="text-[#1A2B49] hover:text-[#44A6D9] transition-colors outline-none" aria-label="Akun Pengguna atau Login">
            <User :size="22" />
          </button>
        </div>

      </header>
    </div>

    <!-- KONTEN UTAMA HALAMAN -->
    <main>
      <slot />
    </main>

    <!-- GORGEOUS POP UP LOGIN MODAL (Persis Mockup Gambar) -->
    <div 
      v-if="isLoginModalOpen" 
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all duration-300"
    >
      <div 
        class="bg-white rounded-[2.5rem] overflow-hidden max-w-4xl w-full flex flex-col md:flex-row border border-slate-100 shadow-[0_20px_60px_rgba(0,0,0,0.15)] relative h-auto md:min-h-[580px] max-h-[90vh]"
      >
        <!-- Close Button -->
        <button 
          @click="isLoginModalOpen = false" 
          class="absolute top-6 right-6 z-10 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition-colors outline-none"
        >
          <X :size="16" />
        </button>

        <!-- LEFT PANEL: Illustration (40% width on Desktop) -->
        <div class="hidden md:flex md:w-[42%] relative bg-[#264790] overflow-hidden select-none shrink-0">
          <img 
            src="/images/pages/login_or_signup/bg-login-signup.png" 
            class="w-full h-full object-cover" 
            alt="Drastha Signup Background"
          />
        </div>

        <!-- RIGHT PANEL: Form (58% width on Desktop) -->
        <div class="w-full md:w-[58%] p-8 sm:p-12 md:p-16 flex flex-col justify-center">
          
          <h2 class="text-3xl font-extrabold text-[#1A2B49] tracking-tight mb-8">Sign In</h2>

          <form @submit.prevent="handleSignIn" class="flex flex-col gap-5">
            <!-- Email Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Email Address</label>
              <input 
                type="email" 
                v-model="loginForm.email"
                placeholder="email@domain.com" 
                required
                class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
              />
            </div>

            <!-- Password Field -->
            <div class="flex flex-col gap-2 relative">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Password</label>
              <div class="relative">
                <input 
                  :type="showPassword ? 'text' : 'password'" 
                  v-model="loginForm.password"
                  placeholder="password" 
                  required
                  class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 pr-12 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
                />
                <button 
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#264790] transition-colors p-1"
                >
                  <Eye v-if="!showPassword" :size="16" />
                  <EyeOff v-else :size="16" />
                </button>
              </div>
              <a href="#" class="self-end text-xs font-bold text-slate-400 hover:text-[#264790] transition-colors mt-1">Lupa Password?</a>
            </div>

            <!-- Sign In solid CTA button -->
            <button 
              type="submit"
              :disabled="loginForm.processing"
              class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-3"
            >
              Sign In
            </button>

            <!-- Create New Account soft CTA button -->
            <Link 
              :href="route('register')"
              @click="isLoginModalOpen = false"
              class="w-full bg-[#F4F7F9] hover:bg-slate-100 text-[#1A2B49] py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-sm transition-all text-center border border-slate-100"
            >
              Create New Account
            </Link>
          </form>

          <div class="w-full h-px bg-slate-100 my-6 relative">
            <div class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">atau</div>
          </div>

          <!-- BLACK GOOGLE SIGN IN BUTTON: Masuk / Daftar -->
          <button 
            @click="triggerGoogleOAuth"
            class="w-full bg-[#000000] hover:bg-[#1A2B49] text-white py-3.5 rounded-full font-extrabold text-xs sm:text-sm transition-all flex items-center justify-center gap-2.5 shadow-md"
          >
            <!-- Google Color Icon inside button -->
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
            </svg>
            <span>Masuk / Daftar</span>
          </button>

        </div>
      </div>
    </div>

    <!-- MOCK GOOGLE ACCOUNT SELECTION PROMPT -->
    <div 
      v-if="isGooglePromptOpen"
      class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm z-[60] flex items-center justify-center p-4 transition-all duration-300"
    >
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 sm:p-8 border border-slate-100 shadow-2xl flex flex-col gap-5 text-center">
        <!-- Close Account selector button -->
        <div class="flex justify-between items-center pb-2 border-b border-slate-50">
          <span class="font-bold text-xs text-slate-400 uppercase tracking-widest">Sign in with Google</span>
          <button @click="isGooglePromptOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
            <X :size="16" />
          </button>
        </div>

        <div class="flex flex-col items-center gap-2.5">
          <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center border border-slate-100 shadow-sm mb-2">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
            </svg>
          </div>
          <h3 class="text-[#1A2B49] font-extrabold text-base">Pilih Akun Google Anda</h3>
          <p class="text-[11px] text-slate-400 font-semibold leading-relaxed">Untuk melanjutkan ke Drastha Learning Platform</p>
        </div>

        <div class="flex flex-col gap-3 mt-2">
          <!-- Choice 1: Already Registered (Logs in automatically) -->
          <button 
            @click="selectGoogleAccount('student@drastha.com')"
            class="w-full flex items-center justify-between p-3.5 rounded-2xl border border-slate-100 hover:border-[#44A6D9] bg-slate-50/50 hover:bg-white text-left transition-all group"
          >
            <div class="flex flex-col">
              <span class="font-extrabold text-xs text-[#1A2B49] group-hover:text-[#264790]">Student Drastha</span>
              <span class="text-[10px] text-slate-400 font-semibold">student@drastha.com</span>
            </div>
            <span class="bg-emerald-100 text-emerald-700 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">Terdaftar</span>
          </button>

          <!-- Choice 2: Unregistered (Redirects to Pre-filled 3-step signup) -->
          <button 
            @click="selectGoogleAccount('newstudent@gmail.com')"
            class="w-full flex items-center justify-between p-3.5 rounded-2xl border border-slate-100 hover:border-[#44A6D9] bg-slate-50/50 hover:bg-white text-left transition-all group"
          >
            <div class="flex flex-col">
              <span class="font-extrabold text-xs text-[#1A2B49] group-hover:text-[#264790]">New Student</span>
              <span class="text-[10px] text-slate-400 font-semibold">newstudent@gmail.com</span>
            </div>
            <span class="bg-blue-100 text-blue-700 text-[8px] font-black px-2 py-0.5 rounded-full uppercase">Baru</span>
          </button>
        </div>
      </div>
    </div>

    <!-- BOTTOM NAVIGATION PWA -->
    <div v-if="!spotlightMode" class="md:hidden fixed bottom-0 left-0 right-0 bg-[#FFFFFF]/90 backdrop-blur-lg shadow-[0_-5px_15px_rgba(0,0,0,0.05)] rounded-t-3xl z-50 border-t border-gray-100">
      <div class="flex justify-around items-center h-20 px-4 relative">
        <Link href="/courses" class="flex flex-col items-center justify-center w-16 text-[#1A2B49] hover:text-[#44A6D9] transition-colors">
          <GraduationCap :size="26" stroke-width="2.5" />
          <span class="text-[11px] font-bold mt-1.5">Education</span>
        </Link>
        <Link href="/" class="bg-[#44A6D9] hover:bg-[#264790] text-white px-8 py-3 rounded-2xl shadow-md transition-colors flex items-center justify-center h-12 w-24">
          <Home :size="24" stroke-width="2.5" />
        </Link>
        <Link href="/blogs" class="flex flex-col items-center justify-center w-16 text-[#1A2B49] hover:text-[#44A6D9] transition-colors">
          <Newspaper :size="26" stroke-width="2.5" />
          <span class="text-[11px] font-bold mt-1.5">Blog</span>
        </Link>
      </div>
      <div class="h-safe-area-bottom bg-[#FFFFFF]"></div>
    </div>

    <!-- Global Floating Chat Support -->
    <FloatingChat />
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat {
  font-family: 'Montserrat', sans-serif;
}
</style>
