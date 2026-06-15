<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    MessageSquare, 
    ChevronRight, 
    CheckCircle2, 
    Circle, 
    Clock, 
    ArrowRight,
    Search,
    User as UserIcon
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    courses: Array
});

const searchQuery = ref('');

const filteredCourses = computed(() => {
    if (!searchQuery.value) return props.courses;
    const query = searchQuery.value.toLowerCase();
    return props.courses.filter(course => 
        course.title.toLowerCase().includes(query) || 
        course.discussions.some(d => d.body.toLowerCase().includes(query))
    );
});

const getStatusBadge = (isResolved) => {
    return isResolved 
        ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
        : 'bg-amber-50 text-amber-600 border-amber-100';
};

</script>

<template>
    <Head title="My Questions & Answers" />

    <GuestLayout>
        <DashboardWrapper>
            <div class="mb-8">
                <div class="flex flex-col gap-1 mb-6">
                    <h2 class="text-3xl font-extrabold text-[#1A2B49]">Questions & Answers</h2>
                    <p class="text-slate-500 text-sm font-medium">Pantau diskusi dan pertanyaan yang telah kamu ajukan di setiap kelas</p>
                </div>

                <!-- Search Bar -->
                <div class="relative max-w-md">
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Cari kursus atau pertanyaan..." 
                        class="w-full bg-white border border-slate-200 rounded-2xl py-3 pl-12 pr-4 text-sm font-medium text-[#1A2B49] focus:outline-none focus:ring-2 focus:ring-[#264790]/20 focus:border-[#264790] transition-all shadow-sm"
                    />
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredCourses.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-[2rem] border border-slate-100 shadow-sm">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300">
                    <MessageSquare :size="40" />
                </div>
                <h3 class="text-xl font-extrabold text-[#1A2B49] mb-2">Belum ada diskusi</h3>
                <p class="text-slate-400 font-medium text-sm text-center max-w-sm mb-8">
                    Kamu belum pernah mengajukan pertanyaan di kelas manapun. Yuk aktif berdiskusi di materi kursus!
                </p>
                <Link 
                    href="/dashboard/enrolled-courses"
                    class="bg-[#264790] text-white px-8 py-3 rounded-full font-bold text-sm shadow-md hover:bg-[#1A2B49] transition-all"
                >
                    Lihat Kelas Saya
                </Link>
            </div>

            <!-- Courses with Discussions Grid -->
            <div v-else class="grid grid-cols-1 gap-8">
                <div 
                    v-for="course in filteredCourses" 
                    :key="course.id"
                    class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden"
                >
                    <!-- Course Header -->
                    <div class="p-6 md:p-8 bg-slate-50/50 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 rounded-2xl bg-[#264790] overflow-hidden shrink-0 shadow-sm">
                                <img v-if="course.thumbnail" :src="course.thumbnail" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center text-white">
                                    <BookOpen :size="24" />
                                </div>
                            </div>
                            <div>
                                <h4 class="font-black text-[#1A2B49] text-xl leading-tight mb-1">{{ course.title }}</h4>
                                <p class="text-slate-500 text-xs font-semibold">Instruktur: <span class="text-[#264790]">{{ course.instructor_name }}</span></p>
                            </div>
                        </div>
                        <Link 
                            :href="`/courses/${course.slug}/learn`"
                            class="bg-white border border-slate-200 text-[#1A2B49] hover:bg-[#264790] hover:text-white px-6 py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 shadow-sm shrink-0"
                        >
                            Ke Ruang Belajar <ArrowRight :size="16" />
                        </Link>
                    </div>

                    <!-- Discussions List -->
                    <div class="p-6 md:p-8 space-y-6">
                        <div 
                            v-for="discussion in course.discussions" 
                            :key="discussion.id"
                            class="group relative"
                        >
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 shrink-0 group-hover:bg-[#264790]/5 group-hover:text-[#264790] transition-colors">
                                    <MessageSquare :size="20" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-3 mb-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ discussion.material_title }}</span>
                                        <span :class="getStatusBadge(discussion.is_resolved)" class="px-2.5 py-1 text-[9px] font-black rounded-lg border uppercase tracking-wider">
                                            {{ discussion.is_resolved ? 'Resolved' : 'Waiting Reply' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-bold flex items-center gap-1">
                                            <Clock :size="12" /> {{ discussion.created_at }}
                                        </span>
                                    </div>
                                    <p class="text-slate-700 text-sm font-medium leading-relaxed mb-4">{{ discussion.body }}</p>

                                    <!-- Latest Reply Preview -->
                                    <div v-if="discussion.latest_reply" class="bg-slate-50/80 rounded-2xl p-4 border border-slate-100/50">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-6 h-6 rounded-full bg-[#264790] flex items-center justify-center text-[10px] text-white font-bold">
                                                {{ discussion.latest_reply.user_name.charAt(0) }}
                                            </div>
                                            <span class="text-[11px] font-black text-[#1A2B49]">{{ discussion.latest_reply.user_name }}</span>
                                            <span class="text-[10px] text-slate-400 font-bold ml-auto">{{ discussion.latest_reply.created_at }}</span>
                                        </div>
                                        <p class="text-slate-600 text-xs font-medium leading-relaxed">{{ discussion.latest_reply.body }}</p>
                                    </div>
                                    <div v-else class="text-[11px] font-bold text-slate-400 italic flex items-center gap-1.5 px-2">
                                        <Circle :size="8" class="fill-slate-300 text-slate-300" /> Belum ada balasan dari mentor
                                    </div>
                                </div>
                            </div>
                            <div v-if="course.discussions.length > 1" class="absolute -bottom-3 left-0 right-0 h-px bg-slate-50 last:hidden"></div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardWrapper>
    </GuestLayout>
</template>
