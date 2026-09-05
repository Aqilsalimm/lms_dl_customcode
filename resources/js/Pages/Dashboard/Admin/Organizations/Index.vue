<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
  organizations: Object,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const isModalOpen = ref(false);

const form = useForm({
  name: '',
  code: '',
  description: '',
});

const submit = () => {
  form.post(route('dashboard.organizations.store'), {
    preserveScroll: true,
    onSuccess: () => {
      isModalOpen.value = false;
      form.reset();
    }
  });
};

const handleSearch = () => {
  const urlParams = new URLSearchParams();
  if (search.value) urlParams.set('search', search.value);
  window.location.href = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
};
</script>

<template>
  <Head title="Manajemen Organisasi" />
  
  <DashboardWrapper>
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black text-slate-900 mb-2">Manajemen Organisasi</h1>
        <p class="text-slate-500 font-medium">Kelola data organisasi dan institusi yang bermitra dengan sistem.</p>
      </div>
      <button @click="isModalOpen = true" class="px-5 py-2.5 bg-[#264790] text-white text-sm font-bold rounded-xl shadow hover:bg-[#1a3366] transition-colors">
        + Tambah Organisasi
      </button>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 mb-6 flex items-center justify-between">
      <div class="relative w-full max-w-md">
        <input 
          v-model="search" 
          @keyup.enter="handleSearch"
          type="text" 
          placeholder="Cari organisasi atau kode..."
          class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#264790] focus:ring-1 focus:ring-[#264790]"
        />
        <svg class="w-5 h-5 absolute left-4 top-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
          <thead class="bg-slate-50 border-b border-slate-100 text-slate-500">
            <tr>
              <th class="px-6 py-4 font-bold">Nama Organisasi</th>
              <th class="px-6 py-4 font-bold">Kode</th>
              <th class="px-6 py-4 font-bold">Tanggal Dibuat</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="org in organizations.data" :key="org.id" class="hover:bg-slate-50/50">
              <td class="px-6 py-4 font-bold text-slate-800">{{ org.name }}</td>
              <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 rounded text-xs font-mono text-slate-600">{{ org.code }}</span></td>
              <td class="px-6 py-4 text-slate-500">{{ new Date(org.created_at).toLocaleDateString() }}</td>
            </tr>
            <tr v-if="!organizations.data.length">
              <td colspan="3" class="px-6 py-8 text-center text-slate-500 font-medium">Tidak ada data organisasi.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination (Cursor) -->
      <div v-if="organizations.next_page_url || organizations.prev_page_url" class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end">
        <div class="flex space-x-2">
          <Link
            v-if="organizations.prev_page_url"
            :href="organizations.prev_page_url"
            class="px-4 py-2 text-sm font-bold rounded-lg transition-colors text-slate-500 hover:bg-slate-200 bg-white border border-slate-200"
          >
            &laquo; Sebelumnya
          </Link>
          <Link
            v-if="organizations.next_page_url"
            :href="organizations.next_page_url"
            class="px-4 py-2 text-sm font-bold rounded-lg transition-colors bg-[#264790] hover:bg-[#1a3366] text-white shadow-sm"
          >
            Selanjutnya &raquo;
          </Link>
        </div>
      </div>
    </div>
  </DashboardWrapper>

  <!-- Modal Tambah Organisasi -->
  <Modal :show="isModalOpen" @close="isModalOpen = false">
    <div class="p-6">
      <h2 class="text-xl font-bold text-slate-900 mb-6">Tambah Organisasi Baru</h2>
      
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <InputLabel for="name" value="Nama Organisasi" />
          <TextInput
            id="name"
            v-model="form.name"
            type="text"
            class="mt-1 block w-full"
            required
            autofocus
          />
          <InputError :message="form.errors.name" class="mt-2" />
        </div>

        <div>
          <InputLabel for="code" value="Kode Organisasi" />
          <TextInput
            id="code"
            v-model="form.code"
            type="text"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.code" class="mt-2" />
        </div>

        <div>
          <InputLabel for="description" value="Deskripsi (Opsional)" />
          <textarea
            id="description"
            v-model="form.description"
            class="mt-1 block w-full border-slate-300 focus:border-[#264790] focus:ring-[#264790] rounded-xl shadow-sm"
            rows="3"
          ></textarea>
          <InputError :message="form.errors.description" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <SecondaryButton @click="isModalOpen = false">Batal</SecondaryButton>
          <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
            Simpan
          </PrimaryButton>
        </div>
      </form>
    </div>
  </Modal>
</template>
