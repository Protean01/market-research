import { createInertiaApp } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { registerSW } from 'virtual:pwa-register';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withXSRFToken = true;
axios.defaults.withCredentials = true;

// Setup CSRF token from meta tag as fallback
const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

// Global axios interceptor for session expiration
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            console.warn('Session expired (419), refreshing...');
            window.location.reload();
        }

        return Promise.reject(error);
    }
);

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    async setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia);
        
        // Initialize stores that need lifecycle monitoring
        const { useSurveyStore } = await import('@/stores/survey');
        useSurveyStore(pinia).initialize();

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Register the PWA service worker
if ('serviceWorker' in navigator) {
    registerSW({
        immediate: true,
        onRegisteredSW(swUrl) {
            console.log('SW Registered: ' + swUrl);
        },
    });
}

