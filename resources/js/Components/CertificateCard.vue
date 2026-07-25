<script setup>
import { ref } from 'vue';
import { Award, Lock, CheckCircle2, Download, ExternalLink, RefreshCw, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  certificate: {
    type: Object,
    required: true
  },
  courseSlug: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['claimed']);

const isClaiming = ref(false);
const errorMsg = ref('');

const claimCertificate = () => {
  if (isClaiming.value || !props.certificate.unlocked) return;
  isClaiming.value = true;
  errorMsg.value = '';

  axios.post(`/courses/${props.courseSlug}/certificates/${props.certificate.id}/claim`)
    .then(res => {
      emit('claimed', res.data);
    })
    .catch(err => {
      errorMsg.value = err.response?.data?.message || 'Gagal membuka sertifikat.';
    })
    .finally(() => {
      isClaiming.value = false;
    });
};

const openCertificateDocument = () => {
  if (!props.certificate.certificate_code) return;
  window.open(`/certificates/${props.certificate.certificate_code}`, '_blank');
};
</script>

<template>
  <div 
    :class="[
      certificate.unlocked 
        ? (certificate.type === 'course_completion' ? 'bg-gradient-to-br from-amber-500/10 via-white to-amber-500/5 border-amber-300 shadow-md ring-1 ring-amber-400/30' : 'bg-white border-emerald-300 shadow-sm ring-1 ring-emerald-400/20')
        : 'bg-slate-50/80 border-slate-200/80 opacity-90'
    ]"
    class="rounded-3xl p-5 border flex flex-col justify-between gap-4 transition-all duration-300 relative overflow-hidden group"
  >
    <!-- Background Watermark Badge -->
    <Award 
      :size="120" 
      :class="certificate.unlocked ? (certificate.type === 'course_completion' ? 'text-amber-500/10' : 'text-emerald-500/10') : 'text-slate-300/15'"
      class="absolute -right-4 -bottom-4 pointer-events-none transition-transform group-hover:scale-110 duration-500" 
    />

    <!-- Header & Badge -->
    <div class="relative z-10 space-y-2">
      <div class="flex items-center justify-between">
        <span 
          :class="[
            certificate.type === 'course_completion' 
              ? 'bg-amber-100 text-amber-800 border-amber-200' 
              : (certificate.type === 'multi_session' ? 'bg-indigo-100 text-indigo-800 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-200')
          ]"
          class="text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider border select-none inline-flex items-center gap-1"
        >
          <Award :size="12" />
          {{ certificate.type === 'course_completion' ? 'Completion Sertifikat' : (certificate.type === 'multi_session' ? 'Sertifikat Multi-Sesi' : 'Sertifikat Sesi') }}
        </span>

        <!-- Status Pill -->
        <span 
          :class="certificate.unlocked ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-600'"
          class="text-[10px] font-black px-2.5 py-1 rounded-full flex items-center gap-1 shadow-sm select-none"
        >
          <CheckCircle2 v-if="certificate.unlocked" :size="12" />
          <Lock v-else :size="12" />
          {{ certificate.unlocked ? (certificate.claimed ? 'Terbuka & Terklaim' : 'Siap Di-Klaim') : 'Terkunci' }}
        </span>
      </div>

      <!-- Certificate Title & Description -->
      <div>
        <h4 class="text-sm font-extrabold text-[#1A2B49] leading-snug group-hover:text-[#264790] transition-colors">
          {{ certificate.title }}
        </h4>
        <p class="text-slate-500 text-xs font-medium mt-1 leading-relaxed line-clamp-2">
          {{ certificate.description || 'Selesaikan prasyarat modul di bawah ini untuk membuka sertifikat kelulusan ini.' }}
        </p>
      </div>
    </div>

    <!-- Progress Indicator or Status Callout -->
    <div class="relative z-10 space-y-2 pt-2 border-t border-slate-100">
      
      <!-- Progress Bar if Locked -->
      <template v-if="!certificate.unlocked">
        <div class="flex items-center justify-between text-[11px] font-bold text-slate-600">
          <span>Progres Prasyarat</span>
          <span>{{ certificate.progress_count }} / {{ certificate.total_required }} Sesi ({{ certificate.percentage }}%)</span>
        </div>
        <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
          <div 
            :style="{ width: `${certificate.percentage}%` }" 
            class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-500 rounded-full"
          ></div>
        </div>
        <p class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200/60 p-2 rounded-xl flex items-center gap-1.5 font-medium leading-tight">
          <AlertCircle :size="14" class="shrink-0 text-amber-600" />
          <span>{{ certificate.reason }}</span>
        </p>
      </template>

      <!-- Unlocked Callout -->
      <template v-else>
        <p v-if="certificate.claimed" class="text-[10px] text-emerald-700 font-extrabold flex items-center gap-1">
          <CheckCircle2 :size="12" class="text-emerald-600" />
          <span>Kode Sertifikat: <span class="font-mono text-emerald-800 bg-emerald-100/70 px-1.5 py-0.5 rounded">{{ certificate.certificate_code }}</span></span>
        </p>
        <p v-else class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
          <span>🎉 Seluruh syarat terpenuhi! Klik tombol di bawah untuk klaim sertifikat.</span>
        </p>
      </template>
      
      <!-- Error Feedback -->
      <p v-if="errorMsg" class="text-[10px] text-rose-600 font-bold mt-1">{{ errorMsg }}</p>

      <!-- Action Button -->
      <div class="pt-1">
        <!-- If Unlocked & Claimed -->
        <button 
          v-if="certificate.unlocked && certificate.claimed"
          type="button"
          @click="openCertificateDocument"
          class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-2.5 rounded-xl font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-1.5 cursor-pointer"
        >
          <Download :size="14" />
          <span>Unduh Sertifikat (PDF)</span>
          <ExternalLink :size="12" class="opacity-75" />
        </button>

        <!-- If Unlocked but Not Yet Claimed -->
        <button 
          v-else-if="certificate.unlocked && !certificate.claimed"
          type="button"
          @click="claimCertificate"
          :disabled="isClaiming"
          class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white py-2.5 rounded-xl font-black text-xs shadow-md transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
        >
          <RefreshCw v-if="isClaiming" :size="14" class="animate-spin" />
          <Award v-else :size="14" />
          <span>{{ isClaiming ? 'Membuka Sertifikat...' : 'Klaim & Buka Sertifikat Sekarang' }}</span>
        </button>

        <!-- If Locked -->
        <button 
          v-else
          type="button"
          disabled
          class="w-full bg-slate-200/80 text-slate-400 py-2.5 rounded-xl font-bold text-xs cursor-not-allowed flex items-center justify-center gap-1.5 select-none"
        >
          <Lock :size="14" />
          <span>Sertifikat Terkunci</span>
        </button>
      </div>

    </div>
  </div>
</template>
