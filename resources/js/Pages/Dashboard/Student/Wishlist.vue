<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Heart, BookOpen, User, Tag, ArrowRight, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
  wishlistItems: Array
});

const formatPrice = (val) => {
  return parseFloat(val).toLocaleString('id-ID');
};

const removeFromWishlist = (courseId) => {
  Swal.fire({
    title: 'Hapus dari Wishlist?',
    text: 'Apakah Anda yakin ingin menghapus kelas ini dari daftar keinginan?',
    icon: 'question',
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
      const toggleForm = useForm({
        course_id: courseId
      });
      toggleForm.post(route('wishlist.toggle'), {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            title: 'Berhasil',
            text: 'Kelas dihapus dari wishlist.',
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
</script>

<template>
  <Head title="Wishlist Saya" />

  <GuestLayout>
    <DashboardWrapper>
      <div class="mb-8">
        <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight flex items-center gap-3">
          <div class="p-3 bg-rose-50 rounded-2xl text-rose-500">
            <Heart :size="28" class="fill-rose-500" stroke-width="2.5" />
          </div>
          Wishlist Saya
        </h2>
        <p class="text-slate-500 font-medium text-sm mt-2 pl-1">
          Daftar kelas yang ingin Anda ikuti di masa mendatang. Klik tombol gabung untuk mendaftar.
        </p>
      </div>

      <!-- Grid Wishlist -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="item in wishlistItems" 
          :key="item.id"
          class="bg-white rounded-[2rem] overflow-hidden border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] flex flex-col justify-between hover:scale-[1.02] hover:shadow-[0_15px_30px_rgba(0,0,0,0.05)] transition-all duration-300"
        >
          <!-- Thumbnail -->
          <div class="relative h-44 bg-slate-100 overflow-hidden shrink-0">
            <img 
              :src="item.course.cover_image || '/images/course-placeholder.png'" 
              :alt="item.course.title"
              class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
            />
            <button 
              @click="removeFromWishlist(item.course.id)"
              class="absolute top-4 right-4 p-2.5 bg-white/90 backdrop-blur-sm rounded-full text-rose-500 hover:text-white hover:bg-rose-500 shadow-sm border border-slate-100 transition-colors cursor-pointer"
              title="Hapus dari Wishlist"
            >
              <Trash2 :size="16" />
            </button>
          </div>

          <!-- Content Body -->
          <div class="p-6 flex-1 flex flex-col justify-between">
            <div>
              <div class="flex items-center gap-2 mb-2.5">
                <span class="text-[10px] font-extrabold px-2.5 py-1 bg-[#264790]/5 text-[#264790] rounded-full uppercase tracking-wider">
                  {{ item.course.level || 'Umum' }}
                </span>
                <span v-if="item.course.category" class="text-[10px] font-extrabold px-2.5 py-1 bg-sky-50 text-[#44A6D9] rounded-full uppercase tracking-wider flex items-center gap-1">
                  <Tag :size="8" /> {{ item.course.category.name }}
                </span>
              </div>

              <Link 
                :href="route('courses.show', item.course.slug)"
                class="font-black text-sm text-[#1A2B49] hover:text-[#264790] leading-snug line-clamp-2"
              >
                {{ item.course.title }}
              </Link>

              <div class="flex items-center gap-2 mt-4 text-xs font-semibold text-slate-400">
                <User :size="14" class="text-slate-300" />
                <span>{{ item.course.instructor?.name || 'Instruktur Drastha' }}</span>
              </div>
            </div>

            <!-- Price & Button -->
            <div class="border-t border-slate-50 pt-5 mt-5 flex items-center justify-between gap-4">
              <div class="flex flex-col">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest leading-none">Harga</span>
                <span class="text-sm font-black text-[#264790] mt-1">
                  {{ item.course.price > 0 ? `Rp ${formatPrice(item.course.price)}` : 'Gratis' }}
                </span>
              </div>

              <Link 
                :href="route('courses.show', item.course.slug)"
                class="bg-[#264790] hover:bg-[#1C356A] text-white text-xs font-extrabold px-5 py-3 rounded-2xl flex items-center gap-1.5 transition-all duration-300 shadow-md shadow-indigo-900/5 hover:-translate-y-0.5"
              >
                Detail Kelas <ArrowRight :size="12" />
              </Link>
            </div>
          </div>
        </div>

        <div v-if="wishlistItems.length === 0" class="col-span-full py-20 text-center text-slate-400 bg-white rounded-[2rem] border border-dashed border-slate-200 font-bold flex flex-col items-center justify-center gap-3">
          <Heart :size="32" class="text-slate-300" />
          Belum ada kelas di wishlist Anda.
        </div>
      </div>
    </DashboardWrapper>
  </GuestLayout>
</template>
