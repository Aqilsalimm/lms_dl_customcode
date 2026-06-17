<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, 
  GraduationCap, Home, Newspaper, X, Eye, EyeOff,
  LogOut, LayoutDashboard, LogIn, Check
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
const isMobileProfileMenuOpen = ref(false);
const page = usePage();
const currentLocale = computed(() => page.props.locale || 'id');

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Trigger initial check

    // Listener for Google OAuth Popup
    window.addEventListener('message', (event) => {
      if (event.origin !== window.location.origin) return;
      
      if (event.data.type === 'google-auth-success') {
        if (event.data.success) {
          isLoginModalOpen.value = false;
          Swal.fire({
            title: 'Berhasil Masuk',
            text: 'Selamat datang kembali!',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            customClass: {
              popup: 'rounded-[2rem] p-8 border border-slate-100 shadow-[0_20px_50px_rgba(0,0,0,0.15)] bg-white text-slate-800 font-sans select-none',
            }
          }).then(() => {
            window.location.href = event.data.redirect_url || '/dashboard';
          });
        } else {
          Swal.fire({
            title: 'Gagal Masuk',
            text: event.data.error || 'Terjadi kesalahan saat masuk dengan Google.',
            icon: 'error',
            confirmButtonText: 'Coba Lagi',
            buttonsStyling: false,
            customClass: {
              popup: 'rounded-[2rem] p-8 border border-slate-100 shadow-[0_20px_50px_rgba(0,0,0,0.15)] bg-white text-slate-800 font-sans select-none',
              confirmButton: 'bg-[#264790] hover:bg-[#1A2B49] text-white font-black px-8 py-3 rounded-full text-xs shadow-md transition-all outline-none focus:ring-4 focus:ring-[#264790]/20 active:scale-95 cursor-pointer',
            }
          });
        }
      }
    });
    
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



// Get auth details from Inertia page props
const pageProps = usePage().props;
const isLoggedIn = computed(() => !!pageProps.auth?.user);

// --- DATA MEGA MENU LAYANAN ---
const layananMenu = computed(() => [
  { 
    id: 'semua', 
    title: usePage().props.translations?.menu_all || 'Semua Kursus', 
    desc: usePage().props.translations?.menu_all_desc || 'Seluruh Layanan Course, baik Offline atau Online', 
    filter: 'Semua Kursus' 
  },
  { 
    id: 'smp', 
    title: usePage().props.translations?.menu_smp || 'Kelas SMP', 
    desc: usePage().props.translations?.menu_smp_desc || 'Seluruh Layanan Course khusus untuk anak kelas SMP', 
    filter: 'SMP' 
  },
  { 
    id: 'sd', 
    title: usePage().props.translations?.menu_sd || 'Kelas SD', 
    desc: usePage().props.translations?.menu_sd_desc || 'Seluruh Layanan Course khusus untuk anak kelas Sekolah Dasar', 
    filter: 'SD' 
  },
  { 
    id: 'sma', 
    title: usePage().props.translations?.menu_sma || 'Kelas SMA', 
    desc: usePage().props.translations?.menu_sma_desc || 'Seluruh Layanan Course khusus untuk anak kelas SMA', 
    filter: 'SMA' 
  },
  { 
    id: 'umum', 
    title: usePage().props.translations?.menu_umum || 'Kelas Umum', 
    desc: usePage().props.translations?.menu_umum_desc || 'Seluruh Layanan Course khusus untuk Profesi atau Seminar Umum', 
    filter: 'Umum' 
  },
]);

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

// --- GOOGLE SIGN IN / UP ---
const triggerGoogleOAuth = () => {
  const width = 500;
  const height = 600;
  const left = (window.innerWidth / 2) - (width / 2);
  const top = (window.innerHeight / 2) - (height / 2);
  
  window.open(
    route('auth.google'),
    'GoogleLoginPopup',
    `width=${width},height=${height},left=${left},top=${top},status=no,location=no,menubar=no,toolbar=no`
  );
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
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">{{ $t('sub_about') }}</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">{{ $t('sub_about_desc') }}</span>
                  </a>

                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('blog-aktivitas')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">{{ $t('sub_blog') }}</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">{{ $t('sub_blog_desc') }}</span>
                  </a>

                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('pilihan-kelas')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">{{ $t('sub_class') }}</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">{{ $t('sub_class_desc') }}</span>
                  </a>

                  <a 
                    href="#" 
                    @click.prevent="navigateAndScroll('hubungi-kami')" 
                    class="group flex flex-col items-start text-left text-[#1A2B49] hover:bg-slate-50 p-3 -m-3 rounded-2xl transition-all w-full"
                  >
                    <span class="font-bold text-base mb-1 group-hover:text-[#44A6D9] transition-colors">{{ $t('sub_contact') }}</span>
                    <span class="text-slate-500 text-xs font-medium leading-relaxed">{{ $t('sub_contact_desc') }}</span>
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
        <div class="flex md:hidden justify-between items-center w-full px-2 gap-3 relative">
          
          <!-- 1. Left Section (Cart) -->
          <div class="flex items-center gap-3 w-10">
            <Link href="/cart" class="text-[#1A2B49] hover:text-[#44A6D9] transition-colors" aria-label="Keranjang Belanja">
              <ShoppingCart :size="22" />
            </Link>
          </div>

          <!-- 2. Middle Section: Brand Logo (Decluttered) -->
          <div class="flex-grow flex justify-center">
            <Link href="/">
              <div v-html="Logo()"></div>
            </Link>
          </div>

          <!-- 3. Right Section: User Profile & Popover -->
          <div class="relative w-10 flex justify-end">
            
            <!-- Trigger Button -->
            <button 
              @click="isMobileProfileMenuOpen = !isMobileProfileMenuOpen" 
              class="flex items-center justify-center w-9 h-9 rounded-full bg-slate-50 border border-slate-100 text-[#1A2B49] hover:text-[#44A6D9] active:scale-95 transition-all outline-none"
              aria-label="Menu profil dan pengaturan"
            >
              <User :size="20" stroke-width="2" />
            </button>

            <!-- Transparent Overlay Mask for "Click Outside" to close popover -->
            <div 
              v-if="isMobileProfileMenuOpen" 
              class="fixed inset-0 z-40 bg-transparent cursor-default" 
              @click="isMobileProfileMenuOpen = false"
            ></div>

            <!-- Popover Menu with Smooth Enter/Leave Animations -->
            <Transition
              enter-active-class="transition ease-out duration-200 transform"
              enter-from-class="opacity-0 translate-y-2 scale-95"
              enter-to-class="opacity-100 translate-y-0 scale-100"
              leave-active-class="transition ease-in duration-150 transform"
              leave-from-class="opacity-100 translate-y-0 scale-100"
              leave-to-class="opacity-0 translate-y-2 scale-95"
            >
              <div 
                v-if="isMobileProfileMenuOpen" 
                class="absolute right-0 mt-12 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50 origin-top-right text-left"
              >
                <!-- AUTHENTICATION STATE RENDERING -->
                
                <!-- Case A: Authenticated User -->
                <div v-if="isLoggedIn" class="p-4 flex flex-col gap-3">
                  <div class="pb-2">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Welcome back</p>
                    <h4 class="font-extrabold text-sm text-[#1A2B49] truncate mt-0.5">
                      Hi, {{ pageProps.auth.user.name }}
                    </h4>
                  </div>
                  
                  <Link 
                    href="/dashboard" 
                    @click="isMobileProfileMenuOpen = false"
                    class="flex items-center gap-2.5 px-3 py-2.5 text-xs font-bold text-slate-700 hover:text-[#264790] hover:bg-slate-50 rounded-xl transition-all"
                  >
                    <LayoutDashboard :size="16" class="text-slate-400" />
                    <span>My Dashboard</span>
                  </Link>

                  <Link 
                    href="/logout" 
                    method="post" 
                    as="button" 
                    class="w-full flex items-center gap-2.5 px-3 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50/50 rounded-xl transition-all text-left"
                  >
                    <LogOut :size="16" />
                    <span>Logout</span>
                  </Link>
                </div>

                <!-- Case B: Guest User -->
                <div v-else class="p-4 flex flex-col gap-2">
                  <div class="pb-1">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Account Access</p>
                  </div>
                  
                  <button 
                    @click="isLoginModalOpen = true; isMobileProfileMenuOpen = false"
                    class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-xl font-extrabold text-xs shadow-md transition-all flex items-center justify-center gap-2"
                  >
                    <LogIn :size="16" />
                    <span>Sign In / Register</span>
                  </button>
                </div>

                <!-- VISUALLY SEPARATED LANGUAGE SECTION (Always Visible) -->
                <div class="border-t border-gray-100 bg-slate-50/40 p-4">
                  <span class="flex items-center gap-1.5 text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-3">
                    <Globe :size="12" />
                    Language / Bahasa
                  </span>
                  
                  <div class="grid grid-cols-2 gap-2 bg-slate-100/60 p-1 rounded-xl">
                    <!-- Switch to English -->
                    <Link 
                      href="/language/en" 
                      @click="isMobileProfileMenuOpen = false"
                      class="flex items-center justify-center gap-1.5 py-2 text-[11px] font-extrabold rounded-lg transition-all"
                      :class="currentLocale === 'en' 
                        ? 'bg-white text-[#264790] shadow-sm' 
                        : 'text-slate-500 hover:text-slate-800'"
                    >
                      <span>EN</span>
                      <Check v-if="currentLocale === 'en'" :size="10" stroke-width="3" />
                    </Link>
                    
                    <!-- Switch to Indonesian -->
                    <Link 
                      href="/language/id" 
                      @click="isMobileProfileMenuOpen = false"
                      class="flex items-center justify-center gap-1.5 py-2 text-[11px] font-extrabold rounded-lg transition-all"
                      :class="currentLocale === 'id' 
                        ? 'bg-white text-[#264790] shadow-sm' 
                        : 'text-slate-500 hover:text-slate-800'"
                    >
                      <span>ID</span>
                      <Check v-if="currentLocale === 'id'" :size="10" stroke-width="3" />
                    </Link>
                  </div>
                </div>

              </div>
            </Transition>
          </div>
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
          
          <h2 class="text-3xl font-extrabold text-[#1A2B49] tracking-tight mb-8">{{ $t('login') || 'Sign In' }}</h2>

          <form @submit.prevent="handleSignIn" class="flex flex-col gap-5">
            <!-- Email Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">{{ $t('email_title') || 'Email Address' }}</label>
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
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">{{ $t('password') || 'Password' }}</label>
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
              <a href="#" class="self-end text-xs font-bold text-slate-400 hover:text-[#264790] transition-colors mt-1">{{ $t('forgot_password') || 'Lupa Password?' }}</a>
            </div>

            <!-- Sign In solid CTA button -->
            <button 
              type="submit"
              :disabled="loginForm.processing"
              class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-3"
            >
              {{ $t('login') || 'Sign In' }}
            </button>

            <!-- Create New Account soft CTA button -->
            <Link 
              :href="route('register')"
              @click="isLoginModalOpen = false"
              class="w-full bg-[#F4F7F9] hover:bg-slate-100 text-[#1A2B49] py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-sm transition-all text-center border border-slate-100"
            >
              {{ $t('create_new_account') || 'Create New Account' }}
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
            <span>{{ $t('login_register') || 'Masuk / Daftar' }}</span>
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
