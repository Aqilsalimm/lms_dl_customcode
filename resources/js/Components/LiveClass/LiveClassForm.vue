<script setup>
import { useForm } from '@inertiajs/vue3';
import { 
  Video, MapPin, Link2, Plus, Trash2, Calendar, Clock, 
  Sparkles, CheckCircle2, Film, Image as ImageIcon
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
  liveClass: { type: Object, default: null },
  courseId: { type: [Number, String], default: null },
});

const emit = defineEmits(['success', 'cancel']);

const form = useForm({
  title: props.liveClass?.title || '',
  course_id: props.liveClass?.course_id || props.courseId || null,
  delivery_mode: props.liveClass?.delivery_mode || 'online',
  meeting_link: props.liveClass?.meeting_link || '',
  location_venue: props.liveClass?.location_venue || '',
  recording_url: props.liveClass?.recording_url || '',
  documentation_urls: (props.liveClass?.documentation_urls && Array.isArray(props.liveClass.documentation_urls) && props.liveClass.documentation_urls.length > 0)
    ? [...props.liveClass.documentation_urls]
    : [''],
  start_time: props.liveClass?.start_time ? new Date(props.liveClass.start_time).toISOString().slice(0, 16) : '',
  end_time: props.liveClass?.end_time ? new Date(props.liveClass.end_time).toISOString().slice(0, 16) : '',
});

const addDocLink = () => {
  form.documentation_urls.push('');
};

const removeDocLink = (index) => {
  if (form.documentation_urls.length > 1) {
    form.documentation_urls.splice(index, 1);
  } else {
    form.documentation_urls[0] = '';
  }
};

const submit = () => {
  // Sanitize empty documentation URLs
  const cleanDocs = form.documentation_urls.filter(url => url && url.trim() !== '');

  const options = {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        title: 'Berhasil!',
        text: props.liveClass ? 'Data sesi kelas berhasil diperbarui.' : 'Sesi kelas baru berhasil ditambahkan.',
        icon: 'success',
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
          title: 'text-xl font-extrabold text-[#1A2B49]',
          confirmButton: 'bg-[#264790] text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
        },
        buttonsStyling: false
      });
      emit('success');
    },
    onError: () => {
      Swal.fire({
        title: 'Gagal Menyimpan',
        text: 'Periksa kembali formulir isian Anda.',
        icon: 'error',
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
          title: 'text-xl font-extrabold text-[#1A2B49]',
          confirmButton: 'bg-[#EF4444] text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
        },
        buttonsStyling: false
      });
    }
  };

  form.documentation_urls = cleanDocs;

  if (props.liveClass?.id) {
    form.put(route('live-classes.update', props.liveClass.id), options);
  } else {
    form.post(route('live-classes.store'), options);
  }
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6 bg-white p-6 sm:p-8 rounded-3xl shadow-sm border border-slate-100">
    <!-- Header Title -->
    <div class="border-b border-slate-100 pb-4">
      <h3 class="text-xl font-black text-[#1A2B49] flex items-center gap-2">
        <Sparkles :size="20" class="text-[#44A6D9]" />
        {{ props.liveClass ? 'Edit Sesi Kelas Live / Hybrid' : 'Tambah Sesi Kelas Live / Hybrid' }}
      </h3>
      <p class="text-xs text-slate-400 font-semibold mt-1">
        Atur modalitas (Online vs Offline), lokasi, serta materi & galeri dokumentasi pasca-acara.
      </p>
    </div>

    <!-- Judul Sesi -->
    <div>
      <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-2">Judul Kelas / Sesi</label>
      <input 
        v-model="form.title" 
        type="text" 
        placeholder="Contoh: Sesi 1 - Workshop Tatap Muka Framework Laravel & Vue 3"
        class="w-full rounded-2xl border-slate-200 px-4 py-3 text-xs font-bold text-[#1A2B49] focus:border-[#264790] focus:ring-[#264790]/20 transition-all outline-none"
        required 
      />
      <span v-if="form.errors.title" class="text-xs font-bold text-rose-500 mt-1 block">{{ form.errors.title }}</span>
    </div>

    <!-- Radio Selection Mode -->
    <div>
      <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-3">Moda Pelaksanaan Kelas</label>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Online Radio -->
        <label 
          class="p-4 border-2 rounded-2xl cursor-pointer flex items-center justify-between transition-all duration-200 hover:shadow-sm"
          :class="form.delivery_mode === 'online' ? 'border-[#264790] bg-[#264790]/5 text-[#264790]' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
        >
          <div class="flex items-center gap-3">
            <input type="radio" v-model="form.delivery_mode" value="online" class="w-4 h-4 text-[#264790] focus:ring-0 cursor-pointer" />
            <div>
              <p class="font-extrabold text-xs flex items-center gap-1.5">
                <Video :size="16" class="text-[#44A6D9]" /> Online Class
              </p>
              <p class="text-[11px] text-slate-400 font-semibold mt-0.5">PJJ via Zoom / Google Meet</p>
            </div>
          </div>
        </label>

        <!-- Offline Radio -->
        <label 
          class="p-4 border-2 rounded-2xl cursor-pointer flex items-center justify-between transition-all duration-200 hover:shadow-sm"
          :class="form.delivery_mode === 'offline' ? 'border-amber-500 bg-amber-50/50 text-amber-900' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
        >
          <div class="flex items-center gap-3">
            <input type="radio" v-model="form.delivery_mode" value="offline" class="w-4 h-4 text-amber-600 focus:ring-0 cursor-pointer" />
            <div>
              <p class="font-extrabold text-xs flex items-center gap-1.5">
                <MapPin :size="16" class="text-amber-600" /> Offline Class
              </p>
              <p class="text-[11px] text-slate-400 font-semibold mt-0.5">Tatap Muka / On-Site Venue</p>
            </div>
          </div>
        </label>
      </div>
    </div>

    <!-- Conditional Input 1: Online Meeting Link -->
    <div v-if="form.delivery_mode === 'online'" class="p-5 bg-sky-50/50 rounded-2xl border border-sky-100 flex flex-col gap-2">
      <label class="block text-xs font-extrabold text-sky-900 uppercase tracking-wider flex items-center gap-1.5">
        <Link2 :size="14" class="text-[#44A6D9]" /> Link Zoom / Google Meet
      </label>
      <input 
        v-model="form.meeting_link" 
        type="url" 
        placeholder="https://zoom.us/j/... atau https://meet.google.com/..." 
        class="w-full rounded-xl border-sky-200 px-4 py-2.5 text-xs font-bold text-[#1A2B49] focus:border-[#264790] bg-white outline-none" 
      />
      <span v-if="form.errors.meeting_link" class="text-xs font-bold text-rose-500">{{ form.errors.meeting_link }}</span>
    </div>

    <!-- Conditional Input 2: Offline Location Venue -->
    <div v-if="form.delivery_mode === 'offline'" class="p-5 bg-amber-50/50 rounded-2xl border border-amber-200/60 flex flex-col gap-2">
      <label class="block text-xs font-extrabold text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
        <MapPin :size="14" class="text-amber-600" /> Alamat Lengkap / Lokasi Gedung (Tatap Muka)
      </label>
      <textarea 
        v-model="form.location_venue" 
        rows="2" 
        placeholder="Gedung Utama Lt. 3, Ruang Lab Komputer 2, Jl. Sudirman No. 45..." 
        class="w-full rounded-xl border-amber-300/80 px-4 py-2.5 text-xs font-bold text-[#1A2B49] focus:border-amber-600 bg-white outline-none"
      ></textarea>
      <span v-if="form.errors.location_venue" class="text-xs font-bold text-rose-500">{{ form.errors.location_venue }}</span>
    </div>

    <!-- Jadwal Mulai & Selesai -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
          <Calendar :size="14" /> Waktu Mulai
        </label>
        <input 
          v-model="form.start_time" 
          type="datetime-local" 
          class="w-full rounded-2xl border-slate-200 px-4 py-3 text-xs font-bold text-[#1A2B49] focus:border-[#264790] transition-all outline-none" 
        />
      </div>
      <div>
        <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
          <Clock :size="14" /> Waktu Selesai
        </label>
        <input 
          v-model="form.end_time" 
          type="datetime-local" 
          class="w-full rounded-2xl border-slate-200 px-4 py-3 text-xs font-bold text-[#1A2B49] focus:border-[#264790] transition-all outline-none" 
        />
      </div>
    </div>

    <hr class="border-slate-100" />

    <!-- Post Event Section (Recording & Documentation) -->
    <div class="space-y-4 bg-slate-50/70 p-5 rounded-2xl border border-slate-100">
      <h4 class="font-extrabold text-xs text-[#1A2B49] uppercase tracking-wider flex items-center gap-2">
        <Film :size="16" class="text-purple-600" /> Aset Pasca Pelatihan (Optional / Post-Event Update)
      </h4>

      <!-- Recording Link -->
      <div>
        <label class="block text-xs font-extrabold text-slate-600 mb-1.5">Link Rekaman Acara (Recording URL)</label>
        <input 
          v-model="form.recording_url" 
          type="url" 
          placeholder="https://youtube.com/watch?v=... atau https://drive.google.com/..." 
          class="w-full rounded-xl border-slate-200 px-4 py-2.5 text-xs font-bold text-[#1A2B49] focus:border-[#264790] bg-white outline-none" 
        />
        <span v-if="form.errors.recording_url" class="text-xs font-bold text-rose-500 mt-1 block">{{ form.errors.recording_url }}</span>
      </div>

      <!-- Documentation Gallery Links -->
      <div>
        <label class="block text-xs font-extrabold text-slate-600 mb-2 flex items-center justify-between">
          <span class="flex items-center gap-1"><ImageIcon :size="14" class="text-purple-500" /> Link Galeri Foto / Dokumentasi</span>
          <button 
            type="button" 
            @click="addDocLink" 
            class="text-xs font-bold text-[#264790] hover:underline flex items-center gap-1 cursor-pointer"
          >
            <Plus :size="12" /> Tambah Link
          </button>
        </label>
        
        <div v-for="(url, idx) in form.documentation_urls" :key="idx" class="flex items-center gap-2 mb-2">
          <input 
            v-model="form.documentation_urls[idx]" 
            type="url" 
            placeholder="https://drive.google.com/drive/folders/..." 
            class="flex-1 rounded-xl border-slate-200 px-4 py-2 text-xs font-bold text-[#1A2B49] focus:border-[#264790] bg-white outline-none" 
          />
          <button 
            type="button" 
            @click="removeDocLink(idx)" 
            class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer"
            title="Hapus Link"
          >
            <Trash2 :size="16" />
          </button>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button 
        v-if="props.liveClass" 
        type="button" 
        @click="emit('cancel')" 
        class="px-5 py-3 rounded-2xl font-bold text-xs text-slate-600 hover:bg-slate-100 transition-all cursor-pointer"
      >
        Batal
      </button>

      <button 
        type="submit" 
        :disabled="form.processing" 
        class="px-8 py-3 bg-[#264790] hover:bg-[#1A2B49] text-white rounded-2xl font-extrabold text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
      >
        <CheckCircle2 :size="16" /> {{ props.liveClass ? 'Simpan Perubahan' : 'Simpan Sesi Kelas' }}
      </button>
    </div>
  </form>
</template>
