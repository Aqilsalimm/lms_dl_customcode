<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  Globe, BookOpen, DollarSign, Palette, Sliders, 
  Bell, Shield, Award, Key, Save, Trash2, UploadCloud,
  ShieldCheck, ShieldAlert, UserCheck, Play
} from 'lucide-vue-next';

const props = defineProps({
  settings: Object
});

const getSetting = (key, defaultVal) => {
  if (props.settings && props.settings[key] !== undefined) {
    let val = props.settings[key];
    if (val === 'true') return true;
    if (val === 'false') return false;
    try { return JSON.parse(val); } catch(e) {}
    return val;
  }
  return defaultVal;
};

const getObjectSetting = (key, defaultVal) => {
  const savedVal = getSetting(key, null);
  if (savedVal && typeof savedVal === 'object') {
    const merged = {};
    for (const k in defaultVal) {
      if (defaultVal[k] && typeof defaultVal[k] === 'object' && savedVal[k] && typeof savedVal[k] === 'object') {
        merged[k] = { ...defaultVal[k], ...savedVal[k] };
      } else {
        merged[k] = savedVal[k] !== undefined ? savedVal[k] : defaultVal[k];
      }
    }
    return merged;
  }
  return defaultVal;
};

const activeTab = ref(typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('tab') || 'general') : 'general');

const tabs = [
  { id: 'general', label: 'General', icon: Globe },
  { id: 'course', label: 'Course', icon: BookOpen },
  { id: 'monetization', label: 'Monetization', icon: DollarSign },
  { id: 'design', label: 'Design', icon: Palette },
  { id: 'advanced', label: 'Advanced', icon: Sliders },
  { id: 'notifications', label: 'Notifications', icon: Bell },
  { id: 'authentication', label: 'Authentication', icon: Shield },
  { id: 'certificate', label: 'Certificate', icon: Award },
  { id: 'license', label: 'License', icon: Key },
];

const form = useForm({
  settings: {
    // General Settings
    dashboard_page: getSetting('dashboard_page', 'default'),
    terms_page: getSetting('terms_page', 'terms'),
    privacy_page: getSetting('privacy_page', 'privacy'),
    enable_marketplace: getSetting('enable_marketplace', true),
    pagination_rows: getSetting('pagination_rows', 10),
    become_instructor_button: getSetting('become_instructor_button', true),
    instructor_publish_course: getSetting('instructor_publish_course', false),
    instructor_trash_course: getSetting('instructor_trash_course', false),
    instructor_change_author: getSetting('instructor_change_author', false),
    instructor_reset_progress: getSetting('instructor_reset_progress', false),
    
    // Course Settings
    instructor_course_moderation: getSetting('instructor_course_moderation', false),
    course_visibility_login: getSetting('course_visibility_login', true),
    course_content_access_admin: getSetting('course_content_access_admin', true),
    content_summary: getSetting('content_summary', true),
    spotlight_mode: getSetting('spotlight_mode', false),
    auto_complete_course: getSetting('auto_complete_course', false),
    course_completion_process: getSetting('course_completion_process', 'strict'),
    course_retake: getSetting('course_retake', false),
    publish_course_review_approval: getSetting('publish_course_review_approval', true),
    redirect_instructor_publish: getSetting('redirect_instructor_publish', false),
    attachment_open_mode: getSetting('attachment_open_mode', 'download'),
    enable_course_gifting: getSetting('enable_course_gifting', false),
    video_lesson_completion_control: getSetting('video_lesson_completion_control', false),
    auto_load_next_content: getSetting('auto_load_next_content', true),
    enable_lesson_comment: getSetting('enable_lesson_comment', true),
    quiz_time_expires_action: getSetting('quiz_time_expires_action', 'auto_submit'),
    correct_answer_display_time: getSetting('correct_answer_display_time', 5),
    default_quiz_attempt_limit: getSetting('default_quiz_attempt_limit', 0),
    show_quiz_previous_button: getSetting('show_quiz_previous_button', true),
    final_grade_calc_quiz: getSetting('final_grade_calc_quiz', 'highest'),
    hide_quiz_details_students: getSetting('hide_quiz_details_students', false),
    final_grade_calc_assignment: getSetting('final_grade_calc_assignment', 'highest'),
    preferred_video_source: getSetting('preferred_video_source', ['youtube', 'html5']),
    use_tutor_player_youtube: getSetting('use_tutor_player_youtube', true),
    use_tutor_player_vimeo: getSetting('use_tutor_player_vimeo', false),
    use_tutor_player_gdrive: getSetting('use_tutor_player_gdrive', false),
    
    // Monetization Settings
    ecommerce_engine: getSetting('ecommerce_engine', 'native'),
    auto_complete_ecommerce_orders: getSetting('auto_complete_ecommerce_orders', false),
    generate_ecommerce_order: getSetting('generate_ecommerce_order', false),
    auto_redirect_to_courses: getSetting('auto_redirect_to_courses', false),
    enable_guest_mode: getSetting('enable_guest_mode', false),
    enable_revenue_sharing: getSetting('enable_revenue_sharing', true),
    active_payment_gateway: getSetting('active_payment_gateway', 'midtrans'),
    midtrans_client_key: getSetting('midtrans_client_key', ''),
    midtrans_server_key: getSetting('midtrans_server_key', ''),
    midtrans_sandbox_mode: getSetting('midtrans_sandbox_mode', true),
    xendit_public_key: getSetting('xendit_public_key', ''),
    xendit_secret_key: getSetting('xendit_secret_key', ''),
    faspay_merchant_id: getSetting('faspay_merchant_id', ''),
    faspay_merchant_password: getSetting('faspay_merchant_password', ''),
    pivot_api_key: getSetting('pivot_api_key', ''),
    doku_client_id: getSetting('doku_client_id', ''),
    doku_shared_key: getSetting('doku_shared_key', ''),

    // Revenue Sharing sub-settings
    sharing_percentage_admin: getSetting('sharing_percentage_admin', 30),
    sharing_percentage_instructor: getSetting('sharing_percentage_instructor', 70),
    deduct_fees_enabled: getSetting('deduct_fees_enabled', false),
    deduct_fees_name: getSetting('deduct_fees_name', 'Transaction Fee'),
    deduct_fees_type: getSetting('deduct_fees_type', 'fixed'),
    deduct_fees_value: getSetting('deduct_fees_value', 0),
    minimum_withdrawal_amount: getSetting('minimum_withdrawal_amount', 100000),
    minimum_days_before_available: getSetting('minimum_days_before_available', 7),
    withdrawal_methods: getSetting('withdrawal_methods', ['bank_transfer']),
    bank_instructions: getSetting('bank_instructions', ''),

    // Design Settings
    course_logo: getSetting('course_logo', '/images/logo-placeholder.png'),
    course_columns: getSetting('course_columns', 3),
    courses_per_page: getSetting('courses_per_page', 12),
    course_filter: getSetting('course_filter', false),
    course_sorting: getSetting('course_sorting', true),

    instructor_list_layout: getSetting('instructor_list_layout', 'cover'),
    instructor_profile_layout: getSetting('instructor_profile_layout', 'classic'),
    student_profile_layout: getSetting('student_profile_layout', 'classic'),

    page_features: getObjectSetting('page_features', {
      instructor_info: false,
      wishlist: true,
      qa: false,
      author: true,
      level: true,
      social_share: true,
      duration: true,
      total_enrolled: true,
      update_date: true,
      progress_bar: true,
      material: true,
      about: true,
      description: true,
      benefits: true,
      requirements: true,
      target_audience: true,
      announcements: true,
      review: true,
      sticky_sidebar: false
    }),
    enrollment_box_mobile: getSetting('enrollment_box_mobile', 'bottom'),
    showcase_certificate: getSetting('showcase_certificate', false),

    preset_colors: getSetting('preset_colors', 'default'),
    primary_color: getSetting('primary_color', '#264790'),
    primary_hover_color: getSetting('primary_hover_color', '#1A2B49'),
    text_color: getSetting('text_color', '#1A2B49'),
    gray_color: getSetting('gray_color', '#E3E5EB'),
    border_color: getSetting('border_color', '#CDCFD5'),
    text_color_hover: getSetting('text_color_hover', '#ffffff'),

    // Advanced Settings
    builder_fields_basics: getObjectSetting('builder_fields_basics', {
      admin: { general: true, content_drip: true, enrollment: true, featured_image: true, intro_video: true, scheduling: true, pricing: true, categories: true, tags: true, author: true, instructor: true },
      instructor: { general: true, content_drip: false, enrollment: true, featured_image: true, intro_video: true, scheduling: false, pricing: true, categories: true, tags: true, author: false, instructor: false }
    }),
    builder_fields_curriculum: getObjectSetting('builder_fields_curriculum', {
      admin: { featured_image: true, video: true, playback: true, exercise_files: true, preview: true },
      instructor: { featured_image: true, video: true, playback: true, exercise_files: true, preview: true }
    }),
    builder_fields_additional: getObjectSetting('builder_fields_additional', {
      admin: { what_will_i_learn: true, target_audience: true, duration: true, materials: true, requirements: true, certificate: true, attachments: true, live_class: true },
      instructor: { what_will_i_learn: true, target_audience: true, duration: true, materials: true, requirements: true, certificate: false, attachments: true, live_class: true }
    }),
    
    hide_course_product_shop: getSetting('hide_course_product_shop', false),
    course_archive_page: getSetting('course_archive_page', 'courses'),
    instructor_registration_page: getSetting('instructor_registration_page', 'instructor-register'),
    student_registration_page: getSetting('student_registration_page', 'student-register'),
    youtube_api_key: getSetting('youtube_api_key', ''),
    hide_admin_bar_restrict_access: getSetting('hide_admin_bar_restrict_access', true),

    permalink_course: getSetting('permalink_course', 'courses'),
    permalink_lesson: getSetting('permalink_lesson', 'lessons'),
    permalink_quiz: getSetting('permalink_quiz', 'quizzes'),
    permalink_assignment: getSetting('permalink_assignment', 'assignments'),

    profile_completion_alert: getSetting('profile_completion_alert', true),
    enable_email_update: getSetting('enable_email_update', true),
    erase_on_uninstall: getSetting('erase_on_uninstall', false),
    maintenance_mode: getSetting('maintenance_mode', false),

    prevent_hotlinking: getSetting('prevent_hotlinking', false),
    copy_protection: getSetting('copy_protection', false),

    ai_studio_enabled: getSetting('ai_studio_enabled', false),
    ai_api_key: getSetting('ai_api_key', ''),

    // Notification Settings
    notification_student: getObjectSetting('notification_student', {
      course_enrolled: { onsite: true, push: true },
      cancel_enrollment: { onsite: true, push: false },
      assignment_graded: { onsite: true, push: true },
      announcement_posted: { onsite: true, push: true },
      qa_answered: { onsite: true, push: true },
      quiz_feedback: { onsite: true, push: false },
      removed_from_course: { push: true }
    }),
    notification_instructor: getObjectSetting('notification_instructor', {
      application_accepted: { onsite: true, push: true },
      application_rejected: { onsite: true, push: false }
    }),
    notification_admin: getObjectSetting('notification_admin', {
      application_received: { onsite: true, push: true }
    }),

    // Authentication Settings
    two_factor_auth_enabled: getSetting('two_factor_auth_enabled', false),
    two_factor_auth_method: getSetting('two_factor_auth_method', 'email'),
    two_factor_auth_locations: getSetting('two_factor_auth_locations', ['admin', 'tutor']),
    fraud_protection_enabled: getSetting('fraud_protection_enabled', false),
    fraud_protection_method: getSetting('fraud_protection_method', 'recaptcha_v3'),
    recaptcha_site_key: getSetting('recaptcha_site_key', ''),
    recaptcha_secret_key: getSetting('recaptcha_secret_key', ''),
    fraud_protection_locations: getSetting('fraud_protection_locations', ['tutor_login', 'tutor_register']),
    limit_login_sessions: getSetting('limit_login_sessions', false),
    max_active_sessions: getSetting('max_active_sessions', 3),
    email_verification_enabled: getSetting('email_verification_enabled', true),

    // Certificate Settings
    cert_authorised_name: getSetting('cert_authorised_name', ''),
    cert_company_name: getSetting('cert_company_name', ''),
    cert_page: getSetting('cert_page', 'certificate'),
    cert_show_instructor: getSetting('cert_show_instructor', true),
    cert_email_link: getSetting('cert_email_link', false),
    cert_signature: getSetting('cert_signature', '/images/signature-placeholder.png'),

    // License Settings
    license_key: getSetting('license_key', ''),
  }
});

const logoInput = ref(null);
const triggerLogoUpload = () => {
  if (logoInput.value) {
    logoInput.value.click();
  }
};
const handleLogoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (event) => {
      form.settings.course_logo = event.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const signatureInput = ref(null);
const triggerSignatureUpload = () => {
  if (signatureInput.value) {
    signatureInput.value.click();
  }
};
const handleSignatureChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (event) => {
      form.settings.cert_signature = event.target.result;
    };
    reader.readAsDataURL(file);
  }
};
const removeLogo = () => {
  form.settings.course_logo = '/images/logo-placeholder.png';
};
const removeSignature = () => {
  form.settings.cert_signature = '/images/signature-placeholder.png';
};

const currentDomain = ref(typeof window !== 'undefined' ? window.location.hostname : 'localhost');

const applyPresetColors = (presetName) => {
  form.settings.preset_colors = presetName;
  if (presetName === 'default') {
    form.settings.primary_color = '#264790';
    form.settings.primary_hover_color = '#1A2B49';
    form.settings.text_color = '#1A2B49';
    form.settings.gray_color = '#E3E5EB';
    form.settings.border_color = '#CDCFD5';
    form.settings.text_color_hover = '#ffffff';
  } else if (presetName === 'teal') {
    form.settings.primary_color = '#0d9488';
    form.settings.primary_hover_color = '#0f766e';
    form.settings.text_color = '#115e59';
    form.settings.gray_color = '#f0fdfa';
    form.settings.border_color = '#ccfbf1';
    form.settings.text_color_hover = '#ffffff';
  } else if (presetName === 'violet') {
    form.settings.primary_color = '#7c3aed';
    form.settings.primary_hover_color = '#6d28d9';
    form.settings.text_color = '#5b21b6';
    form.settings.gray_color = '#f5f3ff';
    form.settings.border_color = '#ddd6fe';
    form.settings.text_color_hover = '#ffffff';
  } else if (presetName === 'rose') {
    form.settings.primary_color = '#e11d48';
    form.settings.primary_hover_color = '#be123c';
    form.settings.text_color = '#9f1239';
    form.settings.gray_color = '#fff1f2';
    form.settings.border_color = '#ffe4e6';
    form.settings.text_color_hover = '#ffffff';
  }
};

const showSuccessModal = ref(false);

const saveSettings = () => {
  form.post(route('dashboard.settings.update'), {
    preserveScroll: true,
    onSuccess: () => {
      showSuccessModal.value = true;
    }
  });
};

const triggerTestNotification = (eventType, role) => {
  router.post(route('notifications.test-trigger'), {
    event_type: eventType,
    role: role
  }, {
    preserveScroll: true
  });
};
</script>

<template>
  <Head title="LMS Settings" />

  <DashboardWrapper>
    <div class="flex flex-col md:flex-row gap-8">
      
      <!-- Settings Sidebar -->
      <div class="w-full md:w-64 shrink-0">
        <h2 class="text-3xl font-extrabold text-slate-800 mb-6 pl-2">Settings</h2>
        <div class="bg-slate-50/50 p-3 rounded-2xl border border-slate-100 flex flex-col gap-1.5 shadow-sm">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id 
                ? 'bg-white text-blue-600 shadow-sm border border-slate-150' 
                : 'text-slate-500 hover:bg-slate-100/70 hover:text-slate-700 border border-transparent'
            ]"
            class="flex items-center gap-3.5 px-4 py-3.5 rounded-xl text-sm font-bold transition-all duration-300 text-left"
          >
            <component :is="tab.icon" :size="18" :class="activeTab === tab.id ? 'text-blue-500' : 'text-slate-400'" />
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Settings Content Area -->
      <div class="flex-1 bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
        
        <!-- Flash Alert for License Requirement -->
        <div v-if="$page.props.flash && $page.props.flash.error" class="mb-6 bg-rose-50 border border-rose-100 p-4.5 rounded-2xl flex items-start gap-3.5 shadow-sm">
          <ShieldAlert :size="22" class="text-rose-500 shrink-0 mt-0.5" />
          <div>
            <span class="block text-sm font-black text-rose-800">License Verification Required</span>
            <span class="text-xs font-semibold text-rose-600/95 leading-relaxed block mt-0.5">{{ $page.props.flash.error }}</span>
          </div>
        </div>

        <div v-if="activeTab === 'general'" class="flex flex-col gap-8">
          
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">General Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Manage core functionalities and pages for your LMS.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-7 max-w-3xl">
            <!-- 1. Dashboard Page -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Dashboard Page</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">This page will be used for student and instructor dashboard.</p>
              <select v-model="form.settings.dashboard_page" class="w-full md:w-2/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                <option value="default">Default Dashboard</option>
                <option value="custom1">Custom Dashboard 1</option>
              </select>
            </div>

            <!-- 2. Terms and Conditions Page -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Terms and Conditions Page</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">This page will be used as the Terms and Conditions page.</p>
              <select v-model="form.settings.terms_page" class="w-full md:w-2/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                <option value="terms">Terms of Service</option>
                <option value="none">- Select Page -</option>
              </select>
            </div>

            <!-- 3. Privacy Policy -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Privacy Policy</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Choose the page for privacy policy.</p>
              <select v-model="form.settings.privacy_page" class="w-full md:w-2/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                <option value="privacy">Privacy Policy</option>
                <option value="none">- Select Page -</option>
              </select>
            </div>
            
            <hr class="border-slate-100 my-2" />

            <!-- 4. Enable Marketplace -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Enable Marketplace</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Allow multiple instructors to sell their courses.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.enable_marketplace = !form.settings.enable_marketplace">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.enable_marketplace ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.enable_marketplace ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 5. Pagination -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Pagination</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Set the number of rows to be displayed per page.</p>
              <input v-model.number="form.settings.pagination_rows" type="number" class="w-full md:w-1/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50 transition-colors" />
            </div>

            <hr class="border-slate-100 my-2" />

            <!-- 6. Become an Instructor Button -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Become an Instructor Button</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable the option to display this button on the student dashboard.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.become_instructor_button = !form.settings.become_instructor_button">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.become_instructor_button ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.become_instructor_button ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 7. Allow Instructor to Publish Courses -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Allow Instructor to Publish Courses</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable instructors to publish the course directly. If disabled, admins will be able to review course content before publishing.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1.5" @click="form.settings.instructor_publish_course = !form.settings.instructor_publish_course">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.instructor_publish_course ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.instructor_publish_course ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 8. Allow Instructor to Trash Courses -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Allow Instructor to Trash Courses</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable this setting to allow instructors to delete courses.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.instructor_trash_course = !form.settings.instructor_trash_course">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.instructor_trash_course ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.instructor_trash_course ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 9. Allow Instructor to Change course Author -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Allow Instructor to Change course Author</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">If enabled, instructors can change the course author for their courses.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.instructor_change_author = !form.settings.instructor_change_author">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.instructor_change_author ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.instructor_change_author ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 10. Allow Instructor to Reset Student Progress -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Allow Instructor to Reset Student Progress</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable to allow instructors to reset a student's course progress.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.instructor_reset_progress = !form.settings.instructor_reset_progress">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.instructor_reset_progress ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.instructor_reset_progress ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

          </div>
        </div>

        <!-- COURSE SETTINGS TAB -->
        <div v-else-if="activeTab === 'course'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">Course Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Configure course behaviors, visibility, and features.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-7 max-w-3xl">
            <!-- 0. Course Moderation for Instructor -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Instructor Course Moderation (Pending Status)</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable so that new courses created manually or imported by Instructors do not immediately become "Published", but instead have a "Pending" status requiring Admin approval.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.instructor_course_moderation = !form.settings.instructor_course_moderation">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.instructor_course_moderation ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.instructor_course_moderation ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 1. Course Visibility -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Course Visibility</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Students must be logged in to view course.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.course_visibility_login = !form.settings.course_visibility_login">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.course_visibility_login ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.course_visibility_login ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 2. Course Content Access -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Course Content Access</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Allow instructors and admins to view the course content without enrolling.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.course_content_access_admin = !form.settings.course_content_access_admin">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.course_content_access_admin ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.course_content_access_admin ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 3. Content Summary -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Content Summary</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enabling this feature will show a course content summary on the Course Details page.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.content_summary = !form.settings.content_summary">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.content_summary ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.content_summary ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 4. Spotlight Mode -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Spotlight Mode</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">This will hide the header and the footer and enable spotlight (full screen) mode when students view lessons.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.spotlight_mode = !form.settings.spotlight_mode">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.spotlight_mode ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.spotlight_mode ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 5. Auto Complete Course -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Auto Complete Course on All Lesson Completion</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">If enabled, an Enrolled Course will be automatically completed if all its Lessons, Quizzes, and Assignments are already completed by the Student.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.auto_complete_course = !form.settings.auto_complete_course">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.auto_complete_course ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.auto_complete_course ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 6. Course Completion Process -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Course Completion Process</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Choose when a user can click on the "Complete Course" button.</p>
              <div class="flex flex-col gap-3">
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" v-model="form.settings.course_completion_process" value="flexible" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500" />
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Flexible Mode</span>
                    <span class="text-xs text-slate-500">Students can complete course anytime.</span>
                  </div>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" v-model="form.settings.course_completion_process" value="strict" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500" />
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Strict Mode</span>
                    <span class="text-xs text-slate-500">Students must complete all lessons, quizzes, and assignments to mark their courses as complete.</span>
                  </div>
                </label>
              </div>
            </div>
            
            <!-- 7. Course Retake -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Course Retake</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enabling this feature will allow students to reset course progress and start over.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.course_retake = !form.settings.course_retake">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.course_retake ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.course_retake ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 8. Publish Course Review -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Publish Course Review on Admin's Approval</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable to publish/re-publish Course Review after the approval of Site Admin.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.publish_course_review_approval = !form.settings.publish_course_review_approval">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.publish_course_review_approval ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.publish_course_review_approval ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 9. Redirect Instructor Publish -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Redirect Instructor to "My Course" once Publish Button is Clicked</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable to Redirect an Instructor to the "My Courses" Page once he clicks on the "Publish" button.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.redirect_instructor_publish = !form.settings.redirect_instructor_publish">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.redirect_instructor_publish ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.redirect_instructor_publish ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 10. Attachment Open Mode -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Attachment Open Mode</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">How you want users to view attached files.</p>
              <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.settings.attachment_open_mode" value="download" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500" />
                  <span class="text-sm font-bold text-slate-700">Download</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="radio" v-model="form.settings.attachment_open_mode" value="new_tab" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500" />
                  <span class="text-sm font-bold text-slate-700">View in new tab</span>
                </label>
              </div>
            </div>

            <hr class="border-slate-100 my-2" />

            <!-- 11. Enable Course Gifting -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Enable Course Gifting</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Allow users to purchase and send courses as gifts.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.enable_course_gifting = !form.settings.enable_course_gifting">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.enable_course_gifting ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.enable_course_gifting ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 12. Video Lesson Completion Control -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Video Lesson Completion Control</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable to set the minimum video watch % for lesson completion, only works with Tutor Player.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.video_lesson_completion_control = !form.settings.video_lesson_completion_control">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.video_lesson_completion_control ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.video_lesson_completion_control ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 13. Auto Load Next Course Content -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Automatically Load Next Course Content</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable this feature to automatically load the next course content after the current one is finished.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.auto_load_next_content = !form.settings.auto_load_next_content">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.auto_load_next_content ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.auto_load_next_content ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 14. Enable Lesson Comment -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Enable Lesson Comment</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable this feature to allow students to post comments on lessons.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.enable_lesson_comment = !form.settings.enable_lesson_comment">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.enable_lesson_comment ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.enable_lesson_comment ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <hr class="border-slate-100 my-2" />
            <h4 class="text-lg font-bold text-slate-800 -mb-2">Quiz & Assignment</h4>

            <!-- 15. When Time Expires -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">When Time Expires</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Choose which action to follow when the quiz time expires.</p>
              <div class="flex flex-col gap-3">
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" v-model="form.settings.quiz_time_expires_action" value="auto_submit" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500" />
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Auto Submit</span>
                    <span class="text-xs text-slate-500">The current quiz answer are submitted automatically.</span>
                  </div>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                  <input type="radio" v-model="form.settings.quiz_time_expires_action" value="auto_abandon" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500" />
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Auto Abandon</span>
                    <span class="text-xs text-slate-500">Attempts must be submitted before time expires, otherwise they will not be counted.</span>
                  </div>
                </label>
              </div>
            </div>

            <!-- 16. Correct Answer Display Time -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Correct Answer Display Time</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Put the answer display time in seconds (When Reveal Mode is Enabled).</p>
              <input v-model.number="form.settings.correct_answer_display_time" type="number" class="w-full md:w-1/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50 transition-colors" />
            </div>

            <!-- 17. Default Quiz Attempt Limit -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Default Quiz Attempt Limit</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">The highest number of attempts allowed for students to participate a quiz. 0 means unlimited.</p>
              <input v-model.number="form.settings.default_quiz_attempt_limit" type="number" class="w-full md:w-1/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50 transition-colors" />
            </div>

            <!-- 18. Show Quiz Previous Button -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Show Quiz Previous Button</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Choose whether to show or hide the previous button for each question.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.show_quiz_previous_button = !form.settings.show_quiz_previous_button">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.show_quiz_previous_button ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.show_quiz_previous_button ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 19. Final Grade Calculation for Quiz -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Final Grade Calculation for Quiz</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">When multiple attempts are allowed, select which method should be used to calculate a student's final grade.</p>
              <select v-model="form.settings.final_grade_calc_quiz" class="w-full md:w-2/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                <option value="highest">Highest grade</option>
                <option value="average">Average grade</option>
                <option value="first">First attempt</option>
                <option value="last">Last attempt</option>
              </select>
            </div>

            <!-- 20. Hide Quiz Details from Students -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Hide Quiz Details from Students</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">If enabled, the students will not be able to see their quiz attempts details.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.hide_quiz_details_students = !form.settings.hide_quiz_details_students">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.hide_quiz_details_students ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.hide_quiz_details_students ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 21. Final Grade Calculation for Assignment -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Final Grade Calculation for Assignment</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">When multiple attempts are allowed, select which method should be used to calculate a student's final grade.</p>
              <select v-model="form.settings.final_grade_calc_assignment" class="w-full md:w-2/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                <option value="highest">Highest grade</option>
                <option value="average">Average grade</option>
                <option value="first">First attempt</option>
                <option value="last">Last attempt</option>
              </select>
            </div>

            <hr class="border-slate-100 my-2" />
            <h4 class="text-lg font-bold text-slate-800 -mb-2">Video Configuration</h4>

            <!-- 22. Preferred Video Source -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Preferred Video Source</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Select the video hosting platform(s) you want to enable.</p>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <label v-for="source in ['html5', 'external', 'youtube', 'vimeo', 'embedded', 'shortcode', 'gdrive']" :key="source" class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" :value="source" v-model="form.settings.preferred_video_source" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                  <span class="text-sm font-bold text-slate-700 capitalize">{{ source === 'html5' ? 'HTML 5 (mp4)' : source === 'external' ? 'External URL' : source }}</span>
                </label>
              </div>
            </div>

            <!-- 23. Use Tutor Player for YouTube -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Use Tutor Player for YouTube</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable this option to use Tutor LMS video player for YouTube.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.use_tutor_player_youtube = !form.settings.use_tutor_player_youtube">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.use_tutor_player_youtube ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.use_tutor_player_youtube ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 24. Use Tutor Player for Vimeo -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Use Tutor Player for Vimeo</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable this option to use Tutor LMS video player for Vimeo.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.use_tutor_player_vimeo = !form.settings.use_tutor_player_vimeo">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.use_tutor_player_vimeo ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.use_tutor_player_vimeo ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 25. Use Tutor Player for Gdrive Video -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Use Tutor Player for Gdrive Video</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Enable this option to use Tutor LMS video player for Google Drive/Photos Video Player.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.use_tutor_player_gdrive = !form.settings.use_tutor_player_gdrive">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.use_tutor_player_gdrive ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.use_tutor_player_gdrive ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

          </div>
        </div>

        <!-- MONETIZATION SETTINGS TAB -->
        <div v-else-if="activeTab === 'monetization'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">Monetization Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Configure eCommerce and revenue sharing options.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-7 max-w-3xl">
            <h4 class="text-lg font-bold text-slate-800 -mb-2 border-b border-slate-100 pb-2">Options</h4>
            
            <!-- 1. Select eCommerce Engine -->
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Select eCommerce Engine</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Select a monetization option to generate revenue by selling courses.</p>
              <select v-model="form.settings.ecommerce_engine" class="w-full md:w-2/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                <option value="none">None</option>
                <option value="native">Native eCommerce (Drastha Engine)</option>
              </select>
            </div>

            <!-- Payment Gateway Selector -->
            <div v-if="form.settings.ecommerce_engine === 'native'">
              <label class="block text-sm font-bold text-slate-700 mb-1.5">Select Payment Gateway</label>
              <p class="text-xs text-slate-500 mb-3 leading-relaxed">Choose the primary payment gateway for transactions.</p>
              <select v-model="form.settings.active_payment_gateway" class="w-full md:w-2/3 border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                <option value="midtrans">Midtrans</option>
                <option value="xendit">Xendit</option>
                <option value="faspay">Faspay</option>
                <option value="pivot">Pivot</option>
                <option value="doku">Doku</option>
              </select>
            </div>

            <!-- Midtrans Config Fields -->
            <div v-if="form.settings.ecommerce_engine === 'native' && form.settings.active_payment_gateway === 'midtrans'" class="bg-slate-50 p-5 rounded-2xl border border-slate-100 flex flex-col gap-4">
              <h5 class="text-sm font-extrabold text-slate-800">Midtrans Configuration</h5>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Midtrans Client Key</label>
                <input type="text" v-model="form.settings.midtrans_client_key" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" placeholder="e.g. SB-Mid-client-..." />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Midtrans Server Key</label>
                <input type="password" v-model="form.settings.midtrans_server_key" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" placeholder="e.g. SB-Mid-server-..." />
              </div>
              <div class="flex items-center justify-between mt-1">
                <div>
                  <label class="block text-xs font-bold text-slate-600">Sandbox Mode</label>
                  <p class="text-[10px] text-slate-400">Enable Sandbox mode for testing transactions.</p>
                </div>
                <div class="relative inline-block w-10 h-6 cursor-pointer" @click="form.settings.midtrans_sandbox_mode = !form.settings.midtrans_sandbox_mode">
                  <div class="w-10 h-6 rounded-full transition-colors duration-300" :class="form.settings.midtrans_sandbox_mode ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-0.5 left-0.5 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.midtrans_sandbox_mode ? 'translate-x-4' : 'translate-x-0'"></div>
                </div>
              </div>
            </div>

            <!-- Xendit Config Fields -->
            <div v-if="form.settings.ecommerce_engine === 'native' && form.settings.active_payment_gateway === 'xendit'" class="bg-slate-50 p-5 rounded-2xl border border-slate-100 flex flex-col gap-4">
              <h5 class="text-sm font-extrabold text-slate-800">Xendit Configuration</h5>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Xendit Public Key</label>
                <input type="text" v-model="form.settings.xendit_public_key" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" placeholder="xnd_public_key..." />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Xendit Secret Key</label>
                <input type="password" v-model="form.settings.xendit_secret_key" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" placeholder="xnd_development_..." />
              </div>
            </div>

            <!-- Faspay Config Fields -->
            <div v-if="form.settings.ecommerce_engine === 'native' && form.settings.active_payment_gateway === 'faspay'" class="bg-slate-50 p-5 rounded-2xl border border-slate-100 flex flex-col gap-4">
              <h5 class="text-sm font-extrabold text-slate-800">Faspay Configuration</h5>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Merchant ID</label>
                <input type="text" v-model="form.settings.faspay_merchant_id" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Merchant Password</label>
                <input type="password" v-model="form.settings.faspay_merchant_password" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
              </div>
            </div>

            <!-- Pivot Config Fields -->
            <div v-if="form.settings.ecommerce_engine === 'native' && form.settings.active_payment_gateway === 'pivot'" class="bg-slate-50 p-5 rounded-2xl border border-slate-100 flex flex-col gap-4">
              <h5 class="text-sm font-extrabold text-slate-800">Pivot Configuration</h5>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Pivot API Key</label>
                <input type="text" v-model="form.settings.pivot_api_key" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
              </div>
            </div>

            <!-- Doku Config Fields -->
            <div v-if="form.settings.ecommerce_engine === 'native' && form.settings.active_payment_gateway === 'doku'" class="bg-slate-50 p-5 rounded-2xl border border-slate-100 flex flex-col gap-4">
              <h5 class="text-sm font-extrabold text-slate-800">Doku Configuration</h5>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Doku Client ID</label>
                <input type="text" v-model="form.settings.doku_client_id" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Doku Shared Key</label>
                <input type="password" v-model="form.settings.doku_shared_key" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
              </div>
            </div>

            <h4 class="text-lg font-bold text-slate-800 mt-4 -mb-2 border-b border-slate-100 pb-2">eCommerce</h4>

            <!-- 2. Automatically Complete eCommerce Orders -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Automatically Complete eCommerce Orders</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">If enabled, in the case of Courses, eCommerce Orders will get the "Completed" status.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.auto_complete_ecommerce_orders = !form.settings.auto_complete_ecommerce_orders">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.auto_complete_ecommerce_orders ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.auto_complete_ecommerce_orders ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 3. Generate eCommerce Order -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Generate eCommerce Order</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">If you want to create an eCommerce Order to keep Track of your Sales Report for Manual Enrolment.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.generate_ecommerce_order = !form.settings.generate_ecommerce_order">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.generate_ecommerce_order ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.generate_ecommerce_order ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 4. Auto Redirect to Courses -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Auto Redirect to Courses</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">When a user's eCommerce order is auto-completed, they will be redirected to enrolled courses.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.auto_redirect_to_courses = !form.settings.auto_redirect_to_courses">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.auto_redirect_to_courses ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.auto_redirect_to_courses ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- 5. Enable Guest Mode -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Enable Guest Mode</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Allow customers to place orders without an account.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.enable_guest_mode = !form.settings.enable_guest_mode">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.enable_guest_mode ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.enable_guest_mode ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <h4 class="text-lg font-bold text-slate-800 mt-4 -mb-2 border-b border-slate-100 pb-2">Revenue Sharing</h4>

            <!-- 6. Enable Revenue Sharing -->
            <div class="flex items-start justify-between group">
              <div class="pr-6">
                <label class="block text-sm font-bold text-slate-700">Enable Revenue Sharing</label>
                <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">Allow revenue generated from selling courses to be shared with course creators.</p>
              </div>
              <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.enable_revenue_sharing = !form.settings.enable_revenue_sharing">
                <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.enable_revenue_sharing ? 'bg-blue-600' : 'bg-slate-300'"></div>
                <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.enable_revenue_sharing ? 'translate-x-5' : 'translate-x-0'"></div>
              </div>
            </div>

            <!-- Revenue Sharing details -->
            <div v-if="form.settings.enable_revenue_sharing" class="bg-slate-50 p-6 rounded-3xl border border-slate-200/60 flex flex-col gap-6">
              
              <!-- 1. Sharing Percentage -->
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Sharing Percentage</label>
                <p class="text-xs text-slate-400 mb-3">Set how the sales revenue will be shared among admins and instructors.</p>
                <div class="flex gap-4 items-center">
                  <div class="w-1/2">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Admin Share (%)</label>
                    <input type="number" min="0" max="100" v-model.number="form.settings.sharing_percentage_admin" @input="form.settings.sharing_percentage_instructor = 100 - form.settings.sharing_percentage_admin" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
                  </div>
                  <div class="w-1/2">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Instructor Share (%)</label>
                    <input type="number" min="0" max="100" v-model.number="form.settings.sharing_percentage_instructor" @input="form.settings.sharing_percentage_admin = 100 - form.settings.sharing_percentage_instructor" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
                  </div>
                </div>
              </div>

              <!-- 2. Deduct fees -->
              <div class="border-t border-slate-200/50 pt-4 flex flex-col gap-4">
                <div class="flex items-start justify-between">
                  <div>
                    <label class="block text-sm font-bold text-slate-700">Deduct Fees</label>
                    <p class="text-xs text-slate-400 mt-1">Fees charged from entire sales amount before revenue sharing distribution.</p>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0 mt-1" @click="form.settings.deduct_fees_enabled = !form.settings.deduct_fees_enabled">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.deduct_fees_enabled ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.deduct_fees_enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>

                <div v-if="form.settings.deduct_fees_enabled" class="flex flex-col md:flex-row gap-4 mt-2">
                  <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Fee Name</label>
                    <input type="text" v-model="form.settings.deduct_fees_name" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" placeholder="e.g. Gateway fee" />
                  </div>
                  <div class="w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Fee Type</label>
                    <select v-model="form.settings.deduct_fees_type" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold bg-white cursor-pointer transition-colors">
                      <option value="fixed">Fixed Amount</option>
                      <option value="percentage">Percentage (%)</option>
                    </select>
                  </div>
                  <div class="w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Fee Value</label>
                    <input type="number" min="0" v-model.number="form.settings.deduct_fees_value" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
                  </div>
                </div>
              </div>

              <!-- 3. Minimum Withdrawal Amount & 4. Available Balance delay -->
              <div class="border-t border-slate-200/50 pt-4 flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                  <label class="block text-sm font-bold text-slate-700 mb-1">Minimum Withdrawal Amount</label>
                  <p class="text-xs text-slate-400 mb-2">Instructors must earn at least this amount to request withdrawals.</p>
                  <input type="number" min="0" v-model.number="form.settings.minimum_withdrawal_amount" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
                </div>
                <div class="flex-1">
                  <label class="block text-sm font-bold text-slate-700 mb-1">Minimum Hold Days</label>
                  <p class="text-xs text-slate-400 mb-2">Hold duration (in days) before balance is available to withdraw.</p>
                  <input type="number" min="0" v-model.number="form.settings.minimum_days_before_available" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors" />
                </div>
              </div>

              <!-- 5. Enable Withdrawal Method -->
              <div class="border-t border-slate-200/50 pt-4">
                <label class="block text-sm font-bold text-slate-700 mb-1">Enable Withdrawal Method</label>
                <p class="text-xs text-slate-400 mb-3">Set how instructors can request payouts.</p>
                <div class="flex flex-wrap gap-4 mt-2">
                  <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                    <input type="checkbox" value="bank_transfer" v-model="form.settings.withdrawal_methods" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
                    <span>Bank Transfer</span>
                  </label>
                  <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                    <input type="checkbox" value="e_check" v-model="form.settings.withdrawal_methods" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
                    <span>E-Check</span>
                  </label>
                  <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                    <input type="checkbox" value="paypal" v-model="form.settings.withdrawal_methods" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
                    <span>PayPal</span>
                  </label>
                </div>
              </div>

              <!-- 6. Bank Instructions -->
              <div class="border-t border-slate-200/50 pt-4">
                <label class="block text-sm font-bold text-slate-700 mb-1">Bank Instructions</label>
                <p class="text-xs text-slate-400 mb-2">Write detailed bank instruction parameters for conducting payouts.</p>
                <textarea v-model="form.settings.bank_instructions" rows="3" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-semibold transition-colors resize-none" placeholder="e.g. Please provide Account Name, Bank Name, Account Number, and Swift Code."></textarea>
              </div>

            </div>

          </div>
        </div>

        <!-- DESIGN SETTINGS TAB -->
        <div v-else-if="activeTab === 'design'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">Design Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Customize layouts, presets, logos, and global colors.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-8 max-w-4xl">
            <!-- Course Section -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <BookOpen :size="20" class="text-blue-600" /> Course Catalog Design
              </h4>

              <!-- 1. Course Builder Page Logo -->
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Course Builder Page Logo</label>
                <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-slate-50 rounded-2xl border border-slate-150">
                  <div class="relative w-48 h-28 bg-white rounded-xl border border-slate-200 overflow-hidden flex items-center justify-center group shadow-inner">
                    <img :src="form.settings.course_logo" class="max-w-full max-h-full object-contain p-2" />
                    <button type="button" v-if="form.settings.course_logo !== '/images/logo-placeholder.png'" @click="removeLogo" class="absolute top-2 right-2 bg-rose-500 hover:bg-rose-600 text-white p-1.5 rounded-full shadow transition-all opacity-0 group-hover:opacity-100">
                      <Trash2 :size="14" />
                    </button>
                  </div>
                  <div class="flex-1 text-center sm:text-left">
                    <p class="text-xs font-semibold text-slate-500">Recommended Size: 700x430 pixels</p>
                    <p class="text-xs text-slate-400 mt-1">Supports JPG, JPEG, or PNG format.</p>
                    <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="handleLogoChange" />
                    <button type="button" @click="triggerLogoUpload" class="mt-3 bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 px-4 py-2.5 rounded-xl text-xs font-bold transition-all inline-flex items-center gap-1.5">
                      <UploadCloud :size="14" /> Upload Image
                    </button>
                  </div>
                </div>
              </div>

              <!-- 2. Column Per Row -->
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Column Per Row</label>
                <p class="text-xs text-slate-500 mb-3">Define how many columns you want to use to display courses on the list page.</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                  <div v-for="col in [1, 2, 3, 4]" :key="col" @click="form.settings.course_columns = col" class="cursor-pointer border-2 p-4 rounded-xl flex flex-col items-center justify-center gap-3 transition-all" :class="form.settings.course_columns === col ? 'border-blue-600 bg-blue-50/40' : 'border-slate-100 hover:border-slate-300 bg-white'">
                    <!-- Re-designed layout visual representation -->
                    <div class="w-full flex gap-1 h-8 justify-center items-center">
                      <div v-for="i in col" :key="i" class="bg-blue-600/20 border border-blue-600/30 rounded h-6 w-full flex items-center justify-center text-[9px] font-black text-blue-600/50">█</div>
                    </div>
                    <span class="text-xs font-bold text-slate-700 capitalize">{{ col === 1 ? 'One' : col === 2 ? 'Two' : col === 3 ? 'Three' : 'Four' }} Column</span>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 3. Courses Per Page -->
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Courses Per Page</label>
                  <p class="text-xs text-slate-500 mb-2">Set the number of courses to display per page.</p>
                  <input v-model.number="form.settings.courses_per_page" type="number" class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50 transition-colors" />
                </div>

                <!-- 4. Course Filter & 5. Course Sorting -->
                <div class="flex flex-col gap-4 justify-end pb-1">
                  <div class="flex items-center justify-between">
                    <div>
                      <span class="block text-sm font-bold text-slate-700">Course Filter</span>
                      <span class="text-xs text-slate-500">Show sorting & filtering options on archive.</span>
                    </div>
                    <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.course_filter = !form.settings.course_filter">
                      <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.course_filter ? 'bg-blue-600' : 'bg-slate-300'"></div>
                      <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.course_filter ? 'translate-x-5' : 'translate-x-0'"></div>
                    </div>
                  </div>

                  <div class="flex items-center justify-between">
                    <div>
                      <span class="block text-sm font-bold text-slate-700">Course Sorting</span>
                      <span class="text-xs text-slate-500">Allows sorting by name or date.</span>
                    </div>
                    <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.course_sorting = !form.settings.course_sorting">
                      <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.course_sorting ? 'bg-blue-600' : 'bg-slate-300'"></div>
                      <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.course_sorting ? 'translate-x-5' : 'translate-x-0'"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Layouts Section -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Sliders :size="20" class="text-blue-600" /> Member Layouts
              </h4>

              <!-- 6. Instructor List Layout -->
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Instructor List Layout</label>
                <p class="text-xs text-slate-500 mb-3">Choose a layout for the list of instructors inside a course page.</p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                  <div v-for="layout in ['portrait', 'cover', 'minimal', 'portrait_horizontal', 'minimal_horizontal']" :key="layout" @click="form.settings.instructor_list_layout = layout" class="cursor-pointer border-2 p-3 rounded-xl flex flex-col items-center justify-center text-center gap-2.5 transition-all" :class="form.settings.instructor_list_layout === layout ? 'border-blue-600 bg-blue-50/40' : 'border-slate-100 hover:border-slate-300 bg-white'">
                    <!-- Customized Mock Layout preview -->
                    <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] text-slate-400 font-bold overflow-hidden border border-slate-200">
                      <div v-if="layout === 'portrait'" class="flex flex-col items-center w-full h-full p-1 gap-1">
                        <div class="w-6 h-6 rounded-full bg-slate-300"></div>
                        <div class="w-8 h-1.5 bg-slate-300 rounded"></div>
                      </div>
                      <div v-else-if="layout === 'cover'" class="flex flex-col items-center w-full h-full relative">
                        <div class="w-full h-1/2 bg-slate-300"></div>
                        <div class="w-4 h-4 rounded-full bg-slate-205 border border-white absolute top-1.5"></div>
                      </div>
                      <div v-else-if="layout === 'minimal'" class="flex flex-col items-center justify-center gap-1 w-full h-full">
                        <div class="w-5 h-5 rounded-full bg-slate-300"></div>
                      </div>
                      <div v-else-if="layout === 'portrait_horizontal'" class="flex items-center w-full h-full p-1 gap-1">
                        <div class="w-5 h-5 rounded-full bg-slate-300 shrink-0"></div>
                        <div class="flex flex-col gap-0.5 w-full">
                          <div class="w-6 h-1 bg-slate-300 rounded"></div>
                          <div class="w-4 h-0.5 bg-slate-300 rounded"></div>
                        </div>
                      </div>
                      <div v-else-if="layout === 'minimal_horizontal'" class="flex items-center justify-center w-full h-full p-1 gap-1">
                        <div class="w-4 h-4 rounded-full bg-slate-300 shrink-0"></div>
                        <div class="w-5 h-1 bg-slate-300 rounded"></div>
                      </div>
                    </div>
                    <span class="text-[11px] font-bold text-slate-700 capitalize leading-tight">{{ layout.replace('_', ' ') }}</span>
                  </div>
                </div>
              </div>

              <!-- 7. Instructor Public Profile Layout & 8. Student Public Profile Layout -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">Instructor Public Profile Layout</label>
                  <div class="grid grid-cols-2 gap-2">
                    <div v-for="lay in ['private', 'modern', 'minimal', 'classic']" :key="lay" @click="form.settings.instructor_profile_layout = lay" class="cursor-pointer border-2 p-2.5 rounded-xl flex items-center gap-2.5 transition-all" :class="form.settings.instructor_profile_layout === lay ? 'border-blue-600 bg-blue-50/40' : 'border-slate-100 hover:border-slate-300 bg-white'">
                      <div class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-[9px] font-bold border border-slate-200 text-slate-500 capitalize">{{ lay[0] }}</div>
                      <span class="text-xs font-bold text-slate-700 capitalize">{{ lay }}</span>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-2">Student Public Profile Layout</label>
                  <div class="grid grid-cols-2 gap-2">
                    <div v-for="lay in ['private', 'modern', 'minimal', 'classic']" :key="lay" @click="form.settings.student_profile_layout = lay" class="cursor-pointer border-2 p-2.5 rounded-xl flex items-center gap-2.5 transition-all" :class="form.settings.student_profile_layout === lay ? 'border-blue-600 bg-blue-50/40' : 'border-slate-100 hover:border-slate-300 bg-white'">
                      <div class="w-6 h-6 rounded bg-slate-100 flex items-center justify-center text-[9px] font-bold border border-slate-200 text-slate-500 capitalize">{{ lay[0] }}</div>
                      <span class="text-xs font-bold text-slate-700 capitalize">{{ lay }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Course Details Features Section -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Sliders :size="20" class="text-blue-600" /> Course Details Page
              </h4>

              <!-- 9. Page Features (19 Modes) -->
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Page Features</label>
                <p class="text-xs text-slate-500 mb-4">Select which visual sections you want to display on the Course details page template.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                  <div v-for="(val, key) in form.settings.page_features" :key="key" @click="form.settings.page_features[key] = !form.settings.page_features[key]" class="cursor-pointer border p-3 rounded-xl flex items-center justify-between gap-3 transition-all" :class="val ? 'border-blue-200 bg-blue-50/30' : 'border-slate-200 hover:border-slate-300 bg-white'">
                    <span class="text-xs font-bold text-slate-700 capitalize">{{ key.replace('_', ' ') }}</span>
                    <div class="w-3.5 h-3.5 rounded-full flex items-center justify-center border" :class="val ? 'border-blue-600 bg-blue-600' : 'border-slate-300 bg-white'">
                      <svg v-if="val" class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 8 8"><path d="M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z"/></svg>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 10. Position of Enrollment Box & 11. Showcase Certificate -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Position of Enrollment Box in Mobile View</label>
                  <p class="text-xs text-slate-500 mb-2.5">Decide where you want to show the enrollment button box on mobile layout.</p>
                  <select v-model="form.settings.enrollment_box_mobile" class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-semibold bg-slate-50 transition-colors cursor-pointer">
                    <option value="default">Select Option</option>
                    <option value="top">On Page Top</option>
                    <option value="bottom">On Page Bottom</option>
                  </select>
                </div>

                <div class="flex items-center justify-between bg-slate-50 p-4 rounded-xl border border-slate-150 self-end">
                  <div>
                    <label class="block text-sm font-bold text-slate-700">Showcase Certificate</label>
                    <p class="text-xs text-slate-500 mt-0.5">Enable showing certificates on course details.</p>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.showcase_certificate = !form.settings.showcase_certificate">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.showcase_certificate ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.showcase_certificate ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Colors Section -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Palette :size="20" class="text-blue-600" /> Dynamic Color Palette
              </h4>

              <!-- 12. Preset Colors -->
              <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Preset Colors</label>
                <p class="text-xs text-slate-500 mb-3.5">Instantly match your platform's brand voice with custom curated layout presets.</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                  <div v-for="preset in ['default', 'teal', 'violet', 'rose']" :key="preset" @click="applyPresetColors(preset)" class="cursor-pointer border-2 p-3.5 rounded-xl flex flex-col gap-2.5 transition-all" :class="form.settings.preset_colors === preset ? 'border-blue-600 bg-blue-50/40' : 'border-slate-100 hover:border-slate-300 bg-white'">
                    <span class="text-xs font-bold text-slate-700 capitalize">{{ preset }} theme</span>
                    <div class="flex gap-1">
                      <div class="w-4 h-4 rounded-full border shadow-sm" :style="{ backgroundColor: preset === 'default' ? '#264790' : preset === 'teal' ? '#0d9488' : preset === 'violet' ? '#7c3aed' : '#e11d48' }"></div>
                      <div class="w-4 h-4 rounded-full border shadow-sm" :style="{ backgroundColor: preset === 'default' ? '#1A2B49' : preset === 'teal' ? '#0f766e' : preset === 'violet' ? '#6d28d9' : '#be123c' }"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Color Pickers -->
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 pt-2">
                <!-- 13. Primary Color -->
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Primary Color</label>
                  <div class="flex items-center gap-3 border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                    <input type="color" v-model="form.settings.primary_color" class="w-10 h-10 border rounded-lg cursor-pointer" />
                    <input type="text" v-model="form.settings.primary_color" class="w-full text-xs font-mono font-bold bg-transparent border-none outline-none text-slate-700" />
                  </div>
                </div>

                <!-- 14. Primary Hover Color -->
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Primary Hover Color</label>
                  <div class="flex items-center gap-3 border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                    <input type="color" v-model="form.settings.primary_hover_color" class="w-10 h-10 border rounded-lg cursor-pointer" />
                    <input type="text" v-model="form.settings.primary_hover_color" class="w-full text-xs font-mono font-bold bg-transparent border-none outline-none text-slate-700" />
                  </div>
                </div>

                <!-- 15. Text Color -->
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Text Color</label>
                  <div class="flex items-center gap-3 border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                    <input type="color" v-model="form.settings.text_color" class="w-10 h-10 border rounded-lg cursor-pointer" />
                    <input type="text" v-model="form.settings.text_color" class="w-full text-xs font-mono font-bold bg-transparent border-none outline-none text-slate-700" />
                  </div>
                </div>

                <!-- 16. Gray -->
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Gray Color</label>
                  <div class="flex items-center gap-3 border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                    <input type="color" v-model="form.settings.gray_color" class="w-10 h-10 border rounded-lg cursor-pointer" />
                    <input type="text" v-model="form.settings.gray_color" class="w-full text-xs font-mono font-bold bg-transparent border-none outline-none text-slate-700" />
                  </div>
                </div>

                <!-- 17. Border -->
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Border Color</label>
                  <div class="flex items-center gap-3 border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                    <input type="color" v-model="form.settings.border_color" class="w-10 h-10 border rounded-lg cursor-pointer" />
                    <input type="text" v-model="form.settings.border_color" class="w-full text-xs font-mono font-bold bg-transparent border-none outline-none text-slate-700" />
                  </div>
                </div>

                <!-- 18. Text Color Hover -->
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Text Color Hover</label>
                  <div class="flex items-center gap-3 border border-slate-200 rounded-xl p-2.5 bg-slate-50">
                    <input type="color" v-model="form.settings.text_color_hover" class="w-10 h-10 border rounded-lg cursor-pointer" />
                    <input type="text" v-model="form.settings.text_color_hover" class="w-full text-xs font-mono font-bold bg-transparent border-none outline-none text-slate-700" />
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- ADVANCED SETTINGS TAB -->
        <div v-else-if="activeTab === 'advanced'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">Advanced Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Configure course builder fields visibility, permalinks, security, and AI integrations.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-8 max-w-4xl">
            <!-- 1. Basics, 2. Curriculum, 3. Additional -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Sliders :size="20" class="text-blue-600" /> Course Builder Fields Visibility Control
              </h4>

              <div class="flex flex-col gap-6">
                <!-- Basics Section -->
                <div>
                  <h5 class="text-sm font-bold text-slate-700 mb-3 pb-1 border-b border-slate-100/60">Basics Fields</h5>
                  <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                      <thead>
                        <tr class="border-b border-slate-200">
                          <th class="py-2 text-xs font-bold text-slate-500 w-1/2">Field Name</th>
                          <th class="py-2 text-xs font-bold text-slate-500 text-center w-1/4">Admin</th>
                          <th class="py-2 text-xs font-bold text-slate-500 text-center w-1/4">Instructor</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="field in ['general', 'content_drip', 'enrollment', 'featured_image', 'intro_video', 'scheduling', 'pricing', 'categories', 'tags', 'author', 'instructor']" :key="field" class="border-b border-slate-100 hover:bg-slate-50/50">
                          <td class="py-2 text-xs font-semibold text-slate-700 capitalize">{{ field.replace('_', ' ') }}</td>
                          <td class="py-2 text-center">
                            <input type="checkbox" v-model="form.settings.builder_fields_basics.admin[field]" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                          </td>
                          <td class="py-2 text-center">
                            <input type="checkbox" v-model="form.settings.builder_fields_basics.instructor[field]" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Curriculum Section -->
                <div class="mt-2">
                  <h5 class="text-sm font-bold text-slate-700 mb-3 pb-1 border-b border-slate-100/60">Curriculum Fields</h5>
                  <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                      <thead>
                        <tr class="border-b border-slate-200">
                          <th class="py-2 text-xs font-bold text-slate-500 w-1/2">Field Name</th>
                          <th class="py-2 text-xs font-bold text-slate-500 text-center w-1/4">Admin</th>
                          <th class="py-2 text-xs font-bold text-slate-500 text-center w-1/4">Instructor</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="field in ['featured_image', 'video', 'playback', 'exercise_files', 'preview']" :key="field" class="border-b border-slate-100 hover:bg-slate-50/50">
                          <td class="py-2 text-xs font-semibold text-slate-700 capitalize">{{ field.replace('_', ' ') }}</td>
                          <td class="py-2 text-center">
                            <input type="checkbox" v-model="form.settings.builder_fields_curriculum.admin[field]" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                          </td>
                          <td class="py-2 text-center">
                            <input type="checkbox" v-model="form.settings.builder_fields_curriculum.instructor[field]" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Additional Section -->
                <div class="mt-2">
                  <h5 class="text-sm font-bold text-slate-700 mb-3 pb-1 border-b border-slate-100/60">Additional Fields</h5>
                  <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                      <thead>
                        <tr class="border-b border-slate-200">
                          <th class="py-2 text-xs font-bold text-slate-500 w-1/2">Field Name</th>
                          <th class="py-2 text-xs font-bold text-slate-500 text-center w-1/4">Admin</th>
                          <th class="py-2 text-xs font-bold text-slate-500 text-center w-1/4">Instructor</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="field in ['what_will_i_learn', 'target_audience', 'duration', 'materials', 'requirements', 'certificate', 'attachments', 'live_class']" :key="field" class="border-b border-slate-100 hover:bg-slate-50/50">
                          <td class="py-2 text-xs font-semibold text-slate-700 capitalize">{{ field.replace('_', ' ') }}</td>
                          <td class="py-2 text-center">
                            <input type="checkbox" v-model="form.settings.builder_fields_additional.admin[field]" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                          </td>
                          <td class="py-2 text-center">
                            <input type="checkbox" v-model="form.settings.builder_fields_additional.instructor[field]" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

              </div>
            </div>

            <!-- 4. Course -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <BookOpen :size="20" class="text-blue-600" /> Course Settings & API
              </h4>

              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-sm font-bold text-slate-700">Hide course product on shop page</span>
                  <span class="text-xs text-slate-500">Enable to hide course products on shop page.</span>
                </div>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.hide_course_product_shop = !form.settings.hide_course_product_shop">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.hide_course_product_shop ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.hide_course_product_shop ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Course Archive Page</label>
                  <input v-model="form.settings.course_archive_page" type="text" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Instructor Registration Page</label>
                  <input v-model="form.settings.instructor_registration_page" type="text" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Student Registration Page</label>
                  <input v-model="form.settings.student_registration_page" type="text" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-bold text-slate-700 mb-1.5">YouTube Key API</label>
                <p class="text-xs text-slate-500 mb-2">To host live videos on your platform using YouTube, enter your YouTube API key.</p>
                <input v-model="form.settings.youtube_api_key" type="text" placeholder="AIzaSy..." class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-sm font-bold text-slate-700">Hide Admin Bar and Restrict Access to Admin Page for Instructors</span>
                  <span class="text-xs text-slate-500">Enable to hide Admin Bar from frontend and prevent instructors from accessing Admin panel.</span>
                </div>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.hide_admin_bar_restrict_access = !form.settings.hide_admin_bar_restrict_access">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.hide_admin_bar_restrict_access ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.hide_admin_bar_restrict_access ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>
            </div>

            <!-- 5. Base Permalink -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Sliders :size="20" class="text-blue-600" /> Base Permalink Config (Optional / Custom URLs)
              </h4>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Course Permalink Base</label>
                  <input v-model="form.settings.permalink_course" type="text" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Lesson Permalink Base</label>
                  <input v-model="form.settings.permalink_lesson" type="text" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Quiz Permalink Base</label>
                  <input v-model="form.settings.permalink_quiz" type="text" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Assignment Permalink Base</label>
                  <input v-model="form.settings.permalink_assignment" type="text" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
              </div>
            </div>

            <!-- 6. Options -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Sliders :size="20" class="text-blue-600" /> Miscellaneous Options
              </h4>

              <div class="flex flex-col gap-5">
                <div class="flex items-center justify-between">
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Profile Completion</span>
                    <span class="text-xs text-slate-500">Enabling this feature will show a notification bar to students and instructors to complete their profile information.</span>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.profile_completion_alert = !form.settings.profile_completion_alert">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.profile_completion_alert ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.profile_completion_alert ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Enable Email Update</span>
                    <span class="text-xs text-slate-500">Allow students and instructors to change their email directly from their profile.</span>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.enable_email_update = !form.settings.enable_email_update">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.enable_email_update ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.enable_email_update ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Erase Upon Uninstallation</span>
                    <span class="text-xs text-slate-500">Delete all data during uninstallation.</span>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.erase_on_uninstall = !form.settings.erase_on_uninstall">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.erase_on_uninstall ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.erase_on_uninstall ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Maintenance Mode</span>
                    <span class="text-xs text-slate-500">Enabling maintenance mode will display a custom message on the frontend.</span>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.maintenance_mode = !form.settings.maintenance_mode">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.maintenance_mode ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.maintenance_mode ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 7. Content Security -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Shield :size="20" class="text-blue-600" /> Content Security
              </h4>

              <div class="flex flex-col gap-5">
                <div class="flex items-center justify-between">
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Prevent Hotlinking</span>
                    <span class="text-xs text-slate-500">Use hotlink protection for your self-hosted images and videos.</span>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.prevent_hotlinking = !form.settings.prevent_hotlinking">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.prevent_hotlinking ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.prevent_hotlinking ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div>
                    <span class="block text-sm font-bold text-slate-700">Copy Protection</span>
                    <span class="text-xs text-slate-500">Prevent right-click and copy actions on your website.</span>
                  </div>
                  <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.copy_protection = !form.settings.copy_protection">
                    <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.copy_protection ? 'bg-blue-600' : 'bg-slate-300'"></div>
                    <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.copy_protection ? 'translate-x-5' : 'translate-x-0'"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 8. AI Studio/AI Agent API -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Globe :size="20" class="text-blue-600" /> AI Studio / AI Agent API
              </h4>

              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-sm font-bold text-slate-700">Enable AI Agent Support</span>
                  <span class="text-xs text-slate-500">Turn on/off OpenAI, Gemini or other AI model support on builder.</span>
                </div>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.ai_studio_enabled = !form.settings.ai_studio_enabled">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.ai_studio_enabled ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.ai_studio_enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>

              <div v-if="form.settings.ai_studio_enabled">
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Insert OpenAI/Gemini/Another AI Models API Key</label>
                <input v-model="form.settings.ai_api_key" type="password" placeholder="sk-..." class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
              </div>
            </div>

          </div>
        </div>

        <!-- NOTIFICATION SETTINGS TAB -->
        <div v-else-if="activeTab === 'notifications'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">Notification Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Configure when and how students, instructors, and admins receive notifications.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-8 max-w-4xl">
            <!-- 1. Student Notification -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Bell :size="20" class="text-blue-600" /> Student Notifications
              </h4>

              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="border-b border-slate-200">
                      <th class="py-3 text-xs font-bold text-slate-500 w-1/2">Event Trigger</th>
                      <th class="py-3 text-xs font-bold text-slate-500 text-center w-1/4">On-Site Notification</th>
                      <th class="py-3 text-xs font-bold text-slate-500 text-center w-1/4">Push Notification</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Course Enrolled</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">Notification when a student enrolls in a course.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.course_enrolled.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.course_enrolled.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Cancel Enrollment</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">Notification when a student's enrollment is cancelled.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.cancel_enrollment.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.cancel_enrollment.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Assignment Graded</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">When an instructor grades a submitted assignment of the student.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.assignment_graded.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.assignment_graded.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">New Announcement Posted</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">Notification for new announcements posted by the instructor.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.announcement_posted.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.announcement_posted.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Q&A Message Answered</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">When someone answers one of the student's Q&A.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.qa_answered.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.qa_answered.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Feedback Submitted for Quiz Attempt</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">Student receives feedback for a quiz attempt.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.quiz_feedback.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.quiz_feedback.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Removed From Course</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">An instructor/admin deletes a student from the enrollment list.</span>
                      </td>
                      <td class="py-3 text-center text-slate-300 font-bold">-</td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_student.removed_from_course.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 2. Instructor Notification -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Award :size="20" class="text-blue-600" /> Instructor Notifications
              </h4>

              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="border-b border-slate-200">
                      <th class="py-3 text-xs font-bold text-slate-500 w-1/2">Event Trigger</th>
                      <th class="py-3 text-xs font-bold text-slate-500 text-center w-1/4">On-Site Notification</th>
                      <th class="py-3 text-xs font-bold text-slate-500 text-center w-1/4">Push Notification</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Instructor Application Accepted</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">Submitted instructor registration application is accepted by the admin.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_instructor.application_accepted.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_instructor.application_accepted.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Instructor Application Rejected</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">Submitted instructor registration application is rejected by the admin.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_instructor.application_rejected.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_instructor.application_rejected.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 3. Admin Notification -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Shield :size="20" class="text-blue-600" /> Admin Notifications
              </h4>

              <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                  <thead>
                    <tr class="border-b border-slate-200">
                      <th class="py-3 text-xs font-bold text-slate-500 w-1/2">Event Trigger</th>
                      <th class="py-3 text-xs font-bold text-slate-500 text-center w-1/4">On-Site Notification</th>
                      <th class="py-3 text-xs font-bold text-slate-500 text-center w-1/4">Push Notification</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                      <td class="py-3 text-xs font-semibold text-slate-700">
                        <span class="block text-sm font-bold text-slate-700">Instructor Application Received</span>
                        <span class="text-[11px] text-slate-400 font-medium leading-relaxed">When you receive an application from someone wanting to register as an instructor.</span>
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_admin.application_received.onsite" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                      <td class="py-3 text-center">
                        <input type="checkbox" v-model="form.settings.notification_admin.application_received.push" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 4. Notification Event Test Center -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <div>
                <h4 class="text-lg font-bold text-[#1A2B49] flex items-center gap-2">
                  <Play :size="20" class="text-blue-600" /> Notification Simulator
                </h4>
                <p class="text-xs text-slate-400 mt-1">Select an event below to test trigger a notification to your account. The simulator respects your On-Site/Push choices above.</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Students Test -->
                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/30 flex flex-col gap-3">
                  <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Student Events</span>
                  <div class="flex flex-wrap gap-2">
                    <button 
                      v-for="event in [
                        { key: 'course_enrolled', label: 'Enroll' },
                        { key: 'cancel_enrollment', label: 'Cancel Enroll' },
                        { key: 'assignment_graded', label: 'Grade Assign' },
                        { key: 'announcement_posted', label: 'Announce' },
                        { key: 'qa_answered', label: 'Q&A Answer' },
                        { key: 'quiz_feedback', label: 'Quiz Feedback' },
                        { key: 'removed_from_course', label: 'Remove Course' }
                      ]"
                      :key="event.key"
                      @click="triggerTestNotification(event.key, 'student')"
                      class="px-3 py-1.5 bg-white border border-slate-200 hover:border-[#264790] hover:text-[#264790] rounded-lg text-xs font-bold transition-all"
                    >
                      {{ event.label }}
                    </button>
                  </div>
                </div>

                <!-- Instructor/Admin Test -->
                <div class="p-4 rounded-xl border border-slate-100 bg-slate-50/30 flex flex-col gap-3">
                  <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Instructor & Admin Events</span>
                  <div class="flex flex-wrap gap-2">
                    <button 
                      @click="triggerTestNotification('application_accepted', 'instructor')"
                      class="px-3 py-1.5 bg-white border border-slate-200 hover:border-[#264790] hover:text-[#264790] rounded-lg text-xs font-bold transition-all"
                    >
                      Accept Appl.
                    </button>
                    <button 
                      @click="triggerTestNotification('application_rejected', 'instructor')"
                      class="px-3 py-1.5 bg-white border border-slate-200 hover:border-[#264790] hover:text-[#264790] rounded-lg text-xs font-bold transition-all"
                    >
                      Reject Appl.
                    </button>
                    <button 
                      @click="triggerTestNotification('application_received', 'admin')"
                      class="px-3 py-1.5 bg-white border border-slate-200 hover:border-[#264790] hover:text-[#264790] rounded-lg text-xs font-bold transition-all"
                    >
                      Receive Appl.
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- AUTHENTICATION SETTINGS TAB -->
        <div v-else-if="activeTab === 'authentication'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">Authentication Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Manage two-factor authentication, fraud protection, and concurrent session limits.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-8 max-w-4xl">
            <!-- 1. Two-Factor Authentication -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                  <ShieldCheck :size="20" class="text-blue-600" /> Two-Factor Authentication (2FA)
                </h4>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.two_factor_auth_enabled = !form.settings.two_factor_auth_enabled">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.two_factor_auth_enabled ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.two_factor_auth_enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>

              <div v-if="form.settings.two_factor_auth_enabled" class="flex flex-col gap-5">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">2FA Delivery Method</label>
                  <select v-model="form.settings.two_factor_auth_method" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50">
                    <option value="email">Email Authentication Code</option>
                    <option value="sms">SMS OTP Authentication</option>
                    <option value="app">Google Authenticator (TOTP App)</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-3">Enforce 2FA Locations</label>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <label v-for="loc in [
                      { id: 'admin', label: 'Admin Login' },
                      { id: 'tutor', label: 'Tutor Login' },
                      { id: 'student', label: 'Student Login' },
                      { id: 'all', label: 'All Login Methods' }
                    ]" :key="loc.id" class="flex items-center gap-3 border border-slate-100 rounded-xl p-3.5 bg-slate-50/50 hover:bg-slate-50 cursor-pointer">
                      <input type="checkbox" :value="loc.id" v-model="form.settings.two_factor_auth_locations" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      <span class="text-xs font-bold text-slate-700">{{ loc.label }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2 & 3 & 4 & 5. Fraud Protection -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                  <ShieldAlert :size="20" class="text-blue-600" /> Fraud Protection & Bot Defense
                </h4>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.fraud_protection_enabled = !form.settings.fraud_protection_enabled">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.fraud_protection_enabled ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.fraud_protection_enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>

              <div v-if="form.settings.fraud_protection_enabled" class="flex flex-col gap-5">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Defense Method</label>
                  <select v-model="form.settings.fraud_protection_method" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50">
                    <option value="recaptcha_v3">Google reCAPTCHA v3 (Invisible)</option>
                    <option value="recaptcha_v2">Google reCAPTCHA v2 (I'm not a robot)</option>
                    <option value="honeypot">Honeypot Spam Trap (Native)</option>
                  </select>
                </div>

                <div v-if="form.settings.fraud_protection_method.includes('recaptcha')" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Google API Site Key</label>
                    <input v-model="form.settings.recaptcha_site_key" type="text" placeholder="6Ld..." class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                  </div>
                  <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Google API Secret Key</label>
                    <input v-model="form.settings.recaptcha_secret_key" type="password" placeholder="••••••••••••" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-3">Enforce Protection Locations</label>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <label v-for="loc in [
                      { id: 'tutor_login', label: 'Tutor Login Page' },
                      { id: 'tutor_register', label: 'Tutor Registration Page' },
                      { id: 'admin_login', label: 'Admin Login Page' },
                      { id: 'student_login', label: 'Student Login Page' },
                      { id: 'student_register', label: 'Student Registration Page' }
                    ]" :key="loc.id" class="flex items-center gap-3 border border-slate-100 rounded-xl p-3.5 bg-slate-50/50 hover:bg-slate-50 cursor-pointer">
                      <input type="checkbox" :value="loc.id" v-model="form.settings.fraud_protection_locations" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                      <span class="text-xs font-bold text-slate-700">{{ loc.label }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- 6. Manage Active Login Sessions -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h4 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                  <UserCheck :size="20" class="text-blue-600" /> Manage Active Login Sessions
                </h4>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.limit_login_sessions = !form.settings.limit_login_sessions">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.limit_login_sessions ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.limit_login_sessions ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>

              <div v-if="form.settings.limit_login_sessions">
                <label class="block text-sm font-bold text-slate-700 mb-1.5">Maximum Concurrent Active Sessions</label>
                <p class="text-xs text-slate-500 mb-2">Set the maximum number of active login sessions allowed per user.</p>
                <input v-model.number="form.settings.max_active_sessions" type="number" min="1" class="w-full md:w-1/3 border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
              </div>
            </div>

            <!-- 7. Email Verification -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-sm font-bold text-slate-700">Enforce Email Verification</span>
                  <span class="text-xs text-slate-500">Toggle to enable email verification for students and instructor signup.</span>
                </div>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.email_verification_enabled = !form.settings.email_verification_enabled">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.email_verification_enabled ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.email_verification_enabled ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- CERTIFICATE SETTINGS TAB -->
        <div v-else-if="activeTab === 'certificate'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">Certificate Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Configure authorized details, templates, and signature properties printed on course certificates.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-8 max-w-4xl">
            <!-- 1. Legacy Certificate Settings -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Award :size="20" class="text-blue-600" /> Legacy Certificate Settings
              </h4>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Authorised Name</label>
                  <p class="text-xs text-slate-500 mb-2">This name will be printed under the signature.</p>
                  <input v-model="form.settings.cert_authorised_name" type="text" placeholder="John Doe" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Authorised Company Name</label>
                  <p class="text-xs text-slate-500 mb-2">This company name will be printed under the authorised name.</p>
                  <input v-model="form.settings.cert_company_name" type="text" placeholder="Drastha Learning Inc." class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50" />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Certificate Page</label>
                  <p class="text-xs text-slate-500 mb-2">Choose which page is used to display the certificate template.</p>
                  <select v-model="form.settings.cert_page" class="w-full border-2 border-slate-200 rounded-xl px-4 py-2.5 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50">
                    <option value="certificate">Default Certificate Template Page</option>
                    <option value="custom-certificate">Custom Styled Template Page</option>
                    <option value="premium-cert">Premium Gold Template Page</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Upload Authorised Signature</label>
                  <p class="text-xs text-slate-500 mb-2">Accepts transparent PNG/JPG images (Recommended: 300x100px).</p>
                  <div class="flex items-center gap-4">
                    <div class="w-36 h-20 border border-slate-100 rounded-2xl overflow-hidden bg-slate-50 flex items-center justify-center p-2 relative group shrink-0">
                      <img :src="form.settings.cert_signature" class="max-w-full max-h-full object-contain" />
                      <button v-if="form.settings.cert_signature !== '/images/signature-placeholder.png'" @click="removeSignature" class="absolute inset-0 bg-black/55 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white font-extrabold text-[10px] uppercase tracking-wider transition-opacity duration-200 rounded-2xl">
                        Remove
                      </button>
                    </div>
                    <input type="file" ref="signatureInput" @change="handleSignatureChange" accept="image/*" class="hidden" />
                    <button @click="triggerSignatureUpload" type="button" class="border-2 border-slate-200 hover:border-blue-500 text-slate-600 hover:text-blue-600 px-5 py-3 rounded-2xl text-xs font-black tracking-wider uppercase transition-all flex items-center gap-2 bg-white shadow-sm">
                      <UploadCloud :size="16" /> Upload Signature
                    </button>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between border-t border-slate-50 pt-5">
                <div>
                  <span class="block text-sm font-bold text-slate-700">Show Instructor Name on Certificate</span>
                  <span class="text-xs text-slate-500">Show instructor name on certificate before the Authorised Name.</span>
                </div>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.cert_show_instructor = !form.settings.cert_show_instructor">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.cert_show_instructor ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.cert_show_instructor ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>

              <div class="flex items-center justify-between border-t border-slate-50 pt-5">
                <div>
                  <span class="block text-sm font-bold text-slate-700">Certificate link in Course Completion Email</span>
                  <span class="text-xs text-slate-500">Send certificate link along with the course completion email. Student must be logged in to access the certificate if public view is not enabled.</span>
                </div>
                <div class="relative inline-block w-12 h-7 cursor-pointer shrink-0" @click="form.settings.cert_email_link = !form.settings.cert_email_link">
                  <div class="w-12 h-7 rounded-full transition-colors duration-300" :class="form.settings.cert_email_link ? 'bg-blue-600' : 'bg-slate-300'"></div>
                  <div class="absolute top-1 left-1 bg-white w-5 h-5 rounded-full transition-transform duration-300 shadow-sm" :class="form.settings.cert_email_link ? 'translate-x-5' : 'translate-x-0'"></div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- LICENSE SETTINGS TAB -->
        <div v-else-if="activeTab === 'license'" class="flex flex-col gap-8">
          <div class="flex items-center justify-between pb-5 border-b border-slate-100">
            <div>
              <h3 class="text-xl font-extrabold text-slate-800">License Settings</h3>
              <p class="text-sm text-slate-500 mt-1.5">Manage and check your Drastha Learning LMS copy license verification status.</p>
            </div>
            <button @click="saveSettings" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-full text-sm font-bold shadow-sm transition-all flex items-center gap-2">
              <Save :size="16" /> Save Changes
            </button>
          </div>

          <div class="flex flex-col gap-8 max-w-4xl">
            <!-- 1. License Key Details -->
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col gap-6">
              <h4 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <Key :size="20" class="text-blue-600" /> Drastha Learning License Verification
              </h4>

              <div class="flex flex-col gap-5">
                <div>
                  <label class="block text-sm font-bold text-slate-700 mb-1.5">Your License Key</label>
                  <input v-model="form.settings.license_key" type="text" placeholder="DRSTHA-XXXX-XXXX-XXXX" class="w-full border-2 border-slate-200 rounded-xl px-4 py-3 outline-none focus:border-blue-500 text-sm font-bold bg-slate-50 uppercase tracking-widest text-center" />
                  <p class="text-xs text-slate-500 mt-1.5 text-center">Enter the license key obtained from your Drastha client portal to activate all premium features, updates, and addons.</p>
                </div>

                <!-- Status Panel -->
                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 flex flex-col gap-3">
                  <h5 class="text-xs font-black text-slate-500 uppercase tracking-wider">License Status Info</h5>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                    <div>
                      <span class="block text-[10px] text-slate-400 font-bold uppercase">Activation Status</span>
                      <span class="inline-flex items-center gap-1.5 mt-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider" :class="form.settings.license_key ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100'">
                        <span class="w-1.5 h-1.5 rounded-full" :class="form.settings.license_key ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        {{ form.settings.license_key ? 'Activated' : 'Not Activated' }}
                      </span>
                    </div>
                    <div>
                      <span class="block text-[10px] text-slate-400 font-bold uppercase">Domain Locked</span>
                      <span class="text-xs font-extrabold text-slate-700 mt-2 block">{{ currentDomain }}</span>
                    </div>
                    <div>
                      <span class="block text-[10px] text-slate-400 font-bold uppercase">Update Connection</span>
                      <span class="text-xs font-extrabold text-slate-700 mt-2 block">Connected (200 OK)</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-32 text-center">
          <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
            <component :is="tabs.find(t => t.id === activeTab)?.icon" :size="48" class="text-slate-300" />
          </div>
          <h3 class="text-2xl font-extrabold text-slate-800">Settings coming soon</h3>
          <p class="text-slate-500 mt-3 max-w-sm font-medium">The {{ tabs.find(t => t.id === activeTab)?.label }} settings module is currently under development.</p>
        </div>

      </div>

    </div>

    <!-- Beautiful Center Alert Modal (SweetAlert-style) -->
    <Transition name="fade-scale">
      <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-md p-4" @click.self="showSuccessModal = false">
        <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full shadow-2xl border border-slate-100 flex flex-col items-center text-center transform transition-all duration-300 scale-100 relative overflow-hidden">
          <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-50 rounded-full blur-2xl opacity-60"></div>
          <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-blue-50 rounded-full blur-2xl opacity-60"></div>
          
          <div class="w-20 h-20 bg-emerald-50 rounded-full flex items-center justify-center mb-6 border-4 border-emerald-100 shadow-sm">
            <svg class="w-10 h-10 text-emerald-500 animate-pulse" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          
          <h3 class="text-2xl font-black text-slate-800 tracking-tight">Saved Successfully!</h3>
          <p class="text-slate-500 mt-2.5 text-sm font-semibold leading-relaxed px-2">Your system configurations have been updated and synced with the database.</p>
          
          <button @click="showSuccessModal = false" class="mt-8 w-full bg-slate-900 hover:bg-slate-800 active:bg-black text-white font-extrabold py-3.5 px-6 rounded-2xl shadow-xl transition-all text-xs tracking-wider uppercase">
            Great, Thanks!
          </button>
        </div>
      </div>
    </Transition>
  </DashboardWrapper>
</template>

<style scoped>
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.92);
}
</style>
