<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { 
  Users, 
  Image as ImageIcon,
  GraduationCap, 
  Search, 
  Calendar, 
  Clock, 
  MapPin, 
  Code, 
  Calculator, 
  BookText,
  Phone,
  Mail,
  Globe,
  MessageCircle,
  Instagram,
  Twitter,
  Facebook,
  Linkedin
} from 'lucide-vue-next';

const formatPrice = (price) => {
  if (!price) return 'Rp 500.000';
  const num = parseFloat(price);
  if (isNaN(num)) return price;
  return 'Rp ' + Math.round(num).toLocaleString('id-ID');
};

const translateMetadata = (value) => {
  if (!value) return '';
  const isEn = usePage().props.locale === 'en';
  if (!isEn) return value;

  let str = value;
  // Replace patterns
  str = str.replace(/Sesi/g, 'Sessions');
  str = str.replace(/per Minggu/g, 'per Week');
  str = str.replace(/Jam/g, 'Hours');
  str = str.replace(/untuk/g, 'for');
  str = str.replace(/Menit/g, 'Minutes');
  str = str.replace(/Kelas Luring/g, 'Offline Class');
  str = str.replace(/Kelas Daring/g, 'Online Class');
  str = str.replace(/Kelas Offline/g, 'Offline Class');
  str = str.replace(/Kelas Online/g, 'Online Class');
  return str;
};

const formatDate = (dateRaw) => {
  if (!dateRaw) return '';
  const date = new Date(dateRaw);
  const locale = usePage().props.locale || 'id';
  return new Intl.DateTimeFormat(locale === 'en' ? 'en-US' : 'id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(date);
};

const translateCategory = (category) => {
  if (!category) return '';
  const isEn = usePage().props.locale === 'en';
  if (!isEn) return category;
  
  const mapping = {
    'Sertifikasi': 'Certification',
    'Seminar': 'Seminar',
    'Workshop': 'Workshop',
    'Coding IT Class': 'Coding IT Class',
    'Design Class': 'Design Class'
  };
  return mapping[category] || category;
};

// Mendefinisikan Props untuk menangkap data dari Controller Laravel (Inertia)
const props = defineProps({
  // 'courses' akan menerima data dari backend. 
  // Jika backend belum siap, ia akan menggunakan data default di bawah ini.
  courses: {
    type: Array,
    default: () => [
      {
        id: 1,
        title: 'Python Class : Pemrograman dan Perkenalan Bahasa Python',
        bg_color: '#FF4D4F',
        icon_type: 'code',
        sessions: 'Two Session per Week',
        duration: '1 Hour for 1 Session',
        type: 'Offline Class',
        price: 'Rp 500.000',
        period: '/ Bulan'
      },
      {
        id: 2,
        title: 'Audit Class : Pengenalan Audit Forensik Dasar untuk Pemula',
        bg_color: '#73D13D',
        icon_type: 'calculator',
        sessions: 'Two Session per Week',
        duration: '1 Hour for 1 Session',
        type: 'Offline Class',
        price: 'Rp 500.000',
        period: '/ Bulan'
      },
      {
        id: 3,
        title: 'Python Class : Pemrograman dan Perkenalan Bahasa Python',
        bg_color: '#FF4D4F',
        icon_type: 'code',
        sessions: 'Two Session per Week',
        duration: '1 Hour for 1 Session',
        type: 'Offline Class',
        price: 'Rp 500.000',
        period: '/ Bulan'
      },
      {
        id: 4,
        title: 'IPS Class : Sejarah Penyebaran Agama Kristen di Indonesia',
        bg_color: '#40A9FF',
        icon_type: 'book',
        sessions: 'Two Session per Week',
        duration: '1 Hour for 1 Session',
        type: 'Offline Class',
        price: 'Rp 500.000',
        period: '/ Bulan'
      },
      {
        id: 5,
        title: 'Python Class : Pemrograman dan Perkenalan Bahasa Python',
        bg_color: '#FF4D4F',
        icon_type: 'code',
        sessions: 'Two Session per Week',
        duration: '1 Hour for 1 Session',
        type: 'Offline Class',
        price: 'Rp 500.000',
        period: '/ Bulan'
      },
      {
        id: 6,
        title: 'PKN Class : Ideologi Pancasila Untuk Kualitas Hidup Bahagia',
        bg_color: '#F759AB',
        icon_type: 'book',
        sessions: 'Two Session per Week',
        duration: '1 Hour for 1 Session',
        type: 'Offline Class',
        price: 'Rp 500.000',
        period: '/ Bulan'
      }
    ]
  }
});

const teamMembers = [
  { id: 1, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 2, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 3, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 4, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 5, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 6, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 7, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 8, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 9, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
  { id: 10, name: 'Dondo Ferdinanto, SE., CTC.', role: 'Direktur', image: '/images/pages/welcome/Nak_Kanak/pakDondo.png' },
];

const blogPostsRaw = [
  {
    id: 1,
    title: 'Belajar PHP, MySQL dengan Asik dan Menyenangkan.',
    category: 'Coding IT Class',
    date: '19 Februari 2026',
    date_raw: '2026-02-19', 
    image: 'https://placehold.co/600x400/f4f7f9/44a6d9?text=Thumbnail+Blog+1',
    author_name: 'Admin Drastha Learning',
    author_avatar: 'https://placehold.co/100x100/264790/ffffff?text=A'
  },
  {
    id: 2,
    title: 'Membangun Fundamental UI/UX Design untuk Pemula.',
    category: 'Design Class',
    date: '21 Februari 2026',
    date_raw: '2026-02-21', 
    image: 'https://placehold.co/600x400/f4f7f9/44a6d9?text=Thumbnail+Blog+2',
    author_name: 'Admin Drastha Learning',
    author_avatar: 'https://placehold.co/100x100/264790/ffffff?text=A'
  },
  {
    id: 3,
    title: 'Pentingnya Digitalisasi Manajemen Aset Pemerintahan.',
    category: 'Seminar',
    date: '15 Februari 2026',
    date_raw: '2026-02-15',
    image: 'https://placehold.co/600x400/f4f7f9/44a6d9?text=Thumbnail+Blog+3',
    author_name: 'Admin Drastha Learning',
    author_avatar: 'https://placehold.co/100x100/264790/ffffff?text=A'
  },
  {
    id: 4,
    title: 'Pelatihan Sertifikasi Akuntansi Dasar Tingkat Nasional.',
    category: 'Sertifikasi',
    date: '10 Februari 2026',
    date_raw: '2026-02-10',
    image: 'https://placehold.co/600x400/f4f7f9/44a6d9?text=Thumbnail+Blog+4',
    author_name: 'Admin Drastha Learning',
    author_avatar: 'https://placehold.co/100x100/264790/ffffff?text=A'
  },
  {
    id: 5,
    title: 'Workshop Penggunaan AI dalam Dunia Pajak.',
    category: 'Workshop',
    date: '25 Februari 2026',
    date_raw: '2026-02-25',
    image: 'https://placehold.co/600x400/f4f7f9/44a6d9?text=Thumbnail+Blog+5',
    author_name: 'Admin Drastha Learning',
    author_avatar: 'https://placehold.co/100x100/264790/ffffff?text=A'
  },
  {
    id: 6,
    title: 'Belajar Fundamental Hukum Bisnis Era Digital.',
    category: 'Legal Class',
    date: '05 Februari 2026',
    date_raw: '2026-02-05',
    image: 'https://placehold.co/600x400/f4f7f9/44a6d9?text=Thumbnail+Blog+6',
    author_name: 'Admin Drastha Learning',
    author_avatar: 'https://placehold.co/100x100/264790/ffffff?text=A'
  }
];

const sortedBlogPosts = blogPostsRaw.sort((a, b) => new Date(b.date_raw) - new Date(a.date_raw));

// Data Info Kontak (reactive using computed)
const contactInfo = computed(() => [
  { 
    id: 1, 
    title: usePage().props.translations?.phone_title || 'Telepon', 
    icon: Phone, 
    lines: ['031-9960-5068', '0812-3485-9768'] 
  },
  { 
    id: 2, 
    title: usePage().props.translations?.address_title || 'Alamat', 
    icon: MapPin, 
    lines: [
      'PT. Drastha Berkah Sentosa, Jl Budi Luhur B/2', 
      'Wagir Indah Kwangsan, Sedati', 
      'Sidoarjo, Jawa Timur 61253'
    ] 
  },
  { 
    id: 3, 
    title: usePage().props.translations?.email_title || 'Email', 
    icon: Mail, 
    lines: ['admin@drasthabest.com', 'admin@drasthalearning.com'] 
  },
  { 
    id: 4, 
    title: usePage().props.translations?.website_title || 'Website', 
    icon: Globe, 
    lines: ['www.drasthalearning.com'] 
  },
]);

// Data untuk kolom fitur kiri (reactive using computed)
const leftFeatures = computed(() => [
  {
    title: usePage().props.translations?.feature_1_title || 'Harga Kompetitif',
    description: usePage().props.translations?.feature_1_desc || 'Harga terjangkau dan kompetitif untuk menghemat biaya anda.',
    icon: '/images/logo/HargaKompetitifIcon.svg'
  },
  {
    title: usePage().props.translations?.feature_2_title || 'Gratis Konsultasi',
    description: usePage().props.translations?.feature_2_desc || 'Konsultasi gratis untuk pemilihan Peta jalan yang tepat.',
    icon: '/images/logo/GratisKonsultasiIkon.svg'
  }
]);

const rightFeatures = computed(() => [
  {
    title: usePage().props.translations?.feature_3_title || 'Pembayaran Fleksibel',
    description: usePage().props.translations?.feature_3_desc || 'Sistem pembayaran yang fleksibel and bisa bertahap dengan DP 70%',
    icon: '/images/logo/FlexiblePaymenticon.svg'
  },
  {
    title: usePage().props.translations?.feature_4_title || 'Produk yang Disesuaikan',
    description: usePage().props.translations?.feature_4_desc || 'Susunan materi yang disesuaikan dengan kebutuhan profesi anda.',
    icon: '/images/logo/CustomizableProductIcon.svg'
  }
]);
</script>

<template>
    <Head title="Beranda | Drastha Learning" />
    
    <GuestLayout>
      <!-- MAIN CONTENT -->
      <main class="font-montserrat">
        
        <!-- 2. HERO SECTION FOKUS -->
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-12">
          
          <!-- Container Card Hero: Background Surface (#FFFFFF) -->
          <div class="bg-transparent rounded-[2.5rem] md:rounded-[3rem] p-8 md:p-12 lg:p-16 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100/50 flex flex-col md:flex-row items-center justify-between gap-12 lg:gap-8">
            
            <!-- Teks Konten -->
            <div class="w-full md:w-1/2 flex flex-col items-center md:items-start text-center md:text-left space-y-5 lg:pr-8">
              
              <div class="inline-flex items-center px-5 py-2 rounded-full border-[1.5px] border-[#1A2B49]/40 bg-slate-200/50 text-[#1A2B49] font-bold text-sm tracking-wide">
                #BelajarLebihTerarah
              </div>
              
              <h1 class="text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold text-[#000000] leading-[1.1] tracking-tight">
                {{ $t('hero_title') }}
              </h1>
              
              <p class="text-[#264790] text-base md:text-lg leading-relaxed max-w-lg font-medium">
                {{ $t('hero_subtitle') }}
              </p>
              
              <!-- Tombol Aksi -->
              <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto pt-4">
                <button class="bg-[#44A6D9] hover:bg-[#3b8fc2] text-[#000000] font-bold py-2.5 px-6 text-sm rounded-full transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                  {{ $t('start_learning') }}
                </button>
                
                <button class="bg-[#F9CC6B] hover:bg-[#e5bc62] text-[#000000] font-bold py-2.5 px-6 text-sm rounded-full transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                  {{ $t('contact_admin') }} 
                  <svg class="w-3.5 h-3.5 fill-current text-[#000000]" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm6.59-4.846c1.6.95 3.498 1.45 5.419 1.451 5.564 0 10.09-4.524 10.092-10.09.002-2.697-1.047-5.234-2.951-7.138-1.905-1.905-4.439-2.953-7.134-2.954-5.568 0-10.096 4.523-10.099 10.09-.001 1.923.501 3.8 1.453 5.4l-.953 3.486 3.573-.939zm10.163-7.113c-.278-.139-1.643-.811-1.897-.904-.254-.093-.44-.139-.626.139-.186.278-.718.904-.88.1.093-.162.186-.324.463-.463.278-.139.278-.231.417-.37.139-.139.07-.278-.035-.417-.104-.278-.88-2.129-1.206-2.917-.318-.765-.64-.662-.88-.674-.227-.012-.487-.014-.748-.014-.26 0-.683.097-1.04.487-.358.39-1.367 1.343-1.367 3.272 0 1.93 1.4 3.79 1.597 4.047.196.257 2.756 4.21 6.677 5.903.933.402 1.662.642 2.228.822.938.298 1.792.256 2.467.155.752-.112 2.298-.94 2.622-1.848.324-.908.324-1.686.227-1.848-.097-.162-.358-.254-.636-.394z"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Area Gambar Hero (Menggunakan Tag IMG dengan GIF) -->
            <div class="w-full md:w-1/2 flex justify-center md:justify-end">
              <div class="relative w-full max-w-md flex justify-center items-center">
                
                <img 
                  src="/images/pages/welcome/welcome_beranda.gif" 
                  alt="Ilustrasi Hero Drastha Learning" 
                  class="w-full h-auto object-contain relative z-10"
                  width="500"
                  height="400"
                  fetchpriority="high"
                />

              </div>
            </div>

          </div>
        </section>

        <!-- 3. TENTANG DRASTHA LEARNING SECTION -->
        <section id="tentang-kami" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16 text-center scroll-mt-24">
          <h2 class="text-3xl md:text-4xl font-extrabold text-[#1A2B49] mb-4">
            {{ $t('about_title') }} <span class="text-[#264790]">Drastha Learning</span>
          </h2>
          <p class="text-[#264790] leading-relaxed text-base md:text-lg mb-10 md:mb-14 font-medium px-2">
            <span class="font-bold text-[#1A2B49]">Drastha Learning</span> {{ $t('about_subtitle') }}
          </p>
          <div class="bg-[#FFFFFF] rounded-[2rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/50 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-shadow duration-300">
            <h3 class="text-2xl md:text-3xl font-bold text-[#1A2B49] mb-4">
              {{ $t('commitment_title') }}
            </h3>
            <p class="text-[#264790] leading-relaxed text-sm md:text-base font-medium">
              <span class="font-bold text-[#1A2B49]">Drastha Learning</span> {{ $t('commitment_desc') }}
            </p>
          </div>
        </section>
 
        <!-- 4. WHY CHOOSE US SECTION -->
        <section class="py-16 bg-transparent font-montserrat">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            
            <h2 class="text-4xl md:text-5xl font-extrabold text-[#1A2B49] mb-6 leading-tight">
              {{ $t('why_choose_us_title') }} <span class="text-[#264790]">{{ $t('why_choose_us_span') }}</span>
            </h2>
            <p class="text-[#264790] max-w-3xl mx-auto mb-20 text-base md:text-lg leading-relaxed font-medium">
              {{ $t('why_choose_us_subtitle') }}
            </p>
 
            <div class="flex flex-col lg:flex-row items-center justify-center lg:gap-24 gap-12">
              
              <div class="flex flex-col gap-12 w-full lg:w-1/3">
                <div 
                  v-for="(feature, index) in leftFeatures" 
                  :key="index" 
                  class="bg-transparent p-8 rounded-3xl flex flex-col items-center text-center transition-all duration-300 hover:bg-white hover:shadow-xl hover:-translate-y-1"
                >
                  <div class="w-16 h-16 mb-5 flex items-center justify-center"><img :src="feature.icon" class="w-full h-full object-contain" :alt="feature.title" width="64" height="64" loading="lazy" /></div>
                  <h3 class="text-2xl font-bold text-[#2b478b] mb-2 leading-tight">{{ feature.title }}</h3>
                  <p class="text-base text-gray-500 leading-relaxed">{{ feature.description }}</p>
                </div>
              </div>
 
              <div class="w-full lg:w-1/3 flex justify-center py-10 lg:py-0 relative">
                <img 
                  src="/images/pages/welcome/whychooseus_new.gif" 
                  alt="Drastha Learning Advantages" 
                  class="w-full max-w-xl object-contain relative z-10"
                  width="500"
                  height="500"
                  loading="lazy"
                />
              </div>
 
              <div class="flex flex-col gap-12 w-full lg:w-1/3">
                <div 
                  v-for="(feature, index) in rightFeatures" 
                  :key="index" 
                  class="bg-transparent p-8 rounded-3xl flex flex-col items-center text-center transition-all duration-300 hover:bg-white hover:shadow-xl hover:-translate-y-1"
                >
                  <div class="w-16 h-16 mb-5 flex items-center justify-center"><img :src="feature.icon" class="w-full h-full object-contain" :alt="feature.title" width="64" height="64" loading="lazy" /></div>
                  <h3 class="text-2xl font-bold text-[#2b478b] mb-2 leading-tight">{{ feature.title }}</h3>
                  <p class="text-base text-gray-500 leading-relaxed">{{ feature.description }}</p>
                </div>
              </div>
 
            </div>
 
            <div class="mt-20 flex justify-center">
              <button class="bg-[#fbc02d] hover:bg-[#f9a825] text-[#1A2B49] font-bold py-3.5 px-8 rounded-full transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                {{ $t('get_free_demo') }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                </svg>
              </button>
            </div>
 
          </div>
        </section>
 
        <!-- 5. PILIHAN BERBAGAI KELAS SECTION -->
        <section id="pilihan-kelas" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 scroll-mt-24">
          
          <div class="flex flex-col items-center text-center mb-12 md:mb-16">
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full border-[1.5px] border-[#1A2B49] bg-transparent mb-6">
              <GraduationCap :size="18" class="text-[#1A2B49]" />
              <span class="text-[#1A2B49] font-bold text-sm">{{ $t('our_program') }}</span>
            </div>
 
            <h2 class="text-3xl md:text-[2.5rem] font-extrabold text-[#1A2B49] mb-4">
              {{ $t('course_choice_title') }} <span class="text-[#264790]">{{ $t('course_choice_span') }}</span>
            </h2>
 
            <p class="text-[#264790] text-base md:text-lg font-medium max-w-2xl mx-auto">
              {{ $t('course_choice_desc') }}
            </p>
          </div>
 
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            
            <Link 
              v-for="course in courses" 
              :key="course.id"
              :href="'/courses/' + (course.slug || 'python-class')"
              class="bg-[#FFFFFF] rounded-2xl md:rounded-[1.5rem] overflow-hidden shadow-[0_4px_20px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] cursor-pointer"
            >
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
                  width="360"
                  height="200"
                  loading="lazy"
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
 
              <div class="p-6 md:p-8 flex flex-col flex-grow">
                
                <h3 class="text-[#1A2B49] font-extrabold text-lg md:text-xl leading-snug mb-5 line-clamp-2 min-h-[3rem]">
                  {{ course.title }}
                </h3>
 
                <ul class="space-y-3 mb-6">
                  <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                     <Calendar :size="16" class="text-slate-400" />
                    <span>{{ translateMetadata(course.sessions || 'Two Session per Week') }}</span>
                  </li>
                  <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                    <Clock :size="16" class="text-slate-400" />
                    <span>{{ translateMetadata(course.duration || '1 Hour for 1 Session') }}</span>
                  </li>
                  <li class="flex items-center gap-3 text-slate-500 text-sm font-medium">
                    <MapPin :size="16" class="text-slate-400" />
                    <span>{{ translateMetadata(course.type || 'Offline Class') }}</span>
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
 
          </div>
 
          <div class="mt-12 flex justify-center">
            <Link 
              href="/courses" 
              class="bg-[#44A6D9] hover:bg-[#3b8fc2] text-[#1A2B49] font-bold py-3.5 px-8 rounded-full shadow-md transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2"
            >
              {{ $t('search_all_courses') }} 
              <Search :size="18" :stroke-width="2.5" />
            </Link>
          </div>
 
        </section>
 
        <!-- TIM PROFESIONAL SECTION (Non-aktif Sementara) -->
        <!--
        <section id="tim-kami" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 scroll-mt-24">
          
          <div class="flex flex-col items-center text-center mb-12">
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full border-[1.5px] border-[#1A2B49] bg-transparent mb-6">
              <Users :size="18" class="text-[#1A2B49]" />
              <span class="text-[#1A2B49] font-bold text-sm">Tim Profesional</span>
            </div>

            <h2 class="text-3xl md:text-[2.5rem] font-extrabold text-[#1A2B49] mb-4">
              Tim <span class="text-[#264790]">Drastha Learning</span>
            </h2>

            <p class="text-[#44A6D9] text-base md:text-lg font-medium">
              Terdiri dari susunan tim profesional yang ahli di Bidangnya
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
            
            <div 
              v-for="member in teamMembers" 
              :key="member.id"
              class="group bg-[#FFFFFF] border border-[#264790]/20 rounded-3xl p-6 flex flex-col items-center text-center transition-all duration-300 cursor-pointer hover:bg-[#F9CC6B] hover:border-[#F9CC6B] hover:shadow-[0_8px_30px_rgb(249,204,107,0.3)] hover:-translate-y-1.5"
            >
              <div class="w-28 h-28 bg-[#FFFFFF] rounded-full mb-5 overflow-hidden flex items-center justify-center transition-colors duration-300 shadow-sm border border-slate-50 group-hover:border-white/50">
                <img 
                  :src="member.image" 
                  :alt="member.name" 
                  class="w-full h-full object-cover object-center relative z-10" 
                />
              </div>
              
              <h3 class="text-[#1A2B49] font-bold text-[13px] mb-1.5 leading-snug">
                {{ member.name }}
              </h3>
              <p class="text-[#44A6D9] group-hover:text-[#264790] transition-colors duration-300 text-xs font-semibold">
                {{ member.role }}
              </p>
            </div>

          </div>

          <div class="flex justify-center items-center gap-3 mt-10">
            <button class="w-3 h-3 rounded-full bg-[#1A2B49] transition-all"></button>
            <button class="w-3 h-3 rounded-full bg-[#44A6D9]/50 hover:bg-[#44A6D9] transition-all"></button>
            <button class="w-3 h-3 rounded-full bg-[#44A6D9]/50 hover:bg-[#44A6D9] transition-all"></button>
            <button class="w-3 h-3 rounded-full bg-[#44A6D9]/50 hover:bg-[#44A6D9] transition-all"></button>
          </div>

        </section>
        -->

        <!-- BLOG AKTIVITAS SECTION -->
        <section id="blog-aktivitas" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 scroll-mt-24">
 
          <!-- Header Section -->
          <div class="flex flex-col items-center text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full border-[1.5px] border-[#1A2B49] bg-transparent mb-6">
              <ImageIcon :size="18" class="text-[#1A2B49]" />
              <span class="text-[#1A2B49] font-bold text-sm">{{ $t('our_activities') }}</span>
            </div>
 
            <!-- Judul -->
            <h2 class="text-3xl md:text-[2.5rem] font-extrabold text-[#1A2B49] mb-4">
              {{ $t('blog_activities_title') }} <span class="text-[#264790]">{{ $t('blog_activities_span') }}</span>
            </h2>
 
            <!-- Sub-judul -->
            <p class="text-[#264790] text-base md:text-lg font-medium">
              {{ $t('blog_activities_desc') }}
            </p>
          </div>

          <!-- Grid Container Blog Cards (3 Kolom di Desktop) -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <!-- Loop Blog Card Template -->
            <div 
              v-for="post in sortedBlogPosts" 
              :key="post.id"
              class="bg-[#FFFFFF] rounded-[2rem] overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex flex-col transition-transform duration-300 hover:-translate-y-2 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] cursor-pointer p-4 pb-6"
            >
              <!-- Image Thumbnail -->
              <div class="w-full h-52 overflow-hidden rounded-[1.5rem]">
                <img 
                  :src="post.image" 
                  :alt="post.title" 
                  class="w-full h-full object-cover transition-transform duration-500 hover:scale-105" 
                  width="400"
                  height="208"
                  loading="lazy"
                />
              </div>

              <!-- Content Card -->
              <div class="pt-5 px-2 flex flex-col flex-1">
                <!-- Meta: Category & Date -->
                <div class="flex justify-between items-center mb-4">
                  <span class="bg-[#F9CC6B] text-[#1A2B49] text-[11px] font-extrabold px-3 py-1.5 rounded-full uppercase tracking-wide">
                    {{ translateCategory(post.category) }}
                  </span>
                  <span class="text-[#1A2B49]/60 text-xs font-semibold">
                    {{ formatDate(post.date_raw) }}
                  </span>
                </div>

                <!-- Title (Line clamp membatasi teks maksimal 2 baris agar rapi) -->
                <h3 class="text-lg md:text-xl font-bold text-[#1A2B49] leading-snug mb-4 line-clamp-2">
                  {{ post.title }}
                </h3>

                <!-- Flex-grow mendorong author ke paling bawah -->
                <div class="flex-grow"></div>

                <!-- Author -->
                <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                  <img :src="post.author_avatar" :alt="post.author_name" class="w-6 h-6 rounded-full object-cover" width="24" height="24" loading="lazy" />
                  <span class="text-[#264790] text-xs font-semibold">{{ post.author_name }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Tombol Lihat Semua -->
          <div class="mt-14 flex justify-center">
            <button class="bg-[#44A6D9] hover:bg-[#3b8fc2] text-[#1A2B49] font-bold py-3.5 px-8 rounded-full transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
              <span v-html="$t('see_all')"></span>
            </button>
          </div>

        </section>

        <!-- HUBUNGI KAMI SECTION -->
        <section id="hubungi-kami" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 scroll-mt-24">
          
          <div class="flex flex-col items-center text-center mb-12 md:mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#1A2B49] mb-4">
              {{ $t('contact_us_title') }}
            </h2>
            <p class="text-[#264790] text-base md:text-lg font-medium max-w-3xl mx-auto leading-relaxed">
              {{ $t('contact_us_desc') }}
            </p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
            
            <div class="flex flex-col gap-4">
              <div 
                v-for="info in contactInfo" 
                :key="info.id" 
                class="bg-[#FFFFFF] rounded-2xl p-6 shadow-[0_4px_15px_rgb(0,0,0,0.03)] border border-slate-100 flex items-start gap-5 transition-transform hover:-translate-y-1"
              >
                <div class="bg-[#F4F7F9] p-4 rounded-xl flex-shrink-0">
                  <component :is="info.icon" :size="24" class="text-[#264790]" />
                </div>
                
                <div class="flex flex-col justify-center py-1">
                  <h4 class="font-extrabold text-[#1A2B49] text-lg mb-1.5">
                    {{ info.title }}
                  </h4>
                  <p 
                    v-for="(line, idx) in info.lines" 
                    :key="idx" 
                    class="text-[#264790] text-sm md:text-base font-medium leading-relaxed"
                  >
                    {{ line }}
                  </p>
                </div>
              </div>
            </div>

            <div class="group bg-[#44A6D9] hover:bg-[#264790] transition-colors duration-500 rounded-3xl p-8 md:p-12 flex flex-col items-center justify-center text-center shadow-lg border border-[#44A6D9]/50">
              
              <div class="bg-[#F9CC6B] w-24 h-24 rounded-full flex items-center justify-center mb-6 shadow-md transition-transform duration-500 group-hover:scale-110">
                <MessageCircle :size="40" :stroke-width="2.5" class="text-[#1A2B49]" />
              </div>
 
              <h3 class="text-[2rem] font-extrabold text-[#1A2B49] group-hover:text-[#FFFFFF] transition-colors duration-500 mb-4">
                {{ $t('ready_to_consult') }}
              </h3>
 
              <p class="text-[#1A2B49] group-hover:text-slate-200 transition-colors duration-500 text-sm md:text-base font-medium mb-10 max-w-sm leading-relaxed">
                {{ $t('consult_desc') }}
              </p>
 
              <div class="flex flex-col w-full max-w-sm gap-4">
                
                <button class="bg-[#264790] group-hover:bg-[#44A6D9] text-white font-bold py-3.5 px-6 rounded-full transition-colors duration-500 flex items-center justify-center gap-2 w-full shadow-md">
                  <MessageCircle :size="20" /> {{ $t('chat_wa') }}
                </button>
 
                <button class="bg-[#F9CC6B] hover:brightness-95 text-[#1A2B49] font-bold py-3.5 px-6 rounded-full transition-all flex items-center justify-center gap-2 w-full shadow-md">
                  <Mail :size="20" /> {{ $t('send_email') }}
                </button>
                
              </div>
            </div>

          </div>

        </section>

        <!-- FOOTER SECTION -->
        <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-32 md:pb-12">
          
          <div class="bg-[#FFFFFF] rounded-[2.5rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-50">
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-8 mb-12">
              
              <div class="md:col-span-5 flex flex-col gap-6">
                
                <div class="flex items-center gap-2">
                  <ApplicationLogo />
                </div>

                <p class="text-[#264790] text-sm md:text-base font-medium leading-relaxed max-w-md">
                  {{ $t('hero_subtitle') }}
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
                <h4 class="font-extrabold text-[#1A2B49] text-lg">{{ $t('quick_links') || 'Tautan Cepat' }}</h4>
                <ul class="flex flex-col gap-3">
                  <li><Link href="/" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('home') || 'Home' }}</Link></li>
                  <li><Link href="/courses" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('sub_class') || 'Kelas Kami' }}</Link></li>
                  <li><Link href="/contact" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('contact_us_title') || 'Hubungi Kami' }}</Link></li>
                  <li><Link href="/about" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('about_us') || 'Tentang Kami' }}</Link></li>
                  <li><Link href="/clients" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('client_our') || 'Klien Kami' }}</Link></li>
                  <li><Link href="/blog" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">{{ $t('blog') || 'Blog Kami' }}</Link></li>
                </ul>
              </div>

              <div class="md:col-span-4 flex flex-col gap-5">
                <h4 class="font-extrabold text-[#1A2B49] text-lg">{{ $t('contact_label') || 'Kontak' }}</h4>
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
              <p>&copy; 2026 Drastha Learning. {{ $t('all_rights_reserved') || 'All Rights Reserved' }}</p>
              
              <div class="flex flex-wrap justify-center gap-4 md:gap-8">
                <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Privacy Policy</Link>
                <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Terms of Service</Link>
                <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Cookies Settings</Link>
              </div>
            </div>

          </div>
        </footer>
        
      </main>
    </GuestLayout>
</template>

<style scoped>
/* Custom animations if needed */
@keyframes pulse-slow {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}
.animate-pulse {
  animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
