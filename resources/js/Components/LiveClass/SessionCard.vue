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
  <div class="p-6 border border-slate-100 rounded-3xl bg-white shadow-[0_8px_30px_rgb(0,0,0,0.03)] space-y-5 transition-all hover:shadow-md">
    <!-- Header & Badge Status -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
      <div>
        <h3 class="font-extrabold text-[#1A2B49] text-base lg:text-lg leading-snug">{{ session.title }}</h3>
        <div v-if="session.start_time" class="flex items-center gap-2 mt-1 text-xs text-slate-400 font-semibold">
          <Calendar :size="14" class="text-[#44A6D9]" />
          <span>{{ formatDate(session.start_time) }}</span>
        </div>
      </div>
      
      <span 
        class="self-start sm:self-center px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider flex items-center gap-1.5 shrink-0 shadow-sm"
        :class="session.is_offline ? 'bg-amber-50 text-amber-800 border border-amber-200/80' : 'bg-blue-50 text-blue-800 border border-blue-200/80'"
      >
        <MapPin v-if="session.is_offline" :size="14" class="text-amber-600" />
        <Video v-else :size="14" class="text-blue-600" />
        {{ session.is_offline ? '📍 Tatap Muka (Offline)' : '💻 Online Class' }}
      </span>
    </div>

    <!-- Info Lokasi Tatap Muka (Offline Mode) -->
    <div v-if="session.is_offline" class="p-4 bg-amber-50/60 rounded-2xl text-xs text-amber-950 border border-amber-200/60 flex items-start gap-3">
      <Building2 :size="20" class="text-amber-600 shrink-0 mt-0.5" />
      <div>
        <p class="font-black text-[10px] text-amber-800 uppercase tracking-widest mb-1">Lokasi Pelaksanaan Tatap Muka</p>
        <p class="font-bold leading-relaxed text-amber-950">{{ session.location_venue || 'Alamat lokasi fisik akan segera diumumkan oleh instruktur.' }}</p>
      </div>
    </div>

    <!-- Info Link Zoom / Online Meeting (Online Mode) -->
    <div v-else-if="session.meeting_link" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-blue-50/60 rounded-2xl border border-blue-100 gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm">
          <Video :size="20" />
        </div>
        <div>
          <p class="text-xs font-extrabold text-[#1A2B49]">Sesi diselenggarakan via Live Meeting</p>
          <p class="text-[11px] text-slate-400 font-semibold truncate max-w-[260px]">{{ session.meeting_link }}</p>
        </div>
      </div>

      <a 
        :href="session.meeting_link" 
        target="_blank" 
        class="px-5 py-2.5 bg-[#264790] hover:bg-[#1A2B49] text-white text-xs font-extrabold rounded-xl shadow-md transition-all flex items-center justify-center gap-1.5 shrink-0 active:scale-95 cursor-pointer"
      >
        <ExternalLink :size="14" /> Gabung Meeting
      </a>
    </div>

    <!-- Section Rekaman & Dokumentasi (Pasca Acara / Post-Event) -->
    <div v-if="session.has_recording || session.has_documentation" class="pt-4 border-t border-slate-100 flex flex-col gap-3">
      <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 flex items-center gap-1">
        <Sparkles :size="12" class="text-purple-500" /> Aset Pasca Acara & Materi Pembahasan
      </p>

      <div class="flex flex-wrap gap-2.5">
        <!-- Link Rekaman Video -->
        <a 
          v-if="session.has_recording" 
          :href="session.recording_url" 
          target="_blank"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-[#1A2B49] text-slate-800 hover:text-white rounded-xl text-xs font-extrabold transition-all duration-200 shadow-sm active:scale-95 cursor-pointer"
        >
          <Film :size="14" class="text-purple-600" /> 🎥 Tonton Rekaman Sesi
        </a>

        <!-- Link Galeri Dokumentasi -->
        <template v-if="session.has_documentation">
          <a 
            v-for="(docUrl, idx) in session.documentation_urls" 
            :key="idx"
            :href="docUrl" 
            target="_blank"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-50 hover:bg-purple-600 text-purple-700 hover:text-white border border-purple-100 rounded-xl text-xs font-extrabold transition-all duration-200 shadow-sm active:scale-95 cursor-pointer"
          >
            <ImageIcon :size="14" /> 🖼️ Dokumentasi #{{ idx + 1 }}
          </a>
        </template>
      </div>
    </div>
  </div>
</template>
