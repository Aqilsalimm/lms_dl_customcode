<script setup>
import { ref, onMounted, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { MessageSquare, ChevronDown, ChevronUp, Send, CheckCircle2, User as UserIcon, Clock } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    courseId: {
        type: Number,
        required: true
    },
    lessonId: {
        type: Number,
        required: true
    }
});

const isOpen = ref(false);
const discussions = ref([]);
const isLoading = ref(false);
const page = usePage();
const currentUser = page.props.auth.user;

const fetchDiscussions = async () => {
    if (!props.lessonId) return;
    isLoading.ref = true;
    try {
        const response = await axios.get(route('discussions.lesson', props.lessonId));
        discussions.value = response.data;
    } catch (error) {
        console.error('Failed to fetch discussions', error);
    } finally {
        isLoading.value = false;
    }
};

const form = useForm({
    course_id: props.courseId,
    material_id: props.lessonId,
    parent_id: null,
    body: ''
});

const submitQuestion = () => {
    form.post(route('discussions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
            fetchDiscussions();
        }
    });
};

const replyForm = useForm({
    course_id: props.courseId,
    material_id: props.lessonId,
    parent_id: null,
    body: ''
});

const activeReplyId = ref(null);

const submitReply = (parentId) => {
    replyForm.parent_id = parentId;
    replyForm.post(route('discussions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset('body');
            activeReplyId.value = null;
            fetchDiscussions();
        }
    });
};

const toggleResolve = (discussion) => {
    axios.post(route('discussions.resolve', discussion.id))
        .then(() => fetchDiscussions());
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

onMounted(() => {
    fetchDiscussions();
});

watch(() => props.lessonId, () => {
    fetchDiscussions();
});
</script>

<template>
    <div class="mt-6 bg-white rounded-[1.5rem] border border-slate-100 shadow-sm overflow-hidden transition-all duration-500">
        <!-- Accordion Header -->
        <button 
            @click="isOpen = !isOpen"
            class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors group"
        >
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#264790]/5 flex items-center justify-center text-[#264790] group-hover:scale-105 transition-transform duration-500">
                    <MessageSquare :size="20" />
                </div>
                <div class="text-left">
                    <h3 class="font-black text-[#1A2B49] text-base">QnA & Diskusi</h3>
                    <p class="text-slate-400 text-[11px] font-bold">{{ discussions.length }} Pertanyaan tersedia</p>
                </div>
            </div>
            <div :class="isOpen ? 'rotate-180 bg-[#1A2B49] text-white' : 'bg-slate-100 text-[#1A2B49]'" class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-500">
                <ChevronDown :size="16" />
            </div>
        </button>

        <!-- Accordion Content -->
        <div v-show="isOpen" class="px-8 pb-8 border-t border-slate-50">
            <!-- Ask Question Input -->
            <div class="mt-6 mb-8">
                <label class="block text-[10px] font-black text-[#1A2B49] uppercase tracking-widest mb-2">Tanyakan sesuatu</label>
                <div class="relative">
                    <textarea 
                        v-model="form.body"
                        placeholder="Apa yang ingin kamu tanyakan mengenai materi ini?"
                        class="w-full bg-slate-50 border-none rounded-[1.25rem] p-4 text-xs font-medium text-[#1A2B49] focus:ring-2 focus:ring-[#264790]/20 min-h-[100px] resize-none transition-all"
                    ></textarea>
                    <button 
                        @click="submitQuestion"
                        :disabled="form.processing || !form.body"
                        class="absolute bottom-3 right-3 bg-[#264790] hover:bg-[#1A2B49] disabled:bg-slate-300 text-white px-5 py-2 rounded-xl font-bold text-[11px] flex items-center gap-1.5 transition-all shadow-md active:scale-95"
                    >
                        <Send :size="14" /> Kirim Pertanyaan
                    </button>
                </div>
            </div>

            <!-- Discussion List -->
            <div class="space-y-6">
                <div v-for="q in discussions" :key="q.id" class="group/card">
                    <!-- Parent Card -->
                    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 shadow-sm hover:shadow-md transition-all duration-500 relative overflow-hidden">
                        <div v-if="q.is_resolved" class="absolute -right-8 -top-8 w-24 h-24 bg-emerald-50 rounded-full flex items-center justify-center pt-8 pr-8">
                            <CheckCircle2 :size="20" class="text-emerald-500" />
                        </div>

                        <div class="flex items-start gap-4 mb-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                <UserIcon :size="20" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-bold text-[#1A2B49] text-sm">{{ q.user.name }}</h4>
                                    <span v-if="q.is_resolved" class="bg-emerald-50 text-emerald-600 text-[10px] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider flex items-center gap-1">
                                        <CheckCircle2 :size="12" /> Selesai
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-slate-400 text-[10px] font-medium uppercase tracking-widest">
                                    <span class="flex items-center gap-1"><Clock :size="12" /> {{ formatDate(q.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <p class="text-slate-600 text-sm leading-relaxed mb-6">{{ q.body }}</p>

                        <div class="flex items-center gap-4">
                            <button 
                                @click="activeReplyId = activeReplyId === q.id ? null : q.id"
                                class="text-[#264790] text-xs font-black uppercase tracking-widest hover:underline"
                            >
                                Balas
                            </button>
                            <button 
                                v-if="currentUser.id === q.user_id"
                                @click="toggleResolve(q)"
                                :class="q.is_resolved ? 'text-slate-400' : 'text-emerald-600'"
                                class="text-xs font-black uppercase tracking-widest hover:underline"
                            >
                                {{ q.is_resolved ? 'Buka Kembali' : 'Tandai Selesai' }}
                            </button>
                        </div>

                        <!-- Nested Replies -->
                        <div v-if="q.replies && q.replies.length > 0" class="mt-6 space-y-4 pt-6 border-t border-slate-50">
                            <div v-for="reply in q.replies" :key="reply.id" class="flex items-start gap-4 pl-6 border-l-2 border-slate-100">
                                <div class="w-8 h-8 rounded-full bg-[#264790]/10 flex items-center justify-center text-[#264790]">
                                    <UserIcon :size="16" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h5 class="font-bold text-[#1A2B49] text-xs">{{ reply.user.name }}</h5>
                                        <span v-if="reply.user.role === 'instructor' || reply.user.role === 'admin'" class="bg-[#264790] text-white text-[8px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider">Mentor</span>
                                    </div>
                                    <p class="text-slate-500 text-xs leading-relaxed mb-1">{{ reply.body }}</p>
                                    <span class="text-slate-300 text-[9px] font-medium uppercase tracking-widest">{{ formatDate(reply.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Reply Input -->
                        <div v-if="activeReplyId === q.id" class="mt-6 pt-6 border-t border-slate-50">
                            <div class="relative">
                                <textarea 
                                    v-model="replyForm.body"
                                    placeholder="Tulis balasan..."
                                    class="w-full bg-slate-50 border-none rounded-2xl p-4 text-xs font-medium text-[#1A2B49] focus:ring-1 focus:ring-[#264790]/20 min-h-[80px] resize-none"
                                ></textarea>
                                <button 
                                    @click="submitReply(q.id)"
                                    :disabled="replyForm.processing || !replyForm.body"
                                    class="absolute bottom-3 right-3 bg-[#1A2B49] text-white px-4 py-1.5 rounded-lg font-bold text-[10px] flex items-center gap-1.5 transition-all shadow-sm active:scale-95"
                                >
                                    <Send :size="12" /> Balas
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="discussions.length === 0" class="py-12 flex flex-col items-center justify-center opacity-40">
                    <MessageSquare :size="48" class="text-slate-300 mb-4" />
                    <p class="text-slate-400 font-bold text-sm">Belum ada diskusi di materi ini.</p>
                </div>
            </div>
        </div>
    </div>
</template>
