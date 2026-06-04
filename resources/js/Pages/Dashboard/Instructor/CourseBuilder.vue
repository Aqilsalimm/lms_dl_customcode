<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
  BookOpen, Plus, Trash2, ArrowLeft, Settings, 
  ChevronRight, Save, Layout, Edit, Video, HelpCircle,
  PlusCircle, Trash, Check, X, CheckSquare, ExternalLink,
  ChevronDown, Upload, Play, Search, User, Sparkles,
  Clock, RefreshCw, Presentation, FileText, Paperclip, Award
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  course: Object,
  categories: Array,
  tags: Array,
  courses: Array
});

const page = usePage();
const authUser = computed(() => page.props.auth.user);

// Step Navigation State (1: Basics, 2: Curriculum, 3: Additional)
const currentStep = ref(1);

// Settings Form
const form = ref({
  title: props.course.title,
  price: parseFloat(props.course.price),
  level: props.course.level,
  status: props.course.status,
  description: props.course.description || '',
  about: props.course.about || '',
  bg_color: props.course.bg_color || '#44A6D9',
  icon_type: props.course.icon_type || 'code',
  category_id: props.course.category_id || '',
  capacity: props.course.capacity || 20,
  tags: props.course.tags ? props.course.tags.map(t => t.id) : []
});

// Step 1 - Local Mock / Layout States to match UI requirements exactly
const sessionFrequency = ref('Select Session Frequency');
const courseDuration = ref('Select Course Duration');
const classType = ref('Location');
const productUnitType = ref('Tipe Satuan Produk');
const minAge = ref('');
const registrationStatus = ref('Status Pendaftaran');
const activeOptionsTab = ref('general'); // 'general', 'drip', 'enrollment'
const searchCategoryQuery = ref('');
const searchTagQuery = ref('');
const isVisibilityPublic = ref(props.course.status === 'published');
const pricingModel = ref(parseFloat(props.course.price) > 0 ? 'paid' : 'free');
const youtubeVideoUrl = ref('');
const dripType = ref('none');

// Sync Visibility state with form.status
const toggleVisibility = () => {
  isVisibilityPublic.value = !isVisibilityPublic.value;
  form.value.status = isVisibilityPublic.value ? 'published' : 'draft';
};

// Sync Pricing Model state with price
const setPricingModel = (model) => {
  pricingModel.value = model;
  if (model === 'free') {
    form.value.price = 0;
  }
};

// Filter categories locally by search
const filteredCategories = computed(() => {
  if (!searchCategoryQuery.value) return props.categories;
  return props.categories.filter(cat => 
    cat.name.toLowerCase().includes(searchCategoryQuery.value.toLowerCase())
  );
});

// Filter tags locally by search
const filteredTags = computed(() => {
  if (!searchTagQuery.value) return props.tags;
  return props.tags.filter(t => 
    t.name.toLowerCase().includes(searchTagQuery.value.toLowerCase())
  );
});

// Dynamic Local Content Lists for instantaneous updates (Step 2)
const modules = ref([...props.course.modules]);

// Modal State Management
const currentModal = ref(''); // 'module', 'lesson', 'quiz', 'question', 'assignment', 'quiz_settings'
const selectedModule = ref(null);
const selectedQuiz = ref(null);

// Temporary model states for creating/editing
const moduleForm = ref({ id: null, title: '' });
const lessonForm = ref({ 
  id: null, title: '', content: '', featured_image: null, 
  lesson_type: 'video', // 'video', 'slides', 'ppt'
  video_type: 'url', // 'upload', 'url'
  video_url: '', 
  slides_url: '',
  playback_time: { hour: 0, min: 0, sec: 0 }, 
  exercise_files: [], is_preview: false 
});
const assignmentForm = ref({
  id: null, title: '', content: '', attachments: [], 
  time_limit: 0, time_limit_unit: 'Weeks', set_deadline_from_start: false,
  total_points: 10, min_pass_points: 5, file_upload_limit: 1, 
  max_file_size: 2, allow_resubmission: true, max_resubmission_attempts: 5
});
const quizForm = ref({ id: null, title: '', description: '', time_limit_minutes: 15 });
const quizSettingsForm = ref({
  time_limit: 0, time_limit_unit: 'Minutes', hide_quiz_time: false,
  feedback_mode: 'Retry', attempts_allowed: 10, passing_grade: 80, max_questions: 10,
  quiz_auto_start: false, question_layout: 'Single question', question_order: 'Random',
  hide_question_number: false, char_limit_short: 200, char_limit_essay: 500
});
const questionForm = ref({
  id: null, question_text: '', options: ['', '', '', ''], correct_option_index: 0
});

// Handle Featured Image Upload (Local Mock state or URL placeholder)
const featuredImagePreview = ref(props.course.thumbnail ? `/storage/${props.course.thumbnail}` : null);
const triggerImageUpload = () => {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  input.onchange = (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (event) => {
        featuredImagePreview.value = event.target.result;
        form.value.thumbnail = file; // Handled as file on update
      };
      reader.readAsDataURL(file);
    }
  };
  input.click();
};

// Toggle tags check
const toggleTag = (tagId) => {
  const index = form.value.tags.indexOf(tagId);
  if (index === -1) {
    form.value.tags.push(tagId);
  } else {
    form.value.tags.splice(index, 1);
  }
};

// Step 3 states
const initialAbout = () => {
  if (props.course.about && props.course.about.startsWith('{') && props.course.about.endsWith('}')) {
    try {
      const parsed = JSON.parse(props.course.about);
      return {
        class_type: parsed.class_type || 'Online',
        overview: parsed.overview || '',
        what_will_learn: parsed.what_will_learn || '',
        target_audience: parsed.target_audience || '',
        duration_hours: parsed.duration_hours || 0,
        materials_included: parsed.materials_included || 0,
        requirements: parsed.requirements || '',
        selected_certificate: parsed.selected_certificate || 'template_1',
        custom_certificates: parsed.custom_certificates || [],
        prerequisites: parsed.prerequisites || [],
        attachments: parsed.attachments || [],
        live_zoom_link: parsed.live_zoom_link || '',
        live_zoom_data: parsed.live_zoom_data || null,
        live_gmeet_link: parsed.live_gmeet_link || '',
        live_gmeet_data: parsed.live_gmeet_data || null
      };
    } catch (e) {}
  }
  return {
    class_type: 'Online',
    overview: props.course.about || '',
    what_will_learn: '',
    target_audience: '',
    duration_hours: 0,
    materials_included: 0,
    requirements: '',
    selected_certificate: 'template_1',
    custom_certificates: [],
    prerequisites: [],
    attachments: [],
    live_zoom_link: '',
    live_zoom_data: null,
    live_gmeet_link: '',
    live_gmeet_data: null
  };
};

const additionalForm = ref(initialAbout());
const prerequisiteSearchQuery = ref('');

// Computed list of courses that are NOT selected as prerequisites
const filteredPrerequisites = computed(() => {
  const query = prerequisiteSearchQuery.value.trim().toLowerCase();
  const allCourses = props.courses || [];
  
  return allCourses.filter(c => {
    const isAlreadyPrereq = additionalForm.value.prerequisites.includes(c.id);
    const matchesQuery = c.title.toLowerCase().includes(query);
    return !isAlreadyPrereq && matchesQuery;
  });
});

// Helper course lookup for display
const getPrerequisiteCourse = (id) => {
  const allCourses = props.courses || [];
  return allCourses.find(c => c.id === id);
};

const addPrerequisite = (courseId) => {
  if (!additionalForm.value.prerequisites.includes(courseId)) {
    additionalForm.value.prerequisites.push(courseId);
  }
};

const removePrerequisite = (courseId) => {
  additionalForm.value.prerequisites = additionalForm.value.prerequisites.filter(id => id !== courseId);
};

// Upload dynamic custom certificate
const handleCertificateUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (event) => {
      const id = 'custom_' + Date.now();
      additionalForm.value.custom_certificates.push({
        id: id,
        name: file.name,
        url: event.target.result
      });
      additionalForm.value.selected_certificate = id;
    };
    reader.readAsDataURL(file);
  }
};

const triggerCertificateUpload = () => {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  input.onchange = handleCertificateUpload;
  input.click();
};

// Upload attachments
const handleAttachmentUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (event) => {
      additionalForm.value.attachments.push({
        id: 'attach_' + Date.now(),
        name: file.name,
        size: (file.size / 1024 / 1024).toFixed(2) + ' MB',
        url: event.target.result
      });
    };
    reader.readAsDataURL(file);
  }
};

const triggerAttachmentUpload = () => {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = '*/*';
  input.onchange = handleAttachmentUpload;
  input.click();
};

const removeAttachment = (id) => {
  additionalForm.value.attachments = additionalForm.value.attachments.filter(a => a.id !== id);
};

// Rich Text Editing exec commands
const execCommand = (command, value = null) => {
  document.execCommand(command, false, value);
};

const addLinkPrompt = () => {
  const url = prompt('Masukkan URL Link:');
  if (url) {
    execCommand('createLink', url);
  }
};

const addImagePrompt = () => {
  const url = prompt('Masukkan URL Gambar:');
  if (url) {
    execCommand('insertImage', url);
  }
};

const insertCodeBlock = () => {
  const code = prompt('Masukkan baris kode sumber:');
  if (code) {
    const formatted = `<pre class="bg-slate-800 text-slate-100 p-3 rounded-lg font-mono text-xs my-2 overflow-x-auto"><code>${code}</code></pre>`;
    execCommand('insertHTML', formatted);
  }
};

// Custom Meeting Modal states
const showMeetingModal = ref(false);
const meetingModalType = ref('zoom'); // 'zoom' or 'gmeet'
const meetingModalForm = ref({
  name: '',
  summary: '',
  date: '',
  time: '',
  duration: 40,
  durationUnit: 'Minutes',
  timezone: 'Asia/Jakarta',
  link: '',
  meetingId: '',
  password: ''
});

// Trigger opening of modal
const openMeetingModal = (type) => {
  meetingModalType.value = type;
  
  const existingData = type === 'zoom' ? additionalForm.value.live_zoom_data : additionalForm.value.live_gmeet_data;
  
  if (existingData) {
    meetingModalForm.value = { ...existingData };
  } else {
    meetingModalForm.value = {
      name: props.course.title ? `Kelas Live: ${props.course.title}` : '',
      summary: 'Sesi tanya jawab live interaktif mengenai materi pembelajaran.',
      date: new Date().toISOString().substring(0, 10), // default to today
      time: '19:00',
      duration: 40,
      durationUnit: 'Minutes',
      timezone: 'Asia/Jakarta',
      link: '',
      meetingId: '',
      password: ''
    };
  }
  showMeetingModal.value = true;
};

// Generate meeting link upon submitting the modal form
const generateMeetingLink = () => {
  const formVal = meetingModalForm.value;
  if (!formVal.name) {
    alert('Nama meeting wajib diisi!');
    return;
  }
  
  if (meetingModalType.value === 'zoom') {
    if (!formVal.link) {
      const meetingIdNum = Math.floor(1000000000 + Math.random() * 9000000000); // random 10 digits
      const pwdStr = Math.random().toString(36).substring(2, 10);
      formVal.link = `https://zoom.us/j/${meetingIdNum}?pwd=${pwdStr}`;
      formVal.meetingId = meetingIdNum.toString();
      formVal.password = pwdStr;
    }
    additionalForm.value.live_zoom_link = formVal.link;
    additionalForm.value.live_zoom_data = { ...formVal };
  } else {
    if (!formVal.link) {
      const part1 = Math.random().toString(36).substring(2, 5);
      const part2 = Math.random().toString(36).substring(2, 6);
      const part3 = Math.random().toString(36).substring(2, 5);
      formVal.link = `https://meet.google.com/${part1}-${part2}-${part3}`;
    }
    additionalForm.value.live_gmeet_link = formVal.link;
    additionalForm.value.live_gmeet_data = { ...formVal };
  }
  
  // Close modal
  showMeetingModal.value = false;
};

// Save & Next Step
const handleUpdate = () => {
  // Sync step 3 configurations into form.about
  form.value.about = JSON.stringify(additionalForm.value);

  // Save current details to backend
  const formData = new FormData();
  formData.append('_method', 'PUT');
  formData.append('title', form.value.title);
  formData.append('price', form.value.price);
  formData.append('level', form.value.level);
  formData.append('status', form.value.status);
  formData.append('description', form.value.description);
  formData.append('about', form.value.about);
  formData.append('bg_color', form.value.bg_color);
  formData.append('icon_type', form.value.icon_type);
  formData.append('capacity', form.value.capacity);
  if (form.value.category_id) {
    formData.append('category_id', form.value.category_id);
  }
  if (form.value.thumbnail instanceof File) {
    formData.append('thumbnail', form.value.thumbnail);
  }
  form.value.tags.forEach(t => {
    formData.append('tags[]', t);
  });

  axios.post(`/course-builder/courses/${props.course.id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  })
  .then(res => {
    if (currentStep.value < 3) {
      currentStep.value++;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      alert('Informasi kelas berhasil disimpan secara keseluruhan!');
    }
  })
  .catch(err => {
    alert('Gagal memperbarui kelas. Silakan periksa kembali data Anda.');
  });
};

// Navigate directly via header indicator
const navigateToStep = (step) => {
  currentStep.value = step;
};

// --- MODULE CRUD ---
const openModuleModal = (module = null) => {
  if (module) {
    moduleForm.value = { id: module.id, title: module.title };
  } else {
    moduleForm.value = { id: null, title: '' };
  }
  currentModal.value = 'module';
};

const saveModule = () => {
  if (moduleForm.value.id) {
    axios.put(`/course-builder/modules/${moduleForm.value.id}`, { title: moduleForm.value.title })
      .then(res => {
        const idx = modules.value.findIndex(m => m.id === moduleForm.value.id);
        modules.value[idx].title = res.data.module.title;
        currentModal.value = '';
      });
  } else {
    axios.post(`/course-builder/courses/${props.course.id}/modules`, { title: moduleForm.value.title })
      .then(res => {
        modules.value.push({ ...res.data.module, lessons: [], quizzes: [] });
        currentModal.value = '';
      });
  }
};

const deleteModule = (moduleId) => {
  if (confirm('Hapus bab ini beserta seluruh isinya?')) {
    axios.delete(`/course-builder/modules/${moduleId}`)
      .then(() => {
        modules.value = modules.value.filter(m => m.id !== moduleId);
      });
  }
};

// --- LESSON CRUD ---
const openLessonModal = (module, lesson = null) => {
  selectedModule.value = module;
  if (lesson) {
    let parsedContent = lesson.content || '';
    let parsedType = 'video';
    let parsedPptSlides = [];
    let parsedSlidesUrl = '';
    
    // New fields
    let parsedFeaturedImage = null;
    let parsedVideoType = 'url';
    let parsedPlaybackTime = { hour: 0, min: 0, sec: 0 };
    let parsedExerciseFiles = [];
    let parsedIsPreview = false;

    if (parsedContent.startsWith('{') && parsedContent.endsWith('}')) {
      try {
        const obj = JSON.parse(parsedContent);
        parsedType = obj.type || 'video';
        if (parsedType === 'ppt') {
          parsedPptSlides = obj.slides || [];
        } else if (parsedType === 'slides') {
          parsedSlidesUrl = obj.slides_url || '';
        }
        parsedContent = obj.summary || '';
        
        parsedFeaturedImage = obj.featured_image || null;
        parsedVideoType = obj.video_type || 'url';
        parsedPlaybackTime = obj.playback_time || { hour: Math.floor(lesson.duration_minutes / 60), min: lesson.duration_minutes % 60, sec: 0 };
        parsedExerciseFiles = obj.exercise_files || [];
        parsedIsPreview = obj.is_preview || false;
      } catch (e) {
        parsedContent = lesson.content || '';
      }
    } else {
      if (lesson.video_url) {
        parsedType = 'video';
      } else {
        parsedType = 'ppt';
      }
      parsedPlaybackTime = { hour: Math.floor(lesson.duration_minutes / 60), min: lesson.duration_minutes % 60, sec: 0 };
    }

    lessonForm.value = { 
      id: lesson.id, 
      title: lesson.title, 
      content: parsedContent, 
      featured_image: parsedFeaturedImage,
      lesson_type: parsedType,
      video_type: parsedVideoType,
      video_url: lesson.video_url || '', 
      slides_url: parsedSlidesUrl,
      playback_time: parsedPlaybackTime,
      exercise_files: parsedExerciseFiles,
      is_preview: parsedIsPreview,
      ppt_slides: parsedPptSlides.length > 0 ? parsedPptSlides : [{ title: 'Slide 1: Pengantar', content: 'Tulis penjelasan slide di sini...' }]
    };
  } else {
    lessonForm.value = { 
      id: null, 
      title: '', 
      content: '', 
      featured_image: null,
      lesson_type: 'video',
      video_type: 'upload',
      video_url: '', 
      slides_url: '',
      playback_time: { hour: 0, min: 0, sec: 0 },
      exercise_files: [],
      is_preview: false,
      ppt_slides: [
        { title: 'Slide 1: Pengantar', content: 'Tulis penjelasan slide di sini...' }
      ]
    };
  }
  currentModal.value = 'lesson';
};

const saveLesson = () => {
  let finalContentObj = {
    type: lessonForm.value.lesson_type,
    summary: lessonForm.value.content,
    featured_image: lessonForm.value.featured_image,
    exercise_files: lessonForm.value.exercise_files,
    is_preview: lessonForm.value.is_preview
  };

  let finalVideoUrl = lessonForm.value.video_url;

  if (lessonForm.value.lesson_type === 'slides') {
    finalContentObj.slides_url = lessonForm.value.slides_url;
    finalVideoUrl = '';
  } else if (lessonForm.value.lesson_type === 'ppt') {
    finalContentObj.slides = lessonForm.value.ppt_slides;
    finalVideoUrl = '';
  } else {
    finalContentObj.video_type = lessonForm.value.video_type;
    finalContentObj.playback_time = lessonForm.value.playback_time;
  }
  
  let calcDuration = 0;
  if (lessonForm.value.lesson_type === 'video') {
      calcDuration = (parseInt(lessonForm.value.playback_time.hour) || 0) * 60 + (parseInt(lessonForm.value.playback_time.min) || 0);
  }

  const payload = {
    id: lessonForm.value.id,
    title: lessonForm.value.title,
    content: JSON.stringify(finalContentObj),
    video_url: finalVideoUrl,
    duration_minutes: calcDuration
  };

  if (lessonForm.value.id) {
    axios.put(`/course-builder/lessons/${lessonForm.value.id}`, payload)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        const lesIdx = modules.value[modIdx].lessons.findIndex(l => l.id === lessonForm.value.id);
        modules.value[modIdx].lessons[lesIdx] = res.data.lesson;
        currentModal.value = '';
      });
  } else {
    axios.post(`/course-builder/modules/${selectedModule.value.id}/lessons`, payload)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        modules.value[modIdx].lessons.push(res.data.lesson);
        currentModal.value = '';
      });
  }
};

// --- ASSIGNMENT CRUD ---
const openAssignmentModal = (module, assignment = null) => {
  selectedModule.value = module;
  if (assignment) {
    let parsedContent = '';
    let parsedAttachments = [];
    let parsedTimeLimit = 0;
    let parsedTimeUnit = 'Weeks';
    let parsedSetDeadline = false;
    let parsedTotalPoints = 10;
    let parsedMinPass = 5;
    let parsedFileLimit = 1;
    let parsedMaxSize = 2;
    let parsedAllowResub = true;
    let parsedMaxResub = 5;

    if (assignment.content && assignment.content.startsWith('{')) {
      try {
        const obj = JSON.parse(assignment.content);
        parsedContent = obj.summary || '';
        parsedAttachments = obj.attachments || [];
        parsedTimeLimit = obj.time_limit || 0;
        parsedTimeUnit = obj.time_limit_unit || 'Weeks';
        parsedSetDeadline = obj.set_deadline_from_start || false;
        parsedTotalPoints = obj.total_points || 10;
        parsedMinPass = obj.min_pass_points || 5;
        parsedFileLimit = obj.file_upload_limit || 1;
        parsedMaxSize = obj.max_file_size || 2;
        parsedAllowResub = obj.allow_resubmission !== false;
        parsedMaxResub = obj.max_resubmission_attempts || 5;
      } catch (e) {}
    }

    assignmentForm.value = {
      id: assignment.id,
      title: assignment.title,
      content: parsedContent,
      attachments: parsedAttachments,
      time_limit: parsedTimeLimit,
      time_limit_unit: parsedTimeUnit,
      set_deadline_from_start: parsedSetDeadline,
      total_points: parsedTotalPoints,
      min_pass_points: parsedMinPass,
      file_upload_limit: parsedFileLimit,
      max_file_size: parsedMaxSize,
      allow_resubmission: parsedAllowResub,
      max_resubmission_attempts: parsedMaxResub
    };
  } else {
    assignmentForm.value = {
      id: null, title: '', content: '', attachments: [], 
      time_limit: 0, time_limit_unit: 'Weeks', set_deadline_from_start: false,
      total_points: 10, min_pass_points: 5, file_upload_limit: 1, 
      max_file_size: 2, allow_resubmission: true, max_resubmission_attempts: 5
    };
  }
  currentModal.value = 'assignment';
};

const saveAssignment = () => {
  const finalContentObj = {
    type: 'assignment',
    summary: assignmentForm.value.content,
    attachments: assignmentForm.value.attachments,
    time_limit: assignmentForm.value.time_limit,
    time_limit_unit: assignmentForm.value.time_limit_unit,
    set_deadline_from_start: assignmentForm.value.set_deadline_from_start,
    total_points: assignmentForm.value.total_points,
    min_pass_points: assignmentForm.value.min_pass_points,
    file_upload_limit: assignmentForm.value.file_upload_limit,
    max_file_size: assignmentForm.value.max_file_size,
    allow_resubmission: assignmentForm.value.allow_resubmission,
    max_resubmission_attempts: assignmentForm.value.max_resubmission_attempts
  };

  const payload = {
    id: assignmentForm.value.id,
    title: assignmentForm.value.title,
    content: JSON.stringify(finalContentObj),
    video_url: '',
    duration_minutes: 0
  };

  if (assignmentForm.value.id) {
    axios.put(`/course-builder/lessons/${assignmentForm.value.id}`, payload)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        const lesIdx = modules.value[modIdx].lessons.findIndex(l => l.id === assignmentForm.value.id);
        modules.value[modIdx].lessons[lesIdx] = res.data.lesson;
        currentModal.value = '';
      });
  } else {
    axios.post(`/course-builder/modules/${selectedModule.value.id}/lessons`, payload)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        modules.value[modIdx].lessons.push(res.data.lesson);
        currentModal.value = '';
      });
  }
};

const deleteLesson = (module, lessonId) => {
  if (confirm('Hapus sesi materi ini?')) {
    axios.delete(`/course-builder/lessons/${lessonId}`)
      .then(() => {
        const modIdx = modules.value.findIndex(m => m.id === module.id);
        modules.value[modIdx].lessons = modules.value[modIdx].lessons.filter(l => l.id !== lessonId);
      });
  }
};

// --- QUIZ CRUD ---
const openQuizModal = (module, quiz = null) => {
  selectedModule.value = module;
  if (quiz) {
    quizForm.value = { 
      id: quiz.id, 
      title: quiz.title, 
      description: quiz.description || '', 
      time_limit_minutes: quiz.time_limit_minutes 
    };
  } else {
    quizForm.value = { id: null, title: '', description: '', time_limit_minutes: 15 };
  }
  currentModal.value = 'quiz';
};

const saveQuiz = () => {
  if (quizForm.value.id) {
    axios.put(`/course-builder/quizzes/${quizForm.value.id}`, quizForm.value)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        const qIdx = modules.value[modIdx].quizzes.findIndex(q => q.id === quizForm.value.id);
        const questionsTemp = modules.value[modIdx].quizzes[qIdx].questions || [];
        modules.value[modIdx].quizzes[qIdx] = { ...res.data.quiz, questions: questionsTemp };
        currentModal.value = '';
      });
  } else {
    axios.post(`/course-builder/modules/${selectedModule.value.id}/quizzes`, quizForm.value)
      .then(res => {
        const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
        if (!modules.value[modIdx].quizzes) modules.value[modIdx].quizzes = [];
        modules.value[modIdx].quizzes.push({ ...res.data.quiz, questions: [] });
        currentModal.value = '';
      });
  }
};

const deleteQuiz = (module, quizId) => {
  if (confirm('Hapus kuis ini?')) {
    axios.delete(`/course-builder/quizzes/${quizId}`)
      .then(() => {
        const modIdx = modules.value.findIndex(m => m.id === module.id);
        modules.value[modIdx].quizzes = modules.value[modIdx].quizzes.filter(q => q.id !== quizId);
      });
  }
};

const openQuizSettingsModal = (module, quiz) => {
  selectedModule.value = module;
  selectedQuiz.value = quiz;
  
  let settingsObj = {};
  if (quiz.description && quiz.description.startsWith('{')) {
    try {
      settingsObj = JSON.parse(quiz.description);
    } catch(e) {}
  }

  quizSettingsForm.value = {
    time_limit: settingsObj.time_limit || 0,
    time_limit_unit: settingsObj.time_limit_unit || 'Minutes',
    hide_quiz_time: settingsObj.hide_quiz_time || false,
    feedback_mode: settingsObj.feedback_mode || 'Retry',
    attempts_allowed: settingsObj.attempts_allowed || 10,
    passing_grade: settingsObj.passing_grade || 80,
    max_questions: settingsObj.max_questions || 10,
    quiz_auto_start: settingsObj.quiz_auto_start || false,
    question_layout: settingsObj.question_layout || 'Single question',
    question_order: settingsObj.question_order || 'Random',
    hide_question_number: settingsObj.hide_question_number || false,
    char_limit_short: settingsObj.char_limit_short || 200,
    char_limit_essay: settingsObj.char_limit_essay || 500
  };
  
  currentModal.value = 'quiz_settings';
};

const saveQuizSettings = () => {
  const payload = {
    id: selectedQuiz.value.id,
    title: selectedQuiz.value.title,
    time_limit_minutes: selectedQuiz.value.time_limit_minutes,
    description: JSON.stringify(quizSettingsForm.value)
  };

  axios.put(`/course-builder/quizzes/${selectedQuiz.value.id}`, payload)
    .then(res => {
      const modIdx = modules.value.findIndex(m => m.id === selectedModule.value.id);
      const qIdx = modules.value[modIdx].quizzes.findIndex(q => q.id === selectedQuiz.value.id);
      const questionsTemp = modules.value[modIdx].quizzes[qIdx].questions || [];
      modules.value[modIdx].quizzes[qIdx] = { ...res.data.quiz, questions: questionsTemp };
      currentModal.value = '';
    });
};

// --- QUIZ QUESTIONS ---
const openQuestionModal = (quiz, question = null) => {
  selectedQuiz.value = quiz;
  if (question) {
    let parsedType = 'multiple_choice';
    let opts = [...question.options];
    if (opts[0] === '[TRUE_FALSE]') {
      parsedType = 'true_false';
      opts = [opts[1] || 'Benar', opts[2] || 'Salah'];
    } else if (opts[0] === '[ESSAY]') {
      parsedType = 'essay';
      opts = ['', opts[1] || ''];
    } else if (opts[0] === '[MATH_FORMULA]') {
      parsedType = 'math_formula';
      opts = ['', opts[1] || '', opts[2] || ''];
    }

    questionForm.value = {
      id: question.id,
      question_text: question.question_text,
      options: opts,
      correct_option_index: question.correct_option_index,
      question_type: parsedType
    };
  } else {
    questionForm.value = {
      id: null,
      question_text: '',
      options: ['', '', '', ''],
      correct_option_index: 0,
      question_type: 'multiple_choice'
    };
  }
  currentModal.value = 'question';
};

const saveQuestion = () => {
  let finalOptions = [...questionForm.value.options];
  let finalCorrectIndex = questionForm.value.correct_option_index;

  if (questionForm.value.question_type === 'true_false') {
    finalOptions = ['[TRUE_FALSE]', questionForm.value.options[0] || 'Benar', questionForm.value.options[1] || 'Salah'];
  } else if (questionForm.value.question_type === 'essay') {
    finalOptions = ['[ESSAY]', questionForm.value.options[1] || ''];
    finalCorrectIndex = 0;
  } else if (questionForm.value.question_type === 'math_formula') {
    finalOptions = ['[MATH_FORMULA]', questionForm.value.options[1] || '', questionForm.value.options[2] || ''];
    finalCorrectIndex = 0;
  }

  const payload = {
    id: questionForm.value.id,
    question_text: questionForm.value.question_text,
    options: finalOptions,
    correct_option_index: finalCorrectIndex
  };

  if (questionForm.value.id) {
    axios.put(`/course-builder/questions/${questionForm.value.id}`, payload)
      .then(res => {
        for (let m = 0; m < modules.value.length; m++) {
          const qIdx = (modules.value[m].quizzes || []).findIndex(q => q.id === selectedQuiz.value.id);
          if (qIdx !== -1) {
            const qstIdx = modules.value[m].quizzes[qIdx].questions.findIndex(q => q.id === questionForm.value.id);
            modules.value[m].quizzes[qIdx].questions[qstIdx] = res.data.question;
            break;
          }
        }
        currentModal.value = '';
      });
  } else {
    axios.post(`/course-builder/quizzes/${selectedQuiz.value.id}/questions`, payload)
      .then(res => {
        for (let m = 0; m < modules.value.length; m++) {
          const qIdx = (modules.value[m].quizzes || []).findIndex(q => q.id === selectedQuiz.value.id);
          if (qIdx !== -1) {
            if (!modules.value[m].quizzes[qIdx].questions) {
              modules.value[m].quizzes[qIdx].questions = [];
            }
            modules.value[m].quizzes[qIdx].questions.push(res.data.question);
            break;
          }
        }
        currentModal.value = '';
      });
  }
};

const deleteQuestion = (quiz, questionId) => {
  if (confirm('Hapus pertanyaan ini?')) {
    axios.delete(`/course-builder/questions/${questionId}`)
      .then(() => {
        for (let m = 0; m < modules.value.length; m++) {
          const qIdx = (modules.value[m].quizzes || []).findIndex(q => q.id === quiz.id);
          if (qIdx !== -1) {
            modules.value[m].quizzes[qIdx].questions = modules.value[m].quizzes[qIdx].questions.filter(q => q.id !== questionId);
            break;
          }
        }
      });
  }
};
</script>

<template>
  <Head :title="'Course Builder - ' + course.title" />

  <AuthenticatedLayout>
    <template #header>
      <!-- HEADER WITH 3 STEPS AND ACTIONS -->
      <div class="flex flex-col md:flex-row items-center justify-between gap-4 py-2">
        
        <!-- Back Button -->
        <div class="flex items-center gap-3 self-start md:self-auto">
          <Link 
            :href="route('course-builder.index')"
            class="p-2 border-2 border-slate-100 hover:bg-slate-50 rounded-full text-slate-500 hover:text-[#1A2B49] transition-all"
          >
            <ArrowLeft :size="16" />
          </Link>
          <span class="text-xl font-extrabold text-[#1A2B49] tracking-tight">Course Builder</span>
        </div>

        <!-- 3 Steps Indicator -->
        <div class="flex items-center gap-2 md:gap-4 select-none">
          <button 
            @click="navigateToStep(1)"
            class="flex items-center gap-2 px-1 focus:outline-none group"
          >
            <span :class="[
              currentStep === 1 
                ? 'bg-[#264790] text-white ring-4 ring-[#264790]/15' 
                : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'
            ]" class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all">
              1
            </span>
            <span :class="currentStep === 1 ? 'text-[#1A2B49] font-extrabold' : 'text-slate-400 font-bold'" class="text-sm transition-all">
              1. Basics
            </span>
          </button>

          <div class="w-8 md:w-16 h-[2px] bg-slate-200"></div>

          <button 
            @click="navigateToStep(2)"
            class="flex items-center gap-2 px-1 focus:outline-none group"
          >
            <span :class="[
              currentStep === 2 
                ? 'bg-[#264790] text-white ring-4 ring-[#264790]/15' 
                : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'
            ]" class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all">
              2
            </span>
            <span :class="currentStep === 2 ? 'text-[#1A2B49] font-extrabold' : 'text-slate-400 font-bold'" class="text-sm transition-all">
              2. Curriculum
            </span>
          </button>

          <div class="w-8 md:w-16 h-[2px] bg-slate-200"></div>

          <button 
            @click="navigateToStep(3)"
            class="flex items-center gap-2 px-1 focus:outline-none group"
          >
            <span :class="[
              currentStep === 3 
                ? 'bg-[#264790] text-white ring-4 ring-[#264790]/15' 
                : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'
            ]" class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all">
              3
            </span>
            <span :class="currentStep === 3 ? 'text-[#1A2B49] font-extrabold' : 'text-slate-400 font-bold'" class="text-sm transition-all">
              3. Additional
            </span>
          </button>
        </div>

        <!-- Header Actions -->
        <div class="flex items-center gap-3 self-end md:self-auto">
          <a 
            :href="`/courses/${course.slug}`" 
            target="_blank"
            class="px-5 py-2.5 rounded-full border-2 border-slate-200 hover:border-slate-300 bg-white font-bold text-xs text-slate-600 hover:text-slate-800 transition-all flex items-center gap-1.5"
          >
            Preview <ExternalLink :size="12" />
          </a>
          <button 
            @click="handleUpdate"
            class="px-6 py-2.5 rounded-full bg-[#264790] hover:bg-[#44A6D9] text-white font-bold text-xs transition-all flex items-center gap-1.5 shadow-sm"
          >
            {{ currentStep === 3 ? 'Finish & Save' : 'Update' }} <ChevronRight :size="14" />
          </button>
        </div>

      </div>
    </template>

    <div class="py-10 bg-slate-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- STEP 1: BASICS -->
        <div v-if="currentStep === 1" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          
          <!-- LEFT COLUMN: Main Details Form -->
          <div class="lg:col-span-2 flex flex-col gap-6">
            
            <!-- Course Details Card -->
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-5">
              <h3 class="text-lg font-extrabold text-[#1A2B49]">Course Details</h3>

              <!-- Course Title -->
              <div>
                <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Course Title</label>
                <input 
                  v-model="form.title" 
                  type="text" 
                  placeholder="e.g., Mastering HTML, CSS, Tailwind v4" 
                  class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-all duration-300"
                />
              </div>

              <!-- Course Description -->
              <div>
                <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Description</label>
                <!-- Editor Header Mockup -->
                <div class="border border-slate-200 rounded-2xl overflow-hidden focus-within:border-[#264790] transition-colors">
                  <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex flex-wrap items-center gap-3.5 text-slate-400">
                    <button type="button" class="font-extrabold hover:text-[#1A2B49]">B</button>
                    <button type="button" class="italic hover:text-[#1A2B49]">I</button>
                    <button type="button" class="underline hover:text-[#1A2B49]">U</button>
                    <div class="w-[1px] h-4 bg-slate-200"></div>
                    <button type="button" class="hover:text-[#1A2B49] text-sm">List Bullets</button>
                    <button type="button" class="hover:text-[#1A2B49] text-sm">List Numbers</button>
                    <div class="w-[1px] h-4 bg-slate-200"></div>
                    <button type="button" class="hover:text-[#1A2B49] text-sm">Link</button>
                    <button type="button" class="hover:text-[#1A2B49] text-sm">Image</button>
                    <button type="button" class="hover:text-[#1A2B49] text-sm">&lt;/&gt;</button>
                  </div>
                  <textarea 
                    v-model="form.description"
                    rows="6"
                    placeholder="Enter a comprehensive course description here..."
                    class="w-full bg-white px-4 py-3 outline-none text-[#1A2B49] font-medium resize-none"
                  ></textarea>
                </div>
              </div>

              <!-- Session Info & Duration Selects -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Session Info</label>
                  <select 
                    v-model="sessionFrequency" 
                    class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-all duration-300"
                  >
                    <option>Select Session Frequency</option>
                    <option>2 Sesi / Minggu</option>
                    <option>3 Sesi / Minggu</option>
                    <option>Setiap Hari</option>
                  </select>
                </div>
                <div>
                  <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Duration</label>
                  <select 
                    v-model="courseDuration" 
                    class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-all duration-300"
                  >
                    <option>Select Course Duration</option>
                    <option>1 Bulan</option>
                    <option>3 Bulan</option>
                    <option>6 Bulan</option>
                    <option>Akses Selamanya</option>
                  </select>
                </div>
              </div>

            </div>

            <!-- Extra Details Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              
              <div>
                <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Tipe Kelas</label>
                <select 
                  v-model="additionalForm.class_type" 
                  class="w-full bg-white border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-all shadow-[0_8px_30px_rgb(0,0,0,0.01)]"
                >
                  <option value="Offline">Offline</option>
                  <option value="Online">Online</option>
                  <option value="Hybrid">Hybrid</option>
                </select>
              </div>

              <div>
                <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Tipe Satuan Produk</label>
                <select 
                  v-model="productUnitType" 
                  class="w-full bg-white border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-all shadow-[0_8px_30px_rgb(0,0,0,0.01)]"
                >
                  <option>Tipe Satuan Produk</option>
                  <option>Sekali Bayar</option>
                  <option>Langganan Bulanan</option>
                </select>
              </div>

              <div>
                <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Usia Peserta (Minimal Usia)</label>
                <input 
                  v-model="minAge" 
                  type="text" 
                  placeholder="e.g., 10 Tahun"
                  class="w-full bg-white border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-all shadow-[0_8px_30px_rgb(0,0,0,0.01)]"
                />
              </div>

              <div>
                <label class="block text-slate-400 text-xs font-bold mb-2 uppercase tracking-wide">Status Pendaftaran</label>
                <select 
                  v-model="registrationStatus" 
                  class="w-full bg-white border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-all shadow-[0_8px_30px_rgb(0,0,0,0.01)]"
                >
                  <option>Status Pendaftaran</option>
                  <option>Buka</option>
                  <option>Tutup</option>
                  <option>Segera Hadir</option>
                </select>
              </div>

            </div>

            <!-- Options Tabbed Section -->
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-6">
              <h3 class="text-lg font-extrabold text-[#1A2B49]">Options</h3>

              <div class="flex flex-col md:flex-row gap-6">
                <!-- Tabs Sidebar -->
                <div class="w-full md:w-44 shrink-0 flex flex-row md:flex-col gap-1 border-b md:border-b-0 md:border-r border-slate-150 pb-4 md:pb-0 md:pr-4">
                  <button 
                    @click="activeOptionsTab = 'general'"
                    :class="activeOptionsTab === 'general' ? 'bg-[#F4F7F9] text-[#264790]' : 'text-slate-500 hover:bg-slate-50'"
                    class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2"
                  >
                    <Settings :size="14" /> General
                  </button>
                  <button 
                    @click="activeOptionsTab = 'drip'"
                    :class="activeOptionsTab === 'drip' ? 'bg-[#F4F7F9] text-[#264790]' : 'text-slate-500 hover:bg-slate-50'"
                    class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2"
                  >
                    <Clock :size="14" /> Content Drip
                  </button>
                  <button 
                    @click="activeOptionsTab = 'enrollment'"
                    :class="activeOptionsTab === 'enrollment' ? 'bg-[#F4F7F9] text-[#264790]' : 'text-slate-500 hover:bg-slate-50'"
                    class="w-full text-left px-4 py-2.5 rounded-xl font-bold text-xs transition-all flex items-center gap-2"
                  >
                    <RefreshCw :size="14" /> Enrollment
                  </button>
                </div>

                <!-- Tabs Content -->
                <div class="flex-1 min-h-[140px] flex flex-col justify-center pl-0 md:pl-6">
                  
                  <div v-if="activeOptionsTab === 'general'" class="flex flex-col gap-4">
                    <div>
                      <label class="block text-slate-400 text-xs font-bold mb-1.5 uppercase tracking-wide">Difficulty Level</label>
                      <select 
                        v-model="form.level"
                        class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-colors"
                      >
                        <option value="SD">Sekolah Dasar (SD)</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                        <option value="Umum">Umum / Profesional</option>
                      </select>
                    </div>

                    <div class="flex items-center justify-between mt-2">
                      <div>
                        <span class="block text-sm font-bold text-[#1A2B49]">Public Course</span>
                        <span class="block text-xs text-slate-400">Anyone can view this course without login.</span>
                      </div>
                      <button 
                        @click="toggleVisibility"
                        :class="isVisibilityPublic ? 'bg-[#264790]' : 'bg-slate-200'"
                        class="w-12 h-6.5 rounded-full p-1 transition-colors duration-300 relative focus:outline-none"
                      >
                        <span 
                          :class="isVisibilityPublic ? 'translate-x-5' : 'translate-x-0'"
                          class="block w-4.5 h-4.5 rounded-full bg-white shadow-md transform transition-transform duration-300"
                        ></span>
                      </button>
                    </div>
                  </div>

                  <div v-if="activeOptionsTab === 'drip'" class="flex flex-col gap-4">
                    <div>
                      <h4 class="text-base font-extrabold text-[#1A2B49] mb-1">Content Drip Type</h4>
                      <p class="text-xs text-slate-400 mb-5 leading-relaxed">
                        You can schedule your course content using one of the following Content Drip options
                      </p>
                      
                      <div class="flex flex-col gap-3.5">
                        <label class="flex items-center gap-3 cursor-pointer text-xs font-bold text-slate-600 hover:text-[#1A2B49] select-none">
                          <input type="radio" v-model="dripType" value="date" class="w-4.5 h-4.5 text-[#264790] border-slate-350 focus:ring-0 cursor-pointer" />
                          <span>Schedule course content by date</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer text-xs font-bold text-slate-600 hover:text-[#1A2B49] select-none">
                          <input type="radio" v-model="dripType" value="days" class="w-4.5 h-4.5 text-[#264790] border-slate-350 focus:ring-0 cursor-pointer" />
                          <span>Content available after X days from enrollment</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer text-xs font-bold text-slate-600 hover:text-[#1A2B49] select-none">
                          <input type="radio" v-model="dripType" value="sequential" class="w-4.5 h-4.5 text-[#264790] border-slate-350 focus:ring-0 cursor-pointer" />
                          <span>Course content available sequentially</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer text-xs font-bold text-slate-600 hover:text-[#1A2B49] select-none">
                          <input type="radio" v-model="dripType" value="prerequisites" class="w-4.5 h-4.5 text-[#264790] border-slate-350 focus:ring-0 cursor-pointer" />
                          <span>Course content unlocked after finishing prerequisites</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer text-xs font-bold text-slate-600 hover:text-[#1A2B49] select-none">
                          <input type="radio" v-model="dripType" value="none" class="w-4.5 h-4.5 text-[#264790] border-slate-350 focus:ring-0 cursor-pointer" />
                          <span>None</span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div v-if="activeOptionsTab === 'enrollment'" class="flex flex-col gap-5">
                    <!-- Maximum Student Field -->
                    <div>
                      <div class="flex items-center gap-1.5 mb-2">
                        <label class="block text-slate-700 text-xs font-bold">Maximum Student</label>
                        <span class="text-slate-400 cursor-pointer" title="Jumlah maksimal siswa yang dapat mendaftar kelas ini"><HelpCircle :size="14" /></span>
                      </div>
                      <div class="relative flex items-center">
                        <input 
                          v-model.number="form.capacity"
                          type="number"
                          placeholder="e.g., 100"
                          class="w-full bg-white border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold text-xs transition-colors"
                        />
                        <button 
                          v-if="form.capacity"
                          type="button"
                          @click="form.capacity = ''"
                          class="absolute right-4 text-slate-400 hover:text-slate-600 focus:outline-none"
                        >
                          <X :size="16" />
                        </button>
                      </div>
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                      <span class="block text-sm font-bold text-[#1A2B49] mb-1">Masa Berlaku Pendaftaran</span>
                      <p class="text-xs text-slate-400 leading-relaxed mb-4">
                        Tentukan berapa lama murid dapat mengakses materi kelas setelah melakukan pembayaran.
                      </p>
                      <div class="flex items-center gap-3">
                        <input type="checkbox" id="limitEnroll" class="w-4 h-4 text-[#264790]" />
                        <label for="limitEnroll" class="text-xs font-bold text-slate-500">Batasi Akses Kelas (120 Hari)</label>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>

          </div>

          <!-- RIGHT COLUMN: Sidebar Settings -->
          <div class="flex flex-col gap-6">
            
            <!-- Visibility Card -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-3">
              <h3 class="text-sm font-bold text-[#1A2B49] uppercase tracking-wide">Visibility</h3>
              
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <button 
                    @click="form.status = 'published'"
                    class="flex items-center gap-2 font-bold text-xs"
                    :class="form.status === 'published' ? 'text-[#264790]' : 'text-slate-400'"
                  >
                    <span :class="form.status === 'published' ? 'bg-[#264790] ring-4 ring-[#264790]/15' : 'bg-slate-100'" class="w-4 h-4 rounded-full flex items-center justify-center border transition-all">
                      <span v-if="form.status === 'published'" class="w-1.5 h-1.5 bg-white rounded-full"></span>
                    </span>
                    Public
                  </button>
                  <button 
                    @click="form.status = 'draft'"
                    class="flex items-center gap-2 font-bold text-xs"
                    :class="form.status === 'draft' ? 'text-[#264790]' : 'text-slate-400'"
                  >
                    <span :class="form.status === 'draft' ? 'bg-[#264790] ring-4 ring-[#264790]/15' : 'bg-slate-100'" class="w-4 h-4 rounded-full flex items-center justify-center border transition-all">
                      <span v-if="form.status === 'draft'" class="w-1.5 h-1.5 bg-white rounded-full"></span>
                    </span>
                    Private
                  </button>
                </div>
              </div>
              <p class="text-xs text-slate-400 mt-1">
                {{ form.status === 'published' ? 'Course will be visible to students in the public catalogue.' : 'Course is hidden and saved as a private draft.' }}
              </p>
            </div>

            <!-- Featured Image Upload Card -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-3">
              <h3 class="text-sm font-bold text-[#1A2B49] uppercase tracking-wide">Featured Image</h3>
              
              <div 
                @click="triggerImageUpload"
                class="border-2 border-dashed border-slate-200 hover:border-[#264790] hover:bg-slate-50/50 rounded-2xl aspect-[1.8/1] flex flex-col items-center justify-center p-4 text-center cursor-pointer transition-all duration-300 group overflow-hidden relative"
              >
                <div v-if="featuredImagePreview" class="absolute inset-0 w-full h-full">
                  <img :src="featuredImagePreview" class="w-full h-full object-cover" />
                  <div class="absolute inset-0 bg-[#1A2B49]/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                    <Upload :size="18" class="mr-1.5" /> Ganti Gambar
                  </div>
                </div>
                <div v-else class="flex flex-col items-center">
                  <Upload :size="28" class="text-slate-300 group-hover:text-[#264790] transition-colors mb-2" />
                  <span class="text-xs font-bold text-[#1A2B49] mb-1">Drag and drop or click to upload featured image</span>
                  <span class="text-[10px] text-slate-400">Supports PNG, JPG, JPEG (Max 2MB)</span>
                </div>
              </div>
            </div>

            <!-- Intro Video Card -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-3">
              <h3 class="text-sm font-bold text-[#1A2B49] uppercase tracking-wide">Intro Video</h3>
              
              <div class="relative flex items-center">
                <span class="absolute left-4.5 text-slate-400"><Video :size="16" /></span>
                <input 
                  v-model="youtubeVideoUrl"
                  type="text" 
                  placeholder="YouTube Video Link" 
                  class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-2xl pl-11 pr-4 py-3 outline-none text-[#1A2B49] font-medium text-xs transition-colors"
                />
              </div>

              <!-- Gray Mock Player -->
              <div class="bg-slate-100 rounded-2xl aspect-[1.8/1] flex items-center justify-center text-slate-400 shadow-inner">
                <Play :size="32" fill="currentColor" class="text-slate-300 cursor-pointer hover:text-[#264790] transition-colors" />
              </div>
            </div>

            <!-- Pricing Model Card -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-4">
              <h3 class="text-sm font-bold text-[#1A2B49] uppercase tracking-wide">Pricing Model</h3>
              
              <!-- Free vs Paid Radio Buttons -->
              <div class="flex items-center gap-4">
                <button 
                  @click="setPricingModel('free')"
                  class="flex items-center gap-2 font-bold text-xs"
                  :class="pricingModel === 'free' ? 'text-[#264790]' : 'text-slate-400'"
                >
                  <span :class="pricingModel === 'free' ? 'bg-[#264790] ring-4 ring-[#264790]/15' : 'bg-slate-100'" class="w-4 h-4 rounded-full flex items-center justify-center border transition-all">
                    <span v-if="pricingModel === 'free'" class="w-1.5 h-1.5 bg-white rounded-full"></span>
                  </span>
                  Free
                </button>
                <button 
                  @click="setPricingModel('paid')"
                  class="flex items-center gap-2 font-bold text-xs"
                  :class="pricingModel === 'paid' ? 'text-[#264790]' : 'text-slate-400'"
                >
                  <span :class="pricingModel === 'paid' ? 'bg-[#264790] ring-4 ring-[#264790]/15' : 'bg-slate-100'" class="w-4 h-4 rounded-full flex items-center justify-center border transition-all">
                    <span v-if="pricingModel === 'paid'" class="w-1.5 h-1.5 bg-white rounded-full"></span>
                  </span>
                  Paid
                </button>
              </div>

              <!-- Extra fields if Paid -->
              <div v-if="pricingModel === 'paid'" class="flex flex-col gap-3.5 pt-2 border-t border-slate-100">
                <div>
                  <label class="block text-slate-400 text-[10px] font-bold mb-1.5 uppercase tracking-wide">Regular Price Product</label>
                  <select 
                    class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] rounded-xl px-3 py-2 outline-none text-xs text-[#1A2B49] font-bold cursor-pointer"
                  >
                    <option>Select product</option>
                    <option v-for="cat in categories" :key="cat.id">{{ cat.name }}</option>
                  </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-slate-400 text-[10px] font-bold mb-1.5 uppercase tracking-wide">Regular Price</label>
                    <div class="relative flex items-center">
                      <span class="absolute left-3 text-slate-400 text-xs font-bold">Rp</span>
                      <input 
                        v-model.number="form.price"
                        type="number" 
                        class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-xl pl-9 pr-3 py-2 outline-none text-xs text-[#1A2B49] font-bold"
                      />
                    </div>
                  </div>
                  <div>
                    <label class="block text-slate-400 text-[10px] font-bold mb-1.5 uppercase tracking-wide">Sale Price</label>
                    <div class="relative flex items-center">
                      <span class="absolute left-3 text-slate-400 text-xs font-bold">Rp</span>
                      <input 
                        v-model.number="salePrice"
                        type="number" 
                        placeholder="Sale Price"
                        class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-xl pl-9 pr-3 py-2 outline-none text-xs text-[#1A2B49] font-bold"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Categories Card -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-3">
              <h3 class="text-sm font-bold text-[#1A2B49] uppercase tracking-wide">Categories</h3>
              
              <div class="relative flex items-center">
                <span class="absolute left-3.5 text-slate-400"><Search :size="14" /></span>
                <input 
                  v-model="searchCategoryQuery"
                  type="text" 
                  placeholder="Search" 
                  class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-xl pl-9 pr-3 py-2 outline-none text-xs text-[#1A2B49] font-medium"
                />
              </div>

              <!-- Categories List Checkbox mockup -->
              <div class="max-h-48 overflow-y-auto flex flex-col gap-2 mt-2 pr-1">
                <div 
                  v-for="cat in filteredCategories" 
                  :key="cat.id" 
                  class="flex items-center justify-between text-xs text-slate-600 font-bold hover:text-[#264790] cursor-pointer"
                  @click="form.category_id = cat.id"
                >
                  <div class="flex items-center gap-2">
                    <span :class="form.category_id === cat.id ? 'bg-[#264790] border-transparent' : 'bg-white border-slate-200'" class="w-4 h-4 border rounded flex items-center justify-center transition-colors">
                      <Check v-if="form.category_id === cat.id" :size="10" class="text-white" />
                    </span>
                    <span class="text-slate-400">📁</span>
                    <span>{{ cat.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tags Card -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-3">
              <h3 class="text-sm font-bold text-[#1A2B49] uppercase tracking-wide">Tags</h3>
              
              <div class="relative flex items-center">
                <span class="absolute left-3.5 text-slate-400"><Search :size="14" /></span>
                <input 
                  v-model="searchTagQuery"
                  type="text" 
                  placeholder="Tags" 
                  class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-xl pl-9 pr-3 py-2 outline-none text-xs text-[#1A2B49] font-medium"
                />
              </div>

              <!-- Tags list -->
              <div class="flex flex-wrap gap-1.5 mt-2">
                <button 
                  v-for="t in filteredTags" 
                  :key="t.id"
                  @click="toggleTag(t.id)"
                  :class="form.tags.includes(t.id) ? 'bg-[#264790]/10 text-[#264790] border-[#264790]/25' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 border-slate-200'"
                  class="px-2.5 py-1 text-[10px] font-extrabold rounded-lg border transition-all"
                >
                  {{ t.name }}
                </button>
              </div>
            </div>

            <!-- Author Card -->
            <div class="bg-white rounded-3xl p-4 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#44A6D9] text-white flex items-center justify-center font-bold text-sm">
                  {{ authUser.name ? authUser.name.charAt(0).toUpperCase() : 'A' }}
                </div>
                <div>
                  <span class="block text-xs font-bold text-[#1A2B49]">{{ authUser.name }}</span>
                  <span class="block text-[10px] text-slate-400">{{ authUser.email }}</span>
                </div>
              </div>
              <ChevronDown :size="16" class="text-slate-400 cursor-pointer" />
            </div>

          </div>

        </div>

        <!-- STEP 2: CURRICULUM BUILDER -->
        <div v-if="currentStep === 2" class="bg-white rounded-3xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100">
          
          <!-- CTA Header -->
          <div class="flex items-center justify-between mb-8">
            <h3 class="text-lg font-extrabold text-[#1A2B49] flex items-center gap-2">
              <Layout :size="20" class="text-[#264790]" /> Struktur Silabus Kelas
            </h3>
            <button 
              @click="openModuleModal()"
              class="bg-white hover:bg-slate-50 text-[#264790] border border-[#264790]/20 px-4.5 py-2.5 rounded-full font-bold text-xs shadow-sm transition-colors flex items-center gap-1.5"
            >
              <PlusCircle :size="16" /> Tambah Bab Baru
            </button>
          </div>

          <!-- Modules Outline -->
          <div class="flex flex-col gap-6">
            <div 
              v-for="(mod, modIndex) in modules" 
              :key="mod.id" 
              class="bg-white rounded-2xl p-6 border border-slate-150 shadow-sm"
            >
              
              <!-- Module Header -->
              <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <div class="flex items-center gap-3">
                  <span class="w-7 h-7 rounded-lg bg-indigo-50 text-[#264790] flex items-center justify-center text-xs font-bold">
                    {{ modIndex + 1 }}
                  </span>
                  <h4 class="font-extrabold text-base text-[#1A2B49]">{{ mod.title }}</h4>
                </div>
                <div class="flex items-center gap-2">
                  <button @click="openModuleModal(mod)" class="p-2 hover:bg-slate-100 rounded-xl text-slate-400 hover:text-slate-600 transition-colors">
                    <Edit :size="16" />
                  </button>
                  <button @click="deleteModule(mod.id)" class="p-2 hover:bg-rose-50 rounded-xl text-slate-400 hover:text-rose-600 transition-colors">
                    <Trash2 :size="16" />
                  </button>
                </div>
              </div>

              <!-- Module Content Grid (Lessons & Quizzes) -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Lessons Column -->
                <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100/50">
                  <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Materi & Tugas ({{ mod.lessons?.length || 0 }})</span>
                    <div class="flex items-center gap-3">
                      <button @click="openLessonModal(mod)" class="text-[10px] font-bold text-[#264790] hover:text-[#44A6D9] flex items-center gap-1">
                        <Plus :size="10" /> Sesi
                      </button>
                      <button @click="openAssignmentModal(mod)" class="text-[10px] font-bold text-purple-600 hover:text-purple-700 flex items-center gap-1">
                        <Plus :size="10" /> Tugas
                      </button>
                    </div>
                  </div>

                  <div class="flex flex-col gap-3">
                    <div 
                      v-for="les in mod.lessons" 
                      :key="les.id"
                      class="bg-white p-3.5 rounded-2xl border border-slate-100 flex items-center justify-between hover:border-slate-200 transition-colors"
                    >
                      <div class="flex items-center gap-3">
                        <Video v-if="!les.content || !les.content.startsWith('{') || JSON.parse(les.content).type === 'video'" :size="16" class="text-[#264790]" />
                        <Presentation v-else-if="JSON.parse(les.content).type === 'ppt'" :size="16" class="text-emerald-600" />
                        <FileText v-else-if="JSON.parse(les.content).type === 'slides'" :size="16" class="text-amber-500" />
                        <FileText v-else-if="JSON.parse(les.content).type === 'assignment'" :size="16" class="text-purple-600" />
                        <BookOpen v-else :size="16" class="text-slate-400" />
                        <div>
                          <div class="font-bold text-xs text-[#1A2B49]">{{ les.title }}</div>
                          <div class="text-[10px] text-slate-400 font-semibold flex items-center gap-1.5 mt-0.5">
                            <span v-if="(!les.content || !les.content.startsWith('{')) || (les.content.startsWith('{') && JSON.parse(les.content).type !== 'assignment')">{{ les.duration_minutes }} Menit</span>
                            <span v-if="les.content && les.content.startsWith('{') && JSON.parse(les.content).type === 'ppt'" class="px-1.5 py-0.5 bg-emerald-50 text-emerald-650 text-[8px] font-bold rounded">PPT Manual</span>
                            <span v-else-if="les.content && les.content.startsWith('{') && JSON.parse(les.content).type === 'slides'" class="px-1.5 py-0.5 bg-amber-50 text-amber-600 text-[8px] font-bold rounded">Canva / Slides</span>
                            <span v-else-if="les.content && les.content.startsWith('{') && JSON.parse(les.content).type === 'assignment'" class="px-1.5 py-0.5 bg-purple-50 text-purple-600 text-[8px] font-bold rounded">Assignment</span>
                            <span v-else class="px-1.5 py-0.5 bg-indigo-50 text-[#264790] text-[8px] font-bold rounded">Video</span>
                          </div>
                        </div>
                      </div>
                      <div class="flex items-center gap-1.5">
                        <button v-if="les.content && les.content.startsWith('{') && JSON.parse(les.content).type === 'assignment'" @click="openAssignmentModal(mod, les)" class="p-1 hover:bg-slate-50 rounded-lg text-slate-400 hover:text-slate-600">
                          <Edit :size="14" />
                        </button>
                        <button v-else @click="openLessonModal(mod, les)" class="p-1 hover:bg-slate-50 rounded-lg text-slate-400 hover:text-slate-600">
                          <Edit :size="14" />
                        </button>
                        <button @click="deleteLesson(mod, les.id)" class="p-1 hover:bg-rose-50 rounded-lg text-slate-400 hover:text-rose-600">
                          <Trash :size="14" />
                        </button>
                      </div>
                    </div>
                    
                    <div v-if="!mod.lessons || mod.lessons.length === 0" class="text-center py-4 text-xs font-semibold text-slate-400">
                      Belum ada materi pembelajaran atau tugas.
                    </div>
                  </div>
                </div>

                <!-- Quizzes Column -->
                <div class="bg-slate-50/50 rounded-2xl p-5 border border-slate-100/50">
                  <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Evaluasi Kuis ({{ mod.quizzes?.length || 0 }})</span>
                    <button @click="openQuizModal(mod)" class="text-xs font-bold text-[#264790] hover:text-[#44A6D9] flex items-center gap-1">
                      <Plus :size="12" /> Tambah Kuis
                    </button>
                  </div>

                  <div class="flex flex-col gap-3">
                    <div 
                      v-for="qz in mod.quizzes" 
                      :key="qz.id"
                      class="bg-white p-3.5 rounded-2xl border border-slate-100 flex flex-col gap-3 hover:border-slate-200 transition-colors"
                    >
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                          <HelpCircle :size="16" class="text-amber-500" />
                          <div>
                            <div class="font-bold text-xs text-[#1A2B49]">{{ qz.title }}</div>
                            <div class="text-[10px] text-slate-400 font-medium">{{ qz.time_limit_minutes }} Menit • {{ qz.questions?.length || 0 }} Pertanyaan</div>
                          </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                          <button @click="openQuizSettingsModal(mod, qz)" class="p-1 hover:bg-amber-50 rounded-lg text-slate-400 hover:text-amber-600" title="Quiz Settings">
                            <Settings :size="14" />
                          </button>
                          <button @click="openQuizModal(mod, qz)" class="p-1 hover:bg-slate-50 rounded-lg text-slate-400 hover:text-slate-600" title="Edit Quiz Basic Settings">
                            <Edit :size="14" />
                          </button>
                          <button @click="deleteQuiz(mod, qz.id)" class="p-1 hover:bg-rose-50 rounded-lg text-slate-400 hover:text-rose-600" title="Delete Quiz">
                            <Trash :size="14" />
                          </button>
                        </div>
                      </div>

                      <!-- Sub-question list inside quiz -->
                      <div class="bg-slate-50 p-3 rounded-xl flex flex-col gap-2">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5 mb-1.5">
                          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pertanyaan Kuis</span>
                          <button @click="openQuestionModal(qz)" class="text-[10px] font-bold text-[#264790] hover:text-[#264790] flex items-center gap-0.5">
                            <Plus :size="10" /> Tambah Soal
                          </button>
                        </div>
                        
                        <div v-for="(qst, qstIdx) in qz.questions" :key="qst.id" class="flex items-center justify-between text-xs text-[#1A2B49] font-medium p-1.5 bg-white rounded-lg border border-slate-100 gap-2">
                          <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="line-clamp-1 shrink-0 text-slate-400">{{ qstIdx + 1 }}.</span>
                            <span class="line-clamp-1 flex-1">{{ qst.question_text }}</span>
                            <span v-if="qst.options && qst.options[0] === '[TRUE_FALSE]'" class="px-1.5 py-0.5 bg-indigo-50 text-[#264790] text-[8px] font-bold rounded shrink-0">True/False</span>
                            <span v-else-if="qst.options && qst.options[0] === '[ESSAY]'" class="px-1.5 py-0.5 bg-purple-50 text-purple-600 text-[8px] font-bold rounded shrink-0">Esai</span>
                            <span v-else-if="qst.options && qst.options[0] === '[MATH_FORMULA]'" class="px-1.5 py-0.5 bg-amber-50 text-amber-600 text-[8px] font-bold rounded shrink-0">Matematika</span>
                            <span v-else class="px-1.5 py-0.5 bg-slate-50 text-slate-500 text-[8px] font-bold rounded shrink-0">Pilihan Ganda</span>
                          </div>
                          <div class="flex items-center gap-1 shrink-0">
                            <button @click="openQuestionModal(qz, qst)" class="p-0.5 hover:bg-slate-50 text-slate-400 hover:text-slate-600"><Edit :size="10" /></button>
                            <button @click="deleteQuestion(qz, qst.id)" class="p-0.5 hover:bg-rose-50 text-slate-400 hover:text-rose-600"><Trash :size="10" /></button>
                          </div>
                        </div>
                        <div v-if="!qz.questions || qz.questions.length === 0" class="text-center py-2 text-[10px] text-slate-400 font-semibold">
                          Belum ada pertanyaan dibuat.
                        </div>
                      </div>
                    </div>

                    <div v-if="!mod.quizzes || mod.quizzes.length === 0" class="text-center py-4 text-xs font-semibold text-slate-400">
                      Belum ada evaluasi kuis.
                    </div>
                  </div>
                </div>

              </div>

            </div>
            
            <div v-if="modules.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-150 shadow-sm">
              <p class="text-slate-400 font-medium mb-4">Silabus kelas masih kosong.</p>
              <button 
                @click="openModuleModal()"
                class="bg-[#264790] hover:bg-[#44A6D9] text-white px-6 py-2.5 rounded-full font-bold text-xs shadow-sm transition-colors"
              >
                Mulai dengan Membuat Bab Pertama
              </button>
            </div>
          </div>

        </div>

        <!-- STEP 3: ADDITIONAL SETTINGS -->
        <div v-if="currentStep === 3" class="grid grid-cols-1 lg:grid-cols-3 gap-8 animate-fade-in text-slate-800">
          
          <!-- Left Column (Overview & Certificate Templates) -->
          <div class="lg:col-span-2 flex flex-col gap-8">
            
            <!-- OVERVIEW CARD -->
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-6">
              <div>
                <h3 class="text-xl font-extrabold text-[#1A2B49] tracking-tight">Overview</h3>
                <p class="text-slate-400 text-xs font-semibold mt-1">Lengkapi informasi tambahan untuk mengenalkan kelas Anda kepada calon siswa secara profesional.</p>
              </div>

              <!-- What Will I Learn -->
              <div>
                <label class="block text-slate-500 text-xs font-extrabold mb-2 uppercase tracking-wider">What Will I Learn?</label>
                <div class="flex flex-col border border-slate-200 rounded-2xl overflow-hidden shadow-sm focus-within:ring-2 focus-within:ring-[#264790]/20 focus-within:border-[#264790] transition-all">
                  <!-- Toolbar -->
                  <div class="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-200 flex-wrap">
                    <button type="button" @click="execCommand('bold')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">B</button>
                    <button type="button" @click="execCommand('italic')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 italic transition-colors">I</button>
                    <button type="button" @click="execCommand('underline')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 underline transition-colors">U</button>
                    <button type="button" @click="execCommand('strikeThrough')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 line-through transition-colors">S</button>
                    <div class="w-px h-5 bg-slate-300 mx-1"></div>
                    <button type="button" @click="execCommand('insertUnorderedList')" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">• List</button>
                    <button type="button" @click="execCommand('insertOrderedList')" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">1. List</button>
                    <div class="w-px h-5 bg-slate-300 mx-1"></div>
                    <button type="button" @click="addLinkPrompt" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">Link</button>
                    <button type="button" @click="addImagePrompt" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">Image</button>
                    <button type="button" @click="insertCodeBlock" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-mono font-bold transition-colors">&lt;/&gt;</button>
                    <div class="w-px h-5 bg-slate-300 mx-1"></div>
                    <button type="button" @click="execCommand('undo')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">↶</button>
                    <button type="button" @click="execCommand('redo')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">↷</button>
                  </div>
                  <!-- Content Area -->
                  <div 
                    contenteditable="true"
                    @blur="additionalForm.what_will_learn = $event.target.innerHTML"
                    @input="additionalForm.what_will_learn = $event.target.innerHTML"
                    class="p-4 min-h-[120px] max-h-[250px] overflow-y-auto outline-none bg-white text-slate-850 text-sm leading-relaxed prose prose-sm max-w-none"
                    v-html="additionalForm.what_will_learn"
                  ></div>
                </div>
              </div>

              <!-- Target Audience -->
              <div>
                <label class="block text-slate-500 text-xs font-extrabold mb-2 uppercase tracking-wider">Target Audience</label>
                <textarea 
                  v-model="additionalForm.target_audience" 
                  rows="3" 
                  placeholder="Contoh: Siswa SMA, Mahasiswa, Developer Pemula, Umum..." 
                  class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
                ></textarea>
              </div>

              <!-- Total Duration & Materials Included -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-slate-500 text-xs font-extrabold mb-2 uppercase tracking-wider">Total Course Duration</label>
                  <div class="relative flex items-center">
                    <input 
                      v-model.number="additionalForm.duration_hours" 
                      type="number" 
                      placeholder="5" 
                      class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-2xl pl-4 pr-16 py-3 outline-none text-[#1A2B49] font-bold transition-colors text-sm" 
                    />
                    <span class="absolute right-4 text-xs font-bold text-slate-400">hour(s)</span>
                  </div>
                </div>

                <div>
                  <label class="block text-slate-500 text-xs font-extrabold mb-2 uppercase tracking-wider">Materials Included</label>
                  <div class="relative flex items-center">
                    <input 
                      v-model.number="additionalForm.materials_included" 
                      type="number" 
                      placeholder="30" 
                      class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-2xl pl-4 pr-16 py-3 outline-none text-[#1A2B49] font-bold transition-colors text-sm" 
                    />
                    <span class="absolute right-4 text-xs font-bold text-slate-400">min(s)</span>
                  </div>
                </div>
              </div>

              <!-- Requirements / Instructions -->
              <div>
                <label class="block text-slate-500 text-xs font-extrabold mb-2 uppercase tracking-wider">Requirements / Instructions</label>
                <div class="flex flex-col border border-slate-200 rounded-2xl overflow-hidden shadow-sm focus-within:ring-2 focus-within:ring-[#264790]/20 focus-within:border-[#264790] transition-all">
                  <!-- Toolbar -->
                  <div class="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-200 flex-wrap">
                    <button type="button" @click="execCommand('bold')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">B</button>
                    <button type="button" @click="execCommand('italic')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 italic transition-colors">I</button>
                    <button type="button" @click="execCommand('underline')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 underline transition-colors">U</button>
                    <button type="button" @click="execCommand('strikeThrough')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 line-through transition-colors">S</button>
                    <div class="w-px h-5 bg-slate-300 mx-1"></div>
                    <button type="button" @click="execCommand('insertUnorderedList')" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">• List</button>
                    <button type="button" @click="execCommand('insertOrderedList')" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">1. List</button>
                    <div class="w-px h-5 bg-slate-300 mx-1"></div>
                    <button type="button" @click="addLinkPrompt" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">Link</button>
                    <button type="button" @click="addImagePrompt" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-bold transition-colors">Image</button>
                    <button type="button" @click="insertCodeBlock" class="px-2 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 text-xs font-mono font-bold transition-colors">&lt;/&gt;</button>
                    <div class="w-px h-5 bg-slate-300 mx-1"></div>
                    <button type="button" @click="execCommand('undo')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">↶</button>
                    <button type="button" @click="execCommand('redo')" class="w-8 h-8 flex items-center justify-center hover:bg-slate-200 rounded text-slate-700 font-bold transition-colors">↷</button>
                  </div>
                  <!-- Content Area -->
                  <div 
                    contenteditable="true"
                    @blur="additionalForm.requirements = $event.target.innerHTML"
                    @input="additionalForm.requirements = $event.target.innerHTML"
                    class="p-4 min-h-[120px] max-h-[250px] overflow-y-auto outline-none bg-white text-slate-850 text-sm leading-relaxed prose prose-sm max-w-none"
                    v-html="additionalForm.requirements"
                  ></div>
                </div>
              </div>
            </div>

            <!-- CERTIFICATE TEMPLATES CARD -->
            <div class="bg-white rounded-3xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-6">
              <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                  <h3 class="text-xl font-extrabold text-[#1A2B49] tracking-tight">Certificate Templates</h3>
                  <p class="text-slate-400 text-xs font-semibold mt-1">Pilih desain sertifikat kelulusan bagi siswa atau unggah desain kustom Anda sendiri.</p>
                </div>
                <button 
                  type="button" 
                  @click="triggerCertificateUpload"
                  class="bg-white border-2 border-slate-200 hover:border-[#264790] text-[#1A2B49] hover:text-[#264790] px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                >
                  <Upload :size="14" /> Upload Template Baru
                </button>
              </div>

              <!-- Certificate Preview Grid -->
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                <!-- Predesigned Template 1 -->
                <div 
                  @click="additionalForm.selected_certificate = 'template_1'"
                  :class="additionalForm.selected_certificate === 'template_1' ? 'border-2 border-[#264790] ring-4 ring-[#264790]/10' : 'border border-slate-200'"
                  class="rounded-xl overflow-hidden cursor-pointer relative group bg-white shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                >
                  <div class="h-28 bg-slate-50 flex items-center justify-center p-3 relative">
                    <svg class="w-full h-full border border-slate-100 rounded bg-white shadow-[0_1px_4px_rgba(0,0,0,0.02)]" viewBox="0 0 160 110">
                      <rect x="5" y="5" width="150" height="100" fill="none" stroke="#264790" stroke-width="2"/>
                      <rect x="8" y="8" width="144" height="94" fill="none" stroke="#44A6D9" stroke-width="0.5"/>
                      <text x="80" y="32" font-size="7" font-weight="bold" fill="#264790" text-anchor="middle">CERTIFICATE OF COMPLETION</text>
                      <text x="80" y="52" font-size="5" fill="#1A2B49" text-anchor="middle" font-family="serif">This is proudly presented to</text>
                      <line x1="40" y1="72" x2="120" y2="72" stroke="#94A3B8" stroke-width="0.5"/>
                      <text x="80" y="82" font-size="5" fill="#64748B" text-anchor="middle">Name Surname</text>
                    </svg>
                  </div>
                  <div class="p-2 text-center text-[10px] font-bold text-slate-700 bg-slate-50 border-t border-slate-100">Classic Blue</div>
                </div>

                <!-- Predesigned Template 2 -->
                <div 
                  @click="additionalForm.selected_certificate = 'template_2'"
                  :class="additionalForm.selected_certificate === 'template_2' ? 'border-2 border-[#264790] ring-4 ring-[#264790]/10' : 'border border-slate-200'"
                  class="rounded-xl overflow-hidden cursor-pointer relative group bg-white shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                >
                  <div class="h-28 bg-slate-50 flex items-center justify-center p-3 relative">
                    <svg class="w-full h-full border border-slate-100 rounded bg-white shadow-[0_1px_4px_rgba(0,0,0,0.02)]" viewBox="0 0 160 110">
                      <rect x="4" y="4" width="152" height="102" fill="none" stroke="#0F172A" stroke-width="1.5"/>
                      <circle cx="80" cy="18" r="8" fill="#F1F5F9" />
                      <path d="M 80,14 L 83,22 L 75,17 L 85,17 L 77,22 Z" fill="#E2E8F0" />
                      <text x="80" y="40" font-size="8" font-weight="bold" fill="#0F172A" text-anchor="middle" font-family="monospace">CERTIFICATE</text>
                      <text x="80" y="55" font-size="4" fill="#64748B" text-anchor="middle">Awarded to student</text>
                      <line x1="45" y1="75" x2="115" y2="75" stroke="#475569" stroke-width="0.5"/>
                      <text x="80" y="85" font-size="5" fill="#0F172A" text-anchor="middle" font-weight="bold">Name Surname</text>
                    </svg>
                  </div>
                  <div class="p-2 text-center text-[10px] font-bold text-slate-700 bg-slate-50 border-t border-slate-100">Modern Slate</div>
                </div>

                <!-- Predesigned Template 3 -->
                <div 
                  @click="additionalForm.selected_certificate = 'template_3'"
                  :class="additionalForm.selected_certificate === 'template_3' ? 'border-2 border-[#264790] ring-4 ring-[#264790]/10' : 'border border-slate-200'"
                  class="rounded-xl overflow-hidden cursor-pointer relative group bg-white shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                >
                  <div class="h-28 bg-slate-50 flex items-center justify-center p-3 relative">
                    <svg class="w-full h-full border border-slate-100 rounded bg-white shadow-[0_1px_4px_rgba(0,0,0,0.02)]" viewBox="0 0 160 110">
                      <rect x="5" y="5" width="150" height="100" fill="none" stroke="#D97706" stroke-width="2"/>
                      <text x="80" y="32" font-size="7" font-weight="bold" fill="#B45309" text-anchor="middle">CERTIFICATE OF ACHIEVEMENT</text>
                      <text x="80" y="52" font-size="5" fill="#78350F" text-anchor="middle">For successfully completing the program</text>
                      <line x1="40" y1="72" x2="120" y2="72" stroke="#F59E0B" stroke-width="0.5"/>
                      <text x="80" y="82" font-size="5" fill="#B45309" text-anchor="middle">Name Surname</text>
                    </svg>
                  </div>
                  <div class="p-2 text-center text-[10px] font-bold text-slate-700 bg-slate-50 border-t border-slate-100">Golden Luxury</div>
                </div>

                <!-- Predesigned Template 4 -->
                <div 
                  @click="additionalForm.selected_certificate = 'template_4'"
                  :class="additionalForm.selected_certificate === 'template_4' ? 'border-2 border-[#264790] ring-4 ring-[#264790]/10' : 'border border-slate-200'"
                  class="rounded-xl overflow-hidden cursor-pointer relative group bg-white shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                >
                  <div class="h-28 bg-slate-50 flex items-center justify-center p-3 relative">
                    <svg class="w-full h-full border border-slate-100 rounded bg-white shadow-[0_1px_4px_rgba(0,0,0,0.02)]" viewBox="0 0 160 110">
                      <rect x="5" y="5" width="150" height="100" fill="none" stroke="#059669" stroke-width="2"/>
                      <text x="80" y="32" font-size="7" font-weight="bold" fill="#059669" text-anchor="middle">CERTIFICATE OF TRAINING</text>
                      <text x="80" y="52" font-size="5" fill="#047857" text-anchor="middle">This certifies the completion of course</text>
                      <line x1="40" y1="72" x2="120" y2="72" stroke="#10B981" stroke-width="0.5"/>
                      <text x="80" y="82" font-size="5" fill="#059669" text-anchor="middle">Name Surname</text>
                    </svg>
                  </div>
                  <div class="p-2 text-center text-[10px] font-bold text-slate-700 bg-slate-50 border-t border-slate-100">Emerald Mint</div>
                </div>

                <!-- Custom Templates Uploaded by user -->
                <div 
                  v-for="custom in additionalForm.custom_certificates" 
                  :key="custom.id"
                  @click="additionalForm.selected_certificate = custom.id"
                  :class="additionalForm.selected_certificate === custom.id ? 'border-2 border-[#264790] ring-4 ring-[#264790]/10' : 'border border-slate-200'"
                  class="rounded-xl overflow-hidden cursor-pointer relative group bg-white shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
                >
                  <div class="h-28 bg-slate-50 flex items-center justify-center p-2 relative">
                    <img :src="custom.url" class="max-w-full max-h-full object-contain rounded shadow-sm border border-slate-200" />
                    <button 
                      type="button" 
                      @click.stop="additionalForm.custom_certificates = additionalForm.custom_certificates.filter(c => c.id !== custom.id); if (additionalForm.selected_certificate === custom.id) additionalForm.selected_certificate = 'template_1';"
                      class="absolute top-1 right-1 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow transition-colors"
                    >
                      <X :size="10" />
                    </button>
                  </div>
                  <div class="p-2 text-center text-[9px] font-semibold text-slate-700 bg-slate-50 border-t border-slate-100 truncate" :title="custom.name">{{ custom.name }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column (Configs/Course Prerequisites/Attachments/Live Class) -->
          <div class="flex flex-col gap-8">
            
            <!-- COURSE PREREQUISITES -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-4">
              <h3 class="text-sm font-extrabold text-[#1A2B49] uppercase tracking-wide">Course Prerequisites</h3>
              
              <!-- Search Box -->
              <div class="relative">
                <Search :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input 
                  v-model="prerequisiteSearchQuery" 
                  type="text" 
                  placeholder="Search courses for prerequisites" 
                  class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-[#264790] focus:bg-white rounded-xl pl-10 pr-4 py-2 outline-none text-xs text-[#1A2B49] font-medium transition-all" 
                />
              </div>

              <!-- Search Results Dropdown -->
              <div v-if="prerequisiteSearchQuery && filteredPrerequisites.length > 0" class="max-h-48 overflow-y-auto border border-slate-150 rounded-xl bg-white shadow-lg p-1.5 flex flex-col gap-1 z-10">
                <button 
                  v-for="c in filteredPrerequisites" 
                  :key="c.id"
                  type="button"
                  @click="addPrerequisite(c.id); prerequisiteSearchQuery='';"
                  class="flex items-center gap-2.5 p-2 hover:bg-slate-50 rounded-lg text-left transition-colors w-full"
                >
                  <img :src="c.thumbnail ? `/storage/${c.thumbnail}` : '/storage/courses/thumbnails/default.jpg'" class="w-8 h-8 rounded-lg object-cover border border-slate-200" />
                  <div class="flex-grow min-w-0">
                    <p class="text-xs font-bold text-slate-800 truncate">{{ c.title }}</p>
                  </div>
                  <Plus :size="12" class="text-[#264790] shrink-0" />
                </button>
              </div>

              <!-- Selected Prerequisites list -->
              <div class="mt-2 flex flex-col gap-2">
                <div v-if="additionalForm.prerequisites.length === 0" class="text-center py-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200 flex flex-col items-center justify-center p-4">
                  <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                    <Search :size="18" />
                  </div>
                  <span class="text-xs font-bold text-slate-800">No course selected</span>
                  <span class="text-[10px] text-slate-400 mt-0.5 text-center">Select a course to add as a prerequisite.</span>
                </div>
                
                <div 
                  v-for="prereqId in additionalForm.prerequisites" 
                  :key="prereqId"
                  class="flex items-center justify-between gap-3 p-3 bg-slate-50 hover:bg-slate-100/70 border border-slate-150 rounded-2xl transition-colors"
                >
                  <div class="flex items-center gap-2.5 min-w-0">
                    <img :src="getPrerequisiteCourse(prereqId)?.thumbnail ? `/storage/${getPrerequisiteCourse(prereqId).thumbnail}` : '/storage/courses/thumbnails/default.jpg'" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shrink-0" />
                    <span class="text-xs font-bold text-[#1A2B49] truncate leading-tight">{{ getPrerequisiteCourse(prereqId)?.title || 'Kursus Prasyarat' }}</span>
                  </div>
                  <button 
                    type="button" 
                    @click="removePrerequisite(prereqId)"
                    class="p-1.5 hover:bg-red-50 text-red-500 rounded-lg transition-colors shrink-0"
                  >
                    <Trash2 :size="14" />
                  </button>
                </div>
              </div>
            </div>

            <!-- ATTACHMENTS -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-4">
              <h3 class="text-sm font-extrabold text-[#1A2B49] uppercase tracking-wide">Attachments</h3>
              
              <button 
                type="button"
                @click="triggerAttachmentUpload"
                class="w-full border-2 border-dashed border-slate-200 hover:border-[#264790] rounded-2xl py-3 flex items-center justify-center gap-2 text-xs font-bold text-slate-500 hover:text-[#264790] bg-slate-50/50 hover:bg-slate-50 transition-all cursor-pointer"
              >
                <Paperclip :size="14" /> Upload Attachment
              </button>

              <!-- Uploaded Attachments List -->
              <div v-if="additionalForm.attachments.length > 0" class="flex flex-col gap-2">
                <div 
                  v-for="attach in additionalForm.attachments" 
                  :key="attach.id"
                  class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-150 rounded-xl"
                >
                  <div class="flex items-center gap-2 min-w-0">
                    <FileText :size="16" class="text-[#264790] shrink-0" />
                    <div class="min-w-0">
                      <p class="text-xs font-bold text-slate-800 truncate max-w-[130px]">{{ attach.name }}</p>
                      <p class="text-[9px] text-slate-400 font-medium">{{ attach.size }}</p>
                    </div>
                  </div>
                  <button 
                    type="button" 
                    @click="removeAttachment(attach.id)"
                    class="text-red-500 hover:text-red-600 hover:bg-red-50 p-1.5 rounded-lg transition-colors"
                  >
                    <X :size="14" />
                  </button>
                </div>
              </div>
            </div>

            <!-- SCHEDULE LIVE CLASS -->
            <div class="bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-100 flex flex-col gap-4">
              <h3 class="text-sm font-extrabold text-[#1A2B49] uppercase tracking-wide">Schedule Live Class</h3>

              <div v-if="additionalForm.class_type === 'Online' || additionalForm.class_type === 'Hybrid'" class="flex flex-col gap-4">
                <!-- Zoom Link Button -->
                <div class="flex flex-col gap-1.5">
                  <button 
                    type="button" 
                    @click="openMeetingModal('zoom')"
                    :class="additionalForm.live_zoom_link ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-[#EBF3FF] border border-[#BFDBFE] text-[#1E40AF] hover:bg-[#DBEAFE]'"
                    class="w-full py-3.5 rounded-2xl font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer"
                  >
                    <Video :size="16" /> {{ additionalForm.live_zoom_link ? 'Zoom Meeting Linked' : 'Create a Zoom Meeting' }}
                  </button>
                  <div v-if="additionalForm.live_zoom_link" class="flex items-center justify-between px-2 text-[10px] text-slate-500">
                    <span class="truncate pr-4" :title="additionalForm.live_zoom_link">{{ additionalForm.live_zoom_link }}</span>
                    <button type="button" @click="additionalForm.live_zoom_link = ''" class="text-red-500 hover:underline">Remove</button>
                  </div>
                </div>

                <!-- Google Meet Button -->
                <div class="flex flex-col gap-1.5">
                  <button 
                    type="button" 
                    @click="openMeetingModal('gmeet')"
                    :class="additionalForm.live_gmeet_link ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-[#ECFDF5] border border-[#A7F3D0] text-[#065F46] hover:bg-[#D1FAE5]'"
                    class="w-full py-3.5 rounded-2xl font-bold text-xs shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer"
                  >
                    <!-- Google Meet colored look -->
                    <svg v-if="!additionalForm.live_gmeet_link" class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                      <path d="M16 10v-3.5c0-1.38-1.12-2.5-2.5-2.5h-9c-1.38 0-2.5 1.12-2.5 2.5v11c0 1.38 1.12 2.5 2.5 2.5h9c1.38 0 2.5-1.12 2.5-2.5v-3.5l4 4v-11l-4 4z" fill="#0F9D58"/>
                    </svg>
                    <Video v-else :size="16" />
                    {{ additionalForm.live_gmeet_link ? 'Google Meet Linked' : 'Create a Google Meet Link' }}
                  </button>
                  <div v-if="additionalForm.live_gmeet_link" class="flex items-center justify-between px-2 text-[10px] text-slate-500">
                    <span class="truncate pr-4" :title="additionalForm.live_gmeet_link">{{ additionalForm.live_gmeet_link }}</span>
                    <button type="button" @click="additionalForm.live_gmeet_link = ''" class="text-red-500 hover:underline">Remove</button>
                  </div>
                </div>
              </div>

              <!-- Message if class type is Offline -->
              <div v-else class="text-center py-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200 flex flex-col items-center justify-center p-4">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
                  <Video :size="18" />
                </div>
                <span class="text-xs font-bold text-slate-800">Live Class Not Available</span>
                <span class="text-[10px] text-slate-450 mt-1 text-center leading-normal">Fitur kelas live hanya berlaku untuk kelas bertipe <strong>Online</strong> atau <strong>Hybrid</strong>. Silakan ubah tipe kelas pada Step 1 "Basics".</span>
              </div>
            </div>

          </div>

        </div>

      </div>
    </div>

    <!-- Modals (Module, Lesson, Quiz, Question) -->
    <!-- 5. LIVE MEETING MODAL -->
    <div v-if="showMeetingModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-all duration-300">
      <div class="bg-white rounded-3xl max-w-sm w-full shadow-2xl relative border border-slate-100 overflow-hidden flex flex-col">
        <!-- Content Area -->
        <div class="p-6 flex flex-col gap-4 text-slate-800">
          
          <!-- Meeting Name -->
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Meeting Name</label>
            <input 
              v-model="meetingModalForm.name" 
              type="text" 
              placeholder="Enter meeting name" 
              class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
            />
          </div>

          <!-- Meeting Summary -->
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Meeting Summary</label>
            <textarea 
              v-model="meetingModalForm.summary" 
              rows="3" 
              placeholder="Enter meeting summary" 
              class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm resize-none"
            ></textarea>
          </div>

          <!-- Meeting Date & Time -->
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Meeting Date</label>
            <div class="relative flex items-center mb-2">
              <span class="absolute left-4 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
              </span>
              <input 
                v-model="meetingModalForm.date" 
                type="date" 
                class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl pl-12 pr-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              />
            </div>
            <div class="relative flex items-center">
              <span class="absolute left-4 text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </span>
              <input 
                v-model="meetingModalForm.time" 
                type="time" 
                class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl pl-12 pr-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              />
            </div>
          </div>

          <!-- Meeting Duration -->
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Meeting Duration</label>
            <div class="grid grid-cols-2 gap-3">
              <input 
                v-model.number="meetingModalForm.duration" 
                type="number" 
                placeholder="40" 
                class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold text-center text-sm"
              />
              <select 
                v-model="meetingModalForm.durationUnit" 
                class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-colors text-sm"
              >
                <option value="Minutes">Minutes</option>
                <option value="Hours">Hours</option>
              </select>
            </div>
          </div>

          <!-- Divider -->
          <hr class="border-slate-100 my-1" />

          <!-- Link Meeting, ID, Password -->
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Link Meeting (Opsional)</label>
            <input 
              v-model="meetingModalForm.link" 
              type="text" 
              placeholder="https://zoom.us/..." 
              class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-1.5">
              <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Meeting ID</label>
              <input 
                v-model="meetingModalForm.meetingId" 
                type="text" 
                placeholder="ID" 
                class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              />
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Password</label>
              <input 
                v-model="meetingModalForm.password" 
                type="text" 
                placeholder="Passcode" 
                class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium transition-colors text-sm"
              />
            </div>
          </div>
          
          <div class="flex flex-col gap-1.5">
            <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider">Timezone</label>
            <select 
              v-model="meetingModalForm.timezone" 
              class="w-full border-2 border-slate-250 hover:border-blue-500 focus:border-blue-600 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium cursor-pointer transition-colors text-sm"
            >
              <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
              <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
              <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
            </select>
          </div>

        </div>

        <!-- Footer Actions -->
        <div class="flex justify-end items-center gap-3 p-4 bg-slate-50 border-t border-slate-100">
          <button 
            type="button" 
            @click="showMeetingModal = false" 
            class="px-5 py-2.5 text-slate-500 hover:text-slate-700 font-bold text-sm transition-colors"
          >
            Cancel
          </button>
          <button 
            type="button" 
            @click="generateMeetingLink" 
            class="bg-[#3B82F6] hover:bg-blue-700 text-white px-5 py-2.5 rounded-2xl font-bold text-sm shadow transition-colors flex items-center gap-1.5"
          >
            Create Meeting
          </button>
        </div>

      </div>
    </div>

    <!-- 1. MODULE MODAL -->
    <div v-if="currentModal === 'module'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl relative border border-slate-100">
        <button @click="currentModal = ''" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        <h3 class="text-lg font-extrabold text-[#1A2B49] mb-5">{{ moduleForm.id ? 'Edit Bab' : 'Tambah Bab Baru' }}</h3>
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Bab</label>
            <input v-model="moduleForm.title" type="text" placeholder="Contoh: Pengenalan Sintaks Dasar" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium" />
          </div>
          <button @click="saveModule" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-sm transition-colors">
            <Check :size="16" class="inline mr-1" /> Simpan Bab
          </button>
        </div>
      </div>
    </div>

    <!-- 2. LESSON MODAL -->
    <div v-if="currentModal === 'lesson'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-xl w-full max-w-5xl shadow-2xl relative border border-slate-100 flex flex-col max-h-[95vh]">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <FileText :size="18" class="text-slate-500" />
            <h3 class="text-lg font-semibold text-slate-700">Lesson</h3>
            <span class="text-slate-300 mx-2">|</span>
            <span class="text-sm text-slate-500 font-medium">Topic: {{ selectedModule?.title || 'Unknown' }}</span>
          </div>
          <button @click="currentModal = ''" class="text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        </div>
        
        <!-- Content Body -->
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Left Column -->
            <div class="md:col-span-2 flex flex-col gap-6">
              <!-- Name -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Name</label>
                <input v-model="lessonForm.title" type="text" placeholder="Enter Lesson Name" class="w-full bg-white border border-blue-400 rounded-md px-4 py-2.5 outline-none text-slate-700 font-medium focus:ring-2 focus:ring-blue-100 transition-shadow" />
              </div>

              <!-- Content (Rich Text) -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Content</label>
                <div class="border border-slate-200 rounded-md bg-white flex flex-col">
                  <!-- Toolbar -->
                  <div class="flex flex-wrap items-center gap-1 p-2 border-b border-slate-200">
                    <button class="px-3 py-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded text-xs font-semibold flex items-center gap-1">
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Add media
                    </button>
                    <div class="flex-1"></div>
                    <div class="flex bg-slate-100 rounded overflow-hidden">
                      <button class="px-3 py-1 bg-white text-slate-700 text-xs shadow-sm border border-slate-200">Visual</button>
                      <button class="px-3 py-1 text-slate-500 text-xs">Code</button>
                    </div>
                  </div>
                  <!-- Editor Icons -->
                  <div class="flex flex-wrap items-center gap-2 p-2 border-b border-slate-200 bg-slate-50/50">
                    <select class="text-xs border border-slate-200 rounded px-2 py-1 bg-white outline-none"><option>Paragraph</option></select>
                    <div class="h-4 w-px bg-slate-300"></div>
                    <button class="p-1 hover:bg-slate-200 rounded text-slate-700 font-bold">B</button>
                    <button class="p-1 hover:bg-slate-200 rounded text-slate-700 italic font-serif">I</button>
                    <button class="p-1 hover:bg-slate-200 rounded text-slate-700 underline">U</button>
                    <div class="h-4 w-px bg-slate-300"></div>
                    <button class="p-1 hover:bg-slate-200 rounded text-slate-600"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                  </div>
                  <!-- Editor Area -->
                  <textarea v-model="lessonForm.content" rows="12" class="w-full p-4 outline-none resize-y min-h-[250px] text-sm text-slate-700"></textarea>
                  <!-- Resize Handle -->
                  <div class="h-2 bg-slate-50 border-t border-slate-200 cursor-ns-resize flex justify-end px-1"><svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 9h4M10 15h4M15 4l-3-3-3 3M15 20l-3 3-3-3"></path></svg></div>
                </div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="flex flex-col gap-6 border-l border-slate-200 pl-6">
              
              <!-- Featured Image -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Featured Image</label>
                <div class="border border-dashed border-slate-300 rounded-lg p-6 flex flex-col items-center justify-center bg-white text-center">
                  <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                  </div>
                  <button class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-md text-xs font-semibold mb-2">Upload Image</button>
                  <p class="text-[10px] text-slate-400">JPEG, PNG, GIF, and WebP formats, up to 2 GB</p>
                </div>
              </div>

              <!-- Format Switcher -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Format</label>
                <div class="flex bg-slate-100 rounded p-1 gap-1">
                  <button @click="lessonForm.lesson_type = 'video'" :class="lessonForm.lesson_type === 'video' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:bg-slate-200'" class="flex-1 py-1.5 text-xs font-medium rounded transition-colors">Video</button>
                  <button @click="lessonForm.lesson_type = 'slides'" :class="lessonForm.lesson_type === 'slides' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:bg-slate-200'" class="flex-1 py-1.5 text-xs font-medium rounded transition-colors">Slides</button>
                  <button @click="lessonForm.lesson_type = 'ppt'" :class="lessonForm.lesson_type === 'ppt' ? 'bg-white shadow text-indigo-600' : 'text-slate-500 hover:bg-slate-200'" class="flex-1 py-1.5 text-xs font-medium rounded transition-colors">PPT</button>
                </div>
              </div>

              <!-- Content Options based on Format -->
              <div v-if="lessonForm.lesson_type === 'video'">
                <label class="block text-slate-700 text-sm font-medium mb-2">Video</label>
                <div class="border border-dashed border-slate-300 rounded-lg p-6 flex flex-col items-center justify-center bg-white text-center">
                  <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                  </div>
                  <button v-if="lessonForm.video_type === 'upload'" class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-md text-xs font-semibold mb-2">Upload Video</button>
                  <input v-else v-model="lessonForm.video_url" type="text" placeholder="https://..." class="w-full bg-slate-50 border border-slate-200 rounded px-3 py-1.5 outline-none text-xs text-slate-700 font-medium mb-2" />
                  
                  <button @click="lessonForm.video_type = lessonForm.video_type === 'url' ? 'upload' : 'url'" class="text-blue-500 text-xs font-medium hover:underline mb-2">
                    {{ lessonForm.video_type === 'url' ? 'Upload Video' : 'Add from URL' }}
                  </button>
                  <p class="text-[10px] text-slate-400">MP4, and WebM formats, up to 2 GB</p>
                </div>
              </div>

              <div v-else-if="lessonForm.lesson_type === 'slides'">
                <label class="block text-slate-700 text-sm font-medium mb-2">Canva/Google Slides URL</label>
                <input v-model="lessonForm.slides_url" type="text" placeholder="https://..." class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none" />
              </div>

              <div v-else-if="lessonForm.lesson_type === 'ppt'">
                <label class="block text-slate-700 text-sm font-medium mb-2">PPT Slides</label>
                <div class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-1">
                  <div v-for="(slide, sIdx) in lessonForm.ppt_slides" :key="sIdx" class="border border-slate-200 rounded p-2 text-xs flex gap-2">
                    <input v-model="slide.title" class="flex-1 bg-slate-50 border border-slate-200 px-2 py-1 outline-none" placeholder="Title" />
                    <button @click="lessonForm.ppt_slides.splice(sIdx,1)" class="text-red-500"><Trash :size="14"/></button>
                  </div>
                  <button @click="lessonForm.ppt_slides.push({title:'', content:''})" class="text-xs text-blue-500 hover:underline">Add Slide</button>
                </div>
              </div>

              <!-- Video Playback Time -->
              <div v-if="lessonForm.lesson_type === 'video'">
                <label class="block text-slate-700 text-sm font-medium mb-2">Video Playback Time</label>
                <div class="grid grid-cols-3 gap-2">
                  <div class="relative">
                    <input v-model.number="lessonForm.playback_time.hour" type="number" class="w-full border border-slate-200 rounded-md py-2 pl-3 pr-8 text-sm outline-none text-slate-700" />
                    <span class="absolute right-3 top-2.5 text-xs text-slate-400">hour</span>
                  </div>
                  <div class="relative">
                    <input v-model.number="lessonForm.playback_time.min" type="number" class="w-full border border-slate-200 rounded-md py-2 pl-3 pr-8 text-sm outline-none text-slate-700" />
                    <span class="absolute right-3 top-2.5 text-xs text-slate-400">min</span>
                  </div>
                  <div class="relative">
                    <input v-model.number="lessonForm.playback_time.sec" type="number" class="w-full border border-slate-200 rounded-md py-2 pl-3 pr-8 text-sm outline-none text-slate-700" />
                    <span class="absolute right-3 top-2.5 text-xs text-slate-400">sec</span>
                  </div>
                </div>
              </div>

              <!-- Exercise Files -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Exercise Files</label>
                <button class="w-full bg-indigo-50/50 hover:bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-md py-2.5 text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                  <Paperclip :size="14" /> Upload Attachment
                </button>
              </div>

              <!-- Lesson Preview Toggle -->
              <div class="flex items-center justify-between border border-slate-200 rounded-md px-4 py-3 bg-white mt-auto">
                <div class="flex items-center gap-1.5">
                  <span class="text-sm font-medium text-slate-700">Lesson Preview</span>
                  <HelpCircle :size="14" class="text-slate-400" />
                </div>
                <div class="relative inline-block w-10 h-6 cursor-pointer" @click="lessonForm.is_preview = !lessonForm.is_preview">
                  <div class="w-10 h-6 rounded-full transition-colors duration-300" :class="lessonForm.is_preview ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 shadow-sm" :class="lessonForm.is_preview ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
              </div>

              <!-- Save Button -->
              <div class="pt-2 border-t border-slate-200 mt-2">
                <button @click="saveLesson" class="w-full bg-[#264790] hover:bg-blue-700 text-white py-2.5 rounded-md font-bold text-sm shadow-sm transition-colors">
                  Save Lesson
                </button>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ASSIGNMENT MODAL -->
    <div v-if="currentModal === 'assignment'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-xl w-full max-w-5xl shadow-2xl relative border border-slate-100 flex flex-col max-h-[95vh]">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <FileText :size="18" class="text-slate-500" />
            <h3 class="text-lg font-semibold text-slate-700">Assignment</h3>
            <span class="text-slate-300 mx-2">|</span>
            <span class="text-sm text-slate-500 font-medium">Topic: {{ selectedModule?.title || 'Unknown' }}</span>
          </div>
          <button @click="currentModal = ''" class="text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        </div>
        
        <!-- Content Body -->
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Left Column -->
            <div class="md:col-span-2 flex flex-col gap-6">
              <!-- Title -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Title</label>
                <input v-model="assignmentForm.title" type="text" placeholder="Enter Assignment Title" class="w-full bg-white border border-blue-400 rounded-md px-4 py-2.5 outline-none text-slate-700 font-medium focus:ring-2 focus:ring-blue-100 transition-shadow" />
              </div>

              <!-- Content (Rich Text) -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Content</label>
                <div class="border border-slate-200 rounded-md bg-white flex flex-col">
                  <!-- Toolbar -->
                  <div class="flex flex-wrap items-center gap-1 p-2 border-b border-slate-200">
                    <button class="px-3 py-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded text-xs font-semibold flex items-center gap-1">
                      <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Add media
                    </button>
                    <div class="flex-1"></div>
                    <div class="flex bg-slate-100 rounded overflow-hidden">
                      <button class="px-3 py-1 bg-white text-slate-700 text-xs shadow-sm border border-slate-200">Visual</button>
                      <button class="px-3 py-1 text-slate-500 text-xs">Code</button>
                    </div>
                  </div>
                  <!-- Editor Icons -->
                  <div class="flex flex-wrap items-center gap-2 p-2 border-b border-slate-200 bg-slate-50/50">
                    <select class="text-xs border border-slate-200 rounded px-2 py-1 bg-white outline-none"><option>Paragraph</option></select>
                    <div class="h-4 w-px bg-slate-300"></div>
                    <button class="p-1 hover:bg-slate-200 rounded text-slate-700 font-bold">B</button>
                    <button class="p-1 hover:bg-slate-200 rounded text-slate-700 italic font-serif">I</button>
                    <button class="p-1 hover:bg-slate-200 rounded text-slate-700 underline">U</button>
                  </div>
                  <!-- Editor Area -->
                  <textarea v-model="assignmentForm.content" rows="12" class="w-full p-4 outline-none resize-y min-h-[250px] text-sm text-slate-700"></textarea>
                  <!-- Resize Handle -->
                  <div class="h-2 bg-slate-50 border-t border-slate-200 flex justify-end px-1"><svg class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 9h4M10 15h4M15 4l-3-3-3 3M15 20l-3 3-3-3"></path></svg></div>
                </div>
              </div>
            </div>

            <!-- Right Column -->
            <div class="flex flex-col gap-5 border-l border-slate-200 pl-6">
              
              <!-- Attachments -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Attachments</label>
                <button class="w-full bg-indigo-50/50 hover:bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-md py-2.5 text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                  <Paperclip :size="14" /> Upload Attachment
                </button>
              </div>

              <!-- Time Limit -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Time Limit</label>
                <div class="flex gap-2">
                  <input v-model.number="assignmentForm.time_limit" type="number" class="w-1/2 border border-slate-200 rounded-md px-3 py-2 text-sm outline-none" />
                  <select v-model="assignmentForm.time_limit_unit" class="w-1/2 border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white cursor-pointer">
                    <option>Weeks</option>
                    <option>Days</option>
                    <option>Hours</option>
                  </select>
                </div>
              </div>

              <!-- Set Deadline Toggle -->
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 max-w-[80%]">
                  <span class="text-sm font-medium text-slate-700">Set Deadline From Assignment Start Time</span>
                  <HelpCircle :size="14" class="text-slate-400 shrink-0" />
                </div>
                <div class="relative inline-block w-10 h-6 cursor-pointer shrink-0" @click="assignmentForm.set_deadline_from_start = !assignmentForm.set_deadline_from_start">
                  <div class="w-10 h-6 rounded-full transition-colors duration-300" :class="assignmentForm.set_deadline_from_start ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 shadow-sm" :class="assignmentForm.set_deadline_from_start ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
              </div>

              <!-- Total Points -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Total Points</label>
                <input v-model.number="assignmentForm.total_points" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none" />
              </div>

              <!-- Minimum Pass Points -->
              <div>
                <label class="block text-slate-700 text-sm font-medium mb-2">Minimum Pass Points</label>
                <input v-model.number="assignmentForm.min_pass_points" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none" />
              </div>

              <!-- File Upload Limit & Size -->
              <div>
                <div class="flex items-center gap-1.5 mb-2">
                  <label class="block text-slate-700 text-sm font-medium">File Upload Limit</label>
                  <HelpCircle :size="14" class="text-slate-400" />
                </div>
                <input v-model.number="assignmentForm.file_upload_limit" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none mb-4" />
                
                <label class="block text-slate-700 text-sm font-medium mb-2">Maximum File Size Limit</label>
                <div class="relative">
                  <input v-model.number="assignmentForm.max_file_size" type="number" class="w-full border border-slate-200 rounded-md py-2 pl-3 pr-10 text-sm outline-none" />
                  <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-semibold">MB</span>
                </div>
              </div>

              <!-- Allow Resubmission Toggle -->
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-slate-700">Allow Assignment Resubmission</span>
                <div class="relative inline-block w-10 h-6 cursor-pointer shrink-0" @click="assignmentForm.allow_resubmission = !assignmentForm.allow_resubmission">
                  <div class="w-10 h-6 rounded-full transition-colors duration-300" :class="assignmentForm.allow_resubmission ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 shadow-sm" :class="assignmentForm.allow_resubmission ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
              </div>

              <div v-if="assignmentForm.allow_resubmission">
                <div class="flex items-center gap-1.5 mb-2">
                  <label class="block text-slate-700 text-sm font-medium">Maximum Resubmission Attempts</label>
                  <HelpCircle :size="14" class="text-slate-400" />
                </div>
                <input v-model.number="assignmentForm.max_resubmission_attempts" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none" />
              </div>

              <!-- Save Button -->
              <div class="pt-2 border-t border-slate-200 mt-2">
                <button @click="saveAssignment" class="w-full bg-[#264790] hover:bg-blue-700 text-white py-2.5 rounded-md font-bold text-sm shadow-sm transition-colors">
                  Save Assignment
                </button>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- QUIZ SETTINGS MODAL -->
    <div v-if="currentModal === 'quiz_settings'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-xl w-full max-w-4xl shadow-2xl relative border border-slate-100 flex flex-col max-h-[95vh]">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50 rounded-t-xl">
          <div class="flex items-center gap-2">
            <Settings :size="18" class="text-slate-500" />
            <h3 class="text-lg font-semibold text-slate-700">Quiz Settings</h3>
            <span class="text-slate-300 mx-2">|</span>
            <span class="text-sm text-slate-500 font-medium">Topic: {{ selectedQuiz?.title || 'Unknown' }}</span>
          </div>
          <button @click="currentModal = ''" class="text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        </div>
        
        <!-- Content Body -->
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50 flex flex-col md:flex-row gap-6">
          <!-- Main Content (Settings) -->
          <div class="flex-1 flex flex-col gap-6">
            <!-- Time Limit -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div>
                <label class="block text-slate-700 text-sm font-medium">Time Limit</label>
                <p class="text-[11px] text-slate-500 mt-0.5">Set zero to disable time limit.</p>
              </div>
              <div class="flex gap-2 w-64">
                <input v-model.number="quizSettingsForm.time_limit" type="number" class="w-1/2 border border-slate-200 rounded-md px-3 py-1.5 text-sm outline-none bg-white font-medium" />
                <select v-model="quizSettingsForm.time_limit_unit" class="w-1/2 border border-slate-200 rounded-md px-3 py-1.5 text-sm outline-none bg-white cursor-pointer font-medium">
                  <option>Seconds</option>
                  <option>Minutes</option>
                  <option>Hours</option>
                  <option>Days</option>
                  <option>Weeks</option>
                </select>
              </div>
            </div>

            <!-- Hide Quiz Time -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div class="pr-6">
                <label class="block text-slate-700 text-sm font-medium">Hide Quiz Time - Display</label>
                <p class="text-[11px] text-slate-500 mt-0.5">By enabling this option, Quiz timer will not show in frontend.</p>
              </div>
              <div class="relative inline-block w-10 h-6 cursor-pointer shrink-0 mt-1" @click="quizSettingsForm.hide_quiz_time = !quizSettingsForm.hide_quiz_time">
                <div class="w-10 h-6 rounded-full transition-colors duration-300" :class="quizSettingsForm.hide_quiz_time ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 shadow-sm" :class="quizSettingsForm.hide_quiz_time ? 'translate-x-4' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- Quiz Feedback Mode -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div>
                <label class="block text-slate-700 text-sm font-medium">Quiz Feedback Mode</label>
              </div>
              <div class="w-64">
                <select v-model="quizSettingsForm.feedback_mode" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white cursor-pointer font-medium">
                  <option>Default</option>
                  <option>Retry</option>
                  <option>Reveal</option>
                </select>
              </div>
            </div>

            <!-- Attempts Allowed -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div class="pr-6">
                <label class="block text-slate-700 text-sm font-medium">Attempts Allowed</label>
                <p class="text-[11px] text-slate-500 mt-0.5">Restriction on the number of attempts a student is allowed to take for this quiz. 0 for no limit</p>
              </div>
              <div class="w-64">
                <input v-model.number="quizSettingsForm.attempts_allowed" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white font-medium" />
              </div>
            </div>

            <!-- Passing Grade -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div>
                <label class="block text-slate-700 text-sm font-medium">Passing Grade (%)</label>
                <p class="text-[11px] text-slate-500 mt-0.5">Set the passing percentage for this quiz.</p>
              </div>
              <div class="w-64">
                <div class="relative">
                  <input v-model.number="quizSettingsForm.passing_grade" type="number" class="w-full border border-slate-200 rounded-md py-2 pl-3 pr-8 text-sm outline-none bg-white font-medium" />
                  <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">%</span>
                </div>
              </div>
            </div>

            <!-- Max Questions Allowed -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div class="pr-6">
                <label class="block text-slate-700 text-sm font-medium">Max Questions Allowed to Answer</label>
                <p class="text-[11px] text-slate-500 mt-0.5">This amount of question will be available for students to answer.</p>
              </div>
              <div class="w-64">
                <input v-model.number="quizSettingsForm.max_questions" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white font-medium" />
              </div>
            </div>

            <!-- Advanced Settings Accordion Header -->
            <div class="flex items-center gap-2 mt-4 bg-slate-100/50 p-3 rounded-lg border border-slate-200/50">
              <Settings :size="16" class="text-slate-500" />
              <h4 class="font-bold text-slate-700 text-sm">Advanced Settings</h4>
            </div>

            <!-- Quiz Auto Start -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div class="pr-6">
                <label class="block text-slate-700 text-sm font-medium">Quiz Auto Start</label>
                <p class="text-[11px] text-slate-500 mt-0.5">If you enable this option, the quiz will start automatically after the page is loaded.</p>
              </div>
              <div class="relative inline-block w-10 h-6 cursor-pointer shrink-0 mt-1" @click="quizSettingsForm.quiz_auto_start = !quizSettingsForm.quiz_auto_start">
                <div class="w-10 h-6 rounded-full transition-colors duration-300" :class="quizSettingsForm.quiz_auto_start ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 shadow-sm" :class="quizSettingsForm.quiz_auto_start ? 'translate-x-4' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- Question Layout -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div>
                <label class="block text-slate-700 text-sm font-medium">Question Layout</label>
              </div>
              <div class="w-64">
                <select v-model="quizSettingsForm.question_layout" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white cursor-pointer font-medium">
                  <option>Single question</option>
                  <option>All questions</option>
                  <option>Pagination</option>
                </select>
              </div>
            </div>

            <!-- Question Order -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div>
                <label class="block text-slate-700 text-sm font-medium">Question Order</label>
              </div>
              <div class="w-64">
                <select v-model="quizSettingsForm.question_order" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white cursor-pointer font-medium">
                  <option>Random</option>
                  <option>Sequential</option>
                </select>
              </div>
            </div>

            <!-- Short Answer Characters Limit -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div>
                <label class="block text-slate-700 text-sm font-medium">Short Answer Characters Limit</label>
              </div>
              <div class="w-64">
                <input v-model.number="quizSettingsForm.char_limit_short" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white font-medium" />
              </div>
            </div>

            <!-- Open-Ended Character Limit -->
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
              <div>
                <label class="block text-slate-700 text-sm font-medium">Open-Ended/Essay Question Character Limit</label>
              </div>
              <div class="w-64">
                <input v-model.number="quizSettingsForm.char_limit_essay" type="number" class="w-full border border-slate-200 rounded-md px-3 py-2 text-sm outline-none bg-white font-medium" />
              </div>
            </div>

          </div>
          
          <!-- Sidebar Actions -->
          <div class="w-full md:w-64 flex flex-col gap-3">
            <button @click="saveQuizSettings" class="w-full bg-[#264790] hover:bg-blue-700 text-white py-3 rounded-md font-bold text-sm shadow-sm transition-colors flex items-center justify-center gap-1.5">
              Save Settings
            </button>
            <button @click="currentModal = ''" class="w-full bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 py-3 rounded-md font-bold text-sm shadow-sm transition-colors">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. QUIZ MODAL -->
    <div v-if="currentModal === 'quiz'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl relative border border-slate-100">
        <button @click="currentModal = ''" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        <h3 class="text-lg font-extrabold text-[#1A2B49] mb-5">{{ quizForm.id ? 'Edit Kuis' : 'Tambah Kuis Baru' }}</h3>
        <div class="flex flex-col gap-4">
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Judul Kuis</label>
            <input v-model="quizForm.title" type="text" placeholder="Contoh: Evaluasi Aritmatika" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium" />
          </div>

          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Batas Waktu (Menit)</label>
            <input v-model.number="quizForm.time_limit_minutes" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold" />
          </div>

          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Instruksi Singkat</label>
            <textarea v-model="quizForm.description" rows="3" placeholder="Tuliskan petunjuk pengerjaan kuis..." class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium"></textarea>
          </div>

          <button @click="saveQuiz" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3 rounded-2xl font-bold text-sm shadow-sm transition-colors">
            <Check :size="16" class="inline mr-1" /> Simpan Kuis
          </button>
        </div>
      </div>
    </div>

    <!-- 4. QUESTION MODAL -->
    <div v-if="currentModal === 'question'" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="bg-white rounded-3xl max-w-xl w-full p-8 shadow-2xl relative border border-slate-100 max-h-[90vh] overflow-y-auto">
        <button @click="currentModal = ''" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600"><X :size="20" /></button>
        <h3 class="text-lg font-extrabold text-[#1A2B49] mb-5">{{ questionForm.id ? 'Edit Pertanyaan' : 'Tambah Pertanyaan Kuis' }}</h3>
        
        <div class="flex flex-col gap-5">
          <!-- Question Type Select -->
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Format Tipe Soal Kuis</label>
            <select 
              v-model="questionForm.question_type"
              class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-bold text-xs cursor-pointer transition-colors"
            >
              <option value="multiple_choice">Pilihan Ganda (Multiple Choice)</option>
              <option value="true_false">Benar / Salah (True or False)</option>
              <option value="essay">Esai / Jawaban Singkat (Essay / Short Answer)</option>
              <option value="math_formula">Rumus Matematika (Math Formula / LaTeX)</option>
            </select>
          </div>

          <!-- Question Text Area -->
          <div>
            <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2">Pertanyaan / Soal</label>
            <textarea v-model="questionForm.question_text" rows="3" placeholder="Contoh: Berapakah hasil turunan dari f(x) = x^2 + 5x?" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 outline-none text-[#1A2B49] font-medium text-xs"></textarea>
          </div>

          <!-- Dynamic Answers UI depending on Question Type -->
          <div class="p-4 bg-slate-50/50 rounded-2xl border border-slate-150/40">
            <!-- 1. Multiple Choice Options Editor -->
            <div v-if="questionForm.question_type === 'multiple_choice'" class="flex flex-col gap-3">
              <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider mb-1">Pilihan Jawaban (Pilih Bulatan untuk Jawaban yang Benar)</label>
              <div v-for="(opt, idx) in questionForm.options" :key="idx" class="flex items-center gap-3">
                <input 
                  type="radio" 
                  name="mc_correct_index" 
                  :value="idx" 
                  v-model="questionForm.correct_option_index"
                  class="w-5 h-5 text-[#264790] cursor-pointer"
                />
                <input 
                  v-model="questionForm.options[idx]" 
                  type="text" 
                  :placeholder="'Ketik jawaban pilihan ' + (idx + 1)"
                  class="flex-1 bg-white border border-slate-200 hover:border-slate-300 rounded-xl px-3 py-2 outline-none text-xs text-[#1A2B49] font-medium"
                />
              </div>
            </div>

            <!-- 2. True or False Editor -->
            <div v-if="questionForm.question_type === 'true_false'" class="flex flex-col gap-3">
              <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider mb-2">Tentukan Kebenaran Jawaban</label>
              <div class="grid grid-cols-2 gap-3">
                <label 
                  :class="questionForm.correct_option_index === 0 ? 'bg-[#264790]/10 border-[#264790]' : 'bg-white border-slate-200'"
                  class="flex items-center justify-between p-4 rounded-xl border cursor-pointer select-none"
                >
                  <span class="text-xs font-bold text-[#1A2B49]">Benar (True)</span>
                  <input type="radio" :value="0" v-model="questionForm.correct_option_index" class="w-4 h-4 text-[#264790]" />
                </label>
                <label 
                  :class="questionForm.correct_option_index === 1 ? 'bg-[#264790]/10 border-[#264790]' : 'bg-white border-slate-200'"
                  class="flex items-center justify-between p-4 rounded-xl border cursor-pointer select-none"
                >
                  <span class="text-xs font-bold text-[#1A2B49]">Salah (False)</span>
                  <input type="radio" :value="1" v-model="questionForm.correct_option_index" class="w-4 h-4 text-[#264790]" />
                </label>
              </div>
            </div>

            <!-- 3. Essay Editor -->
            <div v-if="questionForm.question_type === 'essay'" class="flex flex-col gap-3">
              <div>
                <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider mb-2">Kunci Jawaban / Kata Kunci Penilaian (Kecocokan Otomatis)</label>
                <input v-model="questionForm.options[1]" type="text" placeholder="Masukkan kata kunci/jawaban singkat yang diharapkan..." class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 outline-none text-xs text-[#1A2B49] font-bold" />
              </div>
              <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">
                * Sistem akan mencocokkan kata kunci jawaban esai siswa dengan teks kunci jawaban di atas saat melakukan pemeriksaan otomatis.
              </p>
            </div>

            <!-- 4. Math Formula LaTeX Editor -->
            <div v-if="questionForm.question_type === 'math_formula'" class="flex flex-col gap-3">
              <div>
                <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider mb-2">Ketik Rumus Matematika (Gunakan LaTeX atau Sintaks Biasa)</label>
                <input v-model="questionForm.options[1]" type="text" placeholder="Contoh: \int_0^\infty e^{-x^2} dx = \frac{\sqrt{\pi}}{2}" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 outline-none text-xs text-[#1A2B49] font-bold" />
              </div>
              
              <!-- Formula Preview Container -->
              <div v-if="questionForm.options[1]" class="bg-amber-50/50 border border-amber-100 rounded-xl p-3 flex flex-col gap-1 items-center justify-center">
                <span class="text-[9px] font-bold text-amber-500 uppercase tracking-widest">Pratinjau Rumus Matematis</span>
                <span class="font-serif italic text-base text-[#1A2B49] select-all font-semibold">$$ {{ questionForm.options[1] }} $$</span>
              </div>

              <div>
                <label class="block text-[#1A2B49] text-xs font-bold uppercase tracking-wider mb-2">Nilai / Hasil Kunci Jawaban</label>
                <input v-model="questionForm.options[2]" type="text" placeholder="Contoh: \sqrt{\pi}/2 atau 5" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 outline-none text-xs text-[#1A2B49] font-bold" />
              </div>
            </div>
          </div>

          <button @click="saveQuestion" class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-3.5 rounded-2xl font-bold text-sm shadow-sm transition-colors">
            <Check :size="16" class="inline mr-1" /> Simpan Pertanyaan Soal
          </button>
        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>
