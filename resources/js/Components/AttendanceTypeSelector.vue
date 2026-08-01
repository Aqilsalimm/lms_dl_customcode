<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { MapPin, Video, AlertCircle, CheckCircle2, Clock } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
  liveClass: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['updated']);

const form = useForm({
  attendance_type: props.liveClass?.attendance_preference || 'online'
});

// Check if within 24h cutoff
const isCutoffReached = computed(() => {
  if (!props.liveClass?.start_time) return false;
  const startTime = new Date(props.liveClass.start_time).getTime();
  const now = new Date().getTime();
  const diffHours = (startTime - now) / (1000 * 60 * 60);
  return diffHours < 24;
});

const isFullOnsite = computed(() => {
  if (!props.liveClass?.offline_capacity) return false;
  const currentCount = props.liveClass.onsite_count || 0;
  const limit = props.liveClass.offline_capacity;
  const isAlreadyOnsite = props.liveClass.attendance_preference === 'onsite';
  return !isAlreadyOnsite && currentCount >= limit;
});

const selectAttendance = (type) => {
  if (isCutoffReached.value || (type === 'onsite' && isFullOnsite.value)) return;
  if (form.attendance_type === type && props.liveClass?.attendance_preference === type) return;

  const typeLabel = type === 'onsite' ? 'Onsite (Tatap Muka)' : 'Online (Streaming)';

  Swal.fire({
    title: 'Konfirmasi Kehadiran',
    text: `Apakah Anda yakin ingin memilih/mengubah pilihan kehadiran menjadi ${typeLabel}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Ubah Kehadiran',
    cancelButtonText: 'Batal',
    customClass: {
      popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-xl',
      title: 'text-xl font-extrabold text-[#1A2B49]',
      confirmButton: 'bg-[#264790] hover:bg-[#1A2B49] text-white font-bold px-6 py-2.5 rounded-full text-xs cursor-pointer mr-2',
      cancelButton: 'bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-6 py-2.5 rounded-full text-xs cursor-pointer'
    },
    buttonsStyling: false
  }).then((result) => {
    if (result.isConfirmed) {
      form.attendance_type = type;
      form.post(route('live-classes.select-attendance', props.liveClass.id), {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({
            title: 'Berhasil!',
            text: `Tipe kehadiran Anda berhasil diset ke ${typeLabel}.`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            customClass: {
              popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
              title: 'text-xl font-extrabold text-[#1A2B49]'
            }
          });
          emit('updated');
        },
        onError: (err) => {
          const msg = Object.values(err)[0] || 'Gagal mengubah tipe kehadiran.';
          Swal.fire({
            title: 'Gagal',
            text: msg,
            icon: 'error',
            customClass: {
              popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
              title: 'text-xl font-extrabold text-[#1A2B49]',
              confirmButton: 'bg-rose-600 text-white font-bold px-6 py-2.5 rounded-full text-xs'
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
  <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm font-sans space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
      <div>
        <h4 class="text-sm font-extrabold text-[#1A2B49] uppercase tracking-wider">Tipe Kehadiran Peserta</h4>
        <p class="text-xs text-slate-500 font-medium mt-0.5">Pilih atau ubah moda kehadiran Anda untuk sesi ini.</p>
      </div>

      <!-- Current Badge status -->
      <div 
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-extrabold border shrink-0"
        :class="liveClass.attendance_preference === 'onsite' 
          ? 'bg-amber-50 text-amber-700 border-amber-200' 
          : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
      >
        <CheckCircle2 :size="14" />
        Status: {{ liveClass.attendance_preference === 'onsite' ? 'Hadir Onsite (Gedung)' : 'Hadir Online' }}
      </div>
    </div>

    <!-- Cutoff Warning Notice if H-1 reached -->
    <div v-if="isCutoffReached" class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold flex items-start gap-2.5">
      <Clock :size="16" class="text-amber-600 shrink-0 mt-0.5" />
      <span>⚠️ Perubahan tipe kehadiran dikunci karena sesi dimulai dalam kurun waktu kurang dari H-1.</span>
    </div>

    <!-- Selector Buttons -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      
      <!-- Option 1: Onsite -->
      <button 
        type="button"
        @click="selectAttendance('onsite')"
        :disabled="isCutoffReached || isFullOnsite || form.processing"
        class="p-4 rounded-2xl border-2 text-left transition-all duration-200 relative cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
        :class="liveClass.attendance_preference === 'onsite'
          ? 'border-[#264790] bg-[#264790]/5 text-[#1A2B49] shadow-sm'
          : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white'"
      >
        <div class="flex items-start justify-between mb-2">
          <div class="p-2 rounded-xl" :class="liveClass.attendance_preference === 'onsite' ? 'bg-[#264790] text-white' : 'bg-slate-100 text-slate-500'">
            <MapPin :size="20" />
          </div>
          <span v-if="isFullOnsite" class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 font-extrabold text-[10px]">
            Kursi Fisik Penuh
          </span>
        </div>
        <p class="font-extrabold text-sm text-[#1A2B49]">Onsite (Tatap Muka)</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Hadir di gedung/venue fisik. Tiket presensi QR Code akan diberikan.</p>
        <div v-if="liveClass.offline_capacity" class="mt-3 text-[11px] font-bold text-slate-500 flex items-center gap-1">
          <span>Kapasitas: {{ liveClass.onsite_count || 0 }} / {{ liveClass.offline_capacity }} Kursi</span>
        </div>
      </button>

      <!-- Option 2: Online -->
      <button 
        type="button"
        @click="selectAttendance('online')"
        :disabled="isCutoffReached || form.processing"
        class="p-4 rounded-2xl border-2 text-left transition-all duration-200 relative cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
        :class="liveClass.attendance_preference !== 'onsite'
          ? 'border-[#264790] bg-[#264790]/5 text-[#1A2B49] shadow-sm'
          : 'border-slate-200 hover:border-slate-300 text-slate-700 bg-white'"
      >
        <div class="flex items-start justify-between mb-2">
          <div class="p-2 rounded-xl" :class="liveClass.attendance_preference !== 'onsite' ? 'bg-[#264790] text-white' : 'bg-slate-100 text-slate-500'">
            <Video :size="20" />
          </div>
          <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 font-extrabold text-[10px]">
            Kapasitas Tak Terbatas
          </span>
        </div>
        <p class="font-extrabold text-sm text-[#1A2B49]">Online (Streaming)</p>
        <p class="text-xs text-slate-500 mt-1 font-medium">Mengikuti secara daring via Zoom / Google Meet dari mana saja.</p>
      </button>

    </div>
  </div>
</template>
