<script setup>
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
  Globe, BookOpen, DollarSign, Palette, Sliders, 
  Bell, Shield, Award, Key, Save 
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

const activeTab = ref('general');

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
  }
});

const saveSettings = () => {
  form.post(route('dashboard.settings.update'), {
    preserveScroll: true,
    onSuccess: () => {
      alert("Settings saved successfully!");
    }
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

        <div v-else class="flex flex-col items-center justify-center py-32 text-center">
          <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
            <component :is="tabs.find(t => t.id === activeTab)?.icon" :size="48" class="text-slate-300" />
          </div>
          <h3 class="text-2xl font-extrabold text-slate-800">Settings coming soon</h3>
          <p class="text-slate-500 mt-3 max-w-sm font-medium">The {{ tabs.find(t => t.id === activeTab)?.label }} settings module is currently under development.</p>
        </div>

      </div>

    </div>
  </DashboardWrapper>
</template>
