<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { Code, Key, ChevronDown, CheckCircle2 } from 'lucide-vue-next';

// State to track current registration step
const step = ref(1);

// Breeze Registration Form Model: pre-fill from Google OAuth if present in URL
const urlEmail = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('email') || '' : '';
const prefilledName = urlEmail ? urlEmail.split('@')[0].split('.').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : '';

const form = useForm({
    name: prefilledName,
    email: urlEmail,
    password: '',
    password_confirmation: '', // Handled under-the-hood in Step 1 transition
    otp_code: '',
    role: 'student',
    photo: null,
});

const photoPreviewUrl = ref(null);

const handlePhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    if (file.size > 1024 * 1024) {
      alert('Ukuran file foto profil maksimal adalah 1MB.');
      e.target.value = '';
      form.photo = null;
      photoPreviewUrl.value = null;
      return;
    }
    form.photo = file;
    photoPreviewUrl.value = URL.createObjectURL(file);
  } else {
    form.photo = null;
    photoPreviewUrl.value = null;
  }
};

// Generated dummy OTP for visual presentation and testing ease
const mockOtp = ref('123456');
const showOtpAlert = ref(false);

const generateUsername = (email) => {
  if (!email) return 'namaandadisini123';
  return email.split('@')[0].toLowerCase().replace(/[^a-z0-9]/g, '') + '123';
};

// Transition Step 1 to OTP (send OTP email)
const handleStep1Continue = () => {
  // Client-side validations
  if (!form.name || form.name.length > 50) {
    alert('Nama wajib diisi dan maksimal 50 karakter.');
    return;
  }
  if (!form.email || !form.email.includes('@')) {
    alert('Email Address wajib diisi dengan alamat email yang valid.');
    return;
  }
  if (!form.password || form.password.length < 8 || form.password.length > 25) {
    alert('Password wajib diisi antara 8 sampai 25 karakter.');
    return;
  }

  // Ensure password confirmation for Breeze
  form.password_confirmation = form.password;

  // Send OTP to backend
  router.post(route('otp.send'), { email: form.email }, {
    preserveState: true,
    onSuccess: () => {
      // Advance to OTP entry step
      step.value = 2;
    },
    onError: (errors) => {
      alert(Object.values(errors).join('\n'));
    },
  });
};

// Transition Step 2 to verify OTP
const handleStep2Continue = () => {
  if (!form.otp_code) {
    alert('Silakan masukkan 6 digit kode OTP Anda.');
    return;
  }
  if (form.otp_code.length !== 6) {
    alert('Kode OTP harus terdiri dari tepat 6 digit.');
    return;
  }

  // Verify OTP with backend
  router.post(route('otp.verify'), { email: form.email, otp_code: form.otp_code }, {
    preserveState: true,
    onSuccess: () => {
      // Advance to Step 3 if backend confirms
      step.value = 3;
    },
    onError: (errors) => {
      alert(Object.values(errors).join('\n'));
    },
  });
};

// Submit form and finalize signup to backend
const handleStep3Continue = () => {

  // Trigger Breeze registration
  form.post(route('register'), {
    onFinish: () => {
      // Clear password states
      form.reset('password', 'password_confirmation');
    },
    onError: (errors) => {
      let errMsg = Object.values(errors).join('\n');
      alert('Registrasi gagal:\n' + errMsg);
      if (errors.photo) {
        step.value = 3;
      } else {
        step.value = 1;
      }
    }
  });
};

// Re-send OTP
const handleResendOtp = () => {
  alert('Kode OTP baru berhasil dikirim ulang ke: ' + form.email);
  showOtpAlert.value = true;
};

// Return to Step 1 (Keluar)
const handleKeluar = () => {
  step.value = 1;
};

// Logo Helper referencing DL logo file
const Logo = () => {
  const settings = usePage().props.settings;
  const customLogo = settings?.course_logo;
  if (customLogo && customLogo !== '/images/logo-placeholder.png') {
    return `<div class="flex items-center justify-center gap-2">
      <img src="${customLogo}" alt="Drastha Learning Logo" class="h-10 w-auto object-contain" />
    </div>`;
  }
  return `<div class="flex items-center justify-center gap-2">
    <img src="/images/logo/logo_dl.png" alt="Drastha Learning Logo" class="h-10 w-auto object-contain" />
  </div>`;
};
</script>

<template>
  <Head title="Sign Up Siswa | Drastha Learning" />

  <div class="min-h-screen w-full flex flex-col md:flex-row bg-[#F4F7F9] font-montserrat relative overflow-hidden">
    
    <!-- LEFT SIDEBAR: Illustration Column -->
    <div class="hidden md:flex md:w-[35%] xl:w-[30%] shrink-0 relative bg-[#264790] overflow-hidden min-h-screen shadow-lg">
      <img 
        src="/images/pages/login_or_signup/bg-login-signup.png" 
        class="w-full h-full object-cover" 
        alt="Drastha Learning Signup Illustration"
      />
    </div>

    <!-- RIGHT SIDEBAR: Multi-Step Card Content Column -->
    <div class="flex-grow flex items-center justify-center p-6 sm:p-12 md:p-16 xl:p-24 relative min-h-screen">
      
      <!-- Float Test OTP notification -->
      <div 
        v-if="showOtpAlert"
        class="fixed top-6 right-6 z-50 bg-[#264790] border border-blue-400 text-white rounded-2xl p-5 shadow-2xl max-w-sm flex items-start gap-3.5 transform transition-all duration-300"
      >
        <div class="w-8 h-8 rounded-full bg-white/10 text-white flex items-center justify-center shrink-0">
          <Key :size="16" />
        </div>
        <div>
          <h4 class="font-extrabold text-sm mb-0.5">Kode OTP Drastha</h4>
          <p class="text-[11px] text-white/80 leading-relaxed font-semibold">
            Gunakan kode OTP simulasi berikut untuk memverifikasi pendaftaran Anda: 
            <span class="text-[#F9CC6B] font-extrabold text-xs block mt-1 tracking-widest">123456</span>
          </p>
        </div>
      </div>

      <!-- Main Container Card -->
      <div class="bg-white rounded-[2rem] p-8 sm:p-12 w-full max-w-[500px] shadow-[0_12px_50px_rgba(0,0,0,0.025)] border border-slate-100/50 flex flex-col gap-6 transition-all duration-300">
        
        <!-- STEP 1: New Account Form -->
        <div v-if="step === 1" class="flex flex-col gap-6">
          <div class="flex flex-col items-center text-center">
            <div v-html="Logo()" class="mb-6"></div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1A2B49] leading-tight mb-2">New Account</h2>
            <p class="text-slate-400 text-xs sm:text-sm font-semibold leading-relaxed max-w-xs">
              Lengkapi Form Dibawah ini dengan Menggunakan Data Anda yang Valid
            </p>
          </div>

          <form @submit.prevent="handleStep1Continue" class="flex flex-col gap-5">
            <!-- Name Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Nama (Maks. 50 karakter)</label>
              <input 
                type="text" 
                v-model="form.name"
                placeholder="Nama anda Disini" 
                maxlength="50"
                required
                class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
              />
            </div>

            <!-- Email Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Email Address</label>
              <input 
                type="email" 
                v-model="form.email"
                placeholder="email@domain.com" 
                required
                class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
              />
            </div>

            <!-- Role Toggle Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Mendaftar Sebagai</label>
              <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer bg-[#F4F7F9] px-4 py-3 rounded-xl border border-slate-100 hover:border-[#44A6D9] transition-all flex-1">
                  <input type="radio" v-model="form.role" value="student" class="text-[#264790] focus:ring-[#44A6D9]" />
                  <span class="text-xs sm:text-sm font-bold text-slate-700">Siswa (Student)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer bg-[#F4F7F9] px-4 py-3 rounded-xl border border-slate-100 hover:border-[#44A6D9] transition-all flex-1">
                  <input type="radio" v-model="form.role" value="instructor" class="text-[#264790] focus:ring-[#44A6D9]" />
                  <span class="text-xs sm:text-sm font-bold text-slate-700">Instruktur</span>
                </label>
              </div>
            </div>

            <!-- Password Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Password (Maks. 25 karakter)</label>
              <input 
                type="password" 
                v-model="form.password"
                placeholder="password" 
                maxlength="25"
                required
                class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
              />
            </div>

            <!-- Continue CTA Button -->
            <button 
              type="submit"
              class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-3"
            >
              Continue
            </button>
          </form>

          <div class="text-center text-xs font-bold text-slate-400 mt-2">
            Sudah punya akun? 
            <Link href="/?login=true" class="text-[#264790] hover:underline">Login disini</Link>
          </div>
        </div>

        <!-- STEP 2: OTP Verification Form -->
        <div v-else-if="step === 2" class="flex flex-col gap-6">
          <div class="flex flex-col items-center text-center">
            <div v-html="Logo()" class="mb-6"></div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1A2B49] leading-tight mb-4">Verifikasi Login</h2>
          </div>

          <!-- Alert Info Box matching mockup screen -->
          <div class="bg-[#F9CC6B]/15 border border-[#F9CC6B]/30 rounded-2xl p-5 flex flex-col gap-2 text-left">
            <p class="text-slate-600 text-[11px] sm:text-xs font-bold leading-relaxed">
              Kode OTP telah dikirim ke alamat email anda :<br />
              <span class="text-[#264790] font-extrabold break-all">{{ form.email }}</span>
            </p>
            <p class="text-[#264790] text-[10px] sm:text-[11px] font-extrabold leading-relaxed">
              Silakan Periksa folder inbox, promosi, atau spam jika tidak menemukan email dari kami
            </p>
          </div>

          <form @submit.prevent="handleStep2Continue" class="flex flex-col gap-5">
            <!-- OTP Input Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Masukkan Kode OTP</label>
              <input 
                type="text" 
                v-model="form.otp_code"
                placeholder="Masukkan 6 digit kode OTP" 
                maxlength="6"
                required
                class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400 text-center tracking-widest"
              />
            </div>

            <!-- Continue CTA Button -->
            <button 
              type="submit"
              class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-2"
            >
              Continue
            </button>
          </form>

          <!-- Footer Actions -->
          <div class="flex flex-col gap-4 items-center mt-2">
            <span class="text-xs font-bold text-slate-400">
              Tidak Menerima kode OTP? 
              <button @click="handleResendOtp" class="text-[#44A6D9] hover:underline">Kirim Ulang OTP</button>
            </span>
            <button 
              @click="handleKeluar" 
              class="text-xs font-extrabold text-slate-400 hover:text-slate-600 transition-colors mt-2"
            >
              Keluar
            </button>
          </div>
        </div>

        <!-- STEP 3: Completion Profile Setup -->
        <div v-else-if="step === 3" class="flex flex-col gap-6">
          
          <!-- Avatar Icon and Name Details matching mockup -->
          <div class="flex flex-col items-center text-center">
            <div v-html="Logo()" class="mb-6"></div>
            
            <!-- Custom Upload Circle graphic representation -->
            <div class="w-20 h-20 bg-slate-50 border border-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-4 shadow-sm relative overflow-hidden">
              <img v-if="photoPreviewUrl" :src="photoPreviewUrl" class="w-full h-full object-cover animate-fade-in" />
              <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
            </div>

            <h3 class="text-lg sm:text-xl font-extrabold text-[#1A2B49] mb-0.5 leading-snug">
              {{ form.name || 'Nama Anda Disini' }}
            </h3>
            <span class="text-[11px] sm:text-xs text-slate-400 font-bold mb-4">
              @{{ generateUsername(form.email) }}
            </span>
          </div>

          <form @submit.prevent="handleStep3Continue" class="flex flex-col gap-5">
            <!-- Custom File Upload Field -->
            <div class="flex flex-col gap-2">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Upload Your Photo</label>
              
              <div class="relative w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-3.5 flex items-center justify-between overflow-hidden">
                <span class="text-xs sm:text-sm font-extrabold text-slate-700">Choose File</span>
                <span class="text-xs text-slate-400 font-semibold truncate max-w-[180px]">
                  {{ form.photo ? form.photo.name : 'No file chosen' }}
                </span>
                <!-- Hidden native input -->
                <input 
                  type="file" 
                  accept="image/jpeg,image/png,image/jpg"
                  @change="handlePhotoChange"
                  class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                />
              </div>
              <span class="text-[10px] text-slate-400 font-bold leading-normal">
                Only JPG, JPEG or PNG files with Max Size of 1MB
              </span>
            </div>

            <!-- Submit Finalize CTA Button -->
            <button 
              type="submit"
              :disabled="form.processing"
              class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-3"
            >
              Continue
            </button>
          </form>
        </div>

      </div>

    </div>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
