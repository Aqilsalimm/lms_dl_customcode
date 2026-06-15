<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  Tag, Plus, Trash2, Edit, Save, X, Info, 
  Calendar, Check, BarChart3, Settings, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
  coupons: Array,
  courses: Array
});

// Modals
const showCouponModal = ref(false);
const editingCoupon = ref(null);

const couponForm = useForm({
  code: '',
  type: 'percentage', // 'percentage' or 'fixed'
  value: '',
  course_id: '',
  expires_at: '',
  max_uses: '',
  is_active: true
});

const openAddCoupon = () => {
  editingCoupon.value = null;
  couponForm.reset();
  couponForm.clearErrors();
  showCouponModal.value = true;
};

const openEditCoupon = (coupon) => {
  editingCoupon.value = coupon;
  couponForm.code = coupon.code;
  couponForm.type = coupon.type;
  couponForm.value = coupon.value;
  couponForm.course_id = coupon.course_id || '';
  // Format datetime-local input value (YYYY-MM-DDTHH:MM)
  couponForm.expires_at = coupon.expires_at ? coupon.expires_at.substring(0, 16) : '';
  couponForm.max_uses = coupon.max_uses || '';
  couponForm.is_active = coupon.is_active;
  couponForm.clearErrors();
  showCouponModal.value = true;
};

const saveCoupon = () => {
  if (editingCoupon.value) {
    couponForm.put(route('dashboard.ecommerce.coupons.update', editingCoupon.value.id), {
      onSuccess: () => {
        showCouponModal.value = false;
        couponForm.reset();
      }
    });
  } else {
    couponForm.post(route('dashboard.ecommerce.coupons.store'), {
      onSuccess: () => {
        showCouponModal.value = false;
        couponForm.reset();
      }
    });
  }
};

const deleteCoupon = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus kupon ini?')) {
    couponForm.delete(route('dashboard.ecommerce.coupons.destroy', id));
  }
};

const formatPrice = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val);
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'Selamanya';
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <Head title="Coupon & Dynamic Discount Settings" />

  <DashboardWrapper>
    <div class="max-w-6xl mx-auto flex flex-col gap-8">
      
      <!-- HEADER -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <div class="flex items-center gap-2 text-[#264790] text-xs font-bold uppercase tracking-wider">
            <Tag :size="14" />
            Promo Engine
          </div>
          <h1 class="text-2xl font-extrabold text-[#1A2B49] tracking-tight mt-1">Coupon & Dynamic Discount</h1>
          <p class="text-sm text-slate-500 mt-1">Kelola pembuatan kode kupon promosi, limitasi kelas target, kuota transaksi, serta masa aktif diskon.</p>
        </div>
        <button 
          @click="openAddCoupon" 
          class="bg-[#264790] text-white hover:bg-[#1f3a76] px-5 py-2.5 rounded-full font-bold text-xs shadow-md hover:shadow-lg transition-all duration-300 flex items-center gap-2 cursor-pointer shrink-0"
        >
          <Plus :size="14" /> Tambah Kupon Baru
        </button>
      </div>

      <!-- COUPONS LISTING -->
      <div v-if="coupons.length === 0" class="py-20 text-center bg-white border border-slate-150 rounded-[2.5rem] shadow-sm flex flex-col items-center justify-center p-6">
        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mb-4 shadow-inner">
          <Tag :size="24" />
        </div>
        <h3 class="text-sm font-bold text-slate-800">Belum ada Kupon Aktif</h3>
        <p class="text-xs text-slate-450 mt-1 max-w-xs leading-relaxed">Buat kupon promo diskon nominal tetap atau persentase untuk merangsang penjualan kurikulum Anda.</p>
        <button @click="openAddCoupon" class="mt-4 bg-[#264790]/10 hover:bg-[#264790]/20 text-[#264790] px-5 py-2.5 rounded-full font-bold text-xs transition-colors cursor-pointer">
          Buat Kupon Pertama
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="coupon in coupons" 
          :key="coupon.id" 
          class="bg-white rounded-[2rem] border border-slate-150 shadow-[0_8px_30px_rgb(0,0,0,0.015)] hover:shadow-[0_12px_40px_rgb(0,0,0,0.03)] p-6 transition-all duration-300 flex flex-col justify-between gap-6 group relative overflow-hidden"
        >
          <!-- Corner ribbon for Status -->
          <div 
            :class="[coupon.is_active ? 'bg-emerald-500' : 'bg-slate-400']" 
            class="absolute top-0 right-0 w-2.5 h-full"
          ></div>

          <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-1.5">
              <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#264790] bg-[#EBF3FF] px-2.5 py-1 rounded-full w-max border border-blue-100">
                {{ coupon.type === 'percentage' ? 'Persentase' : 'Nominal Tetap' }}
              </span>
              <h3 class="text-lg font-black text-[#1A2B49] font-mono tracking-wide mt-1.5">{{ coupon.code }}</h3>
            </div>

            <!-- Action buttons visible on hover -->
            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <button 
                @click="openEditCoupon(coupon)" 
                class="p-2 bg-slate-50 hover:bg-blue-50 text-slate-500 hover:text-[#264790] rounded-xl transition-all cursor-pointer"
                title="Edit Kupon"
              >
                <Edit :size="14" />
              </button>
              <button 
                @click="deleteCoupon(coupon.id)" 
                class="p-2 bg-slate-50 hover:bg-red-50 text-slate-500 hover:text-red-500 rounded-xl transition-all cursor-pointer"
                title="Hapus Kupon"
              >
                <Trash2 :size="14" />
              </button>
            </div>
          </div>

          <!-- Coupon info properties -->
          <div class="flex flex-col gap-3 border-t border-b border-slate-100 py-4 font-semibold text-xs text-slate-555">
            <div class="flex justify-between">
              <span>Nilai Diskon:</span>
              <span class="font-extrabold text-[#1A2B49]">
                {{ coupon.type === 'percentage' ? `${coupon.value}%` : formatPrice(coupon.value) }}
              </span>
            </div>
            
            <div class="flex justify-between">
              <span>Target Kelas:</span>
              <span class="font-extrabold text-slate-700 max-w-[150px] truncate text-right">
                {{ coupon.course ? coupon.course.title : 'Semua Kelas' }}
              </span>
            </div>

            <div class="flex justify-between">
              <span>Kadaluwarsa:</span>
              <span class="font-extrabold text-slate-700">{{ formatDate(coupon.expires_at) }}</span>
            </div>

            <div class="flex justify-between">
              <span>Kuota Terpakai:</span>
              <span class="font-extrabold text-slate-700">
                {{ coupon.uses }} / {{ coupon.max_uses || '∞' }}
              </span>
            </div>
          </div>

          <!-- Usage progress bar -->
          <div v-if="coupon.max_uses" class="flex flex-col gap-1.5">
            <div class="flex justify-between text-[10px] font-bold text-slate-400 uppercase tracking-wider">
              <span>Progress Penggunaan</span>
              <span>{{ Math.round((coupon.uses / coupon.max_uses) * 100) }}%</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden flex">
              <div 
                :style="{ width: `${(coupon.uses / coupon.max_uses) * 100}%` }"
                class="bg-[#264790] h-full rounded-full"
              ></div>
            </div>
          </div>
          <div v-else class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
            <Check :size="12" class="text-emerald-500" /> Kuota tidak terbatas
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL ADD/EDIT COUPON -->
    <div v-if="showCouponModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
      <div class="bg-white rounded-[2rem] max-w-md w-full shadow-2xl relative border border-slate-100 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <h3 class="text-base font-extrabold text-[#1A2B49]">
            {{ editingCoupon ? 'Edit Kupon' : 'Buat Kupon Baru' }}
          </h3>
          <button @click="showCouponModal = false" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
            <X :size="18" />
          </button>
        </div>

        <form @submit.prevent="saveCoupon" class="p-6 flex flex-col gap-4 max-h-[80vh] overflow-y-auto">
          
          <div class="flex flex-col gap-1.5">
            <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Kode Kupon (Promo Code)</label>
            <input 
              v-model="couponForm.code" 
              type="text" 
              placeholder="Contoh: BATCHPYTHON20" 
              class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm font-mono uppercase"
              required
            />
            <span v-if="couponForm.errors.code" class="text-xs text-red-500 mt-1 font-bold">{{ couponForm.errors.code }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Tipe Potongan</label>
              <select 
                v-model="couponForm.type" 
                class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm bg-white cursor-pointer"
              >
                <option value="percentage">Persentase (%)</option>
                <option value="fixed">Nominal Tetap (Rp)</option>
              </select>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Nilai Potongan</label>
              <input 
                v-model="couponForm.value" 
                type="number" 
                placeholder="Contoh: 20 atau 50000" 
                class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                required
              />
              <span v-if="couponForm.errors.value" class="text-xs text-red-500 mt-1 font-bold">{{ couponForm.errors.value }}</span>
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Batasan Kelas (Course Target)</label>
            <select 
              v-model="couponForm.course_id" 
              class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm bg-white cursor-pointer"
            >
              <option value="">-- Semua Kelas (General Coupon) --</option>
              <option v-for="course in courses" :key="course.id" :value="course.id">
                {{ course.title }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-1.5">
              <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Masa Berlaku (Expiry)</label>
              <input 
                v-model="couponForm.expires_at" 
                type="datetime-local" 
                class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              />
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Kuota Maksimal (Max Uses)</label>
              <input 
                v-model="couponForm.max_uses" 
                type="number" 
                placeholder="Kosongkan jika tidak terbatas" 
                class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              />
            </div>
          </div>

          <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 p-4 rounded-2xl mt-2">
            <input 
              v-model="couponForm.is_active" 
              id="is_active_checkbox"
              type="checkbox" 
              class="rounded text-[#264790] focus:ring-[#264790] w-5 h-5 cursor-pointer"
            />
            <label for="is_active_checkbox" class="text-xs font-bold text-slate-700 cursor-pointer">
              Aktifkan kupon ini sekarang agar bisa digunakan siswa saat checkout
            </label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 mt-2">
            <button 
              type="button" 
              @click="showCouponModal = false" 
              class="px-5 py-2.5 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 font-bold text-xs transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="couponForm.processing"
              class="bg-[#264790] text-white hover:bg-[#1f3a76] px-6 py-2.5 rounded-full font-bold text-xs shadow-md transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <Save :size="14" /> Simpan Kupon
            </button>
          </div>
        </form>
      </div>
    </div>
  </DashboardWrapper>
</template>
