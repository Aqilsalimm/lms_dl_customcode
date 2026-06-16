<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Clock, CheckCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Menunggu Persetujuan Admin" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl p-8 sm:p-12 border border-slate-100 flex flex-col items-center text-center">
                    
                    <div class="w-24 h-24 bg-[#F9CC6B]/10 rounded-full flex items-center justify-center mb-6">
                        <Clock :size="48" class="text-[#F9CC6B]" />
                    </div>

                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1A2B49] mb-4">
                        Pendaftaran Anda Sedang Ditinjau
                    </h2>
                    
                    <p class="text-slate-500 font-medium max-w-lg mx-auto mb-8 leading-relaxed">
                        Terima kasih, <strong>{{ user.name }}</strong>. Kami telah menerima aplikasi Anda sebagai instruktur. Tim Admin kami akan meninjau profil dan portofolio Anda dalam waktu 1-2 hari kerja.
                    </p>

                    <div class="bg-[#F4F7F9] p-6 rounded-2xl border border-slate-100 w-full max-w-md text-left mb-8">
                        <h3 class="font-extrabold text-[#264790] mb-4">Status Pendaftaran:</h3>
                        <ul class="flex flex-col gap-3">
                            <li class="flex items-center gap-3">
                                <CheckCircle :size="20" class="text-emerald-500 shrink-0" />
                                <span class="text-sm font-semibold text-slate-700">Pendaftaran Akun</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <CheckCircle :size="20" class="text-emerald-500 shrink-0" />
                                <span class="text-sm font-semibold text-slate-700">Verifikasi Email (OTP)</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <CheckCircle :size="20" class="text-emerald-500 shrink-0" />
                                <span class="text-sm font-semibold text-slate-700">Melengkapi Profil & Resume</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="w-5 h-5 rounded-full border-2 border-[#F9CC6B] shrink-0 flex items-center justify-center relative">
                                    <div class="w-2.5 h-2.5 bg-[#F9CC6B] rounded-full animate-pulse"></div>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Menunggu Persetujuan Admin</span>
                            </li>
                        </ul>
                    </div>

                    <Link 
                        :href="route('dashboard')"
                        class="px-6 py-3 bg-[#264790] hover:bg-[#44A6D9] text-white font-bold rounded-xl transition-all shadow-sm"
                    >
                        Segarkan Halaman
                    </Link>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
