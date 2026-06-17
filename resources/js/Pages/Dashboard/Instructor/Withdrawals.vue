<template>
  <DashboardWrapper :auth="auth" title="Penarikan Dana">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Penarikan Dana (Withdrawals)</h2>
        <p class="text-sm text-slate-500 mt-1">Kelola metode pembayaran dan cairkan penghasilan Anda.</p>
      </div>

      <!-- Balance & Action Row -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Balance Card -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-6 text-white shadow-xl shadow-blue-900/20 flex flex-col justify-between">
          <div>
            <div class="text-blue-100 font-medium text-sm mb-1 flex items-center gap-2">
              <WalletIcon class="w-4 h-4" /> Saldo Tersedia
            </div>
            <div class="text-4xl font-extrabold tracking-tight">Rp {{ formatNumber(balance) }}</div>
          </div>
          <div class="mt-8 flex justify-end">
             <button 
                @click="openWithdrawModal"
                :disabled="!profile || balance <= 0"
                class="bg-white text-blue-600 hover:bg-blue-50 px-6 py-2.5 rounded-xl font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed text-sm shadow-sm"
              >
                Tarik Dana
              </button>
          </div>
        </div>

        <!-- Payment Profile Setup -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between">
           <div>
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                  <CreditCardIcon class="w-5 h-5 text-indigo-600" />
                  Akun Pencairan
                </h3>
                <span v-if="profile" class="bg-green-100 text-green-700 text-xs font-bold px-2.5 py-1 rounded-lg">Terhubung</span>
                <span v-else class="bg-amber-100 text-amber-700 text-xs font-bold px-2.5 py-1 rounded-lg">Belum Diatur</span>
              </div>
              
              <div v-if="profile" class="space-y-3">
                 <div class="flex flex-col">
                   <span class="text-xs text-slate-500 font-medium">Metode Pembayaran</span>
                   <span class="text-sm font-bold text-slate-800">{{ profile.method.name }} ({{ profile.method.type.toUpperCase() }})</span>
                 </div>
                 <div class="flex flex-col">
                   <span class="text-xs text-slate-500 font-medium">Nomor Rekening / E-Wallet</span>
                   <span class="text-sm font-bold text-slate-800">{{ profile.account_number }}</span>
                 </div>
                 <div class="flex flex-col">
                   <span class="text-xs text-slate-500 font-medium">Atas Nama</span>
                   <span class="text-sm font-bold text-slate-800">{{ profile.account_name }}</span>
                 </div>
              </div>
              <div v-else class="text-sm text-slate-500 bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">
                Silakan atur akun bank atau e-wallet Anda untuk menerima pembayaran.
              </div>
           </div>
           
           <div class="mt-4 flex justify-end">
              <button 
                @click="openProfileModal"
                class="text-indigo-600 font-bold text-sm hover:text-indigo-800 transition-colors"
              >
                {{ profile ? 'Ubah Akun' : 'Atur Akun Sekarang' }}
              </button>
           </div>
        </div>
      </div>

      <!-- Withdrawal History -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mt-8">
         <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
              <HistoryIcon class="w-5 h-5 text-slate-500" />
              Riwayat Penarikan
            </h3>
         </div>
         
         <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
               <thead>
                  <tr>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Tanggal</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Jumlah</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Status</th>
                     <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200 text-right">Aksi / Bukti</th>
                  </tr>
               </thead>
               <tbody class="divide-y divide-slate-100">
                  <tr v-for="item in withdrawals" :key="item.id" class="hover:bg-slate-50/80 transition-colors">
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-semibold text-slate-800">{{ formatDate(item.created_at) }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-slate-800">Rp {{ formatNumber(item.amount) }}</div>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-3 py-1 rounded-full text-xs font-bold" :class="statusClass(item.status)">
                          {{ item.status.charAt(0).toUpperCase() + item.status.slice(1) }}
                        </span>
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button v-if="item.receipt_path" @click="viewReceipt(item)" class="text-blue-600 hover:text-blue-800 text-sm font-bold flex items-center gap-1 justify-end w-full">
                          <EyeIcon class="w-4 h-4" /> Lihat Bukti
                        </button>
                        <span v-else-if="item.status === 'rejected'" class="text-red-500 text-xs font-medium cursor-help" :title="item.admin_note">Alasan: {{ item.admin_note }}</span>
                        <span v-else class="text-slate-400 text-xs">-</span>
                     </td>
                  </tr>
                  <tr v-if="withdrawals.length === 0">
                     <td colspan="4" class="px-6 py-12 text-center text-slate-500 text-sm">
                        Belum ada riwayat penarikan dana.
                     </td>
                  </tr>
               </tbody>
            </table>
         </div>
      </div>
    </div>

    <!-- Modals -->
    <!-- 1. Profile Setup Modal -->
    <Modal :show="showProfileModal" @close="closeProfileModal">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Pengaturan Akun Pencairan</h3>
        <form @submit.prevent="submitProfile" class="space-y-4">
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Tipe Pencairan</label>
            <select v-model="selectedType" required class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-indigo-500 text-sm font-semibold text-slate-700">
               <option value="" disabled>Pilih Tipe...</option>
               <option value="bank">Transfer Bank</option>
               <option value="ewallet">E-Wallet</option>
            </select>
          </div>
          <div v-if="selectedType">
            <label class="block text-sm font-bold text-slate-700 mb-1">{{ selectedType === 'bank' ? 'Pilih Bank' : 'Pilih E-Wallet' }}</label>
            <select v-model="profileForm.withdrawal_method_id" required class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-indigo-500 text-sm font-semibold text-slate-700">
               <option value="" disabled>Pilih...</option>
               <option v-for="method in filteredMethods" :key="method.id" :value="method.id">
                 {{ method.name }}
               </option>
            </select>
          </div>
          <div v-if="selectedType">
             <label class="block text-sm font-bold text-slate-700 mb-1">{{ selectedType === 'bank' ? 'Nomor Rekening' : 'Nomor Handphone' }}</label>
             <input type="text" v-model="profileForm.account_number" required class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-indigo-500 text-sm font-semibold text-slate-700" :placeholder="selectedType === 'bank' ? 'Contoh: 1234567890' : 'Contoh: 081234567890'">
          </div>
          <div v-if="selectedType">
             <label class="block text-sm font-bold text-slate-700 mb-1">Nama Pemilik Akun</label>
             <input type="text" v-model="profileForm.account_name" required class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-indigo-500 text-sm font-semibold text-slate-700" placeholder="Sesuai buku tabungan/akun">
          </div>
          
          <div class="mt-6 flex justify-end gap-3">
             <button type="button" @click="closeProfileModal" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
             <button type="submit" :disabled="profileForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl disabled:opacity-50 flex items-center gap-2">
               <span v-if="profileForm.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
               Simpan Akun
             </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- 2. Request Withdrawal Modal -->
    <Modal :show="showWithdrawModal" @close="closeWithdrawModal">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Tarik Dana Penghasilan</h3>
        
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-5 flex items-center justify-between">
           <span class="text-sm text-indigo-800 font-medium">Saldo Tersedia:</span>
           <span class="text-lg font-extrabold text-indigo-900">Rp {{ formatNumber(balance) }}</span>
        </div>

        <form @submit.prevent="submitWithdrawal" class="space-y-4">
          <div>
             <label class="block text-sm font-bold text-slate-700 mb-1">Jumlah Penarikan (Rp)</label>
             <input type="number" v-model="withdrawForm.amount" :max="balance" min="10000" required class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold text-slate-700" placeholder="Minimal Rp 10.000">
             <p class="text-xs text-slate-500 mt-1.5">Dana akan ditransfer ke: <strong>{{ profile?.method?.name }} - {{ profile?.account_number }}</strong></p>
          </div>
          
          <div class="mt-6 flex justify-end gap-3">
             <button type="button" @click="closeWithdrawModal" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Batal</button>
             <button type="submit" :disabled="withdrawForm.processing || withdrawForm.amount > balance" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl disabled:opacity-50 flex items-center gap-2">
               <span v-if="withdrawForm.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
               Ajukan Penarikan
             </button>
          </div>
        </form>
      </div>
    </Modal>
    
    <!-- 3. Receipt Viewer Modal -->
    <Modal :show="showReceiptModal" @close="showReceiptModal = false">
      <div class="p-6">
         <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Bukti Transfer</h3>
            <button @click="showReceiptModal = false" class="text-slate-400 hover:text-slate-600">
              <XIcon class="w-5 h-5" />
            </button>
         </div>
         <div v-if="selectedReceipt" class="flex justify-center">
            <img :src="`/storage/${selectedReceipt}`" alt="Bukti Transfer" class="max-w-full rounded-xl max-h-[60vh] object-contain border border-slate-200" />
         </div>
      </div>
    </Modal>

  </DashboardWrapper>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import Modal from '@/Components/Modal.vue';
import { 
  WalletIcon, 
  CreditCardIcon, 
  HistoryIcon, 
  EyeIcon,
  XIcon
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    auth: Object,
    balance: [Number, String],
    profile: Object,
    withdrawals: Array,
    methods: Array
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

const statusClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-amber-100 text-amber-700';
        case 'processing': return 'bg-blue-100 text-blue-700';
        case 'completed': return 'bg-green-100 text-green-700';
        case 'rejected': return 'bg-red-100 text-red-700';
        default: return 'bg-slate-100 text-slate-700';
    }
};

// Profile Setup
const showProfileModal = ref(false);
const selectedType = ref(props.profile?.method?.type || '');
const profileForm = useForm({
    withdrawal_method_id: props.profile?.withdrawal_method_id || '',
    account_number: props.profile?.account_number || '',
    account_name: props.profile?.account_name || ''
});

const filteredMethods = computed(() => {
    return props.methods.filter(m => m.type === selectedType.value);
});

watch(selectedType, (newVal, oldVal) => {
    if (oldVal !== '' && newVal !== oldVal) {
        profileForm.withdrawal_method_id = '';
    }
});

const openProfileModal = () => {
    selectedType.value = props.profile?.method?.type || '';
    profileForm.withdrawal_method_id = props.profile?.withdrawal_method_id || '';
    profileForm.account_number = props.profile?.account_number || '';
    profileForm.account_name = props.profile?.account_name || '';
    showProfileModal.value = true;
};
const closeProfileModal = () => {
    showProfileModal.value = false;
    profileForm.reset();
};

const submitProfile = () => {
    profileForm.post(route('dashboard.payment-profile.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeProfileModal();
            Toast.fire({ icon: 'success', title: "Akun pencairan berhasil disimpan." });
        },
        onError: (errors) => {
            Object.values(errors).forEach(err => Toast.fire({ icon: 'error', title: err }));
        }
    });
};

// Withdrawal Request
const showWithdrawModal = ref(false);
const withdrawForm = useForm({
    amount: ''
});

const openWithdrawModal = () => showWithdrawModal.value = true;
const closeWithdrawModal = () => {
    showWithdrawModal.value = false;
    withdrawForm.reset();
};

const submitWithdrawal = () => {
    withdrawForm.post(route('dashboard.withdrawals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeWithdrawModal();
            Toast.fire({ icon: 'success', title: "Penarikan berhasil diajukan! Menunggu persetujuan Admin." });
        },
        onError: (errors) => {
            if(errors.amount) Toast.fire({ icon: 'error', title: errors.amount });
            else Toast.fire({ icon: 'error', title: "Gagal mengajukan penarikan." });
        }
    });
};

// Receipt Viewer
const showReceiptModal = ref(false);
const selectedReceipt = ref(null);

const viewReceipt = (item) => {
    selectedReceipt.value = item.receipt_path;
    showReceiptModal.value = true;
};
</script>
