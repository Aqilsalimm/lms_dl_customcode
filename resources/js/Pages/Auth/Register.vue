<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    personal_goal: 'Pilih Keahlian',
    photo: null,
});

// Generated dummy OTP for visual presentation and testing ease
const mockOtp = ref('123456');
const showOtpAlert = ref(false);

const generateUsername = (email) => {
  if (!email) return 'namaandadisini123';
  return email.split('@')[0].toLowerCase().replace(/[^a-z0-9]/g, '') + '123';
};

// Transition Step 1 to Step 2
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

  // Set password confirmation to avoid Breeze validation errors
  form.password_confirmation = form.password;

  // Transition to OTP step
  step.value = 2;

  // Show a helpful mock notification containing the test OTP
  showOtpAlert.value = true;
  setTimeout(() => {
    showOtpAlert.value = false;
  }, 10000);
};

// Transition Step 2 to Step 3
const handleStep2Continue = () => {
  if (!form.otp_code) {
    alert('Silakan masukkan 6 digit kode OTP Anda.');
    return;
  }

  // Accept any 6 digit input or the default 123456
  if (form.otp_code.length !== 6) {
    alert('Kode OTP harus terdiri dari tepat 6 digit.');
    return;
  }

  step.value = 3;
};

// Submit form and finalize signup to backend
const handleStep3Continue = () => {
  if (form.personal_goal === 'Pilih Keahlian') {
    alert('Silakan pilih Personal Goal / Keahlian belajar Anda terlebih dahulu.');
    return;
  }

  // Trigger Breeze registration
  form.post(route('register'), {
    onFinish: () => {
      // Clear password states
      form.reset('password', 'password_confirmation');
    },
    onError: (errors) => {
      // If server returns validation errors, move back to step 1 so they can fix them
      step.value = 1;
      let errMsg = Object.values(errors).join('\n');
      alert('Registrasi gagal:\n' + errMsg);
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
const Logo = () => (
  `<div class="flex items-center justify-center gap-2">
    <img src="/images/logo/logo_dl.png" alt="Drastha Learning Logo" class="h-10 w-auto object-contain" />
  </div>`
);
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
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
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
                <span class="text-xs text-slate-400 font-semibold truncate max-w-[180px]">No file chosen</span>
                <!-- Hidden native input -->
                <input 
                  type="file" 
                  accept="image/jpeg,image/png,image/jpg"
                  @change="(e) => { form.photo = e.target.files[0] }"
                  class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                />
              </div>
              <span class="text-[10px] text-slate-400 font-bold leading-normal">
                Only JPG, JPEG or PNG files with Max Size of 1MB
              </span>
            </div>

            <!-- Personal Goal Select Field -->
            <div class="flex flex-col gap-2 relative">
              <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Personal Goal</label>
              <div class="relative">
                <select 
                  v-model="form.personal_goal"
                  class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-bold focus:outline-none appearance-none cursor-pointer outline-none"
                >
                  <option disabled value="Pilih Keahlian">Pilih Keahlian</option>
                  <option value="Web Developer">Web Developer</option>
                  <option value="Mobile Developer">Mobile Developer</option>
                  <option value="UI/UX Designer">UI/UX Designer</option>
                  <option value="Data Scientist">Data Scientist</option>
                  <option value="Python Developer">Python Developer</option>
                </select>
                <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                  <ChevronDown :size="16" />
                </div>
              </div>
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
