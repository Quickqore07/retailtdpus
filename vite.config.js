import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/style.css', 
                'resources/js/app.js', 
                'resources/js/onboarding-process-entry.js',
                'resources/js/single-benefit.js',
                'resources/js/attendance.js',
                'resources/js/upload-portal/main.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@js': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@svg': fileURLToPath(new URL('./resources/svg', import.meta.url)), 
        }
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
