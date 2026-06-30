<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};

// Logo Helper matching other Auth views
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
    <GuestLayout>
        <Head title="Lupa Password | Drastha Learning" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex items-center justify-center font-montserrat">
            <div 
              class="bg-white rounded-[2.5rem] overflow-hidden max-w-4xl w-full flex flex-col md:flex-row border border-slate-100 shadow-[0_20px_60px_rgba(0,0,0,0.03)] relative h-auto md:min-h-[580px]"
            >
                <!-- LEFT PANEL: Illustration -->
                <div class="hidden md:flex md:w-[42%] relative bg-[#264790] overflow-hidden select-none shrink-0">
                  <img 
                    src="/images/pages/login_or_signup/bg-login-signup.png" 
                    class="w-full h-full object-cover" 
                    alt="Drastha Login Background"
                  />
                </div>

                <!-- RIGHT PANEL: Form -->
                <div class="w-full md:w-[58%] p-8 sm:p-12 md:p-16 flex flex-col justify-center">
                  <div class="flex flex-col items-center md:items-start text-center md:text-left mb-6">
                      <div v-html="Logo()" class="mb-6"></div>
                      <h2 class="text-3xl font-extrabold text-[#1A2B49] tracking-tight mb-2">Lupa Password</h2>
                      <p class="text-xs sm:text-sm font-semibold text-slate-400 leading-relaxed mt-2">
                          Masukkan alamat email terdaftar Anda. Kami akan mengirimkan kode verifikasi OTP untuk menyetel ulang password Anda.
                      </p>
                  </div>

                  <div v-if="status" class="mb-4 text-xs font-bold text-green-600 bg-green-50 p-4 rounded-2xl border border-green-100">
                      {{ status }}
                  </div>

                  <form @submit.prevent="submit" class="flex flex-col gap-5">
                    <!-- Email Field -->
                    <div class="flex flex-col gap-2 text-left">
                      <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Alamat Email</label>
                      <input 
                        type="email" 
                        v-model="form.email"
                        placeholder="email@domain.com" 
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
                      />
                      <div v-if="form.errors.email" class="text-xs font-bold text-red-600 mt-1">
                          {{ form.errors.email }}
                      </div>
                    </div>

                    <!-- Submit button -->
                    <button 
                      type="submit"
                      :disabled="form.processing"
                      class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-3"
                    >
                      Kirim Kode OTP
                    </button>

                    <!-- Back to Login -->
                    <Link 
                      :href="route('login')"
                      class="w-full bg-[#F4F7F9] hover:bg-slate-100 text-[#1A2B49] py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-sm transition-all text-center border border-slate-100"
                    >
                      Kembali ke Halaman Masuk
                    </Link>
                  </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat {
  font-family: 'Montserrat', sans-serif;
}
</style>
