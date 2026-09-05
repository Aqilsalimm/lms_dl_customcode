<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    profile: {
        type: Object,
        default: null,
    },
    memberships: {
        type: Array,
        default: () => [],
    },
    returnUrl: {
        type: String,
        default: null,
    },
});

// Personal Profile Form
const profileForm = useForm({
    legal_name: props.profile?.legal_name || props.user.name || '',
    phone: props.profile?.phone || '',
    gender: props.profile?.gender || '',
});

const submitProfile = () => {
    profileForm.patch(route('onboarding.institutional.profile.update'), {
        preserveScroll: true,
    });
};

// Membership Forms mapped by membership ID
const activeTab = ref(0);

const membershipForms = props.memberships.map((m) =>
    useForm({
        legal_name: props.profile?.legal_name || props.user.name || '',
        phone: props.profile?.phone || '',
        gender: props.profile?.gender || '',
        member_type: m.member_type || 'employee_id',
        member_number: m.member_number || '',
        division: m.division || '',
        position: m.position || '',
        return_url: props.returnUrl,
    })
);

const submitMembership = (index, membershipId) => {
    const form = membershipForms[index];
    form.patch(route('onboarding.institutional.membership.update', membershipId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-8">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-2xl p-6 text-white shadow-xl relative">
                <!-- Back Button -->
                <button 
                    @click="returnUrl ? $inertia.visit(returnUrl) : $inertia.visit(route('profile.edit'))" 
                    class="absolute top-6 right-6 p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-colors flex items-center gap-2 text-sm font-medium backdrop-blur-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    Kembali
                </button>

                <h1 class="text-2xl font-bold pr-24">Lengkapi Profil Institusional Anda</h1>
                <p class="mt-2 text-indigo-100 text-sm max-w-2xl">
                    Silakan lengkapi data resmi Anda agar sertifikat dan laporan kehadiran dapat diterbitkan dengan benar.
                </p>
            </div>

            <!-- Card 1: Personal Legal Profile -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">1. Data Diri Resmi</h2>
                        <p class="text-xs text-slate-500">Nama resmi yang akan dicetak pada ijazah/sertifikat.</p>
                    </div>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 font-medium text-xs rounded-full">
                        Wajib
                    </span>
                </div>

                <form @submit.prevent="submitProfile" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nama Lengkap (Sesuai KTP / Dokumen Resmi)</label>
                        <input
                            v-model="profileForm.legal_name"
                            type="text"
                            required
                            placeholder="Contoh: Dr. Budi Santoso, S.Kom., M.T."
                            class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="profileForm.errors.legal_name" class="mt-1 text-xs text-red-600">
                            {{ profileForm.errors.legal_name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Nomor Telepon / WhatsApp (Opsional)</label>
                            <input
                                v-model="profileForm.phone"
                                type="text"
                                placeholder="081234567890"
                                class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="profileForm.errors.phone" class="mt-1 text-xs text-red-600">
                                {{ profileForm.errors.phone }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Jenis Kelamin / Gender</label>
                            <select
                                v-model="profileForm.gender"
                                class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                            <p v-if="profileForm.errors.gender" class="mt-1 text-xs text-red-600">
                                {{ profileForm.errors.gender }}
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium text-sm rounded-xl transition-all shadow-sm"
                        >
                            Simpan Data Diri
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card 2: Organization Membership Cards -->
            <div v-if="memberships.length > 0" class="space-y-6">
                <div
                    v-for="(membership, index) in memberships"
                    :key="membership.id"
                    class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-6"
                >
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">
                                2. Data Keanggotaan: {{ membership.organization?.name || 'Instansi' }}
                            </h2>
                            <p class="text-xs text-slate-500">Kode Instansi: {{ membership.organization?.code }}</p>
                        </div>
                        <span
                            :class="[
                                'px-3 py-1 font-medium text-xs rounded-full',
                                membership.profile_completed_at
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-amber-50 text-amber-700'
                            ]"
                        >
                            {{ membership.profile_completed_at ? 'Lengkap' : 'Belum Lengkap' }}
                        </span>
                    </div>

                    <form @submit.prevent="submitMembership(index, membership.id)" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Tipe Identitas Anggota</label>
                                <select
                                    v-model="membershipForms[index].member_type"
                                    class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="employee_id">ID Pegawai / NIP</option>
                                    <option value="nim">NIM / Nomor Mahasiswa</option>
                                    <option value="nis">NIS / Nomor Siswa</option>
                                    <option value="member_id">Nomor Anggota</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Nomor Pegawai / Anggota</label>
                                <input
                                    v-model="membershipForms[index].member_number"
                                    type="text"
                                    required
                                    placeholder="Contoh: 199408222019031002"
                                    class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="membershipForms[index].errors.member_number" class="mt-1 text-xs text-red-600">
                                    {{ membershipForms[index].errors.member_number }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Unit Kerja / Divisi</label>
                                <input
                                    v-model="membershipForms[index].division"
                                    type="text"
                                    placeholder="Contoh: Ditjen Pajak / IT Ops"
                                    class="mt-1 block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button
                                type="submit"
                                :disabled="membershipForms[index].processing"
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium text-sm rounded-xl transition-all shadow-sm"
                            >
                                Simpan Data Profil & Lanjutkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div v-else class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-6 opacity-70">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-800">2. Data Keanggotaan Institusi</h2>
                        <p class="text-xs text-slate-500">Afiliasi instansi atau organisasi pendaftar.</p>
                    </div>
                    <span class="px-3 py-1 bg-slate-100 text-slate-600 font-medium text-xs rounded-full">
                        Otomatis
                    </span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pb-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-500">Jenis Afiliasi</label>
                        <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 font-semibold">
                            Lainnya (Umum / Freelance)
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500">Nama Instansi</label>
                        <div class="mt-1 block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 font-semibold">
                            Personal
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
