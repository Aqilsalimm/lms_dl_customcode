<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Key, ArrowLeft, Send } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    email: {
        type: String,
        required: true,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    otp_code: '',
});

const resendForm = useForm({});

const submit = () => {
    form.post(route('login.otp.verify'), {
        onFinish: () => form.reset('otp_code'),
    });
};

const resendOtp = () => {
    resendForm.post(route('login.otp.resend'));
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
        <Head title="Verifikasi Login | Drastha Learning" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex items-center justify-center">
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
                    <div class="flex flex-col items-center text-center">
                        <div v-html="Logo()" class="mb-6"></div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1A2B49] leading-tight mb-4">Verifikasi Login</h2>
                    </div>

                    <!-- Status Alerts -->
                    <div v-if="status" class="mb-6 text-xs font-bold text-green-600 bg-green-50 p-4 rounded-2xl border border-green-100">
                        {{ status }}
                    </div>

                    <div v-if="resendForm.processing" class="mb-6 text-xs font-bold text-blue-600 bg-blue-50 p-4 rounded-2xl border border-blue-100">
                        Mengirim ulang kode OTP...
                    </div>

                    <!-- Info Alert Box -->
                    <div class="bg-[#F9CC6B]/15 border border-[#F9CC6B]/30 rounded-2xl p-5 mb-6 flex flex-col gap-2 text-left">
                        <p class="text-slate-600 text-[11px] sm:text-xs font-bold leading-relaxed">
                            Kode OTP telah dikirim ke alamat email anda :<br />
                            <span class="text-[#264790] font-extrabold break-all">{{ email }}</span>
                        </p>
                        <p class="text-[#264790] text-[10px] sm:text-[11px] font-extrabold leading-relaxed">
                            Silakan Periksa folder inbox, promosi, atau spam jika tidak menemukan email dari kami
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="flex flex-col gap-5">
                        <!-- OTP Input Field -->
                        <div class="flex flex-col gap-2 text-left">
                            <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Masukkan Kode OTP</label>
                            <input 
                              type="text" 
                              v-model="form.otp_code"
                              placeholder="Masukkan 6 digit kode OTP" 
                              maxlength="6"
                              required
                              class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs sm:text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400 text-center tracking-widest"
                            />
                            <div v-if="form.errors.otp_code" class="text-xs font-bold text-red-600 mt-1">
                                {{ form.errors.otp_code }}
                            </div>
                        </div>

                        <!-- Continue Button -->
                        <button 
                          type="submit"
                          :disabled="form.processing"
                          class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-2"
                        >
                            Continue
                        </button>
                    </form>

                    <!-- Footer Actions -->
                    <div class="flex flex-col gap-4 items-center mt-6">
                        <span class="text-xs font-bold text-slate-400">
                            Tidak Menerima kode OTP? 
                            <button 
                              @click="resendOtp" 
                              :disabled="resendForm.processing"
                              class="text-[#44A6D9] hover:underline focus:outline-none"
                            >
                                Kirim Ulang OTP
                            </button>
                        </span>
                        
                        <Link 
                          :href="route('login')" 
                          class="flex items-center gap-1.5 text-xs font-extrabold text-slate-400 hover:text-slate-600 transition-colors mt-2"
                        >
                            <ArrowLeft :size="14" />
                            <span>Kembali ke Halaman Login</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
