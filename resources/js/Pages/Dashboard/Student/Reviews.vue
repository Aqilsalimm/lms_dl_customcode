<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Star, Trash2, MessageSquare, Plus, Award } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
  reviews: Array,
  coursesToReview: Array
});

const activeTab = ref('history');
const hoverRating = ref(0);

const form = useForm({
  course_slug: '',
  rating: 5,
  comment: ''
});

const submitReview = () => {
  if (!form.course_slug) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Silakan pilih kelas yang ingin Anda ulas.',
      icon: 'warning',
      customClass: {
        popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
        title: 'text-xl font-extrabold text-[#1A2B49]',
        confirmButton: 'bg-[#44A6D9] text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
      },
      buttonsStyling: false
    });
    return;
  }

  form.post(route('reviews.store', form.course_slug), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        title: 'Berhasil',
        text: 'Ulasan Anda berhasil disimpan!',
        icon: 'success',
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
          title: 'text-xl font-extrabold text-[#1A2B49]',
          confirmButton: 'bg-[#264790] text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
        },
        buttonsStyling: false
      });
      form.reset();
      activeTab.value = 'history';
    },
    onError: () => {
      Swal.fire({
        title: 'Gagal',
        text: 'Gagal menyimpan ulasan. Pastikan Anda telah menulis komentar.',
        icon: 'error',
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
          title: 'text-xl font-extrabold text-[#1A2B49]',
          confirmButton: 'bg-red-500 text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
        },
        buttonsStyling: false
      });
    }
  });
};

const deleteReview = (reviewId) => {
  Swal.fire({
    title: 'Hapus Ulasan?',
    text: 'Apakah Anda yakin ingin menghapus ulasan ini secara permanen?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    customClass: {
      popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
      title: 'text-xl font-extrabold text-[#1A2B49]',
      confirmButton: 'bg-red-500 hover:bg-red-600 text-white font-bold px-6 py-3 rounded-full text-xs cursor-pointer mr-3',
      cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold px-6 py-3 rounded-full text-xs cursor-pointer'
    },
    buttonsStyling: false
  }).then((result) => {
    if (result.isConfirmed) {
      const deleteForm = useForm({});
      deleteForm.delete(route('reviews.destroy', reviewId), {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            title: 'Terhapus',
            text: 'Ulasan Anda berhasil dihapus.',
            icon: 'success',
            customClass: {
              popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
              title: 'text-xl font-extrabold text-[#1A2B49]',
              confirmButton: 'bg-[#264790] text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
            },
            buttonsStyling: false
          });
        }
      });
    }
  });
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};
</script>

<template>
  <Head title="Ulasan Saya" />

  <GuestLayout>
    <DashboardWrapper>
      <div class="mb-8">
        <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight flex items-center gap-3">
          <div class="p-3 bg-sky-50 rounded-2xl text-[#44A6D9]">
            <MessageSquare :size="28" stroke-width="2.5" />
          </div>
          Ulasan Kelas
        </h2>
        <p class="text-slate-500 font-medium text-sm mt-2 pl-1">
          Tulis ulasan untuk kelas yang telah Anda ikuti atau kelola ulasan yang sudah Anda bagikan sebelumnya.
        </p>
      </div>

      <!-- Tab Switcher -->
      <div class="flex gap-4 border-b border-slate-100 pb-4 mb-6">
        <button 
          @click="activeTab = 'history'"
          :class="[activeTab === 'history' ? 'border-[#264790] text-[#264790] font-extrabold' : 'border-transparent text-slate-400 font-bold']"
          class="border-b-2 pb-2 text-sm transition-all px-2"
        >
          Riwayat Ulasan ({{ reviews.length }})
        </button>
        <button 
          @click="activeTab = 'write'"
          :class="[activeTab === 'write' ? 'border-[#264790] text-[#264790] font-extrabold' : 'border-transparent text-slate-400 font-bold']"
          class="border-b-2 pb-2 text-sm transition-all px-2 flex items-center gap-1.5"
        >
          <Plus :size="16" /> Tulis Ulasan Baru
        </button>
      </div>

      <!-- Tab 1: Riwayat Ulasan -->
      <div v-if="activeTab === 'history'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div 
          v-for="review in reviews" 
          :key="review.id"
          class="bg-white rounded-[2rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col justify-between hover:scale-[1.01] transition-transform duration-300"
        >
          <div>
            <div class="flex items-start justify-between gap-4 mb-3">
              <Link 
                :href="route('courses.show', review.course.slug)" 
                class="font-black text-sm text-[#1A2B49] hover:text-[#264790] line-clamp-2 leading-snug"
              >
                {{ review.course.title }}
              </Link>
              
              <button 
                @click="deleteReview(review.id)"
                class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors shrink-0"
                title="Hapus Ulasan"
              >
                <Trash2 :size="16" />
              </button>
            </div>

            <!-- Bintang -->
            <div class="flex items-center gap-1 mb-3">
              <Star 
                v-for="i in 5" 
                :key="i"
                :size="16" 
                :class="i <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-200'"
              />
            </div>

            <p class="text-xs text-slate-600 font-medium leading-relaxed italic bg-slate-50 p-4 rounded-2xl border border-slate-100/50">
              "{{ review.comment || 'Tidak ada komentar tertulis.' }}"
            </p>
          </div>

          <div class="text-[10px] text-slate-400 font-bold mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
            <span>Drastha Student</span>
            <span>{{ formatDate(review.created_at) }}</span>
          </div>
        </div>

        <div v-if="reviews.length === 0" class="col-span-2 py-16 text-center text-slate-400 bg-white rounded-[2rem] border border-dashed border-slate-200 font-bold">
          Belum ada ulasan yang pernah Anda buat.
        </div>
      </div>

      <!-- Tab 2: Tulis Ulasan Baru -->
      <div v-if="activeTab === 'write'" class="max-w-xl mx-auto bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100">
        <h3 class="text-lg font-bold text-[#1A2B49] mb-6 flex items-center gap-2">
          <Award :size="18" class="text-sky-500" /> Tulis Ulasan Anda
        </h3>

        <form @submit.prevent="submitReview" class="flex flex-col gap-5">
          <!-- Pilih Kelas -->
          <div>
            <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2">Pilih Kelas</label>
            <select 
              v-model="form.course_slug"
              class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold bg-white"
              required
            >
              <option value="" disabled>-- Pilih Kelas yang Ingin Diulas --</option>
              <option 
                v-for="course in coursesToReview" 
                :key="course.id" 
                :value="course.slug"
              >
                {{ course.title }}
              </option>
            </select>
          </div>

          <!-- Bintang Interaktif -->
          <div>
            <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-3">Rating Bintang</label>
            <div class="flex items-center gap-2">
              <button 
                v-for="i in 5" 
                :key="i"
                type="button"
                @click="form.rating = i"
                @mouseover="hoverRating = i"
                @mouseleave="hoverRating = 0"
                class="transition-transform hover:scale-110"
              >
                <Star 
                  :size="32" 
                  :class="[
                    i <= (hoverRating || form.rating) 
                      ? 'fill-amber-400 text-amber-400' 
                      : 'text-slate-200'
                  ]"
                />
              </button>
              <span class="text-sm font-extrabold text-slate-400 ml-2">
                {{ form.rating }} dari 5 Bintang
              </span>
            </div>
          </div>

          <!-- Komentar -->
          <div>
            <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2">Komentar / Masukan</label>
            <textarea 
              v-model="form.comment"
              rows="4"
              placeholder="Tulis ulasan jujur Anda tentang materi, metode pengajaran, atau pengalaman Anda di kelas ini..."
              class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold"
              required
            ></textarea>
          </div>

          <!-- Tombol Kirim -->
          <button 
            type="submit"
            :disabled="form.processing"
            class="w-full bg-[#264790] hover:bg-[#1C356A] text-white font-extrabold py-4 px-6 rounded-2xl text-sm transition-all duration-300 shadow-md shadow-indigo-900/10 flex items-center justify-center gap-2 hover:-translate-y-0.5 cursor-pointer disabled:opacity-50"
          >
            Kirim Ulasan
          </button>
        </form>
      </div>

    </DashboardWrapper>
  </GuestLayout>
</template>
