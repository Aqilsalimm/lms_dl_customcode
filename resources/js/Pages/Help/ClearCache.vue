<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Trash2, RefreshCw, CheckCircle2, AlertTriangle, Smartphone, Monitor, Globe } from 'lucide-vue-next';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

const props = defineProps({
    siteName: { type: String, default: 'Drastha Learning' },
});

const status = ref('idle'); // idle | running | done | error
const report = ref(null);
const errorMsg = ref('');

const runCleanup = async () => {
    if (status.value === 'running') return;
    status.value = 'running';
    errorMsg.value = '';
    report.value = null;

    const summary = {
        swUnregistered: 0,
        cachesDeleted: 0,
        localStorageCleared: false,
        sessionStorageCleared: false,
        cookiesCleared: 0,
    };

    try {
        // 1) Unregister service workers
        if ('serviceWorker' in navigator) {
            const regs = await navigator.serviceWorker.getRegistrations();
            summary.swUnregistered = regs.length;
            await Promise.all(regs.map((r) => r.unregister()));
        }

        // 2) Delete all Cache Storage entries
        if ('caches' in window) {
            const keys = await caches.keys();
            summary.cachesDeleted = keys.length;
            await Promise.all(keys.map((k) => caches.delete(k)));
        }

        // 3) Clear localStorage
        try {
            localStorage.clear();
            summary.localStorageCleared = true;
        } catch (_) { /* noop */ }

        // 4) Clear sessionStorage
        try {
            sessionStorage.clear();
            summary.sessionStorageCleared = true;
        } catch (_) { /* noop */ }

        // 5) Best-effort: clear non-HttpOnly cookies we can see.
        //    HttpOnly cookies (session, XSRF) are unreachable from JS by design.
        try {
            const cookies = document.cookie ? document.cookie.split(';') : [];
            summary.cookiesCleared = cookies.length;
            const expires = 'Thu, 01 Jan 1970 00:00:00 GMT';
            const host = window.location.hostname;
            const domains = ['', host, `.${host}`];
            for (const c of cookies) {
                const name = c.split('=')[0].trim();
                if (!name) continue;
                for (const d of domains) {
                    document.cookie = `${name}=; expires=${expires}; path=/${d ? `; domain=${d}` : ''}`;
                }
            }
        } catch (_) { /* noop */ }

        report.value = summary;
        status.value = 'done';
    } catch (err) {
        console.error('[ClearCache] cleanup error', err);
        errorMsg.value = (err && err.message) ? err.message : String(err);
        status.value = 'error';
    }
};

const reload = () => {
    // Force a clean navigation so the new SW (if any) and fresh assets take over.
    window.location.href = '/?nocache=' + Date.now();
};

const browser = (() => {
    if (typeof navigator === 'undefined') return 'unknown';
    const ua = navigator.userAgent;
    if (/iPhone|iPad|iPod/i.test(ua)) return 'ios';
    if (/Android/i.test(ua)) return 'android';
    if (/Edg\//i.test(ua)) return 'edge';
     if (/Chrome\//i.test(ua) && !/OPR\//i.test(ua)) return 'chrome';
     if (/Firefox\//i.test(ua)) return 'firefox';
    if (/Safari\//i.test(ua) && /Version\//i.test(ua)) return 'safari';
    if (/OPR\//i.test(ua)) return 'opera';
    return 'other';
})();

onMounted(() => {
    // Best-effort: tell SW to drop any straggler caches right away.
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        try {
            navigator.serviceWorker.controller.postMessage({ type: 'CLEAN_CACHES' });
        } catch (_) { /* noop */ }
    }
});
</script>

<template>
    <Head :title="`Bersihkan Cache — ${siteName}`" />

    <section class="min-h-[60vh] bg-slate-50 dark:bg-slate-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-100 text-amber-600 mb-4">
                    <AlertTriangle class="w-7 h-7" />
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                    Bersihkan Cache Browser
                </h1>
                <p class="mt-3 text-slate-600 dark:text-slate-300">
                    Jika tampilan website tampak rusak, popup muncul dua kali, atau versi lama masih
                    terlihat — klik tombol di bawah untuk menghapus seluruh cache, service worker,
                    dan data lokal yang tersimpan di browser Anda.
                </p>
            </div>

            <!-- One-click fix -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sm:p-8 mb-8">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                    Perbaikan Otomatis (Disarankan)
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300 mb-5">
                    Tombol ini akan: unregister service worker, menghapus seluruh Cache Storage,
                    membersihkan localStorage & sessionStorage, dan memuat ulang halaman.
                </p>

                <div v-if="status === 'idle'" class="flex flex-col sm:flex-row gap-3">
                    <button
                        @click="runCleanup"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-medium transition-colors"
                    >
                        <Trash2 class="w-5 h-5" />
                        Hapus Cache & Muat Ulang
                    </button>
                    <Link
                        href="/"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    >
                        Batal
                    </Link>
                </div>

                <div v-else-if="status === 'running'" class="flex items-center gap-3 text-slate-700 dark:text-slate-200">
                    <RefreshCw class="w-5 h-5 animate-spin" />
                    Sedang membersihkan...
                </div>

                <div v-else-if="status === 'done'" class="space-y-3">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-medium">
                        <CheckCircle2 class="w-5 h-5" />
                        Cache berhasil dibersihkan.
                    </div>
                    <ul class="text-sm text-slate-600 dark:text-slate-300 space-y-1 pl-1">
                        <li>• Service worker dihapus: <strong>{{ report?.swUnregistered ?? 0 }}</strong></li>
                        <li>• Cache Storage dihapus: <strong>{{ report?.cachesDeleted ?? 0 }}</strong> entri</li>
                        <li>• localStorage: <strong>{{ report?.localStorageCleared ? 'dibersihkan' : 'tidak diakses' }}</strong></li>
                        <li>• sessionStorage: <strong>{{ report?.sessionStorageCleared ? 'dibersihkan' : 'tidak diakses' }}</strong></li>
                        <li>• Cookie yang dapat diakses: <strong>{{ report?.cookiesCleared ?? 0 }}</strong> (cookie sesi HttpOnly tidak dapat dihapus dari sisi browser — ini normal)</li>
                    </ul>
                    <button
                        @click="reload"
                        class="mt-3 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium transition-colors"
                    >
                        <RefreshCw class="w-5 h-5" />
                        Muat Ulang Halaman
                    </button>
                </div>

                <div v-else-if="status === 'error'" class="space-y-3">
                    <div class="flex items-center gap-2 text-rose-600 font-medium">
                        <AlertTriangle class="w-5 h-5" />
                        Terjadi kesalahan: {{ errorMsg }}
                    </div>
                    <button
                        @click="runCleanup"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-medium transition-colors"
                    >
                        Coba Lagi
                    </button>
                </div>
            </div>

            <!-- Manual instructions -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sm:p-8">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    Atau Hapus Cache Secara Manual
                </h2>

                <div class="space-y-6 text-sm text-slate-700 dark:text-slate-300">
                    <div>
                        <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white mb-1">
                            <Smartphone class="w-4 h-4" />
                            Chrome Android
                        </div>
                        <p>Buka <strong>Setelan → Privasi & keamanan → Hapus data penjelajahan</strong> →
                           centang <em>Cache</em> dan <em>Data situs</em> → <strong>Hapus data</strong>.</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white mb-1">
                            <Smartphone class="w-4 h-4" />
                            iPhone / iPad (Safari)
                        </div>
                        <p>Buka <strong>Setelan → Safari → Lanjutan → Data Situs Web → Hapus Data Semua Situs</strong>.</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white mb-1">
                            <Monitor class="w-4 h-4" />
                            Chrome Desktop
                        </div>
                        <p>Tekan <kbd class="px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700">Ctrl</kbd>+
                           <kbd class="px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700">Shift</kbd>+
                           <kbd class="px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700">Del</kbd> →
                           pilih <em>Cache</em> dan <em>Data situs</em> → <strong>Hapus data</strong>.</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white mb-1">
                            <Monitor class="w-4 h-4" />
                            Firefox
                        </div>
                        <p>Tekan <kbd class="px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700">Ctrl</kbd>+
                           <kbd class="px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700">Shift</kbd>+
                           <kbd class="px-1.5 py-0.5 rounded border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700">Del</kbd> →
                           pilih <em>Cache</em> → <strong>Hapus Sekarang</strong>.</p>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white mb-1">
                            <Globe class="w-4 h-4" />
                            Browser Terdeteksi
                        </div>
                        <p>Anda saat ini menggunakan: <strong>{{ browser }}</strong>.</p>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs text-slate-500 dark:text-slate-400 mt-8">
                Halaman ini aman digunakan — tidak menghapus akun atau data server Anda.
            </p>
        </div>
    </section>
</template>