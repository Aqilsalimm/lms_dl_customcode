<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import DashboardWrapper from '@/Components/DashboardWrapper.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { 
    MessageSquare, 
    Search, 
    Filter, 
    ChevronRight, 
    CheckCircle2, 
    Circle, 
    User as UserIcon, 
    Clock, 
    ArrowRight,
    X,
    Send
} from 'lucide-vue-next';

const props = defineProps({
    discussions: Object,
    filters: Object
});

const currentFilter = ref(props.filters.filter || 'all');
const searchQuery = ref('');

const filterDiscussions = (type) => {
    currentFilter.value = type;
    router.get(route('dashboard.qna'), { filter: type }, { preserveState: true });
};

// Modal State
const isModalOpen = ref(false);
const selectedQuestion = ref(null);
const replyForm = useForm({
    course_id: null,
    material_id: null,
    parent_id: null,
    body: ''
});

const openReplyModal = (q) => {
    selectedQuestion.value = q;
    replyForm.course_id = q.course_id;
    replyForm.material_id = q.material_id;
    replyForm.parent_id = q.id;
    isModalOpen.value = true;
};

const submitReply = () => {
    replyForm.post(route('discussions.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            replyForm.reset();
        }
    });
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="QnA Inbox" />

    <GuestLayout>
        <DashboardWrapper>
            <div class="mb-10">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-black text-[#1A2B49] mb-2 tracking-tight">QnA Inbox</h1>
                        <p class="text-slate-500 font-medium">Kelola pertanyaan dan diskusi dari seluruh kursus Anda.</p>
                    </div>
                    
                    <div class="flex items-center gap-3 bg-white p-2 rounded-2xl border border-slate-100 shadow-sm">
                        <button 
                            @click="filterDiscussions('all')"
                            :class="currentFilter === 'all' ? 'bg-[#1A2B49] text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                            class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all"
                        >
                            Semua
                        </button>
                        <button 
                            @click="filterDiscussions('unresolved')"
                            :class="currentFilter === 'unresolved' ? 'bg-[#EF4444] text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                            class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all"
                        >
                            Belum Terjawab
                        </button>
                        <button 
                            @click="filterDiscussions('resolved')"
                            :class="currentFilter === 'resolved' ? 'bg-emerald-500 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                            class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all"
                        >
                            Selesai
                        </button>
                    </div>
                </div>
            </div>

            <!-- Inbox List -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <div v-if="discussions.data.length > 0" class="divide-y divide-slate-50">
                    <div 
                        v-for="q in discussions.data" 
                        :key="q.id"
                        class="p-8 hover:bg-slate-50/50 transition-all group"
                    >
                        <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                            <!-- Context info -->
                            <div class="lg:w-1/4">
                                <div class="flex flex-col gap-1 mb-3">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-[#264790]">{{ q.course.title }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ q.material.title }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                        <UserIcon :size="20" />
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[#1A2B49] text-sm">{{ q.user.name }}</h4>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ formatDate(q.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Question content -->
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <span 
                                        :class="q.is_resolved ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                        class="text-[9px] font-black px-2 py-1 rounded-md uppercase tracking-wider flex items-center gap-1"
                                    >
                                        <CheckCircle2 v-if="q.is_resolved" :size="10" />
                                        <Circle v-else :size="10" />
                                        {{ q.is_resolved ? 'Resolved' : 'Waiting Reply' }}
                                    </span>
                                    <span class="text-slate-300 text-xs font-medium">• {{ q.replies.length }} Balasan</span>
                                </div>
                                <p class="text-slate-600 text-sm leading-relaxed line-clamp-2">{{ q.body }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="lg:w-48 flex justify-end">
                                <button 
                                    @click="openReplyModal(q)"
                                    class="bg-[#264790] hover:bg-[#1A2B49] text-white px-6 py-3 rounded-2xl font-bold text-sm flex items-center gap-2 transition-all shadow-md group-hover:scale-105 active:scale-95"
                                >
                                    Buka Detail <ChevronRight :size="18" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="py-32 flex flex-col items-center justify-center text-center px-6">
                    <div class="w-24 h-24 rounded-full bg-slate-50 flex items-center justify-center text-slate-200 mb-6">
                        <MessageSquare :size="48" />
                    </div>
                    <h3 class="text-xl font-bold text-[#1A2B49] mb-2">Inbox Kosong</h3>
                    <p class="text-slate-400 max-w-xs mx-auto font-medium">Tidak ada pertanyaan yang sesuai dengan filter saat ini.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="discussions.links.length > 3" class="mt-10 flex justify-center gap-2">
                <Link 
                    v-for="(link, k) in discussions.links" 
                    :key="k"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        'px-5 py-2.5 rounded-xl text-sm font-black transition-all',
                        link.active ? 'bg-[#1A2B49] text-white shadow-lg' : 'bg-white text-slate-500 hover:bg-slate-50 border border-slate-100',
                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                />
            </div>
        </DashboardWrapper>
    </GuestLayout>

    <!-- Reply Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div @click="isModalOpen = false" class="absolute inset-0 bg-[#1A2B49]/40 backdrop-blur-sm"></div>
        
        <div class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="p-8 border-b border-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-black text-[#1A2B49]">Detail Diskusi</h3>
                    <div class="flex items-center gap-2 text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-1">
                        <span>{{ selectedQuestion?.course.title }}</span>
                        <ChevronRight :size="10" />
                        <span>{{ selectedQuestion?.material.title }}</span>
                    </div>
                </div>
                <button @click="isModalOpen = false" class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-rose-50 hover:text-rose-500 transition-colors">
                    <X :size="24" />
                </button>
            </div>

            <div class="p-8 max-h-[60vh] overflow-y-auto">
                <!-- Question -->
                <div class="flex items-start gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 flex-shrink-0">
                        <UserIcon :size="24" />
                    </div>
                    <div class="bg-slate-50 p-6 rounded-[2rem] flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-bold text-[#1A2B49]">{{ selectedQuestion?.user.name }}</h4>
                            <span class="text-[10px] text-slate-400 font-medium">{{ formatDate(selectedQuestion?.created_at) }}</span>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed">{{ selectedQuestion?.body }}</p>
                    </div>
                </div>

                <!-- Existing Replies -->
                <div v-if="selectedQuestion?.replies.length > 0" class="space-y-6 mb-8 pl-16">
                    <div v-for="reply in selectedQuestion?.replies" :key="reply.id" class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-2xl bg-[#264790]/10 flex items-center justify-center text-[#264790] flex-shrink-0">
                            <UserIcon :size="20" />
                        </div>
                        <div class="bg-white border border-slate-100 p-5 rounded-2xl flex-1 shadow-sm">
                            <div class="flex items-center justify-between mb-1">
                                <h5 class="font-bold text-[#1A2B49] text-sm">{{ reply.user?.name || 'Mentor' }}</h5>
                                <span class="text-[9px] text-slate-400 font-medium">{{ formatDate(reply.created_at) }}</span>
                            </div>
                            <p class="text-slate-500 text-xs leading-relaxed">{{ reply.body }}</p>
                        </div>
                    </div>
                </div>

                <!-- Reply Form -->
                <div class="pl-16">
                    <label class="block text-[10px] font-black text-[#1A2B49] uppercase tracking-widest mb-3">Tulis Balasan Anda</label>
                    <div class="relative">
                        <textarea 
                            v-model="replyForm.body"
                            placeholder="Berikan jawaban atau panduan untuk siswa..."
                            class="w-full bg-slate-50 border-none rounded-[1.5rem] p-5 text-sm font-medium text-[#1A2B49] focus:ring-2 focus:ring-[#264790]/20 min-h-[150px] resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="p-8 border-t border-slate-50 bg-slate-50/50 flex justify-end gap-3">
                <button @click="isModalOpen = false" class="px-8 py-3.5 rounded-2xl font-bold text-sm text-slate-500 hover:bg-white transition-all">
                    Batal
                </button>
                <button 
                    @click="submitReply"
                    :disabled="replyForm.processing || !replyForm.body"
                    class="bg-[#264790] hover:bg-[#1A2B49] disabled:bg-slate-300 text-white px-10 py-3.5 rounded-2xl font-black text-sm flex items-center gap-2 transition-all shadow-lg active:scale-95"
                >
                    <Send :size="18" /> Kirim Jawaban
                </button>
            </div>
        </div>
    </div>
</template>
