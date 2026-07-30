<script setup>
import { 
  Video, MapPin, Film, Image as ImageIcon, ExternalLink, 
  Calendar, Clock, CheckCircle2, Sparkles, Building2
} from 'lucide-vue-next';

const props = defineProps({
  session: { type: Object, required: true }
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <div class="p-4 sm:p-5 border border-slate-100 rounded-2xl bg-white shadow-[0_4px_20px_rgb(0,0,0,0.02)] space-y-4 transition-all hover:shadow-md overflow-hidden">
    <!-- Header & Badge Status -->
    <div class="flex items-start sm:items-center justify-between gap-2.5 border-b border-slate-100 pb-3.5 min-w-0">
      <div class="min-w-0 flex-1">
        <h3 class="font-extrabold text-[#1A2B49] text-sm lg:text-base leading-snug truncate" :title="session.title">{{ session.title }}</h3>
        <div v-if="session.start_time" class="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-400 font-semibold truncate">
          <Calendar :size="13" class="text-[#44A6D9] shrink-0" />
          <span class="truncate">{{ formatDate(session.start_time) }}</span>
        </div>
      </div>
      
      <span 
        class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1 shrink-0 shadow-2xs"
        :class="session.is_offline ? 'bg-amber-50 text-amber-800 border border-amber-200/80' : 'bg-blue-50 text-blue-800 border border-blue-200/80'"
      >
        <MapPin v-if="session.is_offline" :size="12" class="text-amber-600 shrink-0" />
        <Video v-else :size="12" class="text-blue-600 shrink-0" />
        <span>{{ session.is_offline ? 'Offline' : 'Online Class' }}</span>
      </span>
    </div>

    <!-- Info Lokasi Tatap Muka (Offline Mode) -->
    <div v-if="session.is_offline" class="p-3.5 bg-amber-50/60 rounded-xl text-xs text-amber-950 border border-amber-200/60 flex items-start gap-2.5 overflow-hidden">
      <Building2 :size="18" class="text-amber-600 shrink-0 mt-0.5" />
      <div class="min-w-0 flex-1">
        <p class="font-black text-[10px] text-amber-800 uppercase tracking-widest mb-0.5 truncate">Lokasi Pelaksanaan Tatap Muka</p>
        <p class="font-bold leading-relaxed text-amber-950 text-xs">{{ session.location_venue || 'Alamat lokasi fisik akan segera diumumkan oleh instruktur.' }}</p>
      </div>
    </div>

    <!-- Info Link Zoom / Online Meeting (Online Mode) -->
    <div v-else-if="session.meeting_link" class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 gap-3 min-w-0">
      <div class="flex items-center gap-2.5 min-w-0 flex-1">
        <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm">
          <Video :size="18" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xs font-extrabold text-[#1A2B49] truncate">Sesi Live Meeting</p>
          <p class="text-[11px] text-slate-400 font-semibold truncate">{{ session.meeting_link }}</p>
        </div>
      </div>

      <a 
        :href="session.meeting_link" 
        target="_blank" 
        class="px-4 py-2 bg-[#264790] hover:bg-[#1A2B49] text-white text-xs font-extrabold rounded-xl shadow-sm transition-all flex items-center justify-center gap-1.5 shrink-0 active:scale-95 cursor-pointer"
      >
        <ExternalLink :size="13" /> Gabung Meeting
      </a>
    </div>

    <!-- Section Rekaman & Dokumentasi (Pasca Acara / Post-Event) -->
    <div v-if="session.has_recording || session.has_documentation" class="pt-3 border-t border-slate-100 flex flex-col gap-2.5">
      <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 flex items-center gap-1">
        <Sparkles :size="12" class="text-purple-500 shrink-0" /> Aset Pasca Acara & Materi Pembahasan
      </p>

      <div class="flex flex-wrap gap-2">
        <!-- Link Rekaman Video -->
        <a 
          v-if="session.has_recording" 
          :href="session.recording_url" 
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-[#1A2B49] text-slate-800 hover:text-white rounded-xl text-xs font-extrabold transition-all duration-200 shadow-xs active:scale-95 cursor-pointer"
        >
          <Film :size="13" class="text-purple-600 shrink-0" /> 🎥 Tonton Rekaman Sesi
        </a>

        <!-- Link Galeri Dokumentasi -->
        <template v-if="session.has_documentation">
          <a 
            v-for="(docUrl, idx) in session.documentation_urls" 
            :key="idx"
            :href="docUrl" 
            target="_blank"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-purple-50 hover:bg-purple-600 text-purple-700 hover:text-white border border-purple-100 rounded-xl text-xs font-extrabold transition-all duration-200 shadow-xs active:scale-95 cursor-pointer"
          >
            <ImageIcon :size="13" class="shrink-0" /> 🖼️ Dokumentasi #{{ idx + 1 }}
          </a>
        </template>
      </div>
    </div>
  </div>
</template>
