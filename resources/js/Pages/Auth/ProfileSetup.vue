<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    expertise_area: '',
    portfolio_url: '',
    resume_file: null,
    bio_summary: '',
});

const handleFileChange = (e) => {
    form.resume_file = e.target.files[0];
};

const submit = () => {
    form.post(route('instructor.profile.store'));
};

const Logo = () => {
    const settings = usePage().props.settings;
    const customLogo = settings?.course_logo;
    if (customLogo && customLogo !== '/images/logo-placeholder.png') {
        return `<img src="${customLogo}" alt="Drastha Learning Logo" class="h-10 w-auto object-contain mx-auto mb-6" />`;
    }
    return `<img src="/images/logo/logo_dl.png" alt="Drastha Learning Logo" class="h-10 w-auto object-contain mx-auto mb-6" />`;
};
</script>

<template>
    <Head title="Setup Profil Instruktur" />

    <div class="min-h-screen bg-[#F4F7F9] font-montserrat flex items-center justify-center p-6">
        <div class="bg-white rounded-[2rem] p-8 sm:p-12 w-full max-w-2xl shadow-[0_12px_50px_rgba(0,0,0,0.025)] border border-slate-100/50">
            <div v-html="Logo()"></div>
            
            <h2 class="text-2xl font-extrabold text-[#1A2B49] text-center mb-2">Lengkapi Profil Instruktur</h2>
            <p class="text-slate-500 text-sm text-center font-semibold mb-8">
                Unggah CV/Resume dan bagikan portofolio Anda agar tim kami dapat meninjau pendaftaran Anda.
            </p>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <!-- Bidang Keahlian -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-extrabold text-[#264790]">Bidang Keahlian Utama <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        v-model="form.expertise_area"
                        placeholder="Contoh: Web Development, Digital Marketing" 
                        required
                        class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
                    />
                    <div v-if="form.errors.expertise_area" class="text-red-500 text-xs mt-1">{{ form.errors.expertise_area }}</div>
                </div>

                <!-- Link Portofolio -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-extrabold text-[#264790]">Link Portofolio / LinkedIn</label>
                    <input 
                        type="url" 
                        v-model="form.portfolio_url"
                        placeholder="https://" 
                        class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
                    />
                    <div v-if="form.errors.portfolio_url" class="text-red-500 text-xs mt-1">{{ form.errors.portfolio_url }}</div>
                </div>

                <!-- Resume -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-extrabold text-[#264790]">Unggah CV / Resume <span class="text-red-500">*</span></label>
                    <input 
                        type="file" 
                        @change="handleFileChange"
                        accept=".pdf,.doc,.docx"
                        required
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#264790] file:text-white hover:file:bg-[#44A6D9] cursor-pointer"
                    />
                    <p class="text-xs text-slate-400 mt-1">Format: PDF, DOC, DOCX. Maks: 2MB.</p>
                    <div v-if="form.errors.resume_file" class="text-red-500 text-xs mt-1">{{ form.errors.resume_file }}</div>
                </div>

                <!-- Bio Singkat -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-extrabold text-[#264790]">Ceritakan Singkat Tentang Anda <span class="text-red-500">*</span></label>
                    <textarea 
                        v-model="form.bio_summary"
                        rows="4"
                        placeholder="Pengalaman mengajar, pencapaian, dll." 
                        required
                        class="w-full bg-[#F4F7F9] border border-slate-100 rounded-2xl px-5 py-4 text-sm text-slate-700 font-semibold focus:outline-none focus:border-[#44A6D9]/50 focus:bg-white transition-all placeholder-slate-400"
                    ></textarea>
                    <div v-if="form.errors.bio_summary" class="text-red-500 text-xs mt-1">{{ form.errors.bio_summary }}</div>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-[#264790] hover:bg-[#44A6D9] text-white py-4 rounded-2xl font-bold text-sm shadow-md hover:shadow-lg transition-all text-center mt-4 disabled:opacity-50"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Kirim Pendaftaran' }}
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
.font-montserrat { font-family: 'Montserrat', sans-serif; }
</style>
