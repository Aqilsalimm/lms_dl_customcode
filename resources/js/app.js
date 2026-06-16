import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// Override global window.alert with beautiful SweetAlert2
window.alert = (message) => {
    const msg = String(message);
    const lowerMsg = msg.toLowerCase();
    let icon = 'info';
    let title = 'Pemberitahuan';
    
    if (lowerMsg.includes('berhasil') || lowerMsg.includes('sukses') || lowerMsg.includes('success') || lowerMsg.includes('selamat datang')) {
        icon = 'success';
        title = 'Berhasil';
    } else if (lowerMsg.includes('gagal') || lowerMsg.includes('error') || lowerMsg.includes('salah') || lowerMsg.includes('ditolak') || lowerMsg.includes('wajib') || lowerMsg.includes('kosong')) {
        icon = 'error';
        title = 'Perhatian';
    }

    Swal.fire({
        title: title,
        text: message,
        icon: icon,
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-[2rem] p-8 border border-slate-100 shadow-[0_20px_50px_rgba(0,0,0,0.15)] bg-white text-slate-800 font-sans select-none',
            title: 'text-xl font-extrabold text-[#1A2B49] mb-2',
            htmlContainer: 'text-sm font-semibold text-slate-500 leading-relaxed my-4',
            confirmButton: 'bg-[#264790] hover:bg-[#1A2B49] text-white font-black px-8 py-3 rounded-full text-xs shadow-md transition-all outline-none focus:ring-4 focus:ring-[#264790]/20 active:scale-95 cursor-pointer',
        }
    });
};

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Global translation helper
        app.config.globalProperties.$t = (key) => {
            const translations = props.initialPage.props.translations || {};
            return translations[key] || key;
        };

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Register Service Worker for PWA Support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW Registered!', reg))
            .catch(err => console.log('SW Reg Failed!', err));
    });
}
