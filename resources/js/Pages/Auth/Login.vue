<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Eye, EyeOff, X, Globe, ShoppingCart, User } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            alert('Selamat datang kembali!');
        }
    });
};

const triggerGoogleOAuth = () => {
  window.location.href = route('auth.google');
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign In | Drastha Learning" />

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
                  
                  <h2 class="text-3xl font-extrabold text-[#1A2B49] tracking-tight mb-8">Sign In</h2>

                  <div v-if="status" class="mb-4 text-xs font-bold text-green-600 bg-green-50 p-3 rounded-xl border border-green-100">
                      {{ status }}
                  </div>

                  <form @submit.prevent="submit" class="flex flex-col gap-5">
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
                    <div class="flex flex-col gap-2 relative">
                      <label class="text-xs sm:text-sm font-extrabold text-[#264790]">Password</label>
                      <div class="relative">
                        <input 
                          :type="showPassword ? 'text' : 'password'" 
                          v-model="form.password"
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
                      <Link 
                        v-if="canResetPassword"
                        :href="route('password.request')" 
                        class="self-end text-xs font-bold text-slate-400 hover:text-[#264790] transition-colors mt-1"
                      >
                        Forgot your password?
                      </Link>
                    </div>

                    <!-- Sign In button -->
                    <button 
                      type="submit"
                      :disabled="form.processing"
                      class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md hover:shadow-lg transition-all text-center mt-3"
                    >
                      Sign In
                    </button>

                    <!-- Create New Account -->
                    <Link 
                      :href="route('register')"
                      class="w-full bg-[#F4F7F9] hover:bg-slate-100 text-[#1A2B49] py-4 rounded-2xl font-extrabold text-xs sm:text-sm shadow-sm transition-all text-center border border-slate-100"
                    >
                      Create New Account
                    </Link>
                  </form>

                  <div class="w-full h-px bg-slate-100 my-6 relative">
                    <div class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">atau</div>
                  </div>

                  <!-- Google Sign In Button -->
                  <button 
                    @click="triggerGoogleOAuth"
                    class="w-full bg-[#000000] hover:bg-[#1A2B49] text-white py-3.5 rounded-full font-extrabold text-xs sm:text-sm transition-all flex items-center justify-center gap-2.5 shadow-md"
                  >
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



    </GuestLayout>
</template>
