<template>
  <DashboardWrapper :auth="auth" title="Kelola Penarikan Dana">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Kelola Penarikan Dana</h2>
        <p class="text-sm text-slate-500 mt-1">Setujui permintaan penarikan dan unggah bukti transfer.</p>
      </div>

      <!-- Pending Requests Table -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
         <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-amber-500"></span>
              Menunggu Persetujuan
            </h3>
         </div>
         
         <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
               <thead>
                  <tr>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Tanggal</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Instruktur</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Akun Tujuan</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Jumlah</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Status</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">Aksi</th>
                  </tr>
               </thead>
               <tbody class="divide-y divide-slate-100">
                  <tr v-for="item in pendingWithdrawals" :key="item.id" class="hover:bg-slate-50/80 transition-colors">
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs font-medium text-slate-500">{{ formatDate(item.created_at) }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-slate-800">{{ item.user?.name || 'Unknown' }}</div>
                        <div class="text-xs text-slate-500">{{ item.user?.email }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div v-if="item.user?.payment_profile">
                          <div class="text-xs font-bold text-indigo-700 bg-indigo-50 inline-block px-2 py-0.5 rounded">{{ item.user.payment_profile.method?.name }}</div>
                          <div class="text-sm font-bold text-slate-700 mt-1">{{ item.user.payment_profile.account_number }}</div>
                          <div class="text-xs text-slate-500">a.n {{ item.user.payment_profile.account_name }}</div>
                        </div>
                        <div v-else class="text-xs text-red-500 font-medium">Data Akun Hilang</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-slate-800">Rp {{ formatNumber(item.amount) }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                          Pending
                        </span>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button @click="openApproveModal(item)" class="bg-blue-600 text-white hover:bg-blue-700 px-4 py-1.5 rounded-lg text-xs font-bold mr-2 transition-colors">
                          Transfer
                        </button>
                        <button @click="openRejectModal(item)" class="bg-red-100 text-red-600 hover:bg-red-200 px-4 py-1.5 rounded-lg text-xs font-bold transition-colors">
                          Tolak
                        </button>
                     </td>
                  </tr>
                  <tr v-if="pendingWithdrawals.length === 0">
                     <td colspan="6" class="px-6 py-12 text-center text-slate-500 text-sm">
                        Tidak ada permintaan penarikan yang menunggu persetujuan.
                     </td>
                  </tr>
               </tbody>
            </table>
         </div>
      </div>

      <!-- Processed Requests Table -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mt-8">
         <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-green-500"></span>
              Riwayat Selesai
            </h3>
         </div>
         
         <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
               <thead>
                  <tr>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Tanggal</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Instruktur</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Jumlah</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Status</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">Bukti Transfer</th>
                  </tr>
               </thead>
               <tbody class="divide-y divide-slate-100">
                  <tr v-for="item in processedWithdrawals" :key="item.id" class="hover:bg-slate-50/80 transition-colors">
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs font-medium text-slate-500">{{ formatDate(item.updated_at) }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-slate-800">{{ item.user?.name || 'Unknown' }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-slate-800">Rp {{ formatNumber(item.amount) }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 rounded-full text-xs font-bold" :class="item.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                          {{ item.status === 'completed' ? 'Berhasil' : 'Ditolak' }}
                        </span>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap text-right">
                        <a v-if="item.receipt_path" :href="`/storage/${item.receipt_path}`" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs font-bold">
                          Lihat Bukti
                        </a>
                        <span v-else-if="item.status === 'rejected'" class="text-red-500 text-xs font-medium" :title="item.admin_note">Ditolak: {{ item.admin_note }}</span>
                        <span v-else class="text-slate-400 text-xs">-</span>
                     </td>
                  </tr>
                  <tr v-if="processedWithdrawals.length === 0">
                     <td colspan="5" class="px-6 py-12 text-center text-slate-500 text-sm">
                        Belum ada riwayat penarikan yang diproses.
                     </td>
                  </tr>
               </tbody>
            </table>
         </div>
      </div>
    </div>

    <!-- Modals -->
    <!-- 1. Approve Modal (Upload Receipt) -->
    <Modal :show="showApproveModal" @close="closeApproveModal">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Setujui & Transfer Dana</h3>
        
        <div v-if="selectedItem" class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-5">
           <div class="text-sm text-slate-700 mb-2">Silakan transfer manual sejumlah <span class="font-extrabold">Rp {{ formatNumber(selectedItem.amount) }}</span> ke:</div>
           <div v-if="selectedItem.user?.payment_profile" class="bg-white p-3 rounded-lg border border-indigo-100">
               <div class="text-xs text-slate-500 font-medium">Bank/Wallet:</div>
               <div class="text-sm font-bold text-slate-800 mb-2">{{ selectedItem.user.payment_profile.method?.name }}</div>
               <div class="text-xs text-slate-500 font-medium">Nomor Akun:</div>
               <div class="text-base font-mono font-bold text-slate-800 mb-2">{{ selectedItem.user.payment_profile.account_number }}</div>
               <div class="text-xs text-slate-500 font-medium">Atas Nama:</div>
               <div class="text-sm font-bold text-slate-800">{{ selectedItem.user.payment_profile.account_name }}</div>
           </div>
        </div>

        <form @submit.prevent="submitApprove" class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Unggah Bukti Transfer</label>
            <input type="file" @input="approveForm.receipt = $event.target.files[0]" accept="image/png, image/jpeg, image/jpg" required class="w-full border-2 border-slate-200 rounded-xl px-4 py-2 text-sm text-slate-700 bg-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Catatan (Opsional)</label>
            <textarea v-model="approveForm.admin_note" rows="2" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold text-slate-700 resize-none" placeholder="Catatan untuk instruktur..."></textarea>
          </div>
          
          <div class="mt-6 flex justify-end gap-3">
             <button type="button" @click="closeApproveModal" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
             <button type="submit" :disabled="approveForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl disabled:opacity-50 flex items-center gap-2">
               <span v-if="approveForm.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
               Setujui & Selesaikan
             </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- 2. Reject Modal -->
    <Modal :show="showRejectModal" @close="closeRejectModal">
      <div class="p-6">
        <h3 class="text-lg font-bold text-red-600 mb-4">Tolak Penarikan</h3>
        
        <p class="text-sm text-slate-600 mb-4">
          Anda akan menolak permintaan penarikan dana sebesar <strong>Rp {{ selectedItem ? formatNumber(selectedItem.amount) : 0 }}</strong>. Saldo ini akan dikembalikan secara otomatis ke dompet instruktur.
        </p>

        <form @submit.prevent="submitReject" class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Alasan Penolakan (Wajib)</label>
            <textarea v-model="rejectForm.admin_note" required rows="3" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-red-500 text-sm font-semibold text-slate-700 resize-none" placeholder="Misal: Nomor rekening tidak valid..."></textarea>
          </div>
          
          <div class="mt-6 flex justify-end gap-3">
             <button type="button" @click="closeRejectModal" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
             <button type="submit" :disabled="rejectForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl disabled:opacity-50 flex items-center gap-2">
               <span v-if="rejectForm.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
               Tolak & Kembalikan Saldo
             </button>
          </div>
        </form>
      </div>
    </Modal>

  </DashboardWrapper>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import Modal from '@/Components/Modal.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    auth: Object,
    withdrawals: Array
});

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

const formatNumber = (num) => {
    if (!num) return '0';
    return parseInt(num).toLocaleString('id-ID');
};

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const pendingWithdrawals = computed(() => {
    return props.withdrawals.filter(w => w.status === 'pending');
});

const processedWithdrawals = computed(() => {
    return props.withdrawals.filter(w => w.status !== 'pending');
});

const selectedItem = ref(null);

// Approve Form
const showApproveModal = ref(false);
const approveForm = useForm({
    receipt: null,
    admin_note: ''
});

const openApproveModal = (item) => {
    selectedItem.value = item;
    showApproveModal.value = true;
};
const closeApproveModal = () => {
    showApproveModal.value = false;
    selectedItem.value = null;
    approveForm.reset();
};
const submitApprove = () => {
    approveForm.post(route('dashboard.admin.withdrawals.complete', selectedItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeApproveModal();
            Toast.fire({ icon: 'success', title: "Penarikan disetujui & diselesaikan." });
        },
        onError: (errors) => {
            if(errors.receipt) Toast.fire({ icon: 'error', title: errors.receipt });
            else Toast.fire({ icon: 'error', title: "Gagal memproses penarikan." });
        }
    });
};

// Reject Form
const showRejectModal = ref(false);
const rejectForm = useForm({
    admin_note: ''
});

const openRejectModal = (item) => {
    selectedItem.value = item;
    showRejectModal.value = true;
};
const closeRejectModal = () => {
    showRejectModal.value = false;
    selectedItem.value = null;
    rejectForm.reset();
};
const submitReject = () => {
    rejectForm.post(route('dashboard.admin.withdrawals.reject', selectedItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeRejectModal();
            Toast.fire({ icon: 'success', title: "Penarikan ditolak. Saldo dikembalikan ke instruktur." });
        },
        onError: (errors) => {
            if(errors.admin_note) Toast.fire({ icon: 'error', title: errors.admin_note });
            else Toast.fire({ icon: 'error', title: "Gagal menolak penarikan." });
        }
    });
};
</script>
