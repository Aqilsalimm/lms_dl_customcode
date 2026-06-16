import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('leaflet')) {
                            return 'vendor-leaflet';
                        }
                        if (id.includes('lucide') || id.includes('@lucide')) {
                            return 'vendor-lucide';
                        }
                        if (id.includes('vue') || id.includes('@vue') || id.includes('@inertiajs')) {
                            return 'vendor-core';
                        }
                        return 'vendor';
                    }
                }
            }
        }
    },
    server: {
        port: 5174,
        watch: {
            usePolling: true,
        },
        hmr: {
            host: 'localhost',
        },
    },
});
