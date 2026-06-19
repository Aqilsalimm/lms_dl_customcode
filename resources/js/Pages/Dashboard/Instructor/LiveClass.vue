<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  Video, Calendar, Clock, Link2, Users, CheckCircle2, 
  ExternalLink, Trash2, Edit2, Check, AlertCircle, Sparkles
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
  courses: Array
});

const selectedCourse = ref(null);
const platformType = ref('zoom');

const form = useForm({
  start_date: '',
  end_date: '',
  timezone: 'Asia/Jakarta',
  meeting_url: '',
  recording_url: '',
  max_participants: 100,
  is_event_finished: false,
  platform_type: 'zoom'
});

const formatDateTimeLocal = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const tzoffset = date.getTimezoneOffset() * 60000; //offset in milliseconds
  const localISOTime = (new Date(date - tzoffset)).toISOString().slice(0, 16);
  return localISOTime;
};

const selectCourse = (course) => {
  selectedCourse.value = course;
  platformType.value = (course.meeting_url && course.meeting_url.includes('meet.google.com')) ? 'meet' : 'zoom';
  
  form.start_date = course.start_date ? formatDateTimeLocal(course.start_date) : '';
  form.end_date = course.end_date ? formatDateTimeLocal(course.end_date) : '';
  form.timezone = course.timezone || 'Asia/Jakarta';
  form.meeting_url = course.meeting_url || '';
  form.recording_url = course.recording_url || '';
  form.max_participants = course.max_participants || 100;
  form.is_event_finished = course.is_event_finished || false;
  form.platform_type = platformType.value;
};

// Auto generate meet / zoom link if empty
const generateLink = () => {
  if (platformType.value === 'zoom') {
    const meetingIdNum = Math.floor(1000000000 + Math.random() * 9000000000);
    const pwdStr = Math.random().toString(36).substring(2, 10);
    form.meeting_url = `https://zoom.us/j/${meetingIdNum}?pwd=${pwdStr}`;
  } else {
    const part1 = Math.random().toString(36).substring(2, 5);
    const part2 = Math.random().toString(36).substring(2, 6);
    const part3 = Math.random().toString(36).substring(2, 5);
    form.meeting_url = `https://meet.google.com/${part1}-${part2}-${part3}`;
  }
};

const submit = () => {
  if (!selectedCourse.value) {
    Swal.fire({
      title: 'Peringatan',
      text: 'Silakan pilih kelas terlebih dahulu!',
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

  // Auto generate link on submission if still empty
  if (!form.meeting_url) {
    generateLink();
  }

  form.platform_type = platformType.value;

  form.post(route('dashboard.live-class.update-schedule', selectedCourse.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        title: 'Berhasil',
        text: 'Jadwal Kelas Live berhasil diperbarui!',
        icon: 'success',
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
          title: 'text-xl font-extrabold text-[#1A2B49]',
          confirmButton: 'bg-[#264790] text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
        },
        buttonsStyling: false
      });
      // update state
      const updated = props.courses.find(c => c.id === selectedCourse.value.id);
      if (updated) {
        selectCourse(updated);
      }
    },
    onError: () => {
      Swal.fire({
        title: 'Gagal',
        text: 'Gagal memperbarui jadwal. Pastikan format input sudah benar!',
        icon: 'error',
        customClass: {
          popup: 'rounded-[2rem] p-8 border border-slate-100 bg-white font-sans text-slate-800 shadow-md',
          title: 'text-xl font-extrabold text-[#1A2B49]',
          confirmButton: 'bg-[#EF4444] text-white font-bold px-8 py-3 rounded-full text-xs cursor-pointer'
        },
        buttonsStyling: false
      });
    }
  });
};

const formatDate = (dateString) => {
  if (!dateString) return 'Belum Diatur';
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

const isUpcoming = (dateString) => {
  if (!dateString) return false;
  return new Date(dateString) > new Date();
};
</script>

<template>
  <Head title="Live Class Schedule" />

  <GuestLayout>
    <DashboardWrapper>
      <div class="mb-8">
        <h2 class="text-3xl font-black text-[#1A2B49] tracking-tight flex items-center gap-3">
          <div class="p-3 bg-sky-50 rounded-2xl text-[#44A6D9]">
            <Video :size="28" stroke-width="2.5" />
          </div>
          Live Class Schedule
        </h2>
        <p class="text-slate-500 font-medium text-sm mt-2 pl-1">
          Kelola dan atur jadwal video conference Zoom atau Google Meet untuk kelas live interaktif Anda.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Kiri: Pilih Kursus & Form Input -->
        <div class="lg:col-span-7 flex flex-col gap-6">
          
          <!-- Card Pilihan Kursus -->
          <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100">
            <h3 class="text-lg font-bold text-[#1A2B49] mb-4 flex items-center gap-2">
              <Sparkles :size="18" class="text-sky-500" /> Pilih Kelas Live Anda
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto pr-1">
              <button 
                v-for="course in courses" 
                :key="course.id"
                @click="selectCourse(course)"
                :class="[
                  selectedCourse?.id === course.id
                    ? 'border-[#264790] bg-[#264790]/5 text-[#264790] shadow-sm'
                    : 'border-slate-100 hover:border-slate-300 bg-slate-50 text-[#1A2B49]'
                ]"
                class="flex items-center gap-3 p-4 rounded-2xl border text-left font-bold text-sm transition-all duration-300 hover:scale-[1.02]"
              >
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center shrink-0">
                  <Video :size="18" class="text-[#44A6D9]" />
                </div>
                <span class="truncate leading-tight">{{ course.title }}</span>
              </button>
              <div v-if="courses.length === 0" class="col-span-2 py-8 text-center text-slate-400 font-semibold">
                Belum ada kelas bertipe "Live Class".
              </div>
            </div>
          </div>

          <!-- Card Form Jadwal -->
          <div v-if="selectedCourse" class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 flex flex-col gap-6">
            <div class="border-b border-slate-100 pb-4">
              <h4 class="text-xl font-extrabold text-[#1A2B49] truncate">{{ selectedCourse.title }}</h4>
              <p class="text-xs text-slate-400 font-medium mt-1">Lengkapi data penjadwalan kelas live di bawah ini.</p>
            </div>

            <!-- Platform Switcher -->
            <div>
              <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-3">Platform Live</label>
              <div class="grid grid-cols-2 gap-4">
                <button 
                  type="button"
                  @click="platformType = 'zoom'"
                  :class="platformType === 'zoom' ? 'border-[#44A6D9] bg-sky-50 text-[#44A6D9]' : 'border-slate-200 text-slate-400 hover:border-slate-300 bg-white'"
                  class="flex items-center justify-center gap-2 py-3 border-2 rounded-2xl font-bold transition-all"
                >
                  <img src="https://upload.wikimedia.org/wikipedia/commons/2/24/Zoom-Logo.png" class="h-4 object-contain opacity-80" alt="Zoom" />
                  Zoom
                </button>
                <button 
                  type="button"
                  @click="platformType = 'meet'"
                  :class="platformType === 'meet' ? 'border-emerald-500 bg-emerald-50 text-emerald-600' : 'border-slate-200 text-slate-400 hover:border-slate-300 bg-white'"
                  class="flex items-center justify-center gap-2 py-3 border-2 rounded-2xl font-bold transition-all"
                >
                  <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Google_Meet_icon_%282020%29.svg" class="h-4 object-contain" alt="Google Meet" />
                  Google Meet
                </button>
              </div>
            </div>

            <!-- Form Inputs -->
            <form @submit.prevent="submit" class="flex flex-col gap-5">
              
              <!-- Start & End Date -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                    <Calendar :size="14" /> Mulai Kelas
                  </label>
                  <input 
                    type="datetime-local" 
                    v-model="form.start_date"
                    class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold"
                    required
                  />
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                    <Clock :size="14" /> Selesai Kelas
                  </label>
                  <input 
                    type="datetime-local" 
                    v-model="form.end_date"
                    class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold"
                    required
                  />
                </div>
              </div>

              <!-- Timezone & Max Participants -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2">Zona Waktu</label>
                  <select 
                    v-model="form.timezone"
                    class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold bg-white"
                  >
                    <option value="Asia/Jakarta">WIB (Asia/Jakarta)</option>
                    <option value="Asia/Makassar">WITA (Asia/Makassar)</option>
                    <option value="Asia/Jayapura">WIT (Asia/Jayapura)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                    <Users :size="14" /> Kapasitas Maksimal
                  </label>
                  <input 
                    type="number" 
                    v-model="form.max_participants"
                    min="1"
                    class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold"
                    required
                  />
                </div>
              </div>

              <!-- Meeting Link -->
              <div>
                <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2 flex items-center justify-between">
                  <span class="flex items-center gap-1.5"><Link2 :size="14" /> Meeting URL</span>
                  <button 
                    type="button" 
                    @click="generateLink"
                    class="text-xs font-bold text-[#44A6D9] hover:underline flex items-center gap-1"
                  >
                    Generate Otomatis
                  </button>
                </label>
                <input 
                  type="url" 
                  v-model="form.meeting_url"
                  placeholder="https://zoom.us/j/... atau https://meet.google.com/..."
                  class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold"
                />
              </div>

              <!-- Recording URL -->
              <div>
                <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                  <Video :size="14" /> Link Rekaman Sesi (Opsional)
                </label>
                <input 
                  type="url" 
                  v-model="form.recording_url"
                  placeholder="https://drive.google.com/... atau link youtube rekaman"
                  class="w-full border-slate-200 rounded-2xl px-4 py-3 text-sm focus:border-[#264790] focus:ring-[#264790]/20 font-semibold"
                />
              </div>

              <!-- Finished Toggle -->
              <div class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100 mt-2">
                <div>
                  <span class="block text-sm font-bold text-[#1A2B49]">Tandai Kelas Selesai</span>
                  <span class="text-xs text-slate-400 font-medium">Jika diaktifkan, kelas dianggap telah selesai dikerjakan.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="form.is_event_finished" class="sr-only peer">
                  <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sky-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
              </div>

              <!-- Submit Button -->
              <button 
                type="submit" 
                :disabled="form.processing"
                class="w-full bg-[#264790] hover:bg-[#1C356A] text-white font-extrabold py-4 px-6 rounded-2xl text-sm transition-all duration-300 shadow-md shadow-indigo-900/10 flex items-center justify-center gap-2 hover:-translate-y-0.5 cursor-pointer disabled:opacity-55"
              >
                <CheckCircle2 :size="18" /> Simpan Penjadwalan
              </button>

            </form>
          </div>
        </div>

        <!-- Kanan: Jadwal Aktif & Timeline -->
        <div class="lg:col-span-5 flex flex-col gap-6">
          <div class="bg-white rounded-[2rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100">
            <h3 class="text-lg font-bold text-[#1A2B49] mb-6 flex items-center gap-2">
              <Calendar :size="18" class="text-[#264790]" /> Daftar Jadwal Aktif
            </h3>

            <div class="flex flex-col gap-4">
              <div 
                v-for="c in courses" 
                :key="'list_' + c.id"
                class="p-5 border border-slate-100 rounded-2xl bg-slate-50/50 hover:bg-slate-50 transition-colors flex flex-col gap-3"
              >
                <div class="flex items-start justify-between gap-3">
                  <h4 class="font-extrabold text-sm text-[#1A2B49] leading-snug line-clamp-2">{{ c.title }}</h4>
                  <span 
                    :class="[
                      c.is_event_finished 
                        ? 'bg-slate-200 text-slate-600'
                        : isUpcoming(c.start_date)
                          ? 'bg-sky-50 text-sky-600 border border-sky-100'
                          : 'bg-emerald-50 text-emerald-600 border border-emerald-100'
                    ]"
                    class="text-[10px] font-extrabold px-2.5 py-1 rounded-full shrink-0 uppercase tracking-wider"
                  >
                    {{ c.is_event_finished ? 'Selesai' : isUpcoming(c.start_date) ? 'Mendatang' : 'Sedang Berlangsung' }}
                  </span>
                </div>

                <div class="flex flex-col gap-1 text-slate-500 font-medium text-xs">
                  <div class="flex items-center gap-1.5">
                    <Calendar :size="14" class="text-slate-400" />
                    <span>Mulai: {{ formatDate(c.start_date) }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <Clock :size="14" class="text-slate-400" />
                    <span>Zona Waktu: {{ c.timezone || 'Asia/Jakarta' }}</span>
                  </div>
                  <div class="flex items-center gap-1.5">
                    <Users :size="14" class="text-slate-400" />
                    <span>Kapasitas: {{ c.max_participants || 100 }} Siswa</span>
                  </div>
                </div>

                <div v-if="c.meeting_url" class="border-t border-slate-100 pt-3 mt-1 flex items-center justify-between gap-4">
                  <a 
                    :href="c.meeting_url" 
                    target="_blank" 
                    class="text-xs font-bold text-[#264790] hover:underline flex items-center gap-1.5"
                  >
                    <ExternalLink :size="14" /> Gabung Kelas Live
                  </a>
                  
                  <span class="text-[10px] text-slate-400 font-bold truncate max-w-[150px]">
                    {{ c.meeting_url }}
                  </span>
                </div>
              </div>

              <div v-if="courses.length === 0" class="py-12 text-center text-slate-400 font-medium text-sm">
                Tidak ada kelas live yang aktif saat ini.
              </div>
            </div>
          </div>
        </div>
      </div>
    </DashboardWrapper>
  </GuestLayout>
</template>
