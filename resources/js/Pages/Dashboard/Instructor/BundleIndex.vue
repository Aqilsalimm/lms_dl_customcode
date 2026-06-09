<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  FolderHeart, Plus, Trash2, Settings, 
  X, Check, AlertCircle, BookOpen
} from 'lucide-vue-next';

const props = defineProps({
  bundles: Array,
  courses: Array
});

const isModalOpen = ref(false);
const newBundleTitle = ref('');
const newBundlePrice = ref(0);
const newBundleStatus = ref('draft');
const isSubmitting = ref(false);

const openCreateModal = () => {
  isModalOpen.value = true;
};

const closeCreateModal = () => {
  isModalOpen.value = false;
  newBundleTitle.value = '';
  newBundlePrice.value = 0;
  newBundleStatus.value = 'draft';
};

const createBundle = () => {
  if (!newBundleTitle.value) {
    alert('Judul bundle wajib diisi.');
    return;
  }

  isSubmitting.value = true;

  router.post(route('bundle-builder.store'), {
    title: newBundleTitle.value,
    price: newBundlePrice.value,
    status: newBundleStatus.value,
  }, {
    onSuccess: () => {
      closeCreateModal();
      isSubmitting.value = false;
    },
    onError: () => {
      isSubmitting.value = false;
    }
  });
};

const deleteBundle = (bundleId) => {
  if (confirm('Apakah Anda yakin ingin menghapus bundle ini secara permanen?')) {
    router.delete(route('bundle-builder.destroy', bundleId));
  }
};
</script>

<template>
  <Head title="Kelola Bundle | Bundle Builder" />

  <DashboardWrapper>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-extrabold text-[#1A2B49] leading-tight">
          Kelola Bundle (Bundle Builder)
        </h2>
        <p class="text-slate-500 text-sm mt-1">
          Kombinasikan beberapa kelas pilihan untuk dijual dalam satu paket dengan harga khusus.
        </p>
      </div>
      <button 
        @click="openCreateModal"
        class="bg-[#264790] hover:bg-[#44A6D9] text-white px-5 py-2.5 rounded-full font-bold text-sm shadow-md transition-all duration-300 flex items-center gap-2"
      >
        <Plus :size="16" /> Tambah Bundle Baru
      </button>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="pb-4">Bundle</th>
              <th class="pb-4">Jumlah Kelas</th>
              <th class="pb-4">Harga</th>
              <th class="pb-4">Status</th>
              <th class="pb-4 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 text-sm">
            <tr v-for="bundle in bundles" :key="bundle.id" class="hover:bg-slate-50/50 transition-colors">
              <td class="py-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-[#44A6D9] text-white font-bold">
                  <FolderHeart :size="20" />
                </div>
                <div>
                  <div class="font-extrabold text-[#1A2B49] text-base leading-tight mb-1">
                    {{ bundle.title }}
                  </div>
                  <div class="text-slate-400 text-xs font-medium">
                    Dibuat pada: {{ new Date(bundle.created_at).toLocaleDateString('id-ID') }}
                  </div>
                </div>
              </td>
              <td class="py-5 font-bold text-[#1A2B49]">
                {{ bundle.courses ? bundle.courses.length : 0 }} Kelas
              </td>
              <td class="py-5 font-bold text-[#1A2B49]">
                Rp {{ parseFloat(bundle.price).toLocaleString('id-ID') }}
              </td>
              <td class="py-5">
                <span 
                  :class="{
                    'bg-emerald-50 text-emerald-700 border-emerald-100': bundle.status === 'published',
                    'bg-slate-50 text-slate-600 border-slate-100': bundle.status === 'draft',
                  }"
                  class="px-2.5 py-1 text-xs font-bold rounded-full border uppercase tracking-wider"
                >
                  {{ bundle.status }}
                </span>
              </td>
              <td class="py-5 text-right">
                <div class="flex items-center justify-end gap-3">
                  <Link 
                    :href="route('bundle-builder.edit', bundle.id)"
                    class="bg-indigo-50 hover:bg-indigo-600 text-indigo-600 hover:text-white px-3.5 py-2 rounded-2xl font-bold text-xs shadow-sm transition-all duration-200 flex items-center gap-1.5"
                  >
                    <Settings :size="14" /> Builder
                  </Link>
                  <button 
                    @click="deleteBundle(bundle.id)"
                    class="bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white p-2 rounded-2xl transition-all duration-200"
                  >
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="bundles.length === 0">
              <td colspan="5" class="text-center py-12 text-slate-400 font-bold">
                <FolderHeart :size="48" class="mx-auto text-slate-300 mb-3" />
                Belum ada bundle yang dibuat. Silakan tambah bundle baru untuk mulai mengelompokkan kelas!
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Bundle Modal -->
    <div 
      v-if="isModalOpen" 
      class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl border border-slate-100 relative animate-in fade-in zoom-in duration-200">
        
        <button 
          @click="closeCreateModal"
          class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors"
        >
          <X :size="20" />
        </button>

        <h3 class="text-xl font-extrabold text-[#1A2B49] mb-6">Tambah Bundle Baru</h3>

        <div class="flex flex-col gap-5">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Bundle</label>
            <input 
              v-model="newBundleTitle" 
              type="text" 
              placeholder="Contoh: Paket Pemrograman Web Developer Complete"
              class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Harga (Rupiah)</label>
              <input 
                v-model.number="newBundlePrice" 
                type="number" 
                placeholder="499000"
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold transition-colors"
              />
            </div>
            
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Status Awal</label>
              <select 
                v-model="newBundleStatus"
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold cursor-pointer transition-colors"
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
              </select>
            </div>
          </div>

          <div class="h-px bg-slate-100 my-2"></div>

          <div class="flex gap-4">
            <button 
              @click="closeCreateModal"
              class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-2xl font-bold text-sm transition-colors"
            >
              Batal
            </button>
            <button 
              @click="createBundle"
              :disabled="isSubmitting"
              class="flex-1 bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-1.5"
            >
              <Check :size="16" /> Buat Bundle
            </button>
          </div>
        </div>

      </div>
    </div>
  </DashboardWrapper>
</template>
