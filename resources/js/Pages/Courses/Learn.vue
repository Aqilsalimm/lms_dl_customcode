<script setup>
/**
 * ==================================================================================
 * DRASTHA LEARNING LMS - ACTIVE STUDENT LEARNING PANEL (FRONTEND)
 * ==================================================================================
 * 
 * File Size: ~1292 lines
 * 
 * Role & Responsibilities:
 * - Directs the course workspace interface for enrolled students.
 * - Renders materials: Videos (YouTube/Vimeo), Slides (Google Slides/Canva iframe embeds), 
 *   or Native PPT cards.
 * - Handles interactive quiz sessions, math formulas, essays, and score submissions.
 * - Captures student telemetry data (watching progress logged every 15s via logStudyProgress).
 * - Manages Q&A / discussions via MaterialDiscussion child component.
 * 
 * Maintenance Notes:
 * - Ensure progress logs are debounced/throttled. Telemetry is a major source of DB hits.
 * - Coordinate math formula and true/false quiz validators with QuizAttempt validations on the backend.
 */

import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { 
  ShoppingCart, User, Globe, ChevronDown, GraduationCap, 
  Home, Newspaper, Calendar, Clock, MapPin, Code,
  Play, CheckCircle2, ArrowLeft, Download, Info, Menu, X,
  BookOpen, HelpCircle, Check, Presentation, FileText, ChevronLeft, ChevronRight, AlertCircle, Award
} from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import MaterialDiscussion from '@/Components/MaterialDiscussion.vue';
import axios from 'axios';

const props = defineProps({
  course: Object,
  spotlightMode: {
    type: Boolean,
    default: false
  },
  dbCompletedLessons: {
    type: Array,
    default: () => []
  },
  dbCompletedQuizzes: {
    type: Array,
    default: () => []
  },
  dbCompletedAt: {
    type: String,
    default: null
  },
  decryptionKey: {
    type: String,
    required: true
  }
});

// State Management
const activeTab = ref('resources'); // 'resources' or 'about'
const expandedModules = ref({});
const isSidebarOpen = ref(true);

// State for active lesson (default: very first lesson of first module)
const activeLesson = ref(null);
const activeQuiz = ref(null);
const activeModule = ref(null);

// Completed lessons state (synced with DB & LocalStorage)
const completedLessons = ref([...props.dbCompletedLessons]);
const completedQuizzes = ref([...props.dbCompletedQuizzes]);
const showCompletedOverlay = ref(false);

// Set initial active lesson
if (props.course.modules && props.course.modules.length > 0) {
  // Expand first module by default
  expandedModules.value[props.course.modules[0].id] = true;
  
  if (props.course.modules[0].lessons && props.course.modules[0].lessons.length > 0) {
    activeLesson.value = props.course.modules[0].lessons[0];
    activeModule.value = props.course.modules[0];
  } else if (props.course.modules[0].quizzes && props.course.modules[0].quizzes.length > 0) {
    activeQuiz.value = props.course.modules[0].quizzes[0];
    activeModule.value = props.course.modules[0];
  }
}

// Expand/collapse module accordion
const toggleModule = (id) => {
  expandedModules.value[id] = !expandedModules.value[id];
};

// Select a lesson
const selectLesson = (lesson, moduleObj) => {
  activeQuiz.value = null;
  activeLesson.value = lesson;
  activeModule.value = moduleObj;
  currentSlideIndex.value = 0;
};

// Select a quiz
const selectQuiz = (quiz, moduleObj) => {
  activeLesson.value = null;
  activeQuiz.value = quiz;
  activeModule.value = moduleObj;
  startQuiz(quiz);
};

// Calculate total module duration
const getModuleDuration = (mod) => {
  if (!mod.lessons) return 0;
  return mod.lessons.reduce((acc, l) => acc + parseInt(l.duration_minutes || 0), 0);
};

// Parse YouTube / Vimeo url into iframe embed url
const getEmbedUrl = computed(() => {
  if (!activeLesson.value) return '';
  
  if (activeLessonType.value === 'slides') {
    if (activeLesson.value.slide_url) {
      return activeLesson.value.slide_url;
    }
    if (activeLesson.value.content && activeLesson.value.content.startsWith('{') && activeLesson.value.content.endsWith('}')) {
      try {
        const obj = JSON.parse(activeLesson.value.content);
        if (obj.type === 'slides') {
          let url = obj.slides_url || '';
          if (url.includes('src="')) {
            const match = url.match(/src="([^"]+)"/);
            if (match) url = match[1];
          }
          return url;
        }
      } catch (e) {}
    }
    return '';
  }
  
  let url = activeLesson.value.video_url;

  if (!url) return '';

  // YouTube match
  let youtubeRegExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
  let ytMatch = url.match(youtubeRegExp);
  if (ytMatch && ytMatch[2].length === 11) {
    return `https://www.youtube.com/embed/${ytMatch[2]}?autoplay=1&rel=0`;
  }

  // Vimeo match
  let vimeoRegExp = /vimeo\.com\/(\d+)/;
  let vimeoMatch = url.match(vimeoRegExp);
  if (vimeoMatch) {
    return `https://player.vimeo.com/video/${vimeoMatch[1]}?autoplay=1`;
  }

  return url;
});

// Progress logging telemetry
let progressInterval = null;

const startProgressLogging = () => {
  if (progressInterval) clearInterval(progressInterval);
  progressInterval = setInterval(() => {
    if (!activeLesson.value) return;
    axios.post(`/courses/${props.course.slug}/lessons/${activeLesson.value.id}/log-progress`, {
      watch_seconds: 15,
      activity_type: 'video_progress'
    }).catch(err => {
      console.error('Failed to log watch progress:', err);
    });
  }, 15000); // log every 15 seconds
};

const stopProgressLogging = () => {
  if (progressInterval) {
    clearInterval(progressInterval);
    progressInterval = null;
  }
};

// Local content cache and loading state
const lessonContentCache = ref({});
const isLoadingContent = ref(false);

// Native Web Crypto API AES-256-CBC Decryption Helper
const decryptContent = async (ciphertextBase64, ivHex, secretKeyStr) => {
  try {
    const keyBytes = new Uint8Array(secretKeyStr.match(/.{1,2}/g).map(byte => parseInt(byte, 16)));
    const ivBytes = new Uint8Array(ivHex.match(/.{1,2}/g).map(byte => parseInt(byte, 16)));
    
    const cryptoKey = await window.crypto.subtle.importKey(
      "raw",
      keyBytes,
      { name: "AES-CBC" },
      false,
      ["decrypt"]
    );
    
    const binaryString = window.atob(ciphertextBase64);
    const ciphertextBytes = new Uint8Array(binaryString.length);
    for (let i = 0; i < binaryString.length; i++) {
      ciphertextBytes[i] = binaryString.charCodeAt(i);
    }
    
    const decryptedBuffer = await window.crypto.subtle.decrypt(
      { name: "AES-CBC", iv: ivBytes },
      cryptoKey,
      ciphertextBytes
    );
    
    const decoder = new TextDecoder();
    return decoder.decode(decryptedBuffer);
  } catch (e) {
    console.error("Gagal melakukan dekripsi materi:", e);
    return null;
  }
};

// Fetch and decrypt lesson content
const fetchLessonContent = async (lessonId) => {
  if (!lessonId) return;
  isLoadingContent.value = true;
  try {
    const res = await axios.get(`/courses/${props.course.slug}/lessons/${lessonId}/content`);
    if (res.data && res.data.ciphertext) {
      const decryptedStr = await decryptContent(res.data.ciphertext, res.data.iv, props.decryptionKey);
      if (decryptedStr) {
        const decryptedData = JSON.parse(decryptedStr);
        // Save to cache
        lessonContentCache.value[lessonId] = decryptedData;
        
        // If the user hasn't switched to another lesson, apply it
        if (activeLesson.value && activeLesson.value.id === lessonId) {
          activeLesson.value = {
            ...activeLesson.value,
            video_url: decryptedData.video_url,
            slide_url: decryptedData.slide_url,
            content: decryptedData.content,
            slide_content: decryptedData.slide_content,
          };
        }
      }
    }
  } catch (err) {
    console.error("Gagal memuat materi pelajaran:", err);
  } finally {
    isLoadingContent.value = false;
  }
};

watch(activeLesson, (newLesson, oldLesson) => {
  if (newLesson) {
    // If it is the exact same lesson ID, ignore to prevent infinite loop
    if (oldLesson && newLesson.id === oldLesson.id) {
      return;
    }

    startProgressLogging();
    
    // Lazy load and decrypt content if not cached
    if (lessonContentCache.value[newLesson.id]) {
      const cached = lessonContentCache.value[newLesson.id];
      activeLesson.value = {
        ...newLesson,
        video_url: cached.video_url,
        slide_url: cached.slide_url,
        content: cached.content,
        slide_content: cached.slide_content,
      };
    } else {
      fetchLessonContent(newLesson.id);
    }
  } else {
    stopProgressLogging();
  }
}, { immediate: true });

onBeforeUnmount(() => {
  stopProgressLogging();
});

// Load completed state from LocalStorage on mount if DB records are empty
onMounted(() => {
  if (completedLessons.value.length === 0) {
    const savedCompletions = localStorage.getItem('drastha_course_completions');
    if (savedCompletions) {
      try {
        const completionsMap = JSON.parse(savedCompletions);
        completedLessons.value = completionsMap[props.course.id] || [];
      } catch (e) {}
    }
  }

  if (completedQuizzes.value.length === 0) {
    const savedQuizCompletions = localStorage.getItem('drastha_quiz_completions');
    if (savedQuizCompletions) {
      try {
        const completionsMap = JSON.parse(savedQuizCompletions);
        completedQuizzes.value = completionsMap[props.course.id] || [];
      } catch (e) {}
    }
  }

  if (props.dbCompletedAt) {
    showCompletedOverlay.value = true;
  }
});

// Check if lesson is completed
const isCompleted = (lessonId) => {
  return completedLessons.value.includes(lessonId);
};

// Check if quiz is passed
const isQuizPassed = (quizId) => {
  return completedQuizzes.value.includes(quizId);
};

// Toggle completion state
const toggleComplete = () => {
  if (!activeLesson.value) return;

  const lId = activeLesson.value.id;
  let list = [...completedLessons.value];

  if (list.includes(lId)) {
    list = list.filter(id => id !== lId);
  } else {
    list.push(lId);
  }

  completedLessons.value = list;

  // Save to unified completions map in LocalStorage
  const savedCompletions = localStorage.getItem('drastha_course_completions') || '{}';
  try {
    let completionsMap = JSON.parse(savedCompletions);
    completionsMap[props.course.id] = list;
    localStorage.setItem('drastha_course_completions', JSON.stringify(completionsMap));
  } catch (e) {}

  // Sync to database
  axios.post(`/courses/${props.course.slug}/lessons/${lId}/toggle-complete`)
    .then(response => {
      if (response.data.completedLessons) {
        completedLessons.value = response.data.completedLessons;
      }
      if (response.data.completedAt) {
        showCompletedOverlay.value = true;
      } else {
        showCompletedOverlay.value = false;
      }
    })
    .catch(error => {
      console.error('Failed to sync lesson completion:', error);
    });
};

const totalLessons = computed(() => {
  if (!props.course.modules) return 0;
  return props.course.modules.reduce((acc, m) => acc + (m.lessons?.length || 0), 0);
});

const totalQuizzes = computed(() => {
  if (!props.course.modules) return 0;
  return props.course.modules.reduce((acc, m) => acc + (m.quizzes?.length || 0), 0);
});

const isCourseCompleted = computed(() => {
  const hasLessons = totalLessons.value > 0;
  const lessonsDone = completedLessons.value.length >= totalLessons.value;
  const quizzesDone = completedQuizzes.value.length >= totalQuizzes.value;
  return lessonsDone && quizzesDone && (hasLessons || totalQuizzes.value > 0);
});

// Tool tag mapper based on course name or dynamic tools array
const toolsList = computed(() => {
  const customTools = props.course.tools || [];
  
  if (customTools.length > 0) {
    const iconMap = {
      'python': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/python/python-original.svg',
      'vscode': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vscode/vscode-original.svg',
      'visual studio code': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vscode/vscode-original.svg',
      'terminal': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/bash/bash-original.svg',
      'bash': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/bash/bash-original.svg',
      'html': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/html5/html5-original.svg',
      'css': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/css3/css3-original.svg',
      'javascript': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/javascript/javascript-original.svg',
      'js': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/javascript/javascript-original.svg',
      'typescript': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/typescript/typescript-original.svg',
      'ts': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/typescript/typescript-original.svg',
      'supabase': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/supabase/supabase-original.svg',
      'nodejs': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/nodejs/nodejs-original-wordmark.svg',
      'node': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/nodejs/nodejs-original-wordmark.svg',
      'react': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/react/react-original.svg',
      'vue': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vuejs/vuejs-original.svg',
      'laravel': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/laravel/laravel-original.svg',
      'php': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/php/php-original.svg',
      'figma': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/figma/figma-original.svg',
      'docker': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/docker/docker-original.svg',
      'git': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/git/git-original.svg',
      'github': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/github/github-original.svg',
      'mysql': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/mysql/mysql-original.svg',
      'postgresql': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/postgresql/postgresql-original.svg',
      'tailwind': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/tailwindcss/tailwindcss-original.svg',
      'tailwindcss': 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/tailwindcss/tailwindcss-original.svg',
    };
    const defaultIcon = 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/chrome/chrome-original.svg';

    return customTools.map(tool => {
      const key = tool.toLowerCase().trim();
      return {
        name: tool,
        icon: iconMap[key] || defaultIcon
      };
    });
  }

  // Fallback if no custom tools defined
  const title = props.course.title.toLowerCase();
  if (title.includes('python')) {
    return [
      { name: 'Python', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/python/python-original.svg' },
      { name: 'VS Code', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vscode/vscode-original.svg' },
      { name: 'Terminal', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/bash/bash-original.svg' }
    ];
  }
  
  return [
    { name: 'Visual Studio Code', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/vscode/vscode-original.svg' },
    { name: 'HTML', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/html5/html5-original.svg' },
    { name: 'CSS', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/css3/css3-original.svg' },
    { name: 'Javascript', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/javascript/javascript-original.svg' },
    { name: 'Supabase', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/supabase/supabase-original.svg' },
    { name: 'NodeJs', icon: 'https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/nodejs/nodejs-original-wordmark.svg' }
  ];
});

// Slide index for PPT manual
const currentSlideIndex = ref(0);

const activeLessonType = computed(() => {
  if (!activeLesson.value) return 'video';
  if (activeLesson.value.slide_content) {
    try {
      const slideContentObj = typeof activeLesson.value.slide_content === 'string' ? JSON.parse(activeLesson.value.slide_content) : activeLesson.value.slide_content;
      if (slideContentObj && slideContentObj.type) return slideContentObj.type;
    } catch(e) {}
  }
  if (activeLesson.value.slide_url) return 'slides';
  
  // Legacy fallback
  const content = activeLesson.value.content;
  if (content && content.startsWith('{') && content.endsWith('}')) {
    try {
      const obj = JSON.parse(content);
      return obj.type || 'video';
    } catch (e) {}
  }
  return activeLesson.value.video_url ? 'video' : 'ppt';
});

const pptSlides = computed(() => {
  if (!activeLesson.value) return [];
  
  if (activeLesson.value.slide_content) {
    try {
      const slideContentObj = typeof activeLesson.value.slide_content === 'string' ? JSON.parse(activeLesson.value.slide_content) : activeLesson.value.slide_content;
      if (slideContentObj && slideContentObj.type === 'ppt') {
        return (slideContentObj.slides || []).map((s, idx) => ({
          id: s.id || idx,
          title: s.title || '',
          body_text: s.body_text || s.content || '',
          bg_color: s.bg_color || '#1e293b',
          text_color: s.text_color || '#ffffff'
        }));
      }
    } catch (e) {}
  }
  
  // Legacy fallback
  const content = activeLesson.value.content;
  if (content && content.startsWith('{') && content.endsWith('}')) {
    try {
      const obj = JSON.parse(content);
      if (obj.type === 'ppt') {
        return (obj.slides || []).map((s, idx) => ({
          id: s.id || idx,
          title: s.title || '',
          body_text: s.body_text || s.content || '',
          bg_color: s.bg_color || '#1e293b',
          text_color: s.text_color || '#ffffff'
        }));
      }
    } catch (e) {}
  }
  return [{
    id: 1,
    title: activeLesson.value.title,
    body_text: activeLesson.value.content || 'Materi tertulis...',
    bg_color: '#1e293b',
    text_color: '#ffffff'
  }];
});

// Live Class Countdown Logic
const timeRemaining = ref(0);
const timeEndRemaining = ref(0);
const countdown = ref({ days: '00', hours: '00', minutes: '00' });
let countdownInterval = null;

const updateCountdown = () => {
  if (props.course.course_type !== 'live_class' || !props.course.start_date) return;
  const now = new Date().getTime();
  const startDate = new Date(props.course.start_date).getTime();
  const endDate = new Date(props.course.end_date || props.course.start_date).getTime();
  
  timeRemaining.value = startDate - now;
  timeEndRemaining.value = endDate - now;
  
  if (timeRemaining.value > 0) {
    const d = Math.floor(timeRemaining.value / (1000 * 60 * 60 * 24));
    const h = Math.floor((timeRemaining.value % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const m = Math.floor((timeRemaining.value % (1000 * 60 * 60)) / (1000 * 60));
    
    countdown.value = {
      days: d.toString().padStart(2, '0'),
      hours: h.toString().padStart(2, '0'),
      minutes: m.toString().padStart(2, '0')
    };
  }
};

onMounted(() => {
  if (props.course.course_type === 'live_class') {
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 60000); // update every minute
  }
});

onBeforeUnmount(() => {
  if (countdownInterval) clearInterval(countdownInterval);
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// Interactive Quiz States
const quizAnswers = ref({});
const quizSubmitted = ref(false);
const quizScore = ref(0);
const quizTotalQuestions = ref(0);
const quizResults = ref([]);

const startQuiz = (quiz) => {
  quizAnswers.value = {};
  quizSubmitted.value = false;
  quizScore.value = 0;
  quizTotalQuestions.value = quiz.questions?.length || 0;
  quizResults.value = [];
  
  if (quiz.questions) {
    quiz.questions.forEach(q => {
      quizAnswers.value[q.id] = '';
    });
  }
};

const submitQuiz = () => {
  if (!activeQuiz.value || !activeQuiz.value.questions) return;
  
  const qId = activeQuiz.value.id;
  
  axios.post(`/courses/${props.course.slug}/quizzes/${qId}/toggle-complete`, {
    answers: quizAnswers.value
  })
    .then(response => {
      quizScore.value = response.data.score;
      quizResults.value = response.data.results;
      quizSubmitted.value = true;

      if (response.data.score >= 70) {
        if (!completedQuizzes.value.includes(qId)) {
          completedQuizzes.value.push(qId);
          
          const savedCompletions = localStorage.getItem('drastha_quiz_completions');
          let completionsMap = {};
          if (savedCompletions) {
            try {
              completionsMap = JSON.parse(savedCompletions);
            } catch (e) {}
          }
          completionsMap[props.course.id] = completedQuizzes.value;
          localStorage.setItem('drastha_quiz_completions', JSON.stringify(completionsMap));
        }

        if (response.data.completedAt) {
          showCompletedOverlay.value = true;
        } else {
          showCompletedOverlay.value = false;
        }
      }
    })
    .catch(error => {
      console.error('Failed to submit quiz:', error);
    });
};

// Logo Helper
const Logo = () => {
  const settings = usePage().props.settings;
  const customLogo = settings?.course_logo;
  if (customLogo && customLogo !== '/images/logo-placeholder.png') {
    return `<div class="flex items-center gap-2">
      <img src="${customLogo}" alt="Drastha Learning Logo" class="h-10 w-auto object-contain" />
    </div>`;
  }
  return `<div class="flex items-center gap-2">
    <svg width="32" height="32" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M50 20 L20 35 L50 50 L80 35 Z" fill="#264790"/>
      <path d="M30 40 L30 65 C30 75 70 75 70 65 L70 40" stroke="#44A6D9" stroke-width="6" fill="none"/>
      <path d="M15 45 C15 75 40 90 50 90 C60 90 85 75 85 45" stroke="#264790" stroke-width="4" stroke-dasharray="4 4" fill="none"/>
      <circle cx="75" cy="25" r="3" fill="#44A6D9"/>
      <circle cx="85" cy="15" r="2" fill="#F9CC6B"/>
    </svg>
    <div class="flex flex-col justify-center">
      <span class="font-bold text-[10px] tracking-widest text-[#264790] uppercase leading-tight">Drastha</span>
      <span class="font-bold text-[10px] tracking-widest text-[#44A6D9] uppercase leading-tight">Learning</span>
    </div>
  </div>`;
};
</script>

<template>
  <Head :title="`${course.title} | Kelas Belajar | Drastha Learning`" />

  <GuestLayout :spotlightMode="spotlightMode">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Back to Courses Breadcrumbs -->
      <div v-if="!spotlightMode" class="mb-6 flex justify-between items-center">
        <Link 
          href="/dashboard" 
          class="inline-flex items-center gap-2 text-slate-400 hover:text-[#44A6D9] font-semibold text-xs sm:text-sm transition-colors"
        >
          &lsaquo; Kembali ke Daftar Kelas
        </Link>
      </div>

      <!-- Main Two Column Workspace -->
      <div class="flex flex-col lg:flex-row gap-8 items-start relative">
        
        <!-- SIDEBAR SYLLABUS: Left Column -->
        <div 
          :class="isSidebarOpen ? 'w-full lg:max-w-[32%] flex' : 'hidden lg:hidden'"
          class="flex-col gap-5 shrink-0 transition-all duration-300 w-full"
        >
          
          <!-- Sidebar Toggle Header -->
          <div class="flex items-center gap-3 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm justify-between">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-[#264790]/5 text-[#264790] flex items-center justify-center">
                <Code :size="16" />
              </div>
              <span class="text-[#1A2B49] font-extrabold text-sm">Silabus Belajar</span>
            </div>
            <button 
              @click="isSidebarOpen = false"
              class="lg:hidden text-slate-400 hover:text-slate-600 transition-colors"
            >
              <X :size="18" />
            </button>
          </div>

          <!-- Modules Accordion List -->
          <div class="flex flex-col gap-4 max-h-[80vh] overflow-y-auto pr-1">
            
            <!-- Certificate Panel in Sidebar -->
            <div 
              v-if="isCourseCompleted"
              class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-2xl p-5 text-white shadow-md flex flex-col gap-3"
            >
              <div class="flex items-center gap-2">
                <Award :size="20" class="text-amber-100 animate-pulse" />
                <span class="font-extrabold text-xs tracking-wider uppercase">Kelas Telah Selesai!</span>
              </div>
              <p class="text-[11px] leading-relaxed text-amber-50">Selamat! Anda telah menyelesaikan seluruh materi belajar. Silakan cetak sertifikat kelulusan resmi Anda.</p>
              <Link 
                :href="route('courses.certificate', course.slug)"
                class="w-full bg-white text-amber-700 hover:bg-amber-50 py-2.5 rounded-xl font-bold text-xs shadow-sm transition-colors text-center block"
              >
                Unduh Sertifikat Kelulusan
              </Link>
            </div>

            <div 
              v-for="(mod, modIdx) in course.modules" 
              :key="mod.id"
              class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
            >
              <!-- Accordion Module Header -->
              <button 
                @click="toggleModule(mod.id)"
                class="w-full flex items-center justify-between p-4 sm:p-5 text-left hover:bg-slate-50/50 transition-colors outline-none"
              >
                <div>
                  <h4 class="font-extrabold text-sm sm:text-base text-[#1A2B49] mb-1.5 leading-snug">{{ mod.title }}</h4>
                  <span class="text-[11px] text-slate-400 font-bold block">
                    {{ mod.lessons?.length || 0 }} Sesi • {{ mod.quizzes?.length || 0 }} Kuis
                  </span>
                </div>
                <ChevronDown 
                  :size="16" 
                  :class="{'rotate-180': expandedModules[mod.id]}"
                  class="text-slate-400 transition-transform duration-200 shrink-0" 
                />
              </button>

              <!-- Accordion Module Lessons List -->
              <div 
                v-show="expandedModules[mod.id]"
                class="p-4 sm:p-5 border-t border-slate-50 bg-slate-50/20 flex flex-col gap-3"
              >
                <!-- Sesi Materi -->
                <button 
                  v-for="(les, lesIdx) in mod.lessons" 
                  :key="les.id"
                  @click="selectLesson(les, mod)"
                  :class="activeLesson?.id === les.id ? 'bg-[#264790] text-white shadow-md' : 'bg-[#F4F7F9] text-[#1A2B49] hover:bg-slate-100'"
                  class="w-full flex items-center justify-between p-4 rounded-xl text-left transition-all duration-200 outline-none animate-fade-in"
                >
                  <div class="flex items-center gap-3">
                    <!-- Icon badge inside circle -->
                    <div 
                      :class="activeLesson?.id === les.id ? 'bg-white/10 text-white' : (isCompleted(les.id) ? 'bg-emerald-100 text-emerald-600' : 'bg-white text-[#264790] border border-slate-100')"
                      class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 shadow-sm"
                    >
                      <CheckCircle2 v-if="isCompleted(les.id)" :size="14" />
                      <Presentation v-else-if="(!les.slide_url && les.content && les.content.startsWith('{') && JSON.parse(les.content).type === 'ppt')" :size="12" />
                      <FileText v-else-if="les.slide_url || (les.content && les.content.startsWith('{') && JSON.parse(les.content).type === 'slides')" :size="12" />
                      <Play v-else :size="11" :stroke-width="3" />
                    </div>
                    <div>
                      <span class="font-bold text-xs leading-snug block">{{ les.title }}</span>
                      <span 
                        :class="activeLesson?.id === les.id ? 'text-white/60' : 'text-slate-400'"
                        class="text-[10px] font-bold mt-0.5 block"
                      >
                        {{ les.duration_minutes }} Menit
                      </span>
                    </div>
                  </div>
                </button>

                <!-- Evaluasi Kuis -->
                <button 
                  v-for="(qz, qzIdx) in mod.quizzes" 
                  :key="qz.id"
                  @click="selectQuiz(qz, mod)"
                  :class="activeQuiz?.id === qz.id ? 'bg-[#264790] text-white shadow-md' : 'bg-[#FFFBEB] text-[#78350F] hover:bg-amber-100/50 border border-amber-100'"
                  class="w-full flex items-center justify-between p-4 rounded-xl text-left transition-all duration-200 outline-none animate-fade-in"
                >
                  <div class="flex items-center gap-3">
                    <div 
                      :class="activeQuiz?.id === qz.id ? 'bg-white/10 text-white' : (isQuizPassed(qz.id) ? 'bg-emerald-100 text-emerald-600' : 'bg-white text-amber-500 border border-amber-100')"
                      class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 shadow-sm"
                    >
                      <CheckCircle2 v-if="isQuizPassed(qz.id)" :size="14" />
                      <HelpCircle v-else :size="13" />
                    </div>
                    <div>
                      <span class="font-bold text-xs leading-snug block">{{ qz.title }}</span>
                      <span 
                        :class="activeQuiz?.id === qz.id ? 'text-white/60' : 'text-amber-600'"
                        class="text-[10px] font-bold mt-0.5 block"
                      >
                        {{ qz.time_limit_minutes }} Menit • Kuis
                      </span>
                    </div>
                  </div>
                </button>

                <div v-if="(!mod.lessons || mod.lessons.length === 0) && (!mod.quizzes || mod.quizzes.length === 0)" class="text-center py-4 text-xs font-bold text-slate-400">
                  Belum ada materi pembelajaran.
                </div>
              </div>

            </div>

          </div>

        </div>

        <!-- RIGHT WORKSPACE: Content Player & Material Details -->
        <div class="flex-grow w-full lg:max-w-[65%] flex flex-col gap-6">
          
          <!-- Toggle Sidebar Button (when sidebar is hidden) -->
          <div v-if="!isSidebarOpen" class="mb-2 self-start animate-fade-in">
            <button 
              @click="isSidebarOpen = true"
              class="inline-flex items-center gap-2 bg-white border border-slate-100 shadow-sm px-4 py-2.5 rounded-xl font-extrabold text-xs text-[#264790] hover:text-[#44A6D9] transition-colors"
            >
              <Menu :size="16" /> Tampilkan Silabus
            </button>
          </div>

          <!-- LIVE CLASS BANNER -->
          <div v-if="course.course_type === 'live_class'" class="bg-gradient-to-r from-[#1A2B49] to-[#264790] rounded-3xl p-6 sm:p-10 text-white shadow-xl flex flex-col sm:flex-row gap-6 items-center justify-between relative overflow-hidden border border-[#44A6D9]/20">
            <!-- decorative background -->
            <div class="absolute -right-20 -top-20 opacity-10 pointer-events-none">
              <Calendar :size="200" />
            </div>

            <div class="flex flex-col gap-2 relative z-10 w-full sm:w-auto">
              <span class="bg-amber-500 text-amber-950 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-widest self-start shadow-sm flex items-center gap-1.5">
                <span v-if="!course.is_event_finished" class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                {{ course.is_event_finished ? 'Acara Selesai' : 'Sesi Live Class' }}
              </span>
              <h2 class="text-xl sm:text-2xl font-extrabold mt-1">{{ course.title }}</h2>
              
              <div v-if="course.start_date" class="flex flex-col gap-1.5 mt-2 opacity-90 font-medium text-sm">
                <div class="flex items-center gap-2">
                  <Calendar :size="16" class="text-[#44A6D9]" /> 
                  <span>Mulai: {{ formatDate(course.start_date) }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Clock :size="16" class="text-[#44A6D9]" /> 
                  <span>Selesai: {{ formatDate(course.end_date) }}</span>
                </div>
              </div>
            </div>

            <div class="relative z-10 shrink-0 w-full sm:w-auto flex flex-col items-center sm:items-end gap-3">
              <template v-if="course.is_event_finished">
                <a 
                  v-if="course.recording_url"
                  :href="course.recording_url" 
                  target="_blank"
                  class="w-full sm:w-auto px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl font-extrabold text-sm transition-all shadow-lg flex items-center justify-center gap-2"
                >
                  <Play :size="18" fill="currentColor" /> Watch Recording
                </a>
                <span v-else class="text-xs text-white/60 italic">Rekaman belum tersedia</span>
              </template>
              
              <template v-else>
                <div v-if="timeRemaining > 0" class="flex flex-col items-center sm:items-end gap-2">
                  <span class="text-[10px] uppercase tracking-widest font-bold text-[#44A6D9]">Dimulai dalam</span>
                  <div class="flex items-center gap-2">
                    <div class="bg-white/10 border border-white/20 rounded-xl px-3 py-2 flex flex-col items-center justify-center min-w-[50px] backdrop-blur-sm">
                      <span class="text-xl font-black leading-none mb-1">{{ countdown.days }}</span>
                      <span class="text-[8px] uppercase tracking-wider opacity-75">Hari</span>
                    </div>
                    <div class="text-xl font-black opacity-50">:</div>
                    <div class="bg-white/10 border border-white/20 rounded-xl px-3 py-2 flex flex-col items-center justify-center min-w-[50px] backdrop-blur-sm">
                      <span class="text-xl font-black leading-none mb-1">{{ countdown.hours }}</span>
                      <span class="text-[8px] uppercase tracking-wider opacity-75">Jam</span>
                    </div>
                    <div class="text-xl font-black opacity-50">:</div>
                    <div class="bg-white/10 border border-white/20 rounded-xl px-3 py-2 flex flex-col items-center justify-center min-w-[50px] backdrop-blur-sm">
                      <span class="text-xl font-black leading-none mb-1">{{ countdown.minutes }}</span>
                      <span class="text-[8px] uppercase tracking-wider opacity-75">Mnt</span>
                    </div>
                  </div>
                </div>
                
                <a 
                  v-else-if="timeRemaining <= 0 && timeEndRemaining > 0"
                  :href="course.meeting_url || '#'" 
                  target="_blank"
                  class="w-full sm:w-auto px-8 py-4 bg-rose-500 hover:bg-rose-600 text-white rounded-2xl font-extrabold text-sm transition-all shadow-lg shadow-rose-500/20 flex items-center justify-center gap-2 animate-pulse"
                >
                  <Presentation :size="18" /> Join Live Session
                </a>

                <span v-else-if="timeEndRemaining <= 0" class="px-6 py-3 bg-white/10 text-white/80 rounded-2xl font-extrabold text-sm border border-white/20">
                  Sesi Telah Berakhir
                </span>
              </template>
            </div>
          </div>

          <!-- A. LESSON COMPONENT PLAYER -->
          <div v-if="activeLesson && (course.course_type !== 'live_class' || !course.start_date || timeRemaining <= 0)" class="flex flex-col gap-6">
            <!-- Loading Screen while decrypting payload -->
            <div v-if="isLoadingContent" class="w-full aspect-video rounded-3xl bg-slate-900 border border-slate-950 shadow-lg flex flex-col items-center justify-center text-slate-400 gap-4">
              <svg class="animate-spin h-8 w-8 text-[#44A6D9]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-sm font-semibold tracking-wide">Mengambil materi secara aman...</span>
            </div>

            <!-- Canva / Google Slides Player Frame -->
            <div v-else-if="activeLessonType === 'slides'" class="w-full">
              <div v-if="getEmbedUrl" class="w-full aspect-video rounded-2xl overflow-hidden shadow-lg border border-slate-200 bg-slate-50 relative">
                <iframe 
                  :src="getEmbedUrl"
                  class="w-full h-full absolute inset-0"
                  allowfullscreen
                  loading="lazy"
                  sandbox="allow-scripts allow-same-origin allow-presentation"
                ></iframe>
              </div>
              <div v-else class="w-full aspect-video rounded-2xl bg-slate-900 border border-slate-950 shadow-lg flex flex-col items-center justify-center text-slate-400 gap-3">
                <Play :size="48" class="text-slate-500 animate-pulse" />
                <span class="text-sm font-semibold">Memuat materi belajar...</span>
              </div>
            </div>

            <!-- Video Player Frame -->
            <div v-else-if="activeLessonType === 'video'" class="rounded-3xl overflow-hidden bg-slate-900 border border-slate-950 shadow-lg aspect-video w-full relative">
              <iframe 
                v-if="getEmbedUrl"
                :src="getEmbedUrl"
                class="w-full h-full absolute inset-0"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
              ></iframe>
              <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-3">
                <Play :size="48" class="text-slate-500 animate-pulse" />
                <span class="text-sm font-semibold">Memuat materi belajar...</span>
              </div>
            </div>

            <!-- PPT Manual Slides Player Frame -->
            <div v-else-if="activeLessonType === 'ppt'" class="w-full">
              <div 
                :style="{ 
                  backgroundColor: pptSlides[currentSlideIndex]?.bg_color || '#1e293b', 
                  color: pptSlides[currentSlideIndex]?.text_color || '#ffffff' 
                }"
                class="rounded-2xl overflow-hidden shadow-md aspect-video w-full relative flex flex-col justify-between p-6 sm:p-10 select-none transition-all duration-300 border border-black/5"
              >
                <!-- Slide content top -->
                <div class="flex justify-between items-center border-b border-white/10 pb-4">
                  <span class="text-[10px] font-bold tracking-widest uppercase opacity-75">Presentasi Native Pembelajaran</span>
                  <span class="text-xs font-bold bg-white/10 px-3 py-1 rounded-full">Slide {{ currentSlideIndex + 1 }} dari {{ pptSlides.length }}</span>
                </div>
                
                <!-- Slide Body (with Transition) -->
                <div class="flex-grow flex flex-col justify-center items-center gap-4 py-6 overflow-y-auto">
                  <Transition name="fade-slide" mode="out-in">
                    <div :key="currentSlideIndex" class="text-center max-w-xl mx-auto space-y-4">
                      <h2 class="text-xl sm:text-3xl font-extrabold leading-snug">
                        {{ pptSlides[currentSlideIndex]?.title || 'Slide ' + (currentSlideIndex + 1) }}
                      </h2>
                      <p class="opacity-90 font-medium text-sm sm:text-base leading-relaxed whitespace-pre-line">
                        {{ pptSlides[currentSlideIndex]?.body_text || 'Tidak ada isi penjelasan untuk slide ini.' }}
                      </p>
                    </div>
                  </Transition>
                </div>
                
                <!-- Slide Navigation & Progress Bar Footer -->
                <div class="flex flex-col gap-4 border-t border-white/10 pt-4">
                  <!-- Progress Bar Indicator -->
                  <div class="w-full h-1 bg-white/10 rounded-full overflow-hidden">
                    <div 
                      :style="{ width: `${((currentSlideIndex + 1) / pptSlides.length) * 100}%` }"
                      class="h-full bg-current opacity-75 transition-all duration-350"
                    ></div>
                  </div>

                  <div class="flex justify-between items-center">
                    <button 
                      type="button"
                      @click="currentSlideIndex = Math.max(0, currentSlideIndex - 1)"
                      :disabled="currentSlideIndex === 0"
                      class="px-4 py-2 bg-white/10 hover:bg-white/20 disabled:opacity-20 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer disabled:cursor-not-allowed border border-white/5"
                    >
                      <ChevronLeft :size="14" /> Sebelumnya
                    </button>
                    
                    <button 
                      type="button"
                      @click="
                        if (currentSlideIndex < pptSlides.length - 1) {
                          currentSlideIndex++;
                        } else {
                          if (!isCompleted(activeLesson?.id)) {
                            toggleComplete();
                          }
                        }
                      "
                      class="px-5 py-2 bg-white text-slate-900 hover:bg-white/90 disabled:opacity-30 rounded-xl text-xs font-extrabold transition-all flex items-center gap-1 cursor-pointer border border-white/5"
                    >
                      <span>{{ currentSlideIndex === pptSlides.length - 1 ? 'Selesai' : 'Berikutnya' }}</span>
                      <ChevronRight v-if="currentSlideIndex < pptSlides.length - 1" :size="14" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Lesson Title, Subtitle, & Complete Action Row -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
              <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-[#1A2B49] leading-snug mb-1">
                  {{ activeLesson?.title || 'Materi Belajar' }}
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm font-semibold">
                  Materi bagian : {{ activeModule?.title || 'Pendahuluan' }}
                </p>
              </div>

              <!-- "Selesai" Mark Complete Button -->
              <button 
                @click="toggleComplete"
                :class="isCompleted(activeLesson?.id) ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/10' : 'bg-[#264790] hover:bg-[#44A6D9] text-white shadow-[#264790]/10'"
                class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl font-extrabold text-xs sm:text-sm shadow-md transition-all shrink-0 select-none outline-none cursor-pointer"
              >
                <CheckCircle2 :size="16" /> 
                <span>{{ isCompleted(activeLesson?.id) ? 'Sudah Selesai' : 'Selesai' }}</span>
              </button>
            </div>

            <!-- Tabs Section -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col gap-6">
              
              <!-- Tabs Navigation -->
              <div class="flex gap-4 border-b border-slate-100 pb-3">
                <button 
                  @click="activeTab = 'resources'"
                  :class="activeTab === 'resources' ? 'text-[#264790] border-b-2 border-[#264790]' : 'text-slate-400 hover:text-slate-600'"
                  class="pb-2 font-extrabold text-sm transition-colors outline-none cursor-pointer"
                >
                  Resources
                </button>
                <button 
                  @click="activeTab = 'about'"
                  :class="activeTab === 'about' ? 'text-[#264790] border-b-2 border-[#264790]' : 'text-slate-400 hover:text-slate-600'"
                  class="pb-2 font-extrabold text-sm transition-colors outline-none cursor-pointer"
                >
                  About
                </button>
              </div>

              <!-- Tab Content: Resources -->
              <div v-if="activeTab === 'resources'">
                <a 
                  href="#" 
                  @click.prevent="alert('Mengunduh paket sumber belajar / assets kelas...')"
                  class="inline-flex items-center gap-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/50 p-4 rounded-2xl font-bold text-xs sm:text-sm text-slate-700 transition-colors shadow-sm"
                >
                  <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-[#264790] shadow-sm shrink-0">
                    <Download :size="16" />
                  </div>
                  <div>
                    <span class="block text-slate-800">Download Asset / Tools Belajar</span>
                    <span class="block text-[10px] text-slate-400 font-bold mt-0.5">Format: ZIP / PDF (Drastha Learning)</span>
                  </div>
                </a>
              </div>

              <!-- Tab Content: About -->
              <div v-else class="text-slate-500 font-medium text-xs sm:text-sm leading-relaxed whitespace-pre-line">
                <span v-if="isLoadingContent" class="text-slate-400 italic">Memuat penjelasan materi...</span>
                <span v-else>
                  {{ activeLessonType === 'ppt' ? (activeLesson?.content && activeLesson?.content.startsWith('{') ? JSON.parse(activeLesson.content).summary : activeLesson?.content) : activeLesson?.content || 'Materi video panduan untuk mempersiapkan tools pemrograman yang mendukung pembelajaran secara terstruktur.' }}
                </span>
              </div>

              <!-- QnA Discussion Section -->
              <MaterialDiscussion 
                v-if="activeLesson"
                :course-id="course.id"
                :lesson-id="activeLesson.id"
              />

            </div>
          </div>

          <!-- B. INTERACTIVE QUIZ PLAYER -->
          <div v-else-if="activeQuiz" class="flex flex-col gap-6 animate-fade-in">
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col gap-6">
              
              <!-- Quiz Header -->
              <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                  <h1 class="text-xl sm:text-2xl font-extrabold text-[#1A2B49] leading-snug mb-1">{{ activeQuiz.title }}</h1>
                  <p class="text-slate-400 text-xs sm:text-sm font-semibold">Evaluasi Kuis bab: {{ activeModule?.title }}</p>
                </div>
                <span class="px-3.5 py-1.5 bg-amber-50 text-amber-600 border border-amber-100 text-xs font-bold rounded-xl flex items-center gap-1.5 select-none shrink-0">
                  <Clock :size="14" /> {{ activeQuiz.time_limit_minutes }} Menit
                </span>
              </div>

              <!-- Instructions / Description -->
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-150 flex gap-3 text-slate-500 text-xs font-medium leading-relaxed">
                <Info :size="18" class="text-[#264790] shrink-0" />
                <p>{{ activeQuiz.description || 'Selesaikan kuis evaluasi ini untuk menguji pemahaman Anda. Dapatkan skor minimal 70 untuk lulus.' }}</p>
              </div>

              <!-- Quiz Questions list or Result details -->
              <div v-if="!quizSubmitted" class="flex flex-col gap-6 mt-4">
                <div 
                  v-for="(q, qIdx) in activeQuiz.questions" 
                  :key="q.id" 
                  class="bg-white p-5 rounded-2xl border border-slate-150 flex flex-col gap-4 shadow-sm"
                >
                  <div class="font-bold text-xs sm:text-sm text-[#1A2B49] flex gap-2">
                    <span class="text-[#264790]">{{ qIdx + 1 }}.</span>
                    <span class="flex-1">{{ q.question_text }}</span>
                  </div>

                  <!-- Options selector -->
                  <div class="flex flex-col gap-2.5">
                    <!-- 1. Multiple Choice -->
                    <div v-if="!q.options || q.options[0] !== '[TRUE_FALSE]' && q.options[0] !== '[ESSAY]' && q.options[0] !== '[MATH_FORMULA]'" class="flex flex-col gap-2">
                      <label 
                        v-for="(opt, oIdx) in q.options" 
                        :key="oIdx"
                        :class="quizAnswers[q.id] === oIdx ? 'bg-[#264790]/5 border-[#264790] text-[#264790]' : 'bg-slate-50 hover:bg-slate-100/50 border-slate-200 text-slate-700'"
                        class="flex items-center gap-3 p-3.5 rounded-xl border text-xs font-bold cursor-pointer transition-all select-none"
                      >
                        <input type="radio" :value="oIdx" v-model="quizAnswers[q.id]" class="w-4 h-4 text-[#264790] cursor-pointer" />
                        <span>{{ opt }}</span>
                      </label>
                    </div>

                    <!-- 2. True or False -->
                    <div v-else-if="q.options[0] === '[TRUE_FALSE]'" class="grid grid-cols-2 gap-3">
                      <label 
                        :class="quizAnswers[q.id] === 0 ? 'bg-[#264790]/10 border-[#264790] text-[#264790]' : 'bg-slate-50 hover:bg-slate-100/50 border-slate-200 text-slate-700'"
                        class="flex items-center justify-between p-4 rounded-xl border text-xs font-bold cursor-pointer transition-all select-none"
                      >
                        <span>Benar (True)</span>
                        <input type="radio" :value="0" v-model="quizAnswers[q.id]" class="w-4 h-4 text-[#264790] cursor-pointer" />
                      </label>
                      <label 
                        :class="quizAnswers[q.id] === 1 ? 'bg-[#264790]/10 border-[#264790] text-[#264790]' : 'bg-slate-50 hover:bg-slate-100/50 border-slate-200 text-slate-700'"
                        class="flex items-center justify-between p-4 rounded-xl border text-xs font-bold cursor-pointer transition-all select-none"
                      >
                        <span>Salah (False)</span>
                        <input type="radio" :value="1" v-model="quizAnswers[q.id]" class="w-4 h-4 text-[#264790] cursor-pointer" />
                      </label>
                    </div>

                    <!-- 3. Essay -->
                    <div v-else-if="q.options[0] === '[ESSAY]'">
                      <textarea 
                        v-model="quizAnswers[q.id]" 
                        rows="3" 
                        placeholder="Tuliskan jawaban penjelasan esai Anda di sini secara terperinci..." 
                        class="w-full bg-slate-50 hover:bg-slate-100/50 border border-slate-200 rounded-xl px-4 py-3 outline-none text-xs text-[#1A2B49] font-medium focus:bg-white focus:border-[#264790] transition-all"
                      ></textarea>
                    </div>

                    <!-- 4. Math Formula -->
                    <div v-else-if="q.options[0] === '[MATH_FORMULA]'" class="flex flex-col gap-3">
                      <div v-if="q.options[1]" class="bg-amber-50/50 border border-amber-100 rounded-xl p-3.5 flex flex-col gap-1 items-center justify-center">
                        <span class="text-[9px] font-bold text-amber-500 uppercase tracking-widest">Rumus Soal</span>
                        <span class="font-serif italic text-base text-[#1A2B49] font-bold select-all">$$ {{ q.options[1] }} $$</span>
                      </div>
                      <input 
                        v-model="quizAnswers[q.id]" 
                        type="text" 
                        placeholder="Ketikkan jawaban rumus atau nilai angka hasil perhitungan Anda..." 
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none text-xs text-[#1A2B49] font-bold focus:bg-white focus:border-[#264790] transition-all"
                      />
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <button 
                  @click="submitQuiz"
                  class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-sm shadow-md transition-colors cursor-pointer"
                >
                  Kirim & Periksa Jawaban Kuis
                </button>
              </div>

              <!-- Score Result Card -->
              <div v-else class="flex flex-col gap-6">
                <div class="bg-slate-50 rounded-3xl p-8 border border-slate-150 flex flex-col items-center justify-center text-center gap-4">
                  <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Hasil Evaluasi Kuis</span>
                  
                  <!-- Circular Progress Score -->
                  <div class="relative flex items-center justify-center">
                    <div 
                      :class="quizScore >= 70 ? 'text-emerald-500 bg-emerald-50 border-emerald-200' : 'text-rose-500 bg-rose-50 border-rose-200'"
                      class="w-32 h-32 rounded-full border-4 flex flex-col items-center justify-center shadow-sm"
                    >
                      <span class="text-3xl font-black">{{ quizScore }}</span>
                      <span class="text-[10px] font-bold text-slate-400">SKOR ANDA</span>
                    </div>
                  </div>

                  <div>
                    <h2 v-if="quizScore >= 70" class="text-lg font-black text-emerald-600">Selamat, Anda Lulus Evaluasi!</h2>
                    <h2 v-else class="text-lg font-black text-rose-500">Nilai Belum Mencukupi Kelulusan</h2>
                    <p class="text-slate-400 text-xs font-semibold max-w-sm mt-1">
                      {{ quizScore >= 70 ? 'Hasil ini telah tercatat dalam progress belajar Anda. Silakan lanjutkan ke modul berikutnya!' : 'Batas nilai kelulusan minimal kuis adalah 70. Silakan tinjau kembali jawaban di bawah dan coba lagi.' }}
                    </p>
                  </div>

                  <button 
                    @click="startQuiz(activeQuiz)"
                    class="px-6 py-2.5 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-[#1A2B49] shadow-sm transition-colors cursor-pointer"
                  >
                    Coba Lagi Kuis
                  </button>
                </div>

                <!-- Detailed Answers Summary -->
                <div class="flex flex-col gap-4">
                  <h3 class="text-xs font-bold text-[#1A2B49] uppercase tracking-wider">Tinjauan Koreksi Soal</h3>
                  
                  <div 
                    v-for="(res, idx) in quizResults" 
                    :key="idx"
                    :class="res.is_correct ? 'border-emerald-250 border-emerald-200 bg-emerald-50/20' : 'border-rose-200 bg-rose-50/10'"
                    class="p-4.5 rounded-2xl border flex flex-col gap-2.5"
                  >
                    <div class="flex justify-between items-start gap-4">
                      <span class="text-xs font-bold text-[#1A2B49] leading-snug">{{ idx + 1 }}. {{ res.question_text }}</span>
                      <span 
                        :class="res.is_correct ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600'"
                        class="px-2.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider shrink-0"
                      >
                        {{ res.is_correct ? 'Benar' : 'Salah' }}
                      </span>
                    </div>
                    
                    <div class="text-[11px] font-semibold flex flex-col gap-1 text-slate-500 border-t border-slate-100/50 pt-2.5">
                      <div>Jawaban Anda: <span :class="res.is_correct ? 'text-emerald-600' : 'text-rose-600'" class="font-bold">{{ res.student_answer !== '' ? res.student_answer : '(Kosong)' }}</span></div>
                      <div v-if="!res.is_correct">Kunci Jawaban: <span class="text-[#264790] font-bold">{{ res.correct_text }}</span></div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Tools Kelas Section -->
          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_8px_30px_rgba(0,0,0,0.015)] flex flex-col gap-5">
            <h3 class="text-lg font-extrabold text-[#1A2B49]">
              Tools Kelas
            </h3>
            
            <div class="flex flex-wrap gap-3">
              <div 
                v-for="tool in toolsList" 
                :key="tool.name"
                class="flex items-center gap-2 bg-slate-50 border border-slate-200/40 px-4 py-2.5 rounded-2xl font-bold text-xs text-slate-600 shadow-sm"
              >
                <img :src="tool.icon" class="w-4 h-4 object-contain shrink-0" :alt="tool.name" />
                <span>{{ tool.name }}</span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- CLEAN FOOTER -->
    <footer v-if="!spotlightMode" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mt-16 border-t border-slate-100">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
          <!-- Inline SVG DL Logo -->
          <div class="flex items-center gap-2 mb-4" v-html="Logo()"></div>
          <p class="text-slate-500 font-medium text-xs max-w-sm">
            Platform Learning Management System (LMS) yang dirancang untuk mendukung pembelajaran modern, interaktif, dan berkelanjutan.
          </p>
        </div>

        <div class="flex flex-wrap gap-x-12 gap-y-6">
          <div>
            <h4 class="font-bold text-xs text-[#1A2B49] uppercase tracking-wider mb-3">Tautan Cepat</h4>
            <div class="flex flex-col gap-2 text-xs font-semibold text-slate-400">
              <Link href="/" class="hover:text-[#44A6D9] transition-colors">Home</Link>
              <Link href="/courses" class="hover:text-[#44A6D9] transition-colors">Kelas Kami</Link>
              <Link href="/#hubungi-kami" class="hover:text-[#44A6D9] transition-colors">Hubungi Kami</Link>
            </div>
          </div>
          <div>
            <h4 class="font-bold text-xs text-[#1A2B49] uppercase tracking-wider mb-3">Kontak</h4>
            <p class="text-xs font-semibold text-slate-400 mb-1">PT. DRASTHA BERKAH SENTOSA</p>
            <p class="text-xs font-semibold text-slate-400">Jl. Budi Luhur B/2, Wagir, Kwangsan, Sedati</p>
          </div>
        </div>
      </div>

      <div class="h-px bg-slate-100 my-8"></div>

      <div class="flex flex-col sm:flex-row justify-between items-center text-[10px] font-bold text-slate-400 gap-4">
        <span>&copy; 2026 Drastha Learning. All Rights Reserved</span>
        <div class="flex gap-6">
          <a href="#" class="hover:text-[#44A6D9] transition-colors">Privacy Policy</a>
          <a href="#" class="hover:text-[#44A6D9] transition-colors">Terms of Service</a>
        </div>
      </div>
    </footer>
    <!-- Course Completed Success Overlay Modal -->
    <div 
      v-if="showCompletedOverlay" 
      class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl border border-slate-100 relative text-center flex flex-col items-center">
        
        <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-5">
          <Award :size="36" />
        </div>

        <h3 class="text-xl font-extrabold text-[#1A2B49] mb-2">Selamat! Kelas Selesai!</h3>
        <p class="text-slate-400 text-sm font-semibold mb-6">
          Selamat! Anda telah menyelesaikan seluruh materi belajar di kelas <b class="text-[#1A2B49]">{{ course.title }}</b>. Anda berhak mendapatkan sertifikat kelulusan.
        </p>

        <div class="flex flex-col gap-3 w-full">
          <Link 
            :href="route('courses.certificate', course.slug)"
            class="w-full bg-[#FFFBEB] hover:bg-amber-100/50 text-[#B45309] py-3.5 rounded-2xl font-bold text-sm transition-colors text-center flex items-center justify-center gap-2 border border-amber-200"
          >
            <Award :size="16" class="text-amber-500" /> Lihat / Unduh Sertifikat
          </Link>

          <button 
            @click="showCompletedOverlay = false"
            class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3.5 rounded-2xl font-bold text-sm shadow-md transition-colors"
          >
            Kembali ke Halaman Belajar
          </button>
          
          <Link 
            href="/dashboard"
            class="w-full bg-slate-50 hover:bg-slate-100 text-slate-700 py-3.5 rounded-2xl font-bold text-sm transition-colors text-center border border-slate-200/50"
          >
            Kembali ke Dashboard
          </Link>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }

/* Native Slide Transition */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s ease;
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
