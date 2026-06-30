<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

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
    <Head title="Lupa Password | Drastha Learning" />

    <div class="min-h-screen w-full flex flex-col md:flex-row bg-[#F4F7F9] font-montserrat relative overflow-hidden">
        <!-- LEFT SIDEBAR: Illustration Column -->
        <div class="hidden md:flex md:w-[35%] xl:w-[30%] shrink-0 relative bg-[#264790] overflow-hidden min-h-screen shadow-lg">
            <img 
              src="/images/pages/login_or_signup/bg-login-signup.png" 
              class="w-full h-full object-cover" 
              alt="Drastha Learning Signup Illustration"
            />
        </div>

        <!-- RIGHT SIDEBAR: Card Content Column -->
        <div class="flex-grow flex flex-col items-center justify-center p-6 sm:p-12 md:p-16 xl:p-24 relative min-h-screen">
            <!-- Status Alerts -->
            <div v-if="status" class="mb-6 text-xs font-bold text-green-600 bg-green-50 p-4 rounded-2xl border border-green-100 max-w-[500px] w-full animate-fade-in">
                {{ status }}
            </div>

            <!-- Main Container Card -->
            <div class="bg-white rounded-[2rem] p-8 sm:p-12 w-full max-w-[500px] shadow-[0_12px_50px_rgba(0,0,0,0.025)] border border-slate-100/50 flex flex-col gap-6 transition-all duration-300">
                <div class="flex flex-col items-center text-center">
                    <div v-html="Logo()" class="mb-6"></div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1A2B49] leading-tight mb-2">Lupa Password</h2>
                    <p class="text-slate-400 text-xs sm:text-sm font-semibold leading-relaxed max-w-xs text-center mt-2">
                        Masukkan alamat email terdaftar Anda. Kami akan mengirimkan kode verifikasi OTP untuk menyetel ulang password Anda.
                    </p>
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
                      class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-2"
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
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat {
  font-family: 'Montserrat', sans-serif;
}
</style>
