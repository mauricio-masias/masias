import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';

/**
 * Kept separate from vite.config.ts so the Laravel plugin, which expects a
 * real build with entry points and a manifest, is not loaded during tests.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['./vitest.setup.ts'],
        include: ['resources/js/**/*.test.ts'],
        restoreMocks: true,
        environmentOptions: {
            // A realistic multi-label host, so cookie domain handling is
            // exercised the way it behaves in production rather than on
            // single-label "localhost".
            jsdom: { url: 'https://www.masias.co.uk/' },
        },
    },
});
