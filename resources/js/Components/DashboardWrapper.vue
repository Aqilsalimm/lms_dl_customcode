<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
  LayoutDashboard, User, BookOpen, Star, HelpCircle, 
  Heart, ShoppingCart, MessageCircle, Megaphone, 
  Wallet, MonitorPlay, Video, Settings, LogOut,
  Bell, Plus, ShieldCheck, Check, Trash2, Activity, ChevronDown, ChevronUp, Menu, X
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Swal from 'sweetalert2';

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role || 'student');
const notifications = computed(() => page.props.auth.notifications || []);
const showNotifications = ref(false);
const isSidebarOpen = ref(false);

watch(() => page.url, () => {
  isSidebarOpen.value = false;
});

const handleLogout = () => {
  Swal.fire({
    title: page.props.translations?.confirm_logout || 'Konfirmasi Keluar',
    text: page.props.translations?.logout_confirm_text || 'Apakah Anda yakin ingin keluar dari akun Anda?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: page.props.translations?.yes_logout || 'Ya, Keluar',
    cancelButtonText: page.props.translations?.cancel || 'Batal',
    buttonsStyling: false,
    customClass: {
      popup: 'rounded-[2rem] p-8 border border-slate-100 shadow-[0_20px_50px_rgba(0,0,0,0.15)] bg-white text-slate-800 font-sans select-none',
      title: 'text-xl font-extrabold text-[#1A2B49] mb-2',
      htmlContainer: 'text-sm font-semibold text-slate-500 leading-relaxed my-4',
      confirmButton: 'bg-[#EF4444] hover:bg-red-700 text-white font-black px-8 py-3 rounded-full text-xs shadow-md transition-all outline-none focus:ring-4 focus:ring-red-200 active:scale-95 cursor-pointer mr-3',
      cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-black px-8 py-3 rounded-full text-xs shadow-sm transition-all outline-none focus:ring-4 focus:ring-slate-100 active:scale-95 cursor-pointer'
    }
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(route('logout'));
    }
  });
};

const markAsRead = (id) => {
  router.post(route('notifications.read', id), {}, {
    preserveScroll: true
  });
};

const markAllAsRead = () => {
  router.post(route('notifications.read-all'), {}, {
    preserveScroll: true
  });
};

const sidebarMenu = computed(() => [
  { label: usePage().props.translations?.dashboard || 'Dashboard', icon: LayoutDashboard, route: 'dashboard' },
  { label: usePage().props.translations?.profile || 'My Profile', icon: User, route: 'profile.edit' },
  { label: usePage().props.translations?.enrolled_courses || 'Enrolled Courses', icon: BookOpen, route: 'dashboard.enrolled-courses' },
  { label: usePage().props.translations?.learning_progress || 'Learning Progress', icon: Activity, route: 'dashboard.learning-progress' },
  { label: usePage().props.translations?.reviews || 'Reviews', icon: Star, route: 'dashboard.reviews' },
  { label: usePage().props.translations?.quiz_attempts || 'My Quiz Attempts', icon: HelpCircle, route: 'dashboard.quiz-attempts' },
  { label: usePage().props.translations?.wishlist || 'Wishlist', icon: Heart, route: 'dashboard.wishlist' },
  { label: usePage().props.translations?.order_history || 'Order History', icon: ShoppingCart, route: 'dashboard.order-history' },
  { label: usePage().props.translations?.qa || 'Question & Answer', icon: MessageCircle, route: 'dashboard.qa' },
]);

const instructorMenu = computed(() => [
  { label: usePage().props.translations?.my_courses || 'My Courses', icon: MonitorPlay, route: 'course-builder.index' },
  { label: usePage().props.translations?.my_bundles || 'My Bundles', icon: BookOpen, route: 'bundle-builder.index' },
  { label: usePage().props.translations?.announcements || 'Announcements', icon: Megaphone, route: 'dashboard.announcements' },
  { label: usePage().props.translations?.withdrawals || 'Withdrawals', icon: Wallet, route: 'dashboard.withdrawals' },
  { label: usePage().props.translations?.quiz_attempts || 'Quiz Attempts', icon: HelpCircle, route: 'dashboard.quiz-attempts' },
  { label: usePage().props.translations?.google_meet || 'Google Meet', icon: Video, route: 'dashboard.google-meet' },
  { label: usePage().props.translations?.zoom || 'Zoom', icon: Video, route: 'dashboard.zoom' },
]);

const hasBlogAccess = computed(() => {
  if (role.value === 'admin') return true;
  if (role.value === 'instructor') {
    const allowedJson = page.props.settings?.allowed_blog_instructors;
    let allowed = [];
    if (allowedJson) {
      if (typeof allowedJson === 'string') {
        try {
          allowed = JSON.parse(allowedJson);
        } catch (e) {
          allowed = [];
        }
      } else if (Array.isArray(allowedJson)) {
        allowed = allowedJson;
      }
    }
    return allowed.some(id => String(id) === String(user.value?.id));
  }
  return false;
});

const filteredInstructorMenu = computed(() => {
  return instructorMenu.value;
});

const getInitials = (name) => {
  if (!name) return 'A';
  return name.charAt(0).toUpperCase();
};

const getInitialAccordion = () => {
  const current = route().current();
  if (current === 'dashboard.settings') return 'lms';
  if (current === 'dashboard.settings.course-builder') return 'course';
  if (current?.startsWith('dashboard.ecommerce')) return 'ecommerce';
  if (current === 'dashboard.settings.blog') return 'blog';
  return '';
};
const activeAdminAccordion = ref(getInitialAccordion());
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- TOP BANNER (User Profile Header) -->
    <div class="bg-white rounded-[2rem] p-6 md:p-8 flex flex-col md:flex-row items-center justify-between shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 mb-8 gap-6">
      
      <!-- User Info & Mobile Menu Button -->
      <div class="flex items-center justify-between w-full md:w-auto gap-5">
        <div class="flex items-center gap-5">
          <div class="w-20 h-20 rounded-full bg-[#264790] text-white flex items-center justify-center text-3xl font-extrabold shadow-md shrink-0">
            {{ getInitials(user.name) }}
          </div>
          <div>
            <h2 class="text-2xl font-extrabold text-[#1A2B49] flex items-center gap-2">
              {{ user.name }}
              <ShieldCheck v-if="role === 'admin'" :size="20" class="text-[#44A6D9]" />
            </h2>
            <!-- Golden Stars -->
            <div class="flex items-center gap-1 mt-1 text-[#F9CC6B]">
              <Star v-for="i in 5" :key="i" :size="16" fill="currentColor" />
            </div>
          </div>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <button 
          @click="isSidebarOpen = true"
          class="lg:hidden p-2 rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200 flex-shrink-0"
          aria-label="Buka menu navigasi"
        >
          <Menu :size="24" />
        </button>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-3 md:gap-4 w-full md:w-auto justify-center md:justify-end">
        <!-- Notifications Bell & Dropdown -->
        <div class="relative">
          <button 
            @click="showNotifications = !showNotifications" 
            class="relative w-11 h-11 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#264790] hover:bg-slate-100 transition-colors shadow-sm"
            aria-label="Tampilkan notifikasi"
          >
            <Bell :size="20" />
            <span v-if="notifications.length > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-[10px] font-extrabold flex items-center justify-center border border-white">
              {{ notifications.length }}
            </span>
          </button>

          <!-- Dropdown Card -->
          <div 
            v-if="showNotifications" 
            class="absolute right-0 mt-3 w-80 bg-white rounded-2xl border border-slate-100 shadow-xl py-3 z-50 flex flex-col gap-2 transition-all duration-200"
          >
            <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
              <span class="text-xs font-bold text-slate-800">{{ $t('notifications') || 'Notifications' }}</span>
              <button 
                v-if="notifications.length > 0" 
                @click="markAllAsRead" 
                class="text-[10px] font-extrabold text-[#264790] hover:underline flex items-center gap-1"
              >
                <Check :size="12" /> {{ $t('mark_all_read') || 'Mark all read' }}
              </button>
            </div>

            <!-- Notifications List -->
            <div class="max-h-64 overflow-y-auto px-2 flex flex-col gap-1">
              <div v-if="notifications.length === 0" class="py-8 text-center text-xs text-slate-400 font-bold">
                {{ $t('no_new_notifications') || 'No new notifications' }}
              </div>
              <div 
                v-for="notification in notifications" 
                :key="notification.id"
                class="p-2.5 hover:bg-slate-50 rounded-xl transition-all border border-transparent hover:border-slate-100/50 flex flex-col gap-1 relative group cursor-pointer"
                @click="markAsRead(notification.id)"
              >
                <div class="flex items-start justify-between gap-2">
                  <span class="text-xs font-extrabold text-slate-700 leading-normal">{{ notification.data.title }}</span>
                  <button 
                    @click.stop="markAsRead(notification.id)" 
                    class="text-slate-300 hover:text-slate-500 rounded p-0.5 opacity-0 group-hover:opacity-100 transition-opacity"
                    :title="$t('mark_read') || 'Mark read'"
                  >
                    <Check :size="12" />
                  </button>
                </div>
                <span class="text-[10px] text-slate-500 leading-relaxed">{{ notification.data.message }}</span>
              </div>
            </div>
          </div>
        </div>
        <Link 
          v-if="role === 'instructor' || role === 'admin'"
          :href="route('bundle-builder.index')"
          class="bg-white border-2 border-slate-200 text-[#1A2B49] hover:border-[#264790] hover:text-[#264790] px-4 py-2 sm:px-5 sm:py-2.5 rounded-full font-bold text-xs sm:text-sm transition-all duration-300 flex items-center gap-2 whitespace-nowrap shadow-sm"
        >
          <Plus :size="16" /> {{ $t('new_bundle') || 'New Bundle' }}
        </Link>
        <Link 
          v-if="role === 'instructor' || role === 'admin'"
          :href="route('course-builder.index')"
          class="bg-[#F4F7F9] text-[#264790] hover:bg-[#44A6D9] hover:text-white border border-transparent px-4 py-2 sm:px-5 sm:py-2.5 rounded-full font-bold text-xs sm:text-sm transition-all duration-300 flex items-center gap-2 whitespace-nowrap shadow-sm"
        >
          <Plus :size="16" /> {{ $t('new_course') || 'New Course' }}
        </Link>
      </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="flex flex-col lg:flex-row gap-8">
      
      <!-- Mobile Overlay -->
      <div 
        v-if="isSidebarOpen" 
        @click="isSidebarOpen = false"
        class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden backdrop-blur-sm"
      ></div>

      <!-- SIDEBAR -->
      <aside 
        :class="[
          'fixed lg:static inset-y-0 left-0 z-50 lg:z-10 w-[280px] bg-slate-50 lg:bg-transparent overflow-y-auto lg:overflow-visible transition-transform duration-300 ease-in-out lg:translate-x-0 p-6 lg:p-0 shadow-2xl lg:shadow-none border-r border-slate-200 lg:border-none',
          isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
      >
        <div class="flex items-center justify-between lg:hidden mb-6 pb-6 border-b border-slate-200">
            <span class="font-extrabold text-lg text-[#1A2B49]">{{ $t('navigation_menu') || 'Menu Navigasi' }}</span>
            <button @click="isSidebarOpen = false" class="p-2 text-slate-400 hover:text-red-500 bg-white rounded-full shadow-sm" aria-label="Tutup menu navigasi">
                <X :size="20" />
            </button>
        </div>
        
        <div class="bg-transparent flex flex-col gap-1">
          <!-- General Menu -->
          <Link 
            v-for="item in sidebarMenu" 
            :key="item.label"
            :href="route(item.route)"
            :class="[
              route().current(item.route)
                ? 'bg-[#264790] text-white shadow-md' 
                : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
            ]"
            class="flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300"
          >
            <component :is="item.icon" :size="18" :stroke-width="route().current(item.route) ? 2.5 : 2" />
            {{ item.label }}
          </Link>

          <!-- Instructor Section Header -->
          <div v-if="role === 'instructor' || role === 'admin'" class="text-slate-400 font-bold text-xs uppercase tracking-wider pl-5 mt-6 mb-2">
            Instructor
          </div>

          <!-- Instructor Menu -->
          <template v-if="role === 'instructor' || role === 'admin'">
            <template v-for="item in filteredInstructorMenu" :key="item.label">
              <Link 
                v-if="item.method !== 'post'"
                :href="route(item.route)"
                :class="[
                  route().current(item.route)
                    ? 'bg-slate-100 text-[#264790] shadow-sm'
                    : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
                ]"
                class="flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300"
              >
                <component :is="item.icon" :size="18" :stroke-width="route().current(item.route) ? 2.5 : 2" />
                {{ item.label }}
              </Link>
              
              <Link 
                v-else
                :href="route(item.route)"
                method="post"
                as="button"
                class="w-full flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 text-slate-500 hover:bg-[#1A2B49] hover:text-white shadow-sm mt-2"
              >
                <component :is="item.icon" :size="18" :stroke-width="2" />
                {{ item.label }}
              </Link>
            </template>

            <!-- Blog Settings Accordion for Instructor -->
            <div v-if="role === 'instructor' && hasBlogAccess" class="flex flex-col mb-1 mt-2">
              <button 
                @click="activeAdminAccordion = activeAdminAccordion === 'blog' ? '' : 'blog'"
                :class="[
                  activeAdminAccordion === 'blog' || route().current('dashboard.settings.blog')
                    ? 'bg-slate-100 text-[#264790] shadow-sm'
                    : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
                ]"
                class="flex items-center justify-between px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 w-full text-left"
              >
                <div class="flex items-center gap-3 text-left">
                  <Settings :size="18" :stroke-width="route().current('dashboard.settings.blog') ? 2.5 : 2" />
                  <span class="leading-tight">{{ $t('blog_settings') || 'Blog Settings' }}</span>
                </div>
                <ChevronDown v-if="activeAdminAccordion !== 'blog'" :size="16" />
                <ChevronUp v-else :size="16" />
              </button>
              
              <div v-show="activeAdminAccordion === 'blog'" class="flex flex-col ml-11 mt-1 gap-1 border-l-2 border-slate-100 pl-3">
                <Link :href="route('dashboard.settings.blog') + '?tab=articles'" :class="page.url.includes('tab=articles') || page.url === '/dashboard/settings/blog' ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('manage_articles') || 'Kelola Artikel' }}</Link>
                <Link :href="route('dashboard.settings.blog') + '?tab=categories_tags'" :class="page.url.includes('tab=categories_tags') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('categories_tags') || 'Kategori & Tag' }}</Link>
                <Link :href="route('dashboard.settings.blog') + '?tab=layout_authors'" :class="page.url.includes('tab=layout_authors') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('layout_editorial') || 'Layout & Redaksi' }}</Link>
              </div>
            </div>
          </template>

          <!-- Admin Section Header -->
          <div v-if="role === 'admin'" class="text-slate-400 font-bold text-xs uppercase tracking-wider pl-5 mt-6 mb-2">
            Administrator
          </div>

          <!-- Admin Menu -->
          <template v-if="role === 'admin'">
            <!-- LMS Settings Dropdown -->
            <div class="flex flex-col mb-1">
              <button 
                @click="activeAdminAccordion = activeAdminAccordion === 'lms' ? '' : 'lms'"
                :class="[
                  activeAdminAccordion === 'lms' || route().current('dashboard.settings')
                    ? 'bg-slate-100 text-[#264790] shadow-sm'
                    : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
                ]"
                class="flex items-center justify-between px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 w-full text-left"
              >
                <div class="flex items-center gap-3 text-left">
                  <Settings :size="18" :stroke-width="route().current('dashboard.settings') ? 2.5 : 2" />
                  <span class="leading-tight">{{ $t('lms_settings') || 'LMS Settings' }}</span>
                </div>
                <ChevronDown v-if="activeAdminAccordion !== 'lms'" :size="16" />
                <ChevronUp v-else :size="16" />
              </button>
              
              <div v-show="activeAdminAccordion === 'lms'" class="flex flex-col ml-11 mt-1 gap-1 border-l-2 border-slate-100 pl-3">
                <Link :href="route('dashboard.settings') + '?tab=general'" :class="page.url.includes('tab=general') || (page.url === '/dashboard/settings' && !page.url.includes('tab=')) ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">General</Link>
                <Link :href="route('dashboard.users.manage')" :class="route().current('dashboard.users.*') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">User Manage</Link>
                <Link :href="route('dashboard.settings') + '?tab=course'" :class="page.url.includes('tab=course') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">Course</Link>
                <Link :href="route('dashboard.settings') + '?tab=monetization'" :class="page.url.includes('tab=monetization') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">Monetization</Link>
                <Link :href="route('dashboard.settings') + '?tab=design'" :class="page.url.includes('tab=design') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">Design</Link>
                <Link :href="route('dashboard.settings') + '?tab=advanced'" :class="page.url.includes('tab=advanced') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">Advanced</Link>
              </div>
            </div>

            <!-- Course Builder Settings Dropdown -->
            <div class="flex flex-col mb-1">
              <button 
                @click="activeAdminAccordion = activeAdminAccordion === 'course' ? '' : 'course'"
                :class="[
                  activeAdminAccordion === 'course' || route().current('dashboard.settings.course-builder')
                    ? 'bg-slate-100 text-[#264790] shadow-sm'
                    : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
                ]"
                class="flex items-center justify-between px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 w-full text-left"
              >
                <div class="flex items-center gap-3 text-left">
                  <Settings :size="18" :stroke-width="route().current('dashboard.settings.course-builder') ? 2.5 : 2" />
                  <span class="leading-tight">{{ $t('course_builder_settings') || 'Course Builder Settings' }}</span>
                </div>
                <ChevronDown v-if="activeAdminAccordion !== 'course'" :size="16" />
                <ChevronUp v-else :size="16" />
              </button>
              
              <div v-show="activeAdminAccordion === 'course'" class="flex flex-col ml-11 mt-1 gap-1 border-l-2 border-slate-100 pl-3">
                <Link :href="route('dashboard.settings.course-builder') + '?tab=categories'" :class="page.url.includes('tab=categories') || page.url === '/dashboard/settings/course-builder' ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('course_categories') || 'Kategori Kursus' }}</Link>
                <Link :href="route('dashboard.settings.course-builder') + '?tab=tags'" :class="page.url.includes('tab=tags') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('course_tags') || 'Tag Kursus' }}</Link>
                <Link :href="route('dashboard.settings.course-builder') + '?tab=course-importer'" :class="page.url.includes('tab=course-importer') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">Course Importer</Link>
                <Link :href="route('dashboard.settings.course-builder') + '?tab=quiz-importer'" :class="page.url.includes('tab=quiz-importer') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">Quiz Importer</Link>
              </div>
            </div>

            <!-- e-Commerce Dropdown -->
            <div class="flex flex-col mb-1">
              <button 
                @click="activeAdminAccordion = activeAdminAccordion === 'ecommerce' ? '' : 'ecommerce'"
                :class="[
                  activeAdminAccordion === 'ecommerce' || route().current()?.startsWith('dashboard.ecommerce')
                    ? 'bg-slate-100 text-[#264790] shadow-sm'
                    : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
                ]"
                class="flex items-center justify-between px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 w-full text-left"
              >
                <div class="flex items-center gap-3 text-left">
                  <ShoppingCart :size="18" :stroke-width="route().current()?.startsWith('dashboard.ecommerce') ? 2.5 : 2" />
                  <span class="leading-tight">e-Commerce</span>
                </div>
                <ChevronDown v-if="activeAdminAccordion !== 'ecommerce'" :size="16" />
                <ChevronUp v-else :size="16" />
              </button>
              
              <div v-show="activeAdminAccordion === 'ecommerce'" class="flex flex-col ml-11 mt-1 gap-1 border-l-2 border-slate-100 pl-3">
                <Link :href="route('dashboard.ecommerce.analytics')" :class="route().current('dashboard.ecommerce.analytics') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('analytics_dashboard') || 'Analytics Dashboard' }}</Link>
                <Link :href="route('dashboard.ecommerce.coupons')" :class="route().current('dashboard.ecommerce.coupons') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('coupon_discount') || 'Coupon & Discount' }}</Link>
                <Link :href="route('dashboard.ecommerce.settings')" :class="route().current('dashboard.ecommerce.settings') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('abandoned_cart') || 'Abandoned Cart' }}</Link>
              </div>
            </div>

            <!-- Blog Settings Dropdown -->
            <div class="flex flex-col mb-1">
              <button 
                @click="activeAdminAccordion = activeAdminAccordion === 'blog' ? '' : 'blog'"
                :class="[
                  activeAdminAccordion === 'blog' || route().current('dashboard.settings.blog')
                    ? 'bg-slate-100 text-[#264790] shadow-sm'
                    : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
                ]"
                class="flex items-center justify-between px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 w-full text-left"
              >
                <div class="flex items-center gap-3 text-left">
                  <Settings :size="18" :stroke-width="route().current('dashboard.settings.blog') ? 2.5 : 2" />
                  <span class="leading-tight">{{ $t('blog_settings') || 'Blog Settings' }}</span>
                </div>
                <ChevronDown v-if="activeAdminAccordion !== 'blog'" :size="16" />
                <ChevronUp v-else :size="16" />
              </button>
              
              <div v-show="activeAdminAccordion === 'blog'" class="flex flex-col ml-11 mt-1 gap-1 border-l-2 border-slate-100 pl-3">
                <Link :href="route('dashboard.settings.blog') + '?tab=articles'" :class="page.url.includes('tab=articles') || page.url === '/dashboard/settings/blog' ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('manage_articles') || 'Kelola Artikel' }}</Link>
                <Link :href="route('dashboard.settings.blog') + '?tab=categories_tags'" :class="page.url.includes('tab=categories_tags') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('categories_tags') || 'Kategori & Tag' }}</Link>
                <Link :href="route('dashboard.settings.blog') + '?tab=layout_authors'" :class="page.url.includes('tab=layout_authors') ? 'text-[#264790] font-bold' : 'text-slate-500 hover:text-[#264790]'" class="text-xs font-semibold py-2 transition-colors">{{ $t('layout_editorial') || 'Layout & Redaksi' }}</Link>
              </div>
            </div>
          </template>

          <div class="mt-6 pt-4 border-t border-slate-200 flex flex-col gap-1">
            <Link 
              :href="route('profile.edit')"
              :class="[
                route().current('profile.edit')
                  ? 'bg-slate-100 text-[#264790] shadow-sm'
                  : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
              ]"
              class="flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300"
            >
              <Settings :size="18" :stroke-width="route().current('profile.edit') ? 2.5 : 2" />
              {{ usePage().props.translations?.settings || 'Settings' }}
            </Link>
            
            <button 
              @click="handleLogout"
              class="w-full flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 text-slate-500 hover:bg-[#1A2B49] hover:text-white shadow-sm mt-1 text-left"
            >
              <LogOut :size="18" :stroke-width="2" />
              {{ usePage().props.translations?.logout || 'Logout' }}
            </button>
          </div>
        </div>
      </aside>

      <!-- CONTENT AREA -->
      <main class="flex-1">
        <slot />
      </main>

    </div>

  </div>
</template>
