<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Award, Printer, ArrowLeft, ShieldCheck, Download } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
  course: Object,
  settings: Object,
  completedAt: String,
  studentName: String
});

// Format Date nicely
const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

const printedDate = computed(() => {
  return formatDate(props.completedAt) || formatDate(new Date());
});

const handlePrint = () => {
  window.print();
};
</script>

<template>
  <Head :title="'Sertifikat Kelulusan - ' + course.title" />

  <div class="min-h-screen bg-slate-50 font-montserrat flex flex-col print:p-0 print:bg-white print:min-h-0">
    
    <!-- Top Action Banner (Hidden in Print) -->
    <div class="bg-white border-b border-slate-200/80 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm z-10 print:hidden">
      <div class="flex items-center gap-3">
        <Link 
          :href="route('courses.learn', course.slug)"
          class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#264790] hover:bg-slate-50 transition-colors"
        >
          <ArrowLeft :size="18" />
        </Link>
        <div>
          <h1 class="text-base font-extrabold text-[#1A2B49] leading-snug">{{ course.title }}</h1>
          <span class="text-xs text-slate-400 font-bold block mt-0.5">Sertifikat Kelulusan Anda</span>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <button 
          @click="handlePrint"
          class="bg-[#264790] hover:bg-[#44A6D9] text-white px-5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-all flex items-center gap-2"
        >
          <Printer :size="15" /> Cetak / Unduh PDF
        </button>
      </div>
    </div>

    <!-- Certificate Container -->
    <div class="flex-grow flex items-center justify-center p-6 sm:p-12 print:p-0 print:block">
      
      <!-- DEFAULT CLASSIC TEMPLATE -->
      <div 
        v-if="settings.cert_page === 'certificate'"
        class="certificate-paper relative w-full max-w-[950px] aspect-[1.414/1] bg-white border-[16px] border-double border-slate-800 shadow-2xl p-12 sm:p-16 flex flex-col justify-between items-center text-center overflow-hidden print:shadow-none print:border-[12px] print:m-0"
      >
        <!-- Background decorative ornaments -->
        <div class="absolute inset-4 border border-slate-300 pointer-events-none"></div>
        <div class="absolute top-6 left-6 w-12 h-12 border-t-2 border-l-2 border-slate-800"></div>
        <div class="absolute top-6 right-6 w-12 h-12 border-t-2 border-r-2 border-slate-800"></div>
        <div class="absolute bottom-6 left-6 w-12 h-12 border-b-2 border-l-2 border-slate-800"></div>
        <div class="absolute bottom-6 right-6 w-12 h-12 border-b-2 border-r-2 border-slate-800"></div>

        <!-- Certificate Header -->
        <div class="flex flex-col items-center gap-3 mt-4">
          <div class="w-16 h-16 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-700 shadow-inner">
            <Award :size="32" class="text-slate-800" />
          </div>
          <span class="text-slate-400 font-bold text-[10px] tracking-[0.25em] uppercase">Sertifikat Resmi Kelulusan</span>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col items-center gap-4 my-6">
          <h2 class="font-serif text-3xl sm:text-5xl font-normal text-slate-800 italic tracking-wide">Sertifikat Kelulusan</h2>
          <p class="text-slate-500 text-xs sm:text-sm font-semibold max-w-lg mt-2">
            Dengan ini menyatakan bahwa siswa berprestasi di bawah ini telah berhasil menyelesaikan seluruh kurikulum pembelajaran di platform Drastha Learning:
          </p>
          
          <div class="my-4">
            <h3 class="text-2xl sm:text-4xl font-extrabold text-[#264790] border-b-2 border-slate-200 pb-2 px-12 tracking-wide font-serif italic">
              {{ studentName }}
            </h3>
          </div>

          <p class="text-slate-500 text-xs sm:text-sm font-semibold max-w-lg">
            Atas dedikasi, penyelesaian penugasan, evaluasi kuis, serta materi pada program studi spesialisasi:
            <span class="block text-slate-800 font-extrabold mt-1 uppercase tracking-wider text-sm sm:text-base">{{ course.title }}</span>
          </p>
        </div>

        <!-- Signatures & Footer -->
        <div class="w-full flex justify-between items-end mt-8 px-6">
          <div class="text-left">
            <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Tanggal Kelulusan</span>
            <span class="text-xs font-bold text-slate-700 block mt-1">{{ printedDate }}</span>
          </div>

          <!-- Logo/Seal representation -->
          <div class="hidden sm:block">
            <div class="w-20 h-20 rounded-full border-4 border-slate-100 flex items-center justify-center bg-slate-50/50 shadow-inner rotate-12">
              <span class="text-[8px] font-extrabold text-slate-400 uppercase tracking-widest text-center">DRASTHA<br>SEAL</span>
            </div>
          </div>

          <div class="text-right flex flex-col items-end">
            <!-- Signature Graphic -->
            <div class="h-14 flex items-center justify-center mb-1">
              <img :src="settings.cert_signature" class="max-h-full object-contain" alt="Authorised Signature" />
            </div>
            
            <div class="border-t border-slate-300 pt-1 text-right min-w-[180px]">
              <span v-if="settings.cert_show_instructor" class="text-xs font-extrabold text-slate-700 block leading-tight">
                {{ course.instructor?.name || 'Instructor' }}
              </span>
              <span class="text-xs font-extrabold text-slate-800 block">{{ settings.cert_authorised_name }}</span>
              <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider mt-0.5">{{ settings.cert_company_name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ELEGANT MODERN TEMPLATE -->
      <div 
        v-else-if="settings.cert_page === 'custom-certificate'"
        class="certificate-paper relative w-full max-w-[950px] aspect-[1.414/1] bg-white border-[16px] border-[#264790] shadow-2xl p-12 sm:p-16 flex flex-col justify-between items-center text-center overflow-hidden print:shadow-none print:border-[12px] print:m-0"
      >
        <!-- Modern abstract layout background shapes -->
        <div class="absolute -top-16 -left-16 w-48 h-48 bg-gradient-to-br from-[#264790]/20 to-[#44A6D9]/10 rounded-full blur-2xl"></div>
        <div class="absolute -bottom-16 -right-16 w-64 h-64 bg-gradient-to-br from-[#44A6D9]/10 to-[#264790]/20 rounded-full blur-3xl"></div>
        <div class="absolute top-0 right-0 w-24 h-24 bg-[#264790] opacity-5 rounded-bl-[100px]"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#44A6D9] opacity-5 rounded-tr-[100px]"></div>

        <!-- Certificate Header -->
        <div class="w-full flex justify-between items-center border-b border-slate-100 pb-6">
          <div class="text-left">
            <span class="text-lg font-black text-[#1A2B49] tracking-tight">DRASTHA</span>
            <span class="text-xs text-[#264790] font-black uppercase tracking-wider block">LEARNING</span>
          </div>
          <div class="flex items-center gap-1.5 bg-[#264790]/5 px-3 py-1.5 rounded-full border border-[#264790]/10">
            <ShieldCheck :size="14" class="text-[#264790]" />
            <span class="text-[9px] font-black text-[#264790] tracking-wider uppercase">Sertifikat Terverifikasi</span>
          </div>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col items-center gap-4 my-auto">
          <span class="text-xs text-[#264790] font-black uppercase tracking-[0.25em]">Sertifikat Keahlian</span>
          <h2 class="text-3xl sm:text-5xl font-black text-slate-800 tracking-tight leading-none mt-1">Diberikan Kepada</h2>
          
          <div class="my-5">
            <h3 class="text-3xl sm:text-5xl font-black bg-gradient-to-r from-[#264790] to-[#44A6D9] bg-clip-text text-transparent px-8">
              {{ studentName }}
            </h3>
            <div class="h-1 w-24 bg-gradient-to-r from-[#264790] to-[#44A6D9] mx-auto mt-3 rounded-full"></div>
          </div>

          <p class="text-slate-500 text-xs sm:text-sm font-bold max-w-xl leading-relaxed">
            Telah menyelesaikan seluruh modul teori dan praktik secara komprehensif dalam pelatihan intensif serta ujian kompetensi terpadu pada program kelas belajar:
            <span class="block text-[#1A2B49] font-black mt-2 text-base sm:text-lg tracking-wide uppercase">{{ course.title }}</span>
          </p>
        </div>

        <!-- Signatures & Footer -->
        <div class="w-full flex justify-between items-end border-t border-slate-100 pt-6 px-2">
          <div class="text-left">
            <span class="text-[9px] text-slate-400 font-bold block uppercase tracking-wider">Tanggal Kelulusan</span>
            <span class="text-xs font-black text-slate-800 block mt-0.5">{{ printedDate }}</span>
          </div>

          <div class="text-right flex flex-col items-end">
            <!-- Signature Graphic -->
            <div class="h-12 flex items-center justify-center mb-1">
              <img :src="settings.cert_signature" class="max-h-full object-contain" alt="Authorised Signature" />
            </div>
            
            <div class="text-right">
              <span v-if="settings.cert_show_instructor" class="text-xs font-bold text-slate-600 block leading-tight">
                {{ course.instructor?.name || 'Instructor' }}
              </span>
              <span class="text-xs font-black text-slate-800 block">{{ settings.cert_authorised_name }}</span>
              <span class="text-[9px] text-[#264790] font-black uppercase tracking-wider mt-0.5 block">{{ settings.cert_company_name }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- PREMIUM GOLD TEMPLATE -->
      <div 
        v-else-if="settings.cert_page === 'premium-cert'"
        class="certificate-paper relative w-full max-w-[950px] aspect-[1.414/1] bg-[#0F172A] border-[16px] border-[#D97706] shadow-2xl p-12 sm:p-16 flex flex-col justify-between items-center text-center overflow-hidden print:shadow-none print:border-[12px] print:m-0"
      >
        <!-- Gold corners design -->
        <div class="absolute inset-4 border border-[#D97706]/40 pointer-events-none"></div>
        <div class="absolute top-6 left-6 w-14 h-14 border-t-4 border-l-4 border-[#D97706]"></div>
        <div class="absolute top-6 right-6 w-14 h-14 border-t-4 border-r-4 border-[#D97706]"></div>
        <div class="absolute bottom-6 left-6 w-14 h-14 border-b-4 border-l-4 border-[#D97706]"></div>
        <div class="absolute bottom-6 right-6 w-14 h-14 border-b-4 border-r-4 border-[#D97706]"></div>

        <!-- Certificate Header -->
        <div class="flex flex-col items-center gap-2 mt-4">
          <div class="w-14 h-14 rounded-full border-2 border-[#D97706] bg-amber-950/40 flex items-center justify-center text-[#D97706] shadow-lg shadow-black/30">
            <Award :size="28" />
          </div>
          <span class="text-[#D97706] font-bold text-[9px] tracking-[0.3em] uppercase">Premium Certification of Honor</span>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col items-center gap-4 my-auto">
          <h2 class="text-3xl sm:text-5xl font-serif text-slate-100 tracking-wider">CERTIFICATE OF ACHIEVEMENT</h2>
          <p class="text-slate-400 text-xs sm:text-sm font-medium italic max-w-lg mt-1">
            This premium honor is proudly presented to certify that the scholar:
          </p>
          
          <div class="my-4">
            <h3 class="text-3xl sm:text-5xl font-bold text-[#FBBF24] tracking-widest font-serif border-b border-[#D97706]/40 pb-3 px-16 italic">
              {{ studentName }}
            </h3>
          </div>

          <p class="text-slate-400 text-xs sm:text-sm font-medium max-w-lg">
            has successfully met all required qualifications and completed the intensive assessment requirements under standard study program:
            <span class="block text-white font-extrabold mt-2 tracking-wider text-sm sm:text-base uppercase">{{ course.title }}</span>
          </p>
        </div>

        <!-- Signatures & Footer -->
        <div class="w-full flex justify-between items-end mt-8 px-6 text-slate-400">
          <div class="text-left">
            <span class="text-[9px] text-[#D97706] font-bold block uppercase tracking-wider">Date Granted</span>
            <span class="text-xs font-bold text-white block mt-1">{{ printedDate }}</span>
          </div>

          <!-- Luxury Gold Seal Seal -->
          <div class="hidden sm:block">
            <div class="w-20 h-20 rounded-full border-4 border-double border-[#D97706] flex items-center justify-center bg-amber-950/20 shadow-lg rotate-12">
              <span class="text-[7px] font-black text-[#FBBF24] uppercase tracking-widest text-center leading-normal">OFFICIAL<br>HONOR<br>SEAL</span>
            </div>
          </div>

          <div class="text-right flex flex-col items-end">
            <!-- Signature Graphic (invert colors slightly to look good on black background if it is dark, or render as-is) -->
            <div class="h-12 flex items-center justify-center mb-1 bg-white/5 px-4 py-1 rounded-xl border border-white/5">
              <img :src="settings.cert_signature" class="max-h-full object-contain brightness-110" alt="Authorised Signature" />
            </div>
            
            <div class="border-t border-[#D97706]/40 pt-1.5 text-right min-w-[180px]">
              <span v-if="settings.cert_show_instructor" class="text-xs font-bold text-slate-300 block leading-tight">
                {{ course.instructor?.name || 'Instructor' }}
              </span>
              <span class="text-xs font-bold text-white block">{{ settings.cert_authorised_name }}</span>
              <span class="text-[9px] text-[#D97706] font-bold block uppercase tracking-wider mt-0.5">{{ settings.cert_company_name }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style>
/* CSS Print Styles */
@media print {
  body, html {
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    height: 100% !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  
  .min-h-screen {
    min-height: 0 !important;
    background: #fff !important;
  }
  
  .certificate-paper {
    box-shadow: none !important;
    margin: 0 auto !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    page-break-inside: avoid !important;
    border-width: 12px !important;
  }
}

@page {
  size: landscape;
  margin: 1cm;
}
</style>
