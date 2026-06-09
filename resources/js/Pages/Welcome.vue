<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
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

// Data Info Kontak
const contactInfo = [
  { 
    id: 1, 
    title: 'Telepon', 
    icon: Phone, 
    lines: ['031-9960-5068', '0812-3485-9768'] 
  },
  { 
    id: 2, 
    title: 'Alamat', 
    icon: MapPin, 
    lines: [
      'PT. Drastha Berkah Sentosa, Jl Budi Luhur B/2', 
      'Wagir Indah Kwangsan, Sedati', 
      'Sidoarjo, Jawa Timur 61253'
    ] 
  },
  { 
    id: 3, 
    title: 'Email', 
    icon: Mail, 
    lines: ['admin@drasthabest.com', 'admin@drasthalearning.com'] 
  },
  { 
    id: 4, 
    title: 'Website', 
    icon: Globe, 
    lines: ['www.drasthalearning.com'] 
  },
];

// Data untuk kolom fitur kiri
const leftFeatures = ref([
  {
    title: 'Harga Kompetitif',
    description: 'Harga terjangkau dan kompetitif untuk menghemat biaya anda.',
    icon: '/images/logo/HargaKompetitifIcon.svg'
  },
  {
    title: 'Gratis Konsultasi',
    description: 'Konsultasi gratis untuk pemilihan Peta jalan yang tepat.',
    icon: '/images/logo/GratisKonsultasiIkon.svg'
  }
]);

const rightFeatures = ref([
  {
    title: 'Pembayaran Fleksibel',
    description: 'Sistem pembayaran yang fleksibel and bisa bertahap dengan DP 70%',
    icon: '/images/logo/FlexiblePaymenticon.svg'
  },
  {
    title: 'Produk yang Disesuaikan',
    description: 'Susunan materi yang disesuaikan dengan kebutuhan profesi anda.',
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
          <div class="bg-[#FFFFFF] rounded-[2.5rem] md:rounded-[3rem] p-8 md:p-12 lg:p-16 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100/50 flex flex-col md:flex-row items-center justify-between gap-12 lg:gap-8">
            
            <!-- Teks Konten -->
            <div class="w-full md:w-1/2 flex flex-col items-center md:items-start text-center md:text-left space-y-5 lg:pr-8">
              
              <span class="text-[#264790] font-semibold tracking-wide text-base md:text-lg">
                #BelajarLebihTerarah
              </span>
              
              <h1 class="text-4xl sm:text-5xl lg:text-[3.5rem] font-extrabold text-[#000000] leading-[1.1] tracking-tight">
                Belajar Lebih Terarah,<br class="hidden lg:block" /> Berkembang Lebih Cepat
              </h1>
              
              <p class="text-[#264790] text-base md:text-lg leading-relaxed max-w-lg font-medium">
                Platform Learning Management System (LMS) yang dirancang untuk mendukung pembelajaran modern, interaktif, dan berkelanjutan.
              </p>
              
              <!-- Tombol Aksi -->
              <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto pt-4">
                <button class="bg-[#44A6D9] hover:bg-[#3b8fc2] text-[#000000] font-semibold py-3 px-8 rounded-full transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                  Mulai Belajar Sekarang 
                  <span class="text-sm leading-none">&raquo;</span>
                </button>
                
                <button class="bg-[#F9CC6B] hover:bg-[#e5bc62] text-[#000000] font-semibold py-3 px-8 rounded-full transition-all transform hover:-translate-y-0.5">
                  Lihat Katalog Kelas
                </button>
              </div>
            </div>

            <!-- Area Gambar Hero (Menggunakan Tag IMG dengan GIF) -->
            <div class="w-full md:w-1/2 flex justify-center md:justify-end">
              <div class="relative w-full max-w-md flex justify-center items-center">
                
                <img 
                  src="/images/pages/welcome/Beranda.gif" 
                  alt="Ilustrasi Hero Drastha Learning" 
                  class="w-full h-auto object-contain relative z-10"
                />

              </div>
            </div>

          </div>
        </section>

        <!-- 3. TENTANG DRASTHA LEARNING SECTION -->
        <section id="tentang-kami" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16 text-center scroll-mt-24">
          <h2 class="text-3xl md:text-4xl font-extrabold text-[#1A2B49] mb-4">
            Tentang <span class="text-[#264790]">Drastha Learning</span>
          </h2>
          <p class="text-[#264790] leading-relaxed text-base md:text-lg mb-10 md:mb-14 font-medium px-2">
            <span class="font-bold text-[#1A2B49]">Drastha Learning</span> adalah platform edukasi yang berfokus pada pengembangan keterampilan dan pengetahuan melalui technology dan pembelajaran digital. Menawarkan kursus online dan offline, pelatihan, serta sertifikasi di bidang seperti akuntansi, keuangan, manajemen, perpajakan, hukum, dan digital.
          </p>
          <div class="bg-[#FFFFFF] rounded-[2rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/50 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-shadow duration-300">
            <h3 class="text-2xl md:text-3xl font-bold text-[#1A2B49] mb-4">
              Komitmen Kami
            </h3>
            <p class="text-[#264790] leading-relaxed text-sm md:text-base font-medium">
              <span class="font-bold text-[#1A2B49]">Drastha Learning</span> berkomitmen untuk meningkatkan kompetensi mahasiswa, karyawan, dan masyarakat umum. Dengan pendekatan fleksibel dan berbasis teknologi, platform ini tidak hanya menyediakan kursus dasar tetapi juga mendukung pengembangan keterampilan jangka panjang untuk menghadapi perubahan di dunia kerja dan teknologi.
            </p>
          </div>
        </section>

        <!-- 4. WHY CHOOSE US SECTION -->
        <section class="py-16 bg-transparent font-montserrat">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            
            <h2 class="text-4xl md:text-5xl font-extrabold text-[#1A2B49] mb-6 leading-tight">
              Why Choose <span class="text-[#264790]">Us?</span>
            </h2>
            <p class="text-[#264790] max-w-3xl mx-auto mb-20 text-base md:text-lg leading-relaxed font-medium">
              Kami berkomitmen memberikan layanan terbaik dengan berbagai keunggulan untuk mendukung peningkatan <span class="italic text-gray-700">skill</span> Anda.
            </p>

            <div class="flex flex-col lg:flex-row items-center justify-center lg:gap-24 gap-12">
              
              <div class="flex flex-col gap-12 w-full lg:w-1/3">
                <div 
                  v-for="(feature, index) in leftFeatures" 
                  :key="index" 
                  class="bg-transparent p-8 rounded-3xl flex flex-col items-center text-center transition-all duration-300 hover:bg-white hover:shadow-xl hover:-translate-y-1"
                >
                  <div class="w-16 h-16 mb-5 flex items-center justify-center"><img :src="feature.icon" class="w-full h-full object-contain" :alt="feature.title" /></div>
                  <h3 class="text-2xl font-bold text-[#2b478b] mb-2 leading-tight">{{ feature.title }}</h3>
                  <p class="text-base text-gray-500 leading-relaxed">{{ feature.description }}</p>
                </div>
              </div>

              <div class="w-full lg:w-1/3 flex justify-center py-10 lg:py-0 relative">
                <img 
                  src="/images/pages/welcome/why_choose_us.gif" 
                  alt="Drastha Learning Advantages" 
                  class="w-full max-w-xl object-contain relative z-10 mix-blend-multiply"
                />
              </div>

              <div class="flex flex-col gap-12 w-full lg:w-1/3">
                <div 
                  v-for="(feature, index) in rightFeatures" 
                  :key="index" 
                  class="bg-transparent p-8 rounded-3xl flex flex-col items-center text-center transition-all duration-300 hover:bg-white hover:shadow-xl hover:-translate-y-1"
                >
                  <div class="w-16 h-16 mb-5 flex items-center justify-center"><img :src="feature.icon" class="w-full h-full object-contain" :alt="feature.title" /></div>
                  <h3 class="text-2xl font-bold text-[#2b478b] mb-2 leading-tight">{{ feature.title }}</h3>
                  <p class="text-base text-gray-500 leading-relaxed">{{ feature.description }}</p>
                </div>
              </div>

            </div>

            <div class="mt-20 flex justify-center">
              <button class="bg-[#fbc02d] hover:bg-[#f9a825] text-[#1A2B49] font-bold py-3.5 px-8 rounded-full transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                Get a Free Demo 
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
              <span class="text-[#1A2B49] font-bold text-sm">Program Kami</span>
            </div>

            <h2 class="text-3xl md:text-[2.5rem] font-extrabold text-[#1A2B49] mb-4">
              Pilihan Berbagai <span class="text-[#264790]">Kelas</span>
            </h2>

            <p class="text-[#264790] text-base md:text-lg font-medium max-w-2xl mx-auto">
              Kami memiliki berbagai macam kelas yang fokus kepada profesi-profesi dalam spesialisasi tertentu.
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

          </div>

          <div class="mt-12 flex justify-center">
            <Link 
              href="/courses" 
              class="bg-[#44A6D9] hover:bg-[#3b8fc2] text-[#1A2B49] font-bold py-3.5 px-8 rounded-full shadow-md transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2"
            >
              Search All Course 
              <Search :size="18" :stroke-width="2.5" />
            </Link>
          </div>

        </section>

        <!-- TIM PROFESIONAL SECTION -->
        <section id="tim-kami" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 scroll-mt-24">
          
          <!-- Header Tim -->
          <div class="flex flex-col items-center text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full border-[1.5px] border-[#1A2B49] bg-transparent mb-6">
              <Users :size="18" class="text-[#1A2B49]" />
              <span class="text-[#1A2B49] font-bold text-sm">Tim Profesional</span>
            </div>

            <!-- Judul -->
            <h2 class="text-3xl md:text-[2.5rem] font-extrabold text-[#1A2B49] mb-4">
              Tim <span class="text-[#264790]">Drastha Learning</span>
            </h2>

            <!-- Sub-judul -->
            <p class="text-[#44A6D9] text-base md:text-lg font-medium">
              Terdiri dari susunan tim profesional yang ahli di Bidangnya
            </p>
          </div>

          <!-- Grid Container untuk Card Tim (5 Kolom di Desktop) -->
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
            
            <!-- Card Looping dengan Efek Hover Group -->
            <div 
              v-for="member in teamMembers" 
              :key="member.id"
              class="group bg-[#FFFFFF] border border-[#264790]/20 rounded-3xl p-6 flex flex-col items-center text-center transition-all duration-300 cursor-pointer hover:bg-[#F9CC6B] hover:border-[#F9CC6B] hover:shadow-[0_8px_30px_rgb(249,204,107,0.3)] hover:-translate-y-1.5"
            >
              <!-- Wrapper Foto Bulat -->
              <div class="w-28 h-28 bg-[#FFFFFF] rounded-full mb-5 overflow-hidden flex items-center justify-center transition-colors duration-300 shadow-sm border border-slate-50 group-hover:border-white/50">
                <img 
                  :src="member.image" 
                  :alt="member.name" 
                  class="w-full h-full object-cover object-center relative z-10" 
                />
              </div>
              
              <!-- Teks Nama & Role -->
              <h3 class="text-[#1A2B49] font-bold text-[13px] mb-1.5 leading-snug">
                {{ member.name }}
              </h3>
              <p class="text-[#44A6D9] group-hover:text-[#264790] transition-colors duration-300 text-xs font-semibold">
                {{ member.role }}
              </p>
            </div>

          </div>

          <!-- Indikator Slider/Carousel Statis -->
          <div class="flex justify-center items-center gap-3 mt-10">
            <button class="w-3 h-3 rounded-full bg-[#1A2B49] transition-all"></button>
            <button class="w-3 h-3 rounded-full bg-[#44A6D9]/50 hover:bg-[#44A6D9] transition-all"></button>
            <button class="w-3 h-3 rounded-full bg-[#44A6D9]/50 hover:bg-[#44A6D9] transition-all"></button>
            <button class="w-3 h-3 rounded-full bg-[#44A6D9]/50 hover:bg-[#44A6D9] transition-all"></button>
          </div>

        </section>

        <!-- BLOG AKTIVITAS SECTION -->
        <section id="blog-aktivitas" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 scroll-mt-24">

          <!-- Header Section -->
          <div class="flex flex-col items-center text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full border-[1.5px] border-[#1A2B49] bg-transparent mb-6">
              <ImageIcon :size="18" class="text-[#1A2B49]" />
              <span class="text-[#1A2B49] font-bold text-sm">Kegiatan Kami</span>
            </div>

            <!-- Judul -->
            <h2 class="text-3xl md:text-[2.5rem] font-extrabold text-[#1A2B49] mb-4">
              Blog <span class="text-[#264790]">Aktivitas</span>
            </h2>

            <!-- Sub-judul -->
            <p class="text-[#264790] text-base md:text-lg font-medium">
              Beberapa kegiatan yang sedang dilakukan oleh Drastha Learning
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
                />
              </div>

              <!-- Content Card -->
              <div class="pt-5 px-2 flex flex-col flex-1">
                <!-- Meta: Category & Date -->
                <div class="flex justify-between items-center mb-4">
                  <span class="bg-[#F9CC6B] text-[#1A2B49] text-[11px] font-extrabold px-3 py-1.5 rounded-full uppercase tracking-wide">
                    {{ post.category }}
                  </span>
                  <span class="text-[#1A2B49]/60 text-xs font-semibold">
                    {{ post.date }}
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
                  <img :src="post.author_avatar" :alt="post.author_name" class="w-6 h-6 rounded-full object-cover" />
                  <span class="text-[#264790] text-xs font-semibold">{{ post.author_name }}</span>
                </div>
              </div>
            </div>

          </div>

          <!-- Tombol Lihat Semua -->
          <div class="mt-14 flex justify-center">
            <button class="bg-[#44A6D9] hover:bg-[#3b8fc2] text-[#1A2B49] font-bold py-3.5 px-8 rounded-full transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
              Lihat Semua &raquo;
            </button>
          </div>

        </section>

        <!-- HUBUNGI KAMI SECTION -->
        <section id="hubungi-kami" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 scroll-mt-24">
          
          <div class="flex flex-col items-center text-center mb-12 md:mb-16">
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#1A2B49] mb-4">
              Hubungi Kami
            </h2>
            <p class="text-[#264790] text-base md:text-lg font-medium max-w-3xl mx-auto leading-relaxed">
              Kami siap membantu anda dengan trial class gratis untuk<br class="hidden md:block" /> menentukan pilihan anda dalam pemilihan profesi yang unggul
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
                Siap Berkonsultasi?
              </h3>

              <p class="text-[#1A2B49] group-hover:text-slate-200 transition-colors duration-500 text-sm md:text-base font-medium mb-10 max-w-sm leading-relaxed">
                Tim kami siap membantu Anda memilih program yang tepat untuk kebutuhan pembelajaran anda. Konsultasi gratis tanpa komitmen!
              </p>

              <div class="flex flex-col w-full max-w-sm gap-4">
                
                <button class="bg-[#264790] group-hover:bg-[#44A6D9] text-white font-bold py-3.5 px-6 rounded-full transition-colors duration-500 flex items-center justify-center gap-2 w-full shadow-md">
                  <MessageCircle :size="20" /> Chat via WhatsApp
                </button>

                <button class="bg-[#F9CC6B] hover:brightness-95 text-[#1A2B49] font-bold py-3.5 px-6 rounded-full transition-all flex items-center justify-center gap-2 w-full shadow-md">
                  <Mail :size="20" /> Kirim Email
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
