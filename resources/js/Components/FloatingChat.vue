<script setup>
import { ref, nextTick } from 'vue';
import { Sparkles, Phone, X, Send, ChevronLeft, ChevronDown, ChevronUp } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';

const isOpen = ref(false);
const currentScreen = ref('selection'); // 'selection' or 'ai_chat'
const isCsExpanded = ref(false);
const messages = ref([
  {
    sender: 'ai',
    text: 'Halo! Saya adalah AI Admin Drastha Learning. Ada yang bisa saya bantu hari ini mengenai platform kami?'
  }
]);
const userInput = ref('');
const isTyping = ref(false);

const quickQuestions = [
  'Bagaimana cara mendaftar kelas?',
  'Metode pembayaran apa saja?',
  'Bagaimana cara klaim sertifikat?',
  'Apakah lisensi berlaku selamanya?'
];

// Offline fallback template replies database
const templateReplies = {
  default: "Maaf, saya tidak mengerti pertanyaan Anda. Apakah Anda ingin menanyakan tentang cara membeli kelas, metode pembayaran, sertifikat, atau jam operasional Customer Service?",
  greeting: "Halo! Saya adalah AI Admin Drastha Learning. Ada yang bisa saya bantu mengenai platform pembelajaran kami?",
  pembelian: "Untuk membeli kelas, silakan navigasikan ke menu 'Pilihan Kelas', pilih kelas yang Anda inginkan, klik 'Beli Sekarang' atau 'Tambah ke Keranjang', lalu lakukan checkout dan pembayaran melalui metode yang tersedia.",
  pembayaran: "Drastha Learning mendukung berbagai metode pembayaran otomatis, termasuk Virtual Account (VA), Transfer Bank, e-Wallet (GoPay, OVO, Dana), dan Kartu Kredit.",
  sertifikat: "Sertifikat kelulusan dapat Anda unduh setelah menyelesaikan seluruh materi kelas, lulus kuis akhir, dan mengirim tugas jika diwajibkan. Anda dapat mengunduhnya di tab 'Sertifikat' di halaman detail kursus Anda.",
  operasional: "Customer Service kami beroperasi pada hari Senin - Sabtu pukul 08:00 - 17:00 WIB. Anda dapat menghubungi mereka secara langsung melalui tombol WhatsApp atau Email di menu Layanan Support.",
  durasi: "Masa aktif akses kelas bergantung pada paket yang dibeli (misalnya 1 bulan, 3 bulan, 6 bulan, atau Akses Selamanya/Lifetime) seperti yang tertera pada bagian detail kelas."
};

const getTemplateAnswer = (userMessage) => {
  const msg = userMessage.toLowerCase();
  if (msg.includes("halo") || msg.includes("hi") || msg.includes("pagi") || msg.includes("siang") || msg.includes("sore")) {
    return templateReplies.greeting;
  }
  if (msg.includes("beli") || msg.includes("daftar kelas") || msg.includes("cara mendaftar") || msg.includes("membeli")) {
    return templateReplies.pembelian;
  }
  if (msg.includes("bayar") || msg.includes("metode") || msg.includes("rekening") || msg.includes("transfer")) {
    return templateReplies.pembayaran;
  }
  if (msg.includes("sertifikat") || msg.includes("klaim") || msg.includes("unduh sertifikat")) {
    return templateReplies.sertifikat;
  }
  if (msg.includes("jam") || msg.includes("operasional") || msg.includes("cs") || msg.includes("customer service") || msg.includes("buka")) {
    return templateReplies.operasional;
  }
  if (msg.includes("durasi") || msg.includes("selamanya") || msg.includes("masa aktif") || msg.includes("lisensi")) {
    return templateReplies.durasi;
  }
  return templateReplies.default;
};

// Main chat service handler
const callGeminiAPI = async (promptText) => {
  const apiKey = window.GEMINI_API_KEY || usePage().props.gemini_api_key || '';
  if (!apiKey) {
    // Safe offline fallback
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve(getTemplateAnswer(promptText));
      }, 1000);
    });
  }

  try {
    const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        contents: [
          {
            role: 'user',
            parts: [
              { text: promptText }
            ]
          }
        ],
        systemInstruction: {
          parts: [
            {
              text: `Anda adalah Asisten Virtual Support khusus untuk platform Drastha Learning. Tugas utama Anda adalah membantu calon peserta kursus yang tertarik untuk membeli kelas/kursus di platform kami.

PERATURAN SANGAT KETAT:
1. JAWAB HANYA pertanyaan seputar Drastha Learning (kelas yang ditawarkan, cara pendaftaran/pembelian, harga, metode pembayaran, sertifikat kelulusan, masa aktif kelas, dan jam operasional CS).
2. Jika pengguna meminta Anda menyelesaikan/membuatkan kode program, debugging, menjelaskan konsep pemrograman teknis secara mendalam (misal meminta Anda memecahkan algoritma atau membuat script kode), atau menanyakan topik di luar pembelian kursus di Drastha Learning, Anda WAJIB MENOLAK secara halus. Contoh penolakan: 'Maaf, sebagai asisten support Drastha Learning, saya hanya dapat membantu menjawab pertanyaan seputar platform, informasi kelas, dan pembelian kursus kami. Untuk pertanyaan teknis/pemrograman, silakan diskusikan di forum diskusi kelas setelah Anda bergabung.'
3. Bersikaplah ramah, santun, profesional, dan gunakan bahasa Indonesia yang baik. Jawab secara padat dan jelas untuk menghemat token.`
            }
          ]
        },
        generationConfig: {
          maxOutputTokens: 250,
          temperature: 0.7
        }
      })
    });

    const data = await response.json();
    if (data.candidates && data.candidates[0] && data.candidates[0].content && data.candidates[0].content.parts[0]) {
      return data.candidates[0].content.parts[0].text;
    } else {
      console.warn("Format respon Gemini tidak terduga, menggunakan jawaban template.");
      return getTemplateAnswer(promptText);
    }
  } catch (error) {
    console.error("Gagal memanggil Gemini API:", error);
    return getTemplateAnswer(promptText);
  }
};

const handleSend = async () => {
  if (!userInput.value.trim()) return;

  const userText = userInput.value;
  messages.value.push({
    sender: 'user',
    text: userText
  });
  userInput.value = '';

  await nextTick();
  scrollToBottom();

  isTyping.value = true;
  const aiAnswer = await callGeminiAPI(userText);
  isTyping.value = false;

  messages.value.push({
    sender: 'ai',
    text: aiAnswer
  });

  await nextTick();
  scrollToBottom();
};

const sendQuickQuestion = (question) => {
  userInput.value = question;
  handleSend();
};

const messageContainerRef = ref(null);
const scrollToBottom = () => {
  if (messageContainerRef.value) {
    messageContainerRef.value.scrollTop = messageContainerRef.value.scrollHeight;
  }
};

const toggleOpen = () => {
  isOpen.value = !isOpen.value;
  if (!isOpen.value) {
    currentScreen.value = 'selection';
    isCsExpanded.value = false;
  }
};

const toggleCs = () => {
  console.log('toggleCs called! Previous state:', isCsExpanded.value);
  isCsExpanded.value = !isCsExpanded.value;
  console.log('toggleCs complete! New state:', isCsExpanded.value);
};
</script>

<template>
  <div>
    <!-- Floating Chat Trigger Button (Teal Green Oval/Round matching Picture 1) -->
    <button 
      @click="toggleOpen"
      class="fixed bottom-24 md:bottom-8 right-6 z-[999] bg-[#1A2B49] hover:bg-[#264790] text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 outline-none"
    >
      <Sparkles v-if="!isOpen" :size="24" class="text-white" />
      <X v-else :size="24" class="text-white" />
    </button>

    <!-- Backdrop Overlay -->
    <Transition name="fade">
      <div 
        v-if="isOpen" 
        @click="toggleOpen" 
        class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs z-[9990]"
      ></div>
    </Transition>

    <!-- Right Sidebar Drawer (matching Picture 2 Layout & colors) -->
    <Transition name="drawer">
      <div 
        v-if="isOpen" 
        class="fixed right-0 top-0 bottom-0 w-full max-w-md bg-white shadow-2xl z-[9995] flex flex-col border-l border-slate-100 font-sans"
      >
        <!-- SCREEN 1: Selection Screen (Pilih Layanan Support) -->
        <div v-if="currentScreen === 'selection'" class="flex flex-col h-full">
          <!-- Header -->
          <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2 text-[#1A2B49]">
              <Sparkles :size="20" />
              <span class="font-extrabold text-sm text-[#1A2B49] tracking-tight">Pilih Layanan Support</span>
            </div>
            <button @click="toggleOpen" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition-colors">
              <X :size="16" />
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 px-8 py-10 flex flex-col items-center">
            <!-- Header Text -->
            <h3 class="text-xl font-bold text-slate-800 mb-2">Pilih Layanan Support</h3>
            <p class="text-xs text-slate-400 font-semibold mb-10 text-center">Pilih cara untuk menghubungi kami</p>

            <div class="w-full flex flex-col gap-5">
              <!-- AI Assistant Card -->
              <div 
                @click="currentScreen = 'ai_chat'"
                class="border border-slate-150 rounded-2xl p-5 hover:border-[#2D7D62] hover:bg-slate-50/30 transition-all cursor-pointer flex items-center justify-between group"
              >
                <div class="flex items-start gap-4">
                  <!-- Icon Container -->
                  <div class="w-12 h-12 rounded-full bg-emerald-50 text-[#2D7D62] flex items-center justify-center shrink-0">
                    <Sparkles :size="20" />
                  </div>
                  <!-- Content info -->
                  <div class="flex flex-col">
                    <div class="flex items-center gap-2.5">
                      <span class="font-extrabold text-sm text-[#1A2B49]">AI Assistant</span>
                      <span class="bg-emerald-100 text-[#2D7D62] text-[10px] font-bold px-2 py-0.5 rounded-full">24 Jam</span>
                    </div>
                    <span class="text-xs text-slate-400 font-semibold mt-1">Tersedia 24 jam untuk membantu Anda</span>
                  </div>
                </div>
              </div>

              <!-- Customer Service Card -->
              <div 
                class="border border-slate-150 rounded-2xl p-5 hover:border-[#2D7D62] hover:bg-slate-50/30 transition-all cursor-pointer flex flex-col"
                @click="toggleCs"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-start gap-4">
                    <!-- Icon Container -->
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center shrink-0">
                      <Phone :size="20" />
                    </div>
                    <!-- Content info -->
                    <div class="flex flex-col text-left">
                      <div class="flex items-center gap-2.5">
                        <span class="font-extrabold text-sm text-[#1A2B49]">Customer Service</span>
                        <span class="bg-orange-100 text-orange-600 text-[10px] font-bold px-2 py-0.5 rounded-full">Jam Kerja</span>
                      </div>
                      <span class="text-xs text-slate-400 font-semibold mt-1">Jam Kerja: Senin - Sabtu, 08:00 - 17:00 WIB</span>
                    </div>
                  </div>
                  <!-- Expand Indicator -->
                  <div class="text-slate-400">
                    <ChevronDown v-if="!isCsExpanded" :size="20" />
                    <ChevronUp v-else :size="20" />
                  </div>
                </div>

                <!-- CS Dropdown details -->
                <div v-if="isCsExpanded" @click.stop class="mt-5 pt-4 border-t border-slate-100 flex flex-col gap-3">
                  <p class="text-slate-500 text-xs font-semibold leading-relaxed text-left">
                    Anda dapat berkonsultasi secara langsung mengenai pendaftaran kelas atau kendala transaksi dengan Customer Service kami melalui saluran di bawah ini:
                  </p>
                  <div class="flex flex-col sm:flex-row gap-2.5 mt-2">
                    <!-- WhatsApp support button -->
                    <a 
                      href="https://wa.me/6281234859768?text=Halo%20Customer%20Service%20Drastha%20Learning" 
                      target="_blank"
                      class="flex-1 py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-center text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-2"
                    >
                      <Phone :size="14" /> WhatsApp
                    </a>
                    <!-- Email support button -->
                    <a 
                      href="mailto:support@drastha.com"
                      class="flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-center text-xs font-bold transition-all flex items-center justify-center gap-2"
                    >
                      Email Support
                    </a>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- SCREEN 2: AI Assistant Chat Interface -->
        <div v-else class="flex flex-col h-full bg-slate-50/30">
          <!-- Header -->
          <div class="px-5 py-4.5 border-b border-slate-100 flex items-center justify-between bg-white shadow-xs">
            <div class="flex items-center gap-3">
              <!-- Back button -->
              <button 
                @click="currentScreen = 'selection'"
                class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-500 hover:text-slate-700 flex items-center justify-center transition-colors"
              >
                <ChevronLeft :size="20" />
              </button>
              <div class="flex flex-col">
                <span class="font-extrabold text-sm text-[#1A2B49] leading-tight">AI Assistant</span>
                <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-1 mt-0.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span> Online (24 Jam)
                </span>
              </div>
            </div>
            <button @click="toggleOpen" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition-colors">
              <X :size="16" />
            </button>
          </div>

          <!-- Messages display area -->
          <div 
            ref="messageContainerRef"
            class="flex-1 overflow-y-auto px-5 py-6 flex flex-col gap-4.5"
          >
            <div 
              v-for="(msg, idx) in messages" 
              :key="idx"
              class="flex flex-col"
              :class="msg.sender === 'user' ? 'items-end' : 'items-start'"
            >
              <!-- Avatar or name info above bubble -->
              <span class="text-[9px] font-bold text-slate-400 mb-1 px-1">
                {{ msg.sender === 'user' ? 'Anda' : 'AI Admin' }}
              </span>

              <!-- Bubble body -->
              <div 
                class="max-w-[85%] rounded-2xl px-4 py-3 text-xs leading-relaxed"
                :class="msg.sender === 'user' 
                  ? 'bg-[#2D7D62] text-white rounded-tr-xs' 
                  : 'bg-white border border-slate-150 text-slate-700 rounded-tl-xs shadow-xs'"
              >
                {{ msg.text }}
              </div>
            </div>

            <!-- Typing indicator -->
            <div v-if="isTyping" class="flex flex-col items-start">
              <span class="text-[9px] font-bold text-slate-400 mb-1 px-1">AI Admin</span>
              <div class="bg-white border border-slate-150 rounded-2xl rounded-tl-xs px-4 py-3 shadow-xs flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.4s]"></span>
              </div>
            </div>
          </div>

          <!-- Quick Template Questions -->
          <div class="px-4 py-3 bg-white border-t border-slate-100/60 flex gap-2 overflow-x-auto select-none no-scrollbar">
            <button 
              v-for="q in quickQuestions" 
              :key="q"
              @click="sendQuickQuestion(q)"
              class="shrink-0 bg-slate-50 border border-slate-150 hover:border-[#2D7D62] hover:bg-slate-50/20 text-[10px] font-bold text-[#1A2B49]/80 hover:text-[#2D7D62] px-3.5 py-2 rounded-full transition-all"
            >
              {{ q }}
            </button>
          </div>

          <!-- Input Area -->
          <div class="p-4 bg-white border-t border-slate-100 flex items-center gap-3">
            <input 
              v-model="userInput" 
              type="text" 
              placeholder="Tulis pesan Anda..." 
              @keyup.enter="handleSend"
              class="flex-1 bg-slate-50 border border-slate-150 rounded-2xl px-4 py-3 text-xs outline-none focus:border-[#2D7D62] focus:bg-white transition-all font-semibold text-slate-700 placeholder:text-slate-400"
            />
            <button 
              @click="handleSend"
              class="w-10 h-10 rounded-2xl bg-[#2D7D62] hover:bg-[#205C47] text-white flex items-center justify-center shadow-md shadow-[#2D7D62]/10 transition-all outline-none"
            >
              <Send :size="16" />
            </button>
          </div>
        </div>

      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* Transisi backdrop fade */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Transisi drawer slide right-to-left */
.drawer-enter-active, .drawer-leave-active {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
}
.drawer-enter-from, .drawer-leave-to {
  transform: translateX(100%);
  opacity: 0.9;
}

/* Transisi dropdown CS */
.expand-enter-active, .expand-leave-active {
  transition: max-height 0.3s ease, opacity 0.3s ease;
  max-height: 200px;
  overflow: hidden;
}
.expand-enter-from, .expand-leave-to {
  max-height: 0;
  opacity: 0;
}

/* Hide horizontal scrollbar but keep scrolling */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
