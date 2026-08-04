<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { CheckCircle, XCircle, Download, UserCheck, Shield, BookOpen, AlertCircle, Plus, Trash2, RotateCcw } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    users: Array,
    pendingInstructors: Array,
    trashedUsers: Array,
    globalRevenueShare: [String, Number],
    userManagementSettings: Object
});

const activeTab = ref('pending'); // 'pending', 'all', 'trashed', 'settings'

const showAddUserModal = ref(false);
const addUserForm = useForm({
    name: '',
    email: '',
    role: 'student'
});

const submitAddUser = () => {
    addUserForm.post(route('dashboard.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddUserModal.value = false;
            addUserForm.reset();
            Swal.fire('Berhasil!', 'Pengguna telah ditambahkan. Tautan aktivasi dijadwalkan untuk dikirim via email.', 'success');
        }
    });
};

const showDeleteModal = ref(false);
const userToDelete = ref(null);
const deleteForm = useForm({
    otp_code: ''
});

const openDeleteModal = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
    deleteForm.reset();
};

const sendDeleteOtp = () => {
    router.post(route('dashboard.users.send-delete-otp'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'OTP Terkirim', showConfirmButton: false, timer: 3000 });
        }
    });
};

const submitDeleteUser = () => {
    deleteForm.delete(route('dashboard.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            userToDelete.value = null;
            Swal.fire('Berhasil!', 'Pengguna telah dihapus.', 'success');
        }
    });
};

const restoreUser = (user) => {
    Swal.fire({
        title: 'Pulihkan Pengguna?',
        text: `Apakah Anda yakin ingin memulihkan akun ${user.name}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Pulihkan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#10b981',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('dashboard.users.restore', user.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Berhasil!', 'Akun pengguna berhasil dipulihkan.', 'success');
                }
            });
        }
    });
};

const userManagementForm = useForm({
    silent_delete: props.userManagementSettings?.silent_delete ?? false,
});
const isSavingUserManagement = ref(false);

const saveUserManagementSettings = () => {
    isSavingUserManagement.value = true;
    userManagementForm.post(route('dashboard.users.settings.update'), {
        preserveScroll: true,
        onFinish: () => {
            isSavingUserManagement.value = false;
        },
        onSuccess: () => {
            Swal.fire('Berhasil!', 'Pengaturan penghapusan berhasil diperbarui.', 'success');
        }
    });
};

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
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        @click="showAddUserModal = true"
                        class="bg-[#264790] hover:bg-blue-800 text-white px-5 py-3 rounded-xl font-bold text-sm transition-colors shadow-sm flex items-center justify-center gap-2"
                    >
                        <Plus :size="18" />
                        Tambah Pengguna
                    </button>
                    <button
                        @click="exportToExcel"
                        class="bg-[#10b981] hover:bg-[#059669] text-white px-5 py-3 rounded-xl font-bold text-sm transition-colors shadow-sm flex items-center justify-center gap-2"
                    >
                        <Download :size="18" />
                        Export ke Excel
                    </button>
                </div>
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
                <button
                    @click="activeTab = 'trashed'"
                    :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all flex items-center gap-2', activeTab === 'trashed' ? 'border-[#264790] text-[#264790]' : 'border-transparent text-slate-500 hover:text-slate-700']"
                >
                    <Trash2 :size="18" />
                    Riwayat Terhapus
                </button>
                <button
                    @click="activeTab = 'settings'"
                    :class="['px-6 py-4 text-sm font-bold border-b-2 transition-all flex items-center gap-2', activeTab === 'settings' ? 'border-[#264790] text-[#264790]' : 'border-transparent text-slate-500 hover:text-slate-700']"
                >
                    <Shield :size="18" />
                    Silent Delete
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
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
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
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            v-if="user.id !== 1 && user.role !== 'admin'"
                                            @click="openDeleteModal(user)"
                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors"
                                            title="Hapus Pengguna"
                                        >
                                            <Trash2 :size="18" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Trashed Users -->
                <div v-if="activeTab === 'trashed'">
                    <div v-if="trashedUsers.length === 0" class="text-center py-12">
                        <AlertCircle :size="48" class="text-slate-300 mx-auto mb-4" />
                        <h3 class="text-lg font-bold text-slate-600">Tidak Ada Data</h3>
                        <p class="text-slate-400 text-sm mt-1">Belum ada pengguna yang dihapus.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Pengguna</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tgl Dihapus</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="user in trashedUsers" :key="user.id" class="hover:bg-slate-50/50 transition-colors opacity-75">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-slate-300 text-slate-600 flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-extrabold text-slate-700 text-sm flex items-center gap-1 line-through">
                                                    {{ user.name }}
                                                </div>
                                                <div class="text-xs text-slate-500">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                        {{ formatDate(user.deleted_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            @click="restoreUser(user)"
                                            class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 px-3 py-2 rounded-lg transition-colors inline-flex items-center gap-2 font-bold text-sm"
                                            title="Pulihkan Pengguna"
                                        >
                                            <RotateCcw :size="16" /> Restore
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Settings (Silent Delete) -->
                <div v-if="activeTab === 'settings'">
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-sm flex flex-col gap-6 max-w-3xl mx-auto mt-4">
                        <div class="border-b border-slate-100 pb-4 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-extrabold text-[#1A2B49]">Manajemen Penghapusan Pengguna (Silent Delete)</h3>
                                <p class="text-xs text-slate-500 mt-1">Atur perilaku notifikasi saat administrator menonaktifkan (soft-delete) akun pengguna dari halaman Manajemen Pengguna.</p>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center">
                                <Trash2 :size="20" />
                            </div>
                        </div>

                        <form @submit.prevent="saveUserManagementSettings" class="flex flex-col gap-6">
                            <div class="flex items-center justify-between p-5 bg-slate-50 rounded-2xl border border-slate-150">
                                <div>
                                    <label class="block text-slate-800 text-sm font-bold flex items-center gap-2">
                                        <span>Mode Silent Delete</span>
                                        <span :class="userManagementForm.silent_delete ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase">
                                            {{ userManagementForm.silent_delete ? 'Aktif (Senyap)' : 'Nonaktif (Notifikasi)' }}
                                        </span>
                                    </label>
                                    <p class="text-xs text-slate-500 mt-1 font-medium leading-relaxed">
                                        <strong>ON (Senyap):</strong> Akun dinonaktifkan diam-diam tanpa mengirim email pemberitahuan ke pengguna. <br />
                                        <strong>OFF (Notifikasi):</strong> Sistem mengirim email pemberitahuan penonaktifan akun ke pengguna terkait.
                                    </p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="userManagementForm.silent_delete = !userManagementForm.silent_delete"
                                    :class="userManagementForm.silent_delete ? 'bg-[#264790]' : 'bg-slate-200'"
                                    class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                >
                                    <span 
                                        :class="userManagementForm.silent_delete ? 'translate-x-5' : 'translate-x-0'"
                                        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    ></span>
                                </button>
                            </div>

                            <div class="flex justify-end">
                                <button 
                                    type="submit" 
                                    :disabled="isSavingUserManagement"
                                    class="bg-[#264790] hover:bg-[#1f3a76] text-white px-8 py-3.5 rounded-2xl font-bold text-sm shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                                >
                                    <Shield :size="16" /> {{ isSavingUserManagement ? 'Menyimpan...' : 'Simpan Pengaturan Penghapusan' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </DashboardWrapper>

    <!-- Add User Modal -->
    <div v-if="showAddUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg">Tambah Pengguna Baru</h3>
                <button @click="showAddUserModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <XCircle :size="24" />
                </button>
            </div>
            <form @submit.prevent="submitAddUser" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
                        <input v-model="addUserForm.name" type="text" class="w-full border-slate-200 rounded-xl px-4 py-2.5 focus:ring focus:ring-[#264790] focus:border-[#264790]" required>
                        <div v-if="addUserForm.errors.name" class="text-red-500 text-xs mt-1">{{ addUserForm.errors.name }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Email</label>
                        <input v-model="addUserForm.email" type="email" class="w-full border-slate-200 rounded-xl px-4 py-2.5 focus:ring focus:ring-[#264790] focus:border-[#264790]" required>
                        <div v-if="addUserForm.errors.email" class="text-red-500 text-xs mt-1">{{ addUserForm.errors.email }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Role</label>
                        <select v-model="addUserForm.role" class="w-full border-slate-200 rounded-xl px-4 py-2.5 focus:ring focus:ring-[#264790] focus:border-[#264790]" required>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                        </select>
                        <div v-if="addUserForm.errors.role" class="text-red-500 text-xs mt-1">{{ addUserForm.errors.role }}</div>
                    </div>
                </div>
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" @click="showAddUserModal = false" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Batal</button>
                    <button type="submit" :disabled="addUserForm.processing" class="px-5 py-2.5 text-sm font-bold text-white bg-[#264790] hover:bg-blue-800 rounded-xl transition-colors disabled:opacity-50">
                        {{ addUserForm.processing ? 'Menyimpan...' : 'Simpan & Kirim' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirm Modal with OTP -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-red-100 flex justify-between items-center bg-red-50">
                <h3 class="font-bold text-red-800 text-lg flex items-center gap-2"><AlertCircle :size="20"/> Konfirmasi Hapus</h3>
                <button @click="showDeleteModal = false" class="text-red-400 hover:text-red-600 transition-colors">
                    <XCircle :size="24" />
                </button>
            </div>
            <form @submit.prevent="submitDeleteUser" class="p-6">
                <p class="text-slate-600 text-sm mb-4">
                    Anda akan menonaktifkan pengguna <strong>{{ userToDelete?.name }}</strong>. Akun dapat dipulihkan dari tab Riwayat Terhapus.
                </p>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4 text-sm text-amber-800">
                    Untuk melanjutkan penghapusan, silakan minta kode OTP yang akan dikirimkan ke email Anda.
                </div>

                <div class="mb-4 flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-slate-700 mb-1">Kode OTP</label>
                        <input v-model="deleteForm.otp_code" type="text" maxlength="6" placeholder="Buka email..." class="w-full border-slate-200 rounded-xl px-4 py-2.5 focus:ring focus:ring-red-100 focus:border-red-400 text-center tracking-[0.2em] font-mono text-lg" required>
                    </div>
                    <button type="button" @click="sendDeleteOtp" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-sm transition-colors border border-slate-200">
                        Kirim OTP
                    </button>
                </div>
                <div v-if="deleteForm.errors.otp_code" class="text-red-500 text-xs mb-4 text-center">{{ deleteForm.errors.otp_code }}</div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showDeleteModal = false" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Batal</button>
                    <button type="submit" :disabled="deleteForm.processing" class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors disabled:opacity-50">
                        {{ deleteForm.processing ? 'Menghapus...' : 'Konfirmasi Hapus' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
