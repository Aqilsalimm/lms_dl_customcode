import '../css/app.css';
import './bootstrap';

import { createInertiaApp, usePage, router } from '@inertiajs/vue3';
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
        const updateZiggy = (newZiggy) => {
            if (!newZiggy) return;

            // 1. Update global Ziggy constant in-place if it exists
            if (typeof Ziggy !== 'undefined') {
                if (Ziggy.routes && newZiggy.routes) {
                    // Prune old routes that are not in the new routes definition
                    Object.keys(Ziggy.routes).forEach(key => {
                        if (!newZiggy.routes[key]) {
                            delete Ziggy.routes[key];
                        }
                    });
                    // Merge new routes
                    Object.assign(Ziggy.routes, newZiggy.routes);
                }
                if (Ziggy.defaults && newZiggy.defaults) {
                    Object.assign(Ziggy.defaults, newZiggy.defaults);
                }
                if (newZiggy.url) Ziggy.url = newZiggy.url;
                if (newZiggy.port) Ziggy.port = newZiggy.port;
                if (newZiggy.location) Ziggy.location = newZiggy.location;
            }

            // 2. Update window.Ziggy in-place if it exists, or initialize it
            if (typeof window !== 'undefined' && window.Ziggy) {
                if (window.Ziggy.routes && newZiggy.routes) {
                    Object.keys(window.Ziggy.routes).forEach(key => {
                        if (!newZiggy.routes[key]) {
                            delete window.Ziggy.routes[key];
                        }
                    });
                    Object.assign(window.Ziggy.routes, newZiggy.routes);
                }
                if (window.Ziggy.defaults && newZiggy.defaults) {
                    Object.assign(window.Ziggy.defaults, newZiggy.defaults);
                }
                if (newZiggy.url) window.Ziggy.url = newZiggy.url;
                if (newZiggy.port) window.Ziggy.port = newZiggy.port;
                if (newZiggy.location) window.Ziggy.location = newZiggy.location;
            } else if (typeof window !== 'undefined') {
                window.Ziggy = newZiggy;
            }
        };

        if (props.initialPage.props.ziggy) {
            updateZiggy(props.initialPage.props.ziggy);
        }

        router.on('success', (event) => {
            if (event.detail.page.props.ziggy) {
                updateZiggy(event.detail.page.props.ziggy);
            }
        });

        router.on('navigate', (event) => {
            if (event.detail.page.props.ziggy) {
                updateZiggy(event.detail.page.props.ziggy);
            }
        });

        // Wrap the global route helper to dynamically update Ziggy configuration from Inertia page props
        if (typeof window !== 'undefined' && window.route) {
            const originalRoute = window.route;
            window.route = (name, params, absolute, config) => {
                try {
                    const latestZiggy = usePage().props.ziggy;
                    if (latestZiggy) {
                        updateZiggy(latestZiggy);
                    }
                } catch (e) {
                    // Fallback
                }
                return originalRoute(name, params, absolute, config);
            };
        }

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Wrap Vue global route helper and provided route helper
        const originalVueRoute = app.config.globalProperties.route;
        if (originalVueRoute) {
            const wrappedRoute = (name, params, absolute, config) => {
                try {
                    const latestZiggy = usePage().props.ziggy;
                    if (latestZiggy) {
                        updateZiggy(latestZiggy);
                    }
                } catch (e) {}
                return originalVueRoute(name, params, absolute, config);
            };
            app.config.globalProperties.route = wrappedRoute;
            app.provide('route', wrappedRoute);
        }

        // Global translation helper dynamically fetching from active page props
        app.config.globalProperties.$t = (key) => {
            try {
                const page = usePage();
                const translations = page.props.translations || {};
                return translations[key] !== undefined ? translations[key] : key;
            } catch (e) {
                const translations = props.initialPage.props.translations || {};
                return translations[key] !== undefined ? translations[key] : key;
            }
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
