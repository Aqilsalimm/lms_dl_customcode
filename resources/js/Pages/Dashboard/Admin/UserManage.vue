<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { CheckCircle, XCircle, Download, UserCheck, Shield, BookOpen, AlertCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    users: Array,
    pendingInstructors: Array,
    globalRevenueShare: [String, Number]
});

const activeTab = ref('pending'); // 'pending' or 'all'

const approveInstructor = (user) => {
    Swal.fire({
        title: 'Setujui Instruktur?',
        html: `Apakah Anda yakin ingin menyetujui <b>${user.name}</b> sebagai Instruktur?<br/><br/>
               <div class="bg-blue-50 text-blue-800 text-sm p-3 rounded-lg border border-blue-200">
               <strong>Info Revenue Sharing:</strong><br/>
               Berdasarkan pengaturan LMS, instruktur ini akan menerima <b>${props.globalRevenueShare}%</b> dari total pendapatan setiap penjualan kelasnya.
               </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#10b981',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('dashboard.users.approve', user.id), {}, {
                onSuccess: () => {
                    Swal.fire('Berhasil!', 'Instruktur telah disetujui.', 'success');
                }
            });
        }
    });
};

const rejectInstructor = (user) => {
    Swal.fire({
        title: 'Tolak Instruktur?',
        text: `Apakah Anda yakin ingin menolak aplikasi instruktur dari ${user.name}? Pengguna akan dikembalikan menjadi Siswa (Student) biasa.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tolak',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#ef4444',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('dashboard.users.reject', user.id), {}, {
                onSuccess: () => {
                    Swal.fire('Ditolak!', 'Aplikasi instruktur ditolak.', 'info');
                }
            });
        }
    });
};

const updateRole = (user, newRole) => {
    if (user.id === 1) {
        Swal.fire('Error', 'Role Superadmin tidak dapat diubah.', 'error');
        return;
    }

    router.post(route('dashboard.users.role', user.id), { role: newRole }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Role berhasil diperbarui',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
};

const exportToExcel = () => {
    window.location.href = route('dashboard.users.export');
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }).format(date);
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <DashboardWrapper>
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <!-- Header Section -->
            <div class="p-6 sm:p-8 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-[#1A2B49]">Manajemen Pengguna</h2>
                    <p class="text-sm font-semibold text-slate-500 mt-1">
                        Kelola data siswa, setujui pendaftaran instruktur baru, dan atur role pengguna.
                    </p>
                </div>
                
                <button 
                    @click="exportToExcel"
                    class="bg-[#10b981] hover:bg-[#059669] text-white px-5 py-3 rounded-xl font-bold text-sm transition-colors shadow-sm flex items-center justify-center gap-2"
                >
                    <Download :size="18" />
                    Export ke Excel
                </button>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-slate-100 px-6 sm:px-8 bg-slate-50/50">
                <button 
                    @click="activeTab = 'pending'"
                    :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all flex items-center gap-2', activeTab === 'pending' ? 'border-[#264790] text-[#264790]' : 'border-transparent text-slate-500 hover:text-slate-700']"
                >
                    <UserCheck :size="18" />
                    Persetujuan Instruktur
                    <span v-if="pendingInstructors.length > 0" class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full ml-1">
                        {{ pendingInstructors.length }}
                    </span>
                </button>
                <button 
                    @click="activeTab = 'all'"
                    :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all flex items-center gap-2', activeTab === 'all' ? 'border-[#264790] text-[#264790]' : 'border-transparent text-slate-500 hover:text-slate-700']"
                >
                    <BookOpen :size="18" />
                    Semua Pengguna
                </button>
            </div>

            <div class="p-6 sm:p-8">
                
                <!-- Tab: Pending Instructors -->
                <div v-if="activeTab === 'pending'">
                    <div v-if="pendingInstructors.length === 0" class="text-center py-12">
                        <AlertCircle :size="48" class="text-slate-300 mx-auto mb-4" />
                        <h3 class="text-lg font-bold text-slate-600">Tidak Ada Pendaftaran Baru</h3>
                        <p class="text-slate-400 text-sm mt-1">Semua aplikasi instruktur telah ditinjau.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div v-for="user in pendingInstructors" :key="user.id" class="border border-slate-100 rounded-2xl p-6 bg-white shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center font-bold text-lg">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h3 class="font-extrabold text-[#1A2B49] text-lg">{{ user.name }}</h3>
                                        <p class="text-sm text-slate-500">{{ user.email }}</p>
                                    </div>
                                </div>
                                <span class="bg-amber-100 text-amber-700 text-xs font-bold px-3 py-1 rounded-full">Pending</span>
                            </div>

                            <div v-if="user.instructor_profile" class="bg-slate-50 p-4 rounded-xl mb-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Bidang Keahlian</p>
                                        <p class="font-semibold text-slate-700">{{ user.instructor_profile.expertise_area }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Portofolio</p>
                                        <a v-if="user.instructor_profile.portfolio_url" :href="user.instructor_profile.portfolio_url" target="_blank" class="font-semibold text-[#44A6D9] hover:underline truncate block">Lihat Portofolio</a>
                                        <span v-else class="text-slate-500 font-semibold">-</span>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Bio Singkat</p>
                                        <p class="font-medium text-slate-600 line-clamp-3">{{ user.instructor_profile.bio_summary }}</p>
                                    </div>
                                    <div class="sm:col-span-2 mt-2">
                                        <a v-if="user.instructor_profile.resume_file" :href="user.instructor_profile.resume_file" target="_blank" class="inline-flex items-center gap-2 text-sm font-bold text-[#264790] bg-[#F4F7F9] hover:bg-indigo-50 px-4 py-2 rounded-lg border border-[#264790]/20 transition-colors">
                                            <Download :size="16" /> Unduh Resume / CV
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 mt-4 pt-4 border-t border-slate-100">
                                <button @click="approveInstructor(user)" class="flex-1 bg-[#10b981] hover:bg-[#059669] text-white py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center justify-center gap-2">
                                    <CheckCircle :size="18" /> Setujui
                                </button>
                                <button @click="rejectInstructor(user)" class="flex-1 bg-white border-2 border-red-500 text-red-500 hover:bg-red-50 py-2.5 rounded-xl font-bold text-sm transition-colors flex items-center justify-center gap-2">
                                    <XCircle :size="18" /> Tolak
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: All Users -->
                <div v-if="activeTab === 'all'">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Pengguna</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tgl Daftar</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Role (Akses)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-[#264790] text-white flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-extrabold text-[#1A2B49] text-sm flex items-center gap-1">
                                                    {{ user.name }}
                                                    <Shield v-if="user.id === 1" :size="14" class="text-amber-500" title="Superadmin" />
                                                </div>
                                                <div class="text-xs text-slate-500">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="user.status === 'active'" class="bg-emerald-100 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full">Active</span>
                                        <span v-else-if="user.status === 'pending'" class="bg-amber-100 text-amber-700 text-xs font-bold px-3 py-1 rounded-full">Pending</span>
                                        <span v-else-if="user.status === 'suspended'" class="bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full">Suspended</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <select 
                                            v-model="user.role"
                                            @change="updateRole(user, $event.target.value)"
                                            :disabled="user.id === 1"
                                            class="bg-white border border-slate-200 text-sm font-semibold rounded-lg px-3 pr-8 py-2 w-36 text-slate-700 focus:ring focus:ring-indigo-100 focus:border-indigo-300 disabled:bg-slate-100 disabled:text-slate-400"
                                        >
                                            <option value="student">Student</option>
                                            <option value="instructor">Instructor</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </DashboardWrapper>
</template>
