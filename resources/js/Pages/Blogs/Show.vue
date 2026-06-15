<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Calendar, User as UserIcon, ArrowLeft, Clock, Instagram, Twitter, Facebook, Linkedin, Zap, BookOpen, AlertTriangle } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
  blog: Object,
  relatedBlogs: {
    type: Array,
    default: () => []
  }
});

// Settings template
const blogTemplate = computed(() => usePage().props.settings?.blog_template || '1');

// Newsletter subscription states
const subscribeEmail = ref('');
const subscribeSuccess = ref(false);

const handleSubscribe = () => {
  if (subscribeEmail.value) {
    subscribeSuccess.value = true;
    subscribeEmail.value = '';
    setTimeout(() => {
      subscribeSuccess.value = false;
    }, 5000);
  }
};

// Dynamic Table of Contents (ToC) generator
const toc = computed(() => {
  const headings = [];
  if (!props.blog?.content) return headings;

  // Scan for H2 and H3 tags in HTML content
  const regex = /<h[23][^>]*>(.*?)<\/h[23]>/gi;
  let match;
  let index = 1;
  while ((match = regex.exec(props.blog.content)) !== null) {
    const text = match[1].replace(/<[^>]*>/g, '').trim();
    if (text) {
      headings.push({ id: `heading-${index++}`, text });
    }
  }

  // Fallback for plain-text contents
  if (headings.length === 0) {
    const lines = props.blog.content.split('\n');
    for (const line of lines) {
      const cleanLine = line.trim();
      if (cleanLine.length > 3 && cleanLine.length < 65 && (cleanLine.endsWith(':') || cleanLine.match(/^[A-Z]/))) {
        headings.push({ id: `heading-${index++}`, text: cleanLine.replace(/^[0-9.-]+\s*/, '') });
      }
      if (headings.length >= 6) break;
    }
  }

  // Default fallback if no clear sections
  if (headings.length === 0) {
    headings.push({ id: 'heading-1', text: 'Pendahuluan' });
    headings.push({ id: 'heading-2', text: 'Topik Utama' });
    headings.push({ id: 'heading-3', text: 'Kesimpulan' });
  }

  return headings;
});

// Process content to inject ID attributes into H2/H3 elements for direct anchoring
const processedContent = computed(() => {
  if (!props.blog?.content) return '';
  
  let content = props.blog.content;
  let index = 1;
  
  content = content.replace(/<(h[23])([^>]*)>/gi, (match, tag, attrs) => {
    if (!attrs.includes('id=')) {
      return `<${tag} id="heading-${index++}" ${attrs}>`;
    }
    return match;
  });
  
  return content;
});

// Check if content is HTML
const isHtmlContent = computed(() => {
  return /<[a-z][\s\S]*>/i.test(props.blog?.content || '');
});

// Smooth scroll to ToC anchors
const scrollToHeading = (id) => {
  const element = document.getElementById(id);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

// Merge real database related blogs with mockup fallbacks to guarantee 4 cards
const displayRelatedBlogs = computed(() => {
  const blogs = [...(props.relatedBlogs || [])];
  
  const fallbackBlogs = [
    {
      id: 'mock-1',
      title: '5 Skills for the Future Workplace',
      image: '/images/pages/login_or_signup/bg-login-signup.png',
      category: 'Karir',
      slug: '5-skills-for-the-future-workplace'
    },
    {
      id: 'mock-2',
      title: 'Understanding LMS (Learning Management System)',
      image: '/images/pages/login_or_signup/bg-login-signup.png',
      category: 'Teknologi',
      slug: 'understanding-lms'
    },
    {
      id: 'mock-3',
      title: 'Understanding Digital Centre Workplace',
      image: '/images/pages/login_or_signup/bg-login-signup.png',
      category: 'Edukasi',
      slug: 'understanding-digital-centre-workplace'
    },
    {
      id: 'mock-4',
      title: 'Python Class and Technologies in Python',
      image: '/images/pages/login_or_signup/bg-login-signup.png',
      category: 'Programming',
      slug: 'python-class-and-technologies-in-python'
    }
  ];

  while (blogs.length < 4) {
    const nextFallback = fallbackBlogs[blogs.length];
    if (!nextFallback) break;
    blogs.push(nextFallback);
  }

  return blogs.slice(0, 4);
});
</script>

<template>
  <Head :title="`${blog.title} | Blog | Drastha Learning`" />

  <GuestLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 font-montserrat">
      
      <!-- BREADCRUMBS (Consistent) -->
      <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-8 select-none">
        <Link href="/" class="hover:text-[#44A6D9] transition-colors">Home</Link>
        <span class="text-slate-300">/</span>
        <Link href="/blogs" class="hover:text-[#44A6D9] transition-colors">Blog</Link>
        <span class="text-slate-300">/</span>
        <span class="text-slate-600 truncate max-w-[200px] sm:max-w-none">{{ blog.title }}</span>
      </nav>

      <!-- ========================================================= -->
      <!-- TEMPLATE 1: CLEAN MINIMALIST (DEFAULT)                    -->
      <!-- ========================================================= -->
      <div v-if="blogTemplate === '1'">
        <!-- Hero Header -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center mb-16">
          <div class="lg:col-span-7 flex flex-col gap-4">
            <span class="self-start bg-[#F9CC6B] text-[#1A2B49] text-xs font-extrabold px-3.5 py-1.5 rounded-lg uppercase tracking-wider shadow-sm select-none">
              {{ blog.category || 'Pendidikan' }}
            </span>
            <h1 class="text-2xl sm:text-5xl font-black text-[#1A2B49] leading-tight tracking-tight">
              {{ blog.title }}
            </h1>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold text-slate-400 mt-2 select-none">
              <div class="flex items-center gap-1.5">
                <UserIcon :size="14" class="text-[#264790]" />
                <span>Published by <span class="text-[#1A2B49]">{{ blog.author_name || blog.user?.name || 'Admin Drastha Learning' }}</span></span>
              </div>
              <span class="text-slate-200 hidden sm:inline">|</span>
              <div class="flex items-center gap-1.5">
                <Calendar :size="14" class="text-[#264790]" />
                <span>Updated on {{ new Date(blog.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}</span>
              </div>
            </div>
          </div>
          <div class="lg:col-span-5 h-[280px] sm:h-[350px] w-full rounded-[2.5rem] overflow-hidden shadow-lg border border-slate-100 bg-slate-50 relative select-none">
            <img 
              :src="blog.image || '/images/pages/login_or_signup/bg-login-signup.png'" 
              class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" 
              alt="Hero Cover Image" 
            />
          </div>
        </div>

        <!-- Content Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
          <aside class="lg:col-span-4 flex flex-col gap-8 lg:sticky lg:top-24 select-none">
            <div class="bg-white rounded-[2rem] p-8 border border-slate-100/80 shadow-[0_10px_35px_rgba(0,0,0,0.015)] flex flex-col gap-5">
              <h3 class="font-extrabold text-lg text-[#1A2B49] leading-snug">Subscribe to the Drastha Learning Newsletter</h3>
              <p class="text-xs font-semibold text-slate-400 leading-relaxed">Dapatkan pembaruan berita dan materi terbaru langsung di inbox Anda.</p>
              <form @submit.prevent="handleSubscribe" class="flex flex-col gap-3">
                <input type="email" v-model="subscribeEmail" placeholder="Email" required class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-xs font-bold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all text-slate-700 placeholder-slate-400" />
                <button type="submit" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-extrabold text-xs shadow-md transition-colors uppercase tracking-wider">Subscribe</button>
              </form>
              <transition name="fade"><p v-if="subscribeSuccess" class="text-xs font-bold text-emerald-600">Terima kasih! Anda berhasil mendaftar buletin.</p></transition>
            </div>
            <div class="bg-white rounded-[2rem] p-8 border border-slate-100/80 shadow-[0_10px_35px_rgba(0,0,0,0.015)] flex flex-col gap-5">
              <h3 class="font-extrabold text-lg text-[#1A2B49] border-b border-slate-50 pb-2">Daftar Isi</h3>
              <nav class="flex flex-col gap-3.5">
                <a v-for="(item, idx) in toc" :key="item.id" :href="`#${item.id}`" @click.prevent="scrollToHeading(item.id)" class="text-xs font-bold text-slate-500 hover:text-[#44A6D9] flex gap-2.5 items-start transition-colors leading-relaxed group">
                  <span class="text-[#264790] shrink-0 font-extrabold group-hover:text-[#44A6D9]">{{ idx + 1 }}.</span>
                  <span>{{ item.text }}</span>
                </a>
              </nav>
            </div>
          </aside>
          <main class="lg:col-span-8 bg-white rounded-[2.5rem] p-8 sm:p-14 border border-slate-100/80 shadow-[0_12px_45px_rgba(0,0,0,0.01)]">
            <div :class="['prose prose-slate max-w-none text-[#1A2B49] font-medium text-xs sm:text-base leading-relaxed', isHtmlContent ? '' : 'whitespace-pre-line']" v-html="processedContent"></div>
          </main>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- TEMPLATE 2: POLAROID COLLAGE (CREATIVE)                   -->
      <!-- ========================================================= -->
      <div v-else-if="blogTemplate === '2'" class="creative-polaroid-layout">
        <!-- Huge Creative Title Header -->
        <div class="flex flex-col items-center text-center max-w-3xl mx-auto mb-16 relative">
          <div class="absolute -top-10 -left-10 text-orange-200/50 text-[120px] font-serif select-none pointer-events-none">“</div>
          <span class="bg-orange-100 text-orange-700 text-[10px] font-black px-4 py-2 rounded-full uppercase tracking-widest border border-orange-200 mb-6 shadow-sm">
            {{ blog.category || 'Pendidikan' }}
          </span>
          <h1 class="text-3xl sm:text-5xl font-serif font-black text-[#1A2B49] leading-tight tracking-tight -rotate-1 hover:rotate-0 transition-transform duration-300">
            {{ blog.title }}
          </h1>
          <div class="flex items-center gap-3 mt-6 text-xs font-bold text-slate-500">
            <span class="bg-[#F9CC6B]/30 px-3 py-1 rounded text-[#1A2B49] font-serif italic">Oleh: {{ blog.author_name || blog.user?.name || 'Redaktur Drastha' }}</span>
            <span>•</span>
            <span>{{ new Date(blog.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}</span>
          </div>
        </div>

        <!-- Polaroid Cover Image (Centered & Tilted) -->
        <div class="flex justify-center mb-16">
          <div class="bg-white p-4.5 pb-14 rounded-sm border border-slate-100 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.1)] -rotate-2 hover:rotate-1 hover:scale-[1.02] transition-all duration-500 max-w-2xl w-full">
            <div class="aspect-[16/10] w-full overflow-hidden bg-slate-50">
              <img :src="blog.image || '/images/pages/login_or_signup/bg-login-signup.png'" class="w-full h-full object-cover" alt="Polaroid main cover" />
            </div>
            <div class="mt-6 text-center text-slate-400 font-handwriting text-base">
              {{ blog.title }} - {{ new Date(blog.created_at).toLocaleDateString('id-ID', {year: 'numeric'}) }}
            </div>
          </div>
        </div>

        <!-- Content & Sidebar Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
          
          <!-- Content Main Area -->
          <main class="lg:col-span-8 bg-white p-6 sm:p-12 border border-slate-100 rounded-3xl shadow-sm">
            <div :class="['prose prose-polaroid max-w-none text-[#1A2B49]', isHtmlContent ? '' : 'whitespace-pre-line']" v-html="processedContent"></div>
          </main>

          <!-- Sidebar (Warm Tilted Style) -->
          <aside class="lg:col-span-4 flex flex-col gap-10 lg:sticky lg:top-24 select-none">
            <!-- Newsletter Tilted Warning/Warm Color -->
            <div class="bg-amber-400 p-8 rounded-3xl shadow-lg rotate-1 hover:-rotate-1 transition-transform duration-300 flex flex-col gap-5 border-4 border-black text-black">
              <h3 class="font-serif font-black text-xl leading-snug">Langganan Buletin Drastha</h3>
              <p class="text-xs font-semibold leading-relaxed text-black/80">Dapatkan tips belajar eksklusif, tutorial, dan update kelas terbaru dari kami langsung ke e-mail Anda.</p>
              <form @submit.prevent="handleSubscribe" class="flex flex-col gap-3.5">
                <input type="email" v-model="subscribeEmail" placeholder="Masukkan Email Anda" required class="w-full bg-white border-2 border-black rounded-xl px-4 py-3.5 text-xs font-bold focus:outline-none text-slate-800 placeholder-slate-400 shadow-[3px_3px_0px_0px_#000]" />
                <button type="submit" class="w-full bg-[#1A2B49] text-white hover:bg-[#264790] py-3.5 rounded-xl font-black text-xs uppercase tracking-wider border-2 border-black shadow-[3px_3px_0px_0px_#000] active:translate-x-0.5 active:translate-y-0.5 transition-all">Subscribe Now</button>
              </form>
              <transition name="fade"><p v-if="subscribeSuccess" class="text-xs font-black text-[#1A2B49]">Berhasil didaftarkan!</p></transition>
            </div>

            <!-- Table of Contents -->
            <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm flex flex-col gap-5">
              <h3 class="font-serif font-extrabold text-lg text-[#1A2B49] border-b border-dashed border-slate-200 pb-3">Navigasi Artikel</h3>
              <nav class="flex flex-col gap-3">
                <a v-for="(item, idx) in toc" :key="item.id" :href="`#${item.id}`" @click.prevent="scrollToHeading(item.id)" class="text-xs font-bold text-slate-500 hover:text-orange-500 flex gap-2 items-start transition-colors leading-relaxed">
                  <span class="text-orange-400 font-serif font-black italic">{{ idx + 1 }}.</span>
                  <span>{{ item.text }}</span>
                </a>
              </nav>
            </div>
          </aside>

        </div>
      </div>

      <!-- ========================================================= -->
      <!-- TEMPLATE 3: CYBERPUNK ZEBRA (STRIPED RETRO)               -->
      <!-- ========================================================= -->
      <div v-else class="cyberpunk-zebra-layout">
        <!-- Top Zebra Line Header -->
        <div class="w-full h-5 warning-zebra rounded-xl border-2 border-black mb-10 shadow-[4px_4px_0px_0px_#000]"></div>

        <!-- Cyberpunk Header -->
        <div class="bg-black text-white border-4 border-black p-6 sm:p-10 rounded-3xl shadow-[8px_8px_0px_0px_#44A6D9] mb-12 flex flex-col gap-4">
          <div class="flex items-center gap-2">
            <Zap :size="16" class="text-yellow-400 fill-yellow-400 animate-pulse" />
            <span class="text-[10px] font-black tracking-widest text-[#44A6D9] uppercase">CYBER BLOG DATABASE //</span>
          </div>
          <h1 class="text-2xl sm:text-5xl font-black text-white uppercase leading-tight tracking-wider font-mono">
            {{ blog.title }}
          </h1>
          <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold text-yellow-400 mt-2 select-none font-mono">
            <span>BY // {{ (blog.author_name || blog.user?.name || 'SYSADMIN').toUpperCase() }}</span>
            <span>//</span>
            <span>STAMP // {{ new Date(blog.created_at).toLocaleDateString('id-ID') }}</span>
          </div>
        </div>

        <!-- Cover grid layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
          
          <!-- Main Content (Neon accents) -->
          <main class="lg:col-span-8 bg-white border-4 border-black p-6 sm:p-12 rounded-3xl shadow-[8px_8px_0px_0px_#000] relative overflow-hidden">
            <!-- Diagonal zebra sticker -->
            <div class="absolute -top-12 -right-12 w-28 h-28 warning-zebra border-2 border-black rotate-45 select-none pointer-events-none"></div>
            
            <div class="mb-10 rounded-2xl overflow-hidden border-2 border-black shadow-[4px_4px_0px_0px_#000] aspect-[16/9]">
              <img :src="blog.image || '/images/pages/login_or_signup/bg-login-signup.png'" class="w-full h-full object-cover" alt="Main cover" />
            </div>

            <div :class="['prose prose-cyber max-w-none text-[#1A2B49]', isHtmlContent ? '' : 'whitespace-pre-line']" v-html="processedContent"></div>
          </main>

          <!-- Sidebar (Techy Box layout) -->
          <aside class="lg:col-span-4 flex flex-col gap-8 lg:sticky lg:top-24 select-none">
            
            <!-- Newsletter: Ticket to Knowledge -->
            <div class="bg-[#44A6D9] border-4 border-black p-8 rounded-3xl shadow-[6px_6px_0px_0px_#000] text-black relative">
              <span class="absolute -top-3 left-4 bg-yellow-400 text-black border-2 border-black text-[9px] font-black px-2.5 py-1 uppercase tracking-widest shadow-sm">
                TICKET TO KNOWLEDGE
              </span>
              <h3 class="font-black text-lg mt-2 uppercase leading-snug">Subscribe Terminal</h3>
              <p class="text-xs font-bold leading-relaxed text-black/85 mt-2">Dapatkan info bootcamp online, event webinar, dan modul gratis langsung dari PT. Drastha Berkah Sentosa.</p>
              <form @submit.prevent="handleSubscribe" class="flex flex-col gap-3 mt-4">
                <input type="email" v-model="subscribeEmail" placeholder="Enter Email Address" required class="w-full bg-white border-2 border-black rounded-lg px-4 py-3 text-xs font-black focus:outline-none focus:bg-yellow-50 text-slate-800 placeholder-slate-500" />
                <button type="submit" class="w-full bg-black text-yellow-400 hover:bg-yellow-400 hover:text-black py-3 rounded-lg font-black text-xs uppercase tracking-widest border-2 border-black transition-colors">INITIATE SUBSCRIPTION</button>
              </form>
              <transition name="fade"><p v-if="subscribeSuccess" class="text-xs font-black mt-2 text-white bg-black px-2.5 py-1 rounded w-fit border border-yellow-400">STATUS: SUCCESS</p></transition>
            </div>

            <!-- Table of contents -->
            <div class="bg-white border-4 border-black p-8 rounded-3xl shadow-[6px_6px_0px_0px_#000] flex flex-col gap-5">
              <h3 class="font-black text-base text-black uppercase tracking-wider border-b-2 border-black pb-2 flex items-center gap-1.5">
                <Zap :size="14" class="text-yellow-400 fill-yellow-400" /> FILE DIRECTORY
              </h3>
              <nav class="flex flex-col gap-3 font-mono">
                <a v-for="(item, idx) in toc" :key="item.id" :href="`#${item.id}`" @click.prevent="scrollToHeading(item.id)" class="text-[11px] font-black text-slate-700 hover:text-blue-600 flex gap-2 items-start transition-colors leading-relaxed">
                  <span class="text-slate-400 font-bold">SEC_{{ idx + 1 }} //</span>
                  <span>{{ item.text.toUpperCase() }}</span>
                </a>
              </nav>
            </div>

          </aside>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- RELATED ARTICLES SECTION                                  -->
      <!-- ========================================================= -->
      <div class="mt-24 border-t border-slate-100 pt-16">
        <h2 class="text-2xl sm:text-3xl font-black text-[#1A2B49] mb-10 tracking-tight" :class="blogTemplate === '2' ? 'font-serif' : (blogTemplate === '3' ? 'font-mono uppercase tracking-wider' : '')">
          {{ blogTemplate === '3' ? 'Related Archives //' : 'Related Articles' }}
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
          <div 
            v-for="item in displayRelatedBlogs" 
            :key="item.id"
            :class="[
              blogTemplate === '2' 
                ? 'bg-white p-3 pb-8 rounded-none border border-slate-200 shadow-lg -rotate-1 hover:rotate-0 hover:scale-105 transition-all duration-300'
                : (blogTemplate === '3'
                  ? 'bg-white border-4 border-black rounded-none shadow-[4px_4px_0px_0px_#000] hover:shadow-[6px_6px_0px_0px_#44A6D9] transition-all'
                  : 'bg-white rounded-3xl overflow-hidden border border-slate-100/80 shadow-[0_10px_35px_rgba(0,0,0,0.01)] hover:shadow-[0_15px_45px_rgba(0,0,0,0.025)] hover:-translate-y-1 transition-all duration-300')
            ]"
            class="flex flex-col h-full group"
          >
            <!-- Related Image Cover -->
            <div 
              :class="[
                blogTemplate === '2' ? 'aspect-[4/3] w-full border border-slate-100' : 'aspect-[4/3] w-full relative bg-slate-100 shrink-0 select-none overflow-hidden'
              ]"
            >
              <img 
                :src="item.image || '/images/pages/login_or_signup/bg-login-signup.png'" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                alt="Related Article Cover" 
              />
              <span 
                v-if="blogTemplate !== '2'" 
                :class="[
                  blogTemplate === '3' 
                    ? 'absolute top-2 left-2 bg-black text-yellow-400 text-[8px] font-black px-2 py-1 uppercase tracking-widest border border-black'
                    : 'absolute top-4 left-4 bg-white/95 backdrop-blur-sm text-[#264790] text-[10px] font-black px-2.5 py-1.5 rounded-lg uppercase tracking-wider shadow-sm'
                ]"
              >
                {{ item.category || 'Pendidikan' }}
              </span>
            </div>
            
            <!-- Related Body details -->
            <div 
              :class="[
                blogTemplate === '2' ? 'p-3 pt-4 text-center' : 'p-6',
                'flex flex-col justify-between flex-grow gap-5'
              ]"
            >
              <h3 
                :class="[
                  blogTemplate === '2' ? 'font-serif text-sm' : 'text-sm sm:text-base font-extrabold',
                  blogTemplate === '3' ? 'font-mono uppercase text-slate-800' : 'text-[#1A2B49]',
                  'line-clamp-2 group-hover:text-blue-500 transition-colors leading-snug'
                ]"
              >
                {{ item.title }}
              </h3>
              
              <Link 
                :href="item.slug.startsWith('mock-') ? '/blogs' : `/blogs/${item.slug}`"
                :class="[
                  blogTemplate === '2' 
                    ? 'font-serif text-xs italic text-orange-600 underline'
                    : (blogTemplate === '3'
                      ? 'inline-block bg-black text-yellow-400 text-center font-mono text-[10px] font-black py-2 border-2 border-black shadow-[2px_2px_0px_0px_#000] active:translate-x-0.5 active:translate-y-0.5'
                      : 'inline-block bg-[#264790] hover:bg-[#44A6D9] text-white px-5 py-3 rounded-xl text-xs font-extrabold transition-all w-max shadow-sm hover:shadow')
                ]"
              >
                {{ blogTemplate === '2' ? 'Baca Selengkapnya' : (blogTemplate === '3' ? 'READ_ARCHIVE.EXE' : 'Read More') }}
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================= -->
      <!-- CTA LEVEL UP BANNER (Consistent but with style variations)-->
      <!-- ========================================================= -->
      <div 
        :class="[
          blogTemplate === '3'
            ? 'bg-black text-white border-4 border-black p-10 sm:p-14 shadow-[10px_10px_0px_0px_#44A6D9] text-center'
            : 'bg-gradient-to-r from-[#264790] to-[#44A6D9] rounded-[2.5rem] p-10 sm:p-16 text-center shadow-lg border border-white/5'
        ]"
        class="mt-20 relative overflow-hidden select-none"
      >
        <div v-if="blogTemplate !== '3'" class="absolute -top-12 -left-12 w-48 h-48 rounded-full bg-white/5 blur-xl"></div>
        <div v-if="blogTemplate !== '3'" class="absolute -bottom-12 -right-12 w-64 h-64 rounded-full bg-[#F9CC6B]/10 blur-2xl"></div>

        <h2 
          :class="[
            blogTemplate === '3' ? 'font-mono uppercase tracking-wider text-2xl sm:text-4xl' : 'text-3xl sm:text-4xl font-black',
            'text-white mb-6 relative z-10 tracking-tight'
          ]"
        >
          {{ blogTemplate === '3' ? 'CRITICAL_INIT: Level up your skills now!' : 'Ready to Level up your skills?' }}
        </h2>
        
        <Link 
          href="/courses" 
          :class="[
            blogTemplate === '3'
              ? 'inline-block bg-yellow-400 hover:bg-white text-black font-black font-mono text-xs sm:text-sm px-10 py-4.5 border-4 border-black shadow-[4px_4px_0px_0px_#44A6D9] active:translate-x-0.5 active:translate-y-0.5'
              : 'inline-block bg-[#F9CC6B] hover:bg-[#1A2B49] text-[#1A2B49] hover:text-white font-extrabold text-xs sm:text-sm px-10 py-4.5 rounded-full shadow-md hover:shadow-lg hover:scale-105 transition-all relative z-10 uppercase tracking-wider'
          ]"
        >
          Mulai Belajar Sekarang
        </Link>
      </div>

    </div>

    <!-- ========================================================= -->
    <!-- DEFAULT FOOTER SECTION (REQUIRED TO BE EXACTLY DEFAULT)   -->
    <!-- ========================================================= -->
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-32 md:pb-12 mt-20 select-none">
      <div class="bg-[#FFFFFF] rounded-[2.5rem] p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-50">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-8 mb-12 text-left">
          
          <div class="md:col-span-5 flex flex-col gap-6">
            <div class="flex items-center gap-2">
              <ApplicationLogo />
            </div>
            <p class="text-[#264790] text-sm md:text-base font-medium leading-relaxed max-w-md">
              Platform Learning Management System (LMS) yang dirancang untuk mendukung pembelajaran modern, interaktif, dan berkelanjutan.
            </p>
            <div class="flex items-center gap-4">
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Instagram :size="20" :stroke-width="2.5" />
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Twitter :size="20" :stroke-width="2.5" />
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Facebook :size="20" :stroke-width="2.5" />
              </a>
              <a href="#" class="text-[#264790] hover:text-[#44A6D9] transition-colors p-1 border-[1.5px] border-[#264790] hover:border-[#44A6D9] rounded-lg">
                <Linkedin :size="20" :stroke-width="2.5" />
              </a>
            </div>
          </div>

          <div class="md:col-span-3 flex flex-col gap-5">
            <h4 class="font-extrabold text-[#1A2B49] text-lg">Tautan Cepat</h4>
            <ul class="flex flex-col gap-3">
              <li><Link href="/" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Home</Link></li>
              <li><Link href="/courses" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Kelas Kami</Link></li>
              <li><Link href="/contact" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Hubungi Kami</Link></li>
              <li><Link href="/about" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Tentang Kami</Link></li>
              <li><Link href="/clients" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Klien Kami</Link></li>
              <li><Link href="/blogs" class="text-[#264790] hover:text-[#44A6D9] text-sm md:text-base font-medium transition-colors">Blog Kami</Link></li>
            </ul>
          </div>

          <div class="md:col-span-4 flex flex-col gap-5">
            <h4 class="font-extrabold text-[#1A2B49] text-lg">Kontak</h4>
            <ul class="flex flex-col gap-3 text-[#264790] text-sm md:text-base font-medium leading-relaxed">
              <li class="font-bold text-[#264790] uppercase tracking-wide">PT. DRASTHA BERKAH SENTOSA</li>
              <li>031-9960-5068 (Pulsa)</li>
              <li>0812-3485-9768 (WhatsApp)</li>
              <li class="max-w-xs">Jl Budi Luhur B/2 Wagir Indah Kwangsan, Sedati Sidoarjo Jawa Timur 61253</li>
            </ul>
          </div>

        </div>

        <div class="w-full h-px bg-slate-300/50 mb-6"></div>

        <div class="flex flex-col-reverse md:flex-row justify-between items-center gap-4 text-[#264790] text-xs md:text-sm font-semibold">
          <p>&copy; 2026 Drastha Learning, All Rights Reserved</p>
          <div class="flex flex-wrap justify-center gap-4 md:gap-8">
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Privacy Policy</Link>
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Terms of Service</Link>
            <Link href="#" class="hover:text-[#44A6D9] transition-colors border-b border-transparent hover:border-[#44A6D9] pb-0.5">Cookies Settings</Link>
          </div>
        </div>
      </div>
    </footer>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Architects+Daughter&family=Kalam:wght@700&display=swap');

.font-montserrat {
  font-family: 'Montserrat', sans-serif;
}

.font-handwriting {
  font-family: 'Architects Daughter', cursive, sans-serif;
}

/* Zebra stripes gradient pattern */
.warning-zebra {
  background: repeating-linear-gradient(
    45deg,
    #facc15,
    #facc15 12px,
    #000000 12px,
    #000000 24px
  );
}

:deep(.prose) {
  h1, h2, h3, h4, h5, h6 {
    color: #1A2B49 !important;
    font-family: 'Montserrat', sans-serif !important;
  }
  h2 {
    font-size: 1.5rem !important;
    margin-top: 2.25rem !important;
    margin-bottom: 1rem !important;
    border-bottom: 2px solid #F4F7F9;
    padding-bottom: 0.5rem;
    font-weight: 800 !important;
  }
  h3 {
    font-size: 1.25rem !important;
    margin-top: 1.75rem !important;
    margin-bottom: 0.75rem !important;
    font-weight: 800 !important;
  }
  p {
    margin-bottom: 1.25rem !important;
    color: #4A5568 !important;
    font-size: 0.95rem !important;
    line-height: 1.8 !important;
    font-weight: 500 !important;
  }
  ul {
    list-style-type: disc !important;
    padding-left: 1.5rem !important;
    margin-bottom: 1.25rem !important;
  }
  ol {
    list-style-type: decimal !important;
    padding-left: 1.5rem !important;
    margin-bottom: 1.25rem !important;
  }
  li {
    margin-bottom: 0.5rem !important;
    color: #4A5568 !important;
    font-size: 0.95rem !important;
    font-weight: 500 !important;
  }
  strong {
    color: #1A2B49 !important;
    font-weight: 700 !important;
  }
  img {
    border-radius: 1.5rem !important;
    margin: 2rem auto !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03) !important;
  }
}

/* Polaroid Collage Prose Overrides */
:deep(.prose-polaroid) {
  h2, h3 {
    font-family: Georgia, Cambria, "Times New Roman", Times, serif !important;
    font-style: italic;
    color: #2D3748 !important;
  }
  p {
    font-family: Georgia, Cambria, "Times New Roman", Times, serif !important;
    font-size: 1.05rem !important;
    color: #2D3748 !important;
    line-height: 1.9 !important;
  }
}

/* Cyberpunk Zebra Prose Overrides */
:deep(.prose-cyber) {
  h2, h3 {
    font-family: monospace !important;
    font-weight: 900 !important;
    text-transform: uppercase;
    color: #000000 !important;
    border-left: 6px solid #44A6D9;
    padding-left: 10px;
  }
  p {
    font-family: monospace !important;
    font-size: 0.9rem !important;
    color: #1A202C !important;
  }
  li {
    font-family: monospace !important;
    font-size: 0.9rem !important;
  }
}
</style>
