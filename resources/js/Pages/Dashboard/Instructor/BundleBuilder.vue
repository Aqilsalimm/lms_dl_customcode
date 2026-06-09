<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  ArrowLeft, Save, Upload, FolderHeart, 
  Check, Info, Sparkles, AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
  bundle: Object,
  courses: Array
});

const form = useForm({
  _method: 'PUT',
  title: props.bundle.title,
  price: parseFloat(props.bundle.price),
  status: props.bundle.status,
  description: props.bundle.description || '',
  courses: props.bundle.courses ? props.bundle.courses.map(c => c.id) : [],
  thumbnail: null
});

// Image Preview State
const thumbnailPreview = ref(props.bundle.thumbnail || null);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.thumbnail = file;
    const reader = new FileReader();
    reader.onload = (event) => {
      thumbnailPreview.value = event.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const triggerFileInput = () => {
  document.getElementById('bundle-thumbnail-input').click();
};

const toggleCourse = (courseId) => {
  const index = form.courses.indexOf(courseId);
  if (index === -1) {
    form.courses.push(courseId);
  } else {
    form.courses.splice(index, 1);
  }
};

// Calculations for bundle metrics
const selectedCoursesDetails = computed(() => {
  return props.courses.filter(c => form.courses.includes(c.id));
});

const originalTotalPrice = computed(() => {
  return selectedCoursesDetails.value.reduce((sum, c) => sum + parseFloat(c.price), 0);
});

const discountAmount = computed(() => {
  const diff = originalTotalPrice.value - form.price;
  return diff > 0 ? diff : 0;
});

const discountPercentage = computed(() => {
  if (originalTotalPrice.value <= 0) return 0;
  const pct = (discountAmount.value / originalTotalPrice.value) * 100;
  return Math.round(pct);
});

const submitForm = () => {
  form.post(route('bundle-builder.update', props.bundle.id), {
    forceFormData: true,
    onSuccess: () => {
      // Handled by controller redirect
    }
  });
};
</script>

<template>
  <Head title="Bundle Builder | Edit Bundle" />

  <DashboardWrapper>
    <!-- Header -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
      <div class="flex items-center gap-3">
        <Link 
          :href="route('bundle-builder.index')"
          class="w-10 h-10 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#264790] hover:bg-slate-100 transition-colors"
        >
          <ArrowLeft :size="18" />
        </Link>
        <div>
          <h2 class="text-2xl font-extrabold text-[#1A2B49] leading-tight">
            Edit Bundle: {{ bundle.title }}
          </h2>
          <p class="text-slate-500 text-sm mt-0.5">
            Sunting info bundle, pilih kelas yang ingin digabungkan, dan kelola harga paket.
          </p>
        </div>
      </div>
      <button 
        @click="submitForm"
        :disabled="form.processing"
        class="bg-[#264790] hover:bg-[#44A6D9] text-white px-6 py-3 rounded-full font-bold text-sm shadow-md transition-all duration-300 flex items-center gap-2"
      >
        <Save :size="16" /> {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
      </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Column: Details -->
      <div class="lg:col-span-2 flex flex-col gap-8">
        
        <!-- General Info Card -->
        <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6 flex items-center gap-2">
            <FolderHeart :size="20" class="text-[#264790]" /> Informasi Umum
          </h3>

          <div class="flex flex-col gap-6">
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Bundle</label>
              <input 
                v-model="form.title" 
                type="text" 
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors"
                placeholder="Masukkan Judul Bundle"
              />
              <span v-if="form.errors.title" class="text-rose-500 text-xs mt-1 block font-semibold">{{ form.errors.title }}</span>
            </div>

            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Deskripsi Bundle</label>
              <textarea 
                v-model="form.description" 
                rows="5"
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors resize-none"
                placeholder="Tulis deskripsi detail bundle ini..."
              ></textarea>
              <span v-if="form.errors.description" class="text-rose-500 text-xs mt-1 block font-semibold">{{ form.errors.description }}</span>
            </div>
          </div>
        </div>

        <!-- Course Picker Card -->
        <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-extrabold text-[#1A2B49] flex items-center gap-2">
              <Sparkles :size="20" class="text-[#F9CC6B]" /> Pilih Kelas Dalam Bundle
            </h3>
            <span class="bg-slate-100 text-slate-800 text-xs font-extrabold px-3 py-1 rounded-full border border-slate-200">
              {{ form.courses.length }} Kelas Terpilih
            </span>
          </div>

          <div v-if="courses.length === 0" class="text-center py-8 text-slate-400 font-medium">
            Belum ada kelas yang dipublikasikan untuk dimasukkan ke dalam bundle.
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="course in courses" 
              :key="course.id"
              @click="toggleCourse(course.id)"
              :class="[
                form.courses.includes(course.id)
                  ? 'border-[#264790] bg-slate-50/50 shadow-sm'
                  : 'border-slate-100 hover:border-slate-200 bg-white'
              ]"
              class="border-2 rounded-2xl p-4 flex items-start gap-3 cursor-pointer transition-all duration-200 select-none"
            >
              <div 
                :class="[
                  form.courses.includes(course.id)
                    ? 'bg-[#264790] text-white border-transparent'
                    : 'border-slate-300 bg-white'
                ]"
                class="w-5 h-5 rounded border-2 flex items-center justify-center shrink-0 mt-0.5 transition-all"
              >
                <Check v-if="form.courses.includes(course.id)" :size="12" stroke-width="3" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-bold text-[#1A2B49] text-sm leading-snug truncate">
                  {{ course.title }}
                </div>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-xs font-semibold text-slate-400">Kelas {{ course.level }}</span>
                  <span class="text-xs font-extrabold text-[#264790]">
                    Rp {{ parseFloat(course.price).toLocaleString('id-ID') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <span v-if="form.errors.courses" class="text-rose-500 text-xs mt-3 block font-semibold">{{ form.errors.courses }}</span>
        </div>

      </div>

      <!-- Right Column: Settings & Pricing -->
      <div class="flex flex-col gap-8">
        
        <!-- Thumbnail Card -->
        <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <h3 class="text-lg font-extrabold text-[#1A2B49] mb-4">Cover / Thumbnail</h3>
          
          <input 
            type="file" 
            id="bundle-thumbnail-input" 
            class="hidden" 
            accept="image/*"
            @change="handleFileChange"
          />

          <div 
            @click="triggerFileInput"
            class="aspect-video w-full rounded-2xl border-2 border-dashed border-slate-200 hover:border-[#264790] bg-slate-50 flex flex-col items-center justify-center gap-2 cursor-pointer transition-all overflow-hidden relative group"
          >
            <img 
              v-if="thumbnailPreview" 
              :src="thumbnailPreview" 
              class="w-full h-full object-cover"
            />
            <template v-else>
              <Upload :size="24" class="text-slate-400 group-hover:text-[#264790] transition-colors" />
              <span class="text-xs font-bold text-slate-500 group-hover:text-[#264790] transition-colors">
                Upload Cover Bundle
              </span>
            </template>
          </div>
          <span v-if="form.errors.thumbnail" class="text-rose-500 text-xs mt-1 block font-semibold">{{ form.errors.thumbnail }}</span>
        </div>

        <!-- Pricing Card -->
        <div class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          <h3 class="text-lg font-extrabold text-[#1A2B49] mb-6">Pengaturan Harga & Status</h3>

          <div class="flex flex-col gap-5">
            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Harga Paket (Rupiah)</label>
              <input 
                v-model.number="form.price" 
                type="number" 
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold transition-colors"
                placeholder="499000"
              />
              <span v-if="form.errors.price" class="text-rose-500 text-xs mt-1 block font-semibold">{{ form.errors.price }}</span>
            </div>

            <div>
              <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Status Publikasi</label>
              <select 
                v-model="form.status"
                class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold cursor-pointer transition-colors"
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
              </select>
              <span v-if="form.errors.status" class="text-rose-500 text-xs mt-1 block font-semibold">{{ form.errors.status }}</span>
            </div>

            <!-- Stats Recap -->
            <div class="bg-slate-50 rounded-2xl p-4 flex flex-col gap-2.5 border border-slate-100">
              <div class="flex justify-between items-center text-xs font-medium text-slate-500">
                <span>Total Harga Normal:</span>
                <span class="font-extrabold text-slate-700">
                  Rp {{ originalTotalPrice.toLocaleString('id-ID') }}
                </span>
              </div>
              <div class="flex justify-between items-center text-xs font-medium text-slate-500">
                <span>Harga Bundle:</span>
                <span class="font-extrabold text-[#264790]">
                  Rp {{ form.price.toLocaleString('id-ID') }}
                </span>
              </div>
              
              <div class="h-px bg-slate-200/60 my-1"></div>

              <div class="flex justify-between items-center">
                <span class="text-xs font-extrabold text-slate-800">Hemat Pembeli:</span>
                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                    {{ discountPercentage }}% OFF
                  </span>
                  <span class="text-sm font-extrabold text-emerald-600">
                    Rp {{ discountAmount.toLocaleString('id-ID') }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </DashboardWrapper>
</template>
