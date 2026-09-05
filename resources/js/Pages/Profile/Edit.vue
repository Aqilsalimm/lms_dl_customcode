<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, ChevronRight, CheckCircle2 } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    hasActiveMembership: {
        type: Boolean,
        default: false,
    },
    profile: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

                <!-- Legal Identity & Institutional Data Card -->
                <div class="bg-gradient-to-r from-[#264790] to-[#1A2B49] rounded-2xl p-1 shadow-lg overflow-hidden relative">
                    <div class="absolute right-0 top-0 opacity-10 pointer-events-none translate-x-1/4 -translate-y-1/4">
                        <Building2 :size="200" />
                    </div>

                    <div class="bg-white/5 backdrop-blur-sm p-6 sm:p-8 rounded-xl relative z-10 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-blue-500/20 border border-blue-400/30 rounded-xl text-blue-300">
                                <Building2 :size="28" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xl font-extrabold text-white tracking-tight">Identitas Resmi & Instansi</h3>
                                    <CheckCircle2 v-if="profile && profile.legal_name" :size="18" class="text-emerald-400" />
                                </div>
                                <p class="text-blue-100/70 text-sm mt-1 max-w-lg leading-relaxed">
                                    Lengkapi <strong>Nama Lengkap + Gelar</strong> untuk dicetak pada Ijazah / Sertifikat kelulusan Anda, beserta kelengkapan data instansi (jika ada).
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="route('onboarding.institutional.show')"
                            class="w-full sm:w-auto px-6 py-3.5 bg-white text-[#264790] hover:bg-blue-50 rounded-xl font-black text-sm transition-all shadow flex items-center justify-center gap-2 group shrink-0"
                        >
                            {{ profile && profile.legal_name ? 'Tinjau Detail Identitas' : 'Lengkapi Identitas Resmi' }}
                            <ChevronRight :size="18" class="group-hover:translate-x-1 transition-transform" />
                        </Link>
                    </div>
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :profile="profile"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
