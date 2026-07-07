import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/analytics.js', 'resources/js/marketing-hub.js', 'resources/js/marketing-tracker.js', 'resources/js/dashboard-charts.js', 'resources/js/nova-neural-bg.js', 'resources/js/nova-assistant.js', 'resources/js/widget.js', 'resources/js/form-builder.js', 'resources/js/project-kanban.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    build: {
        // GrapesJS (lazy-loaded landing builder only) is ~1.1 MB minified — expected.
        chunkSizeWarningLimit: 1200,
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        {
                            name: 'vendor-grapesjs',
                            test: /node_modules\/grapesjs/,
                        },
                    ],
                },
            },
        },
    },
});
