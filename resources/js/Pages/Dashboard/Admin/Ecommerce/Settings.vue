<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  Settings, BarChart3, Tag, Save, AlertCircle, 
  Mail, Clock, Check, ChevronRight, HelpCircle, X
} from 'lucide-vue-next';

const props = defineProps({
  settings: Object
});

const form = useForm({
  midtrans_client_key: props.settings.midtrans_client_key || '',
  midtrans_server_key: props.settings.midtrans_server_key || '',
  midtrans_sandbox_mode: props.settings.midtrans_sandbox_mode !== false,
  auto_complete_ecommerce_orders: props.settings.auto_complete_ecommerce_orders === true,
  abandoned_cart_reminder_enabled: props.settings.abandoned_cart_reminder_enabled === true,
  abandoned_cart_reminder_delay: props.settings.abandoned_cart_reminder_delay || 60,
  abandoned_cart_email_subject: props.settings.abandoned_cart_email_subject || 'Ayo selesaikan pembelian kelas Anda di Drastha Learning!',
  abandoned_cart_email_body: props.settings.abandoned_cart_email_body || '',
});

const saveSettings = () => {
  form.post(route('dashboard.ecommerce.settings.update'), {
    preserveScroll: true
  });
};

// Help popover
const showPlaceholderHelp = ref(false);

// Insert placeholders helper function
const insertPlaceholder = (tag) => {
  form.abandoned_cart_email_body += ' ' + tag;
};

// Simulated email preview
const previewBody = computed(() => {
  let body = form.abandoned_cart_email_body;
  if (!body) return 'Tulis pesan email pengingat di samping...';
  
  const buttonHtml = '<a href="#" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #1A2B49 0%, #264790 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 700; margin-top: 10px;">Lanjutkan Belajar Sekarang</a>';
  
  body = body.replace(/{student_name}/g, '<b>Ahmad Ridwan</b>');
  body = body.replace(/{course_names}/g, '<ul><li><i>Python Programming for Beginners</i></li><li><i>UI/UX Masterclass</i></li></ul>');
  body = body.replace(/{checkout_link}/g, buttonHtml);
  body = body.replace(/{discount_amount}/g, '<b>Rp 150.000</b>');
  
  return body.replace(/\n/g, '<br>');
});
</script>

<template>
  <Head title="e-Commerce & Cart Recovery Settings" />

  <DashboardWrapper>
    <div class="max-w-6xl mx-auto flex flex-col gap-8">
      
      <!-- HEADER -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
        <div>
          <div class="flex items-center gap-2 text-[#264790] text-xs font-bold uppercase tracking-wider">
            <Settings :size="14" />
            Pengaturan Transaksi
          </div>
          <h1 class="text-2xl font-extrabold text-[#1A2B49] tracking-tight mt-1">e-Commerce Settings</h1>
          <p class="text-sm text-slate-500 mt-1">Konfigurasi Midtrans payment gateway, optimasi checkout, dan sistem pemulihan keranjang belanja (Abandoned Cart).</p>
        </div>
      </div>

      <!-- SETTINGS FORM -->
      <form @submit.prevent="saveSettings" class="flex flex-col gap-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          <!-- LEFT SIDE: CONFIGURATIONS -->
          <div class="lg:col-span-2 flex flex-col gap-8">
            
            <!-- SECTION 1: ABANDONED CART RECOVERY -->
            <div class="bg-white rounded-[2.5rem] border border-slate-150 shadow-[0_8px_30px_rgb(0,0,0,0.015)] p-8 flex flex-col gap-6">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-[#264790] rounded-2xl flex items-center justify-center shrink-0">
                  <Mail :size="22" />
                </div>
                <div>
                  <h3 class="text-base font-extrabold text-[#1A2B49]">Abandoned Cart Recovery</h3>
                  <p class="text-xs text-slate-500 mt-1">Kirimkan pengingat email otomatis ke siswa yang meninggalkan halaman checkout sebelum menyelesaikan pembayaran.</p>
                </div>
              </div>

              <div class="h-px bg-slate-100"></div>

              <!-- ENABLE TOGGLE -->
              <div class="flex items-center justify-between bg-slate-50/50 border border-slate-100 p-5 rounded-2xl">
                <div class="flex flex-col gap-0.5 max-w-[80%]">
                  <span class="text-xs font-bold text-[#1A2B49]">Aktifkan Pengingat Otomatis</span>
                  <span class="text-[11px] text-slate-500">Scheduler akan berjalan di background untuk memicu pengiriman email.</span>
                </div>
                <div 
                  @click="form.abandoned_cart_reminder_enabled = !form.abandoned_cart_reminder_enabled"
                  class="relative inline-block w-12 h-7 cursor-pointer shrink-0 transition-all duration-300"
                >
                  <div 
                    class="w-12 h-7 rounded-full transition-colors duration-300" 
                    :class="form.abandoned_cart_reminder_enabled ? 'bg-indigo-600' : 'bg-slate-300'"
                  ></div>
                  <div 
                    class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" 
                    :class="form.abandoned_cart_reminder_enabled ? 'translate-x-5' : 'translate-x-0'"
                  ></div>
                </div>
              </div>

              <div v-if="form.abandoned_cart_reminder_enabled" class="flex flex-col gap-5 transition-all duration-300">
                
                <!-- DELAY SETTING -->
                <div class="flex flex-col gap-1.5">
                  <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <Clock :size="13" class="text-slate-400" />
                    Waktu Tunggu Pengiriman (Delay)
                  </label>
                  <div class="flex items-center gap-3">
                    <input 
                      v-model="form.abandoned_cart_reminder_delay" 
                      type="number" 
                      min="5"
                      class="w-32 border-2 border-slate-200 hover:border-indigo-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                      required
                    />
                    <span class="text-xs font-bold text-slate-555">Menit setelah halaman checkout ditinggalkan.</span>
                  </div>
                  <span class="text-[10px] text-slate-400">Direkomendasikan antara 30 hingga 60 menit. Minimal 5 menit.</span>
                </div>

                <!-- EMAIL SUBJECT -->
                <div class="flex flex-col gap-1.5">
                  <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Subjek Email</label>
                  <input 
                    v-model="form.abandoned_cart_email_subject" 
                    type="text" 
                    placeholder="Contoh: Ayo selesaikan pembelian kelas Anda!"
                    class="w-full border-2 border-slate-200 hover:border-indigo-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                    required
                  />
                </div>

                <!-- EMAIL BODY TEMPLATE -->
                <div class="flex flex-col gap-2">
                  <div class="flex items-center justify-between">
                    <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Isi Pesan Email (Template)</label>
                    
                    <button 
                      type="button"
                      @click="showPlaceholderHelp = !showPlaceholderHelp"
                      class="text-indigo-600 hover:text-indigo-800 text-xs font-bold flex items-center gap-1 cursor-pointer"
                    >
                      <HelpCircle :size="13" /> Placeholder Info
                    </button>
                  </div>

                  <!-- Placeholder Tag helper badge list -->
                  <div class="flex flex-wrap gap-2 bg-slate-50 border border-slate-100 p-3.5 rounded-2xl">
                    <div class="text-[10px] font-bold text-slate-450 uppercase tracking-wider w-full mb-1">Klik tag di bawah untuk menyisipkan ke dalam teks editor:</div>
                    <button 
                      type="button" 
                      @click="insertPlaceholder('{student_name}')"
                      class="px-2.5 py-1.5 bg-white border border-slate-200 hover:border-indigo-500 rounded-lg text-xs font-mono font-bold text-[#1A2B49] transition-all cursor-pointer"
                    >
                      {student_name}
                    </button>
                    <button 
                      type="button" 
                      @click="insertPlaceholder('{course_names}')"
                      class="px-2.5 py-1.5 bg-white border border-slate-200 hover:border-indigo-500 rounded-lg text-xs font-mono font-bold text-[#1A2B49] transition-all cursor-pointer"
                    >
                      {course_names}
                    </button>
                    <button 
                      type="button" 
                      @click="insertPlaceholder('{checkout_link}')"
                      class="px-2.5 py-1.5 bg-white border border-slate-200 hover:border-indigo-500 rounded-lg text-xs font-mono font-bold text-[#1A2B49] transition-all cursor-pointer"
                    >
                      {checkout_link}
                    </button>
                    <button 
                      type="button" 
                      @click="insertPlaceholder('{discount_amount}')"
                      class="px-2.5 py-1.5 bg-white border border-slate-200 hover:border-indigo-500 rounded-lg text-xs font-mono font-bold text-[#1A2B49] transition-all cursor-pointer"
                    >
                      {discount_amount}
                    </button>
                  </div>

                  <!-- Textarea Editor -->
                  <textarea 
                    v-model="form.abandoned_cart_email_body" 
                    rows="8"
                    placeholder="Tulis pesan pengingat..."
                    class="w-full border-2 border-slate-200 hover:border-indigo-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                    required
                  ></textarea>
                </div>
              </div>
            </div>

            <!-- SECTION 2: PAYMENT GATEWAY (MIDTRANS) -->
            <div class="bg-white rounded-[2.5rem] border border-slate-150 shadow-[0_8px_30px_rgb(0,0,0,0.015)] p-8 flex flex-col gap-6">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shrink-0">
                  <Settings :size="22" />
                </div>
                <div>
                  <h3 class="text-base font-extrabold text-[#1A2B49]">Midtrans Payment Gateway</h3>
                  <p class="text-xs text-slate-500 mt-1">Konfigurasi kredensial dan mode integrasi API Midtrans Snap untuk transaksi e-commerce LMS.</p>
                </div>
              </div>

              <div class="h-px bg-slate-100"></div>

              <!-- SANDBOX MODE TOGGLE -->
              <div class="flex items-center justify-between bg-slate-50/50 border border-slate-100 p-5 rounded-2xl">
                <div class="flex flex-col gap-0.5 max-w-[80%]">
                  <span class="text-xs font-bold text-[#1A2B49]">Aktifkan Sandbox Mode (Testing)</span>
                  <span class="text-[11px] text-slate-500">Gunakan lingkungan simulasi/testing Midtrans (tidak memproses uang sungguhan).</span>
                </div>
                <div 
                  @click="form.midtrans_sandbox_mode = !form.midtrans_sandbox_mode"
                  class="relative inline-block w-12 h-7 cursor-pointer shrink-0 transition-all duration-300"
                >
                  <div 
                    class="w-12 h-7 rounded-full transition-colors duration-300" 
                    :class="form.midtrans_sandbox_mode ? 'bg-blue-600' : 'bg-slate-300'"
                  ></div>
                  <div 
                    class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" 
                    :class="form.midtrans_sandbox_mode ? 'translate-x-5' : 'translate-x-0'"
                  ></div>
                </div>
              </div>

              <!-- KEYS -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Midtrans Client Key</label>
                  <input 
                    v-model="form.midtrans_client_key" 
                    type="text" 
                    placeholder="Contoh: SB-Mid-client-..."
                    class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                  />
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider">Midtrans Server Key</label>
                  <input 
                    v-model="form.midtrans_server_key" 
                    type="password" 
                    placeholder="Masukkan Server Key Anda"
                    class="w-full border-2 border-slate-200 hover:border-blue-500 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                  />
                </div>
              </div>

              <!-- AUTO COMPLETE ORDERS -->
              <div class="flex items-center justify-between bg-slate-50/50 border border-slate-100 p-5 rounded-2xl">
                <div class="flex flex-col gap-0.5 max-w-[80%]">
                  <span class="text-xs font-bold text-[#1A2B49]">Auto-complete Orders (Instant Activation)</span>
                  <span class="text-[11px] text-slate-500">Secara otomatis menyelesaikan pesanan dan mendaftarkan siswa ke kelas tanpa memverifikasi pembayaran Midtrans (Khusus mode simulasi cepat).</span>
                </div>
                <div 
                  @click="form.auto_complete_ecommerce_orders = !form.auto_complete_ecommerce_orders"
                  class="relative inline-block w-12 h-7 cursor-pointer shrink-0 transition-all duration-300"
                >
                  <div 
                    class="w-12 h-7 rounded-full transition-colors duration-300" 
                    :class="form.auto_complete_ecommerce_orders ? 'bg-blue-600' : 'bg-slate-300'"
                  ></div>
                  <div 
                    class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" 
                    :class="form.auto_complete_ecommerce_orders ? 'translate-x-5' : 'translate-x-0'"
                  ></div>
                </div>
              </div>
            </div>

            <!-- SAVE BUTTON -->
            <div class="flex items-center justify-end">
              <button 
                type="submit"
                :disabled="form.processing"
                class="bg-[#264790] text-white hover:bg-[#1f3a76] px-8 py-3 rounded-full font-bold text-xs shadow-md hover:shadow-lg transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <Save :size="14" /> Simpan Semua Pengaturan
              </button>
            </div>

          </div>

          <!-- RIGHT SIDE: LIVE EMAIL PREVIEW -->
          <div class="flex flex-col gap-6">
            
            <div class="bg-[#1A2B49] text-white rounded-[2.5rem] p-6 shadow-lg flex flex-col gap-2">
              <h4 class="text-xs font-black uppercase tracking-widest text-[#EBF3FF] opacity-90">Live Email Preview</h4>
              <p class="text-[11px] text-blue-200">Pratinjau tampilan email pemulihan yang akan diterima siswa di kotak masuk mereka secara real-time.</p>
            </div>

            <!-- Simulated Desktop Mail Client Frame -->
            <div class="bg-white border border-slate-150 rounded-[2.5rem] shadow-sm overflow-hidden flex flex-col">
              
              <!-- Mail Client Top Header Bar -->
              <div class="bg-slate-50 border-b border-slate-100 px-6 py-4 flex flex-col gap-1.5">
                <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                  <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                  <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                  <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                  <span class="ml-2">Kotak Masuk Email</span>
                </div>
                <div class="text-xs font-extrabold text-[#1A2B49] mt-2 truncate">
                  <span class="text-slate-400 font-semibold mr-1">Subjek:</span>
                  {{ form.abandoned_cart_email_subject || '(Belum ada subjek)' }}
                </div>
                <div class="text-[10px] text-slate-450 font-semibold">
                  <span class="text-slate-400">Dari:</span> Drastha Learning &lt;noreply@drastha.com&gt;
                </div>
              </div>

              <!-- Mail Client Content Body -->
              <div class="p-6 bg-slate-50/20 min-h-[300px] flex flex-col gap-4">
                
                <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                  
                  <!-- Email header banner -->
                  <div class="bg-gradient-to-r from-[#1A2B49] to-[#264790] px-6 py-6 text-center text-white">
                    <h3 class="text-base font-black tracking-wide uppercase">Drastha Learning</h3>
                    <p class="text-[9px] text-slate-300 uppercase tracking-widest mt-0.5">Ignite Your Potential</p>
                  </div>

                  <!-- Email content dynamic body -->
                  <div class="p-6 text-xs text-slate-700 leading-relaxed font-medium">
                    <div v-html="previewBody" class="prose max-w-none text-slate-600"></div>
                  </div>

                  <!-- Email footer -->
                  <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 text-center text-[9px] text-slate-400 font-bold">
                    &copy; 2026 Drastha Learning. All rights reserved.
                  </div>

                </div>

              </div>

            </div>

          </div>

        </div>

      </form>
      
    </div>

    <!-- PLACEHOLDER EXPLANATION DIALOG -->
    <div v-if="showPlaceholderHelp" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
      <div class="bg-white rounded-[2rem] max-w-md w-full shadow-2xl relative border border-slate-100 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <h3 class="text-base font-extrabold text-[#1A2B49]">
            Daftar Tag Placeholder
          </h3>
          <button @click="showPlaceholderHelp = false" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer">
            <X :size="18" />
          </button>
        </div>
        <div class="p-6 flex flex-col gap-4 text-xs font-semibold text-slate-600">
          <p>Gunakan tag berikut untuk menyisipkan variabel data secara dinamis ke dalam email pengingat recovery:</p>
          <div class="flex flex-col gap-3">
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <span class="font-mono font-extrabold text-[#264790] text-sm">{student_name}</span>
              <p class="text-[11px] text-slate-500 mt-1">Akan digantikan oleh nama lengkap siswa yang terdaftar.</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <span class="font-mono font-extrabold text-[#264790] text-sm">{course_names}</span>
              <p class="text-[11px] text-slate-500 mt-1">Akan digantikan oleh daftar judul kelas yang ditinggalkan di checkout.</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <span class="font-mono font-extrabold text-[#264790] text-sm">{checkout_link}</span>
              <p class="text-[11px] text-slate-500 mt-1">Akan menghasilkan tombol/link pemulihan checkout yang langsung memulihkan keranjang belanja siswa.</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
              <span class="font-mono font-extrabold text-[#264790] text-sm">{discount_amount}</span>
              <p class="text-[11px] text-slate-500 mt-1">Akan digantikan oleh nominal diskon kupon yang digunakan (jika ada).</p>
            </div>
          </div>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
          <button 
            @click="showPlaceholderHelp = false"
            class="bg-[#264790] text-white px-5 py-2 rounded-full font-bold text-xs shadow-sm hover:shadow transition-all cursor-pointer"
          >
            Mengerti
          </button>
        </div>
      </div>
    </div>
  </DashboardWrapper>
</template>
