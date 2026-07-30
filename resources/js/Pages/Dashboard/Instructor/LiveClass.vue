<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import LiveClassForm from '@/Components/LiveClass/LiveClassForm.vue';
import SessionCard from '@/Components/LiveClass/SessionCard.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  Video, Calendar, Clock, Link2, Users, CheckCircle2, 
  ExternalLink, Trash2, Edit2, Check, AlertCircle, Sparkles, MapPin
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
              <p class="text-xs text-slate-400 font-medium mt-1">Lengkapi data penjadwalan moda kelas hybrid (Online vs Offline & Pasca-Acara) di bawah ini.</p>
            </div>

            <!-- Hybrid Form Component -->
            <LiveClassForm :course-id="selectedCourse.id" />
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
                class="p-4 sm:p-5 border border-slate-100 rounded-2xl bg-slate-50/50 hover:bg-slate-50 transition-colors flex flex-col gap-3 overflow-hidden"
              >
                <div class="flex items-start justify-between gap-2.5 min-w-0">
                  <div class="min-w-0 flex-1">
                    <h4 class="font-extrabold text-sm text-[#1A2B49] leading-snug truncate" :title="c.title">{{ c.title }}</h4>
                  </div>
                  <span 
                    :class="[
                      c.is_event_finished 
                        ? 'bg-slate-200 text-slate-600'
                        : isUpcoming(c.start_date)
                          ? 'bg-sky-50 text-sky-600 border border-sky-100'
                          : 'bg-emerald-50 text-emerald-600 border border-emerald-100'
                    ]"
                    class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full shrink-0 uppercase tracking-wider"
                  >
                    {{ c.is_event_finished ? 'Selesai' : isUpcoming(c.start_date) ? 'Mendatang' : 'Sedang Berlangsung' }}
                  </span>
                </div>

                <div class="flex flex-col gap-1 text-slate-500 font-medium text-xs">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <Calendar :size="14" class="text-slate-400 shrink-0" />
                    <span class="truncate">Mulai: {{ formatDate(c.start_date) }}</span>
                  </div>
                  <div class="flex items-center gap-1.5 min-w-0">
                    <Clock :size="14" class="text-slate-400 shrink-0" />
                    <span class="truncate">Zona Waktu: {{ c.timezone || 'Asia/Jakarta' }}</span>
                  </div>
                  <div class="flex items-center gap-1.5 min-w-0">
                    <Users :size="14" class="text-slate-400 shrink-0" />
                    <span class="truncate">Kapasitas: {{ c.max_participants || 100 }} Siswa</span>
                  </div>
                </div>

                <!-- Preview Tampilan Siswa (SessionCard Component) -->
                <SessionCard 
                  :session="{
                    title: c.title,
                    is_offline: c.delivery_mode === 'offline',
                    location_venue: c.location_venue,
                    meeting_link: c.meeting_url,
                    has_recording: !!c.recording_url,
                    recording_url: c.recording_url,
                    has_documentation: !!(c.documentation_urls && c.documentation_urls.length > 0),
                    documentation_urls: c.documentation_urls,
                    start_time: c.start_date
                  }"
                />
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
