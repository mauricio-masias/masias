import { createSSRApp, h, DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import '../css/app.css';

createInertiaApp({
    title: (title) => title ? `${title} — Masias` : 'Masias',
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createSSRApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#64ffda',
    },
});

// Push virtual page views to GTM dataLayer on every Inertia navigation.
// The initial load is already tracked by the GTM snippet in app.blade.php,
// so we skip the first fire to avoid double-counting.
let initialNavigation = true;

router.on('navigate', () => {
    if (initialNavigation) {
        initialNavigation = false;
        return;
    }

    // Defer so Vue has rendered and document.title is updated.
    setTimeout(() => {
        (window as any).dataLayer = (window as any).dataLayer || [];
        (window as any).dataLayer.push({
            event: 'page_view',
            page_location: window.location.href,
            page_title: document.title,
        });
    }, 0);
});
