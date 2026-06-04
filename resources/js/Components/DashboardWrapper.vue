<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { 
  LayoutDashboard, User, BookOpen, Star, HelpCircle, 
  Heart, ShoppingCart, MessageCircle, Megaphone, 
  Wallet, MonitorPlay, Video, Settings, LogOut,
  Bell, Plus, ShieldCheck
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role || 'student');

const sidebarMenu = [
  { label: 'Dashboard', icon: LayoutDashboard, route: 'dashboard' },
  { label: 'My Profile', icon: User, route: 'profile.edit' },
  { label: 'Enrolled Courses', icon: BookOpen, route: 'dashboard.enrolled-courses' },
  { label: 'Reviews', icon: Star, route: 'dashboard.reviews' },
  { label: 'My Quiz Attempts', icon: HelpCircle, route: 'dashboard.quiz-attempts' },
  { label: 'Wishlist', icon: Heart, route: 'dashboard.wishlist' },
  { label: 'Order History', icon: ShoppingCart, route: 'dashboard.order-history' },
  { label: 'Question & Answer', icon: MessageCircle, route: 'dashboard.qa' },
];

const instructorMenu = [
  { label: 'My Courses', icon: MonitorPlay, route: 'course-builder.index' },
  { label: 'Announcements', icon: Megaphone, route: 'dashboard.announcements' },
  { label: 'Withdrawals', icon: Wallet, route: 'dashboard.withdrawals' },
  { label: 'Quiz Attempts', icon: HelpCircle, route: 'dashboard.quiz-attempts' },
  { label: 'Google Meet', icon: Video, route: 'dashboard.google-meet' },
  { label: 'Zoom', icon: Video, route: 'dashboard.zoom' },
];

const getInitials = (name) => {
  if (!name) return 'A';
  return name.charAt(0).toUpperCase();
};
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- TOP BANNER (User Profile Header) -->
    <div class="bg-white rounded-[2rem] p-6 md:p-8 flex flex-col md:flex-row items-center justify-between shadow-[0_8px_30px_rgb(0,0,0,0.03)] border border-slate-100 mb-8 gap-6">
      
      <!-- User Info -->
      <div class="flex items-center gap-5">
        <div class="w-20 h-20 rounded-full bg-[#264790] text-white flex items-center justify-center text-3xl font-extrabold shadow-md">
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

      <!-- Action Buttons -->
      <div class="flex items-center gap-4 w-full md:w-auto justify-end">
        <button class="w-11 h-11 rounded-full bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-[#264790] hover:bg-slate-100 transition-colors shadow-sm">
          <Bell :size="20" />
        </button>
        <button 
          v-if="role === 'instructor' || role === 'admin'"
          class="bg-white border-2 border-slate-200 text-[#1A2B49] hover:border-[#264790] hover:text-[#264790] px-5 py-2.5 rounded-full font-bold text-sm transition-all duration-300 flex items-center gap-2 whitespace-nowrap shadow-sm"
        >
          <Plus :size="16" /> New Bundle
        </button>
        <Link 
          v-if="role === 'instructor' || role === 'admin'"
          :href="route('course-builder.index')"
          class="bg-[#F4F7F9] text-[#264790] hover:bg-[#44A6D9] hover:text-white border border-transparent px-5 py-2.5 rounded-full font-bold text-sm transition-all duration-300 flex items-center gap-2 whitespace-nowrap shadow-sm"
        >
          <Plus :size="16" /> New Course
        </Link>
      </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="flex flex-col lg:flex-row gap-8">
      
      <!-- SIDEBAR -->
      <aside class="w-full lg:w-[280px] shrink-0">
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
            <template v-for="item in instructorMenu" :key="item.label">
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
          </template>

          <!-- Admin Section Header -->
          <div v-if="role === 'admin'" class="text-slate-400 font-bold text-xs uppercase tracking-wider pl-5 mt-6 mb-2">
            Administrator
          </div>

          <!-- Admin Menu -->
          <template v-if="role === 'admin'">
            <Link 
              :href="route('dashboard.settings')"
              :class="[
                route().current('dashboard.settings')
                  ? 'bg-slate-100 text-[#264790] shadow-sm'
                  : 'text-slate-500 hover:bg-white hover:text-[#264790] hover:shadow-sm'
              ]"
              class="flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300"
            >
              <Settings :size="18" :stroke-width="route().current('dashboard.settings') ? 2.5 : 2" />
              LMS Settings
            </Link>
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
              Settings
            </Link>
            
            <Link 
              :href="route('logout')"
              method="post"
              as="button"
              class="w-full flex items-center gap-3 px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-300 text-slate-500 hover:bg-[#1A2B49] hover:text-white shadow-sm mt-1"
            >
              <LogOut :size="18" :stroke-width="2" />
              Logout
            </Link>
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
