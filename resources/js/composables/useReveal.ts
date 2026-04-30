import { onMounted, onUnmounted } from 'vue';

export function useReveal(selector = '.reveal') {
    let observer: IntersectionObserver | null = null;

    onMounted(() => {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer?.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -60px 0px' },
        );

        document.querySelectorAll<HTMLElement>(selector).forEach((el) => {
            observer?.observe(el);
        });
    });

    onUnmounted(() => observer?.disconnect());
}
