<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{ status: number }>();

const title = computed(() => ({
    503: 'Service Unavailable',
    500: 'Server Error',
    404: 'Page Not Found',
    403: 'Forbidden',
}[props.status] ?? 'Unexpected Error'));

const description = computed(() => ({
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
    500: 'Something went wrong on our end. We are looking into it.',
    404: 'The page you are looking for could not be found.',
    403: 'You do not have permission to access this page.',
}[props.status] ?? 'An unexpected error occurred.'));
</script>

<template>
    <Head :title="`${status} — ${title}`" />

    <AppLayout>
        <section class="min-h-[80vh] flex items-center justify-center px-6">
            <div class="text-center max-w-lg">
                <p class="font-mono text-[#64ffda] text-sm tracking-widest uppercase mb-6">
                    Error {{ status }}
                </p>

                <h1 class="font-display font-bold text-5xl sm:text-6xl text-white mb-6 leading-tight">
                    {{ title }}
                </h1>

                <p class="text-[#888] text-lg leading-relaxed mb-10">
                    {{ description }}
                </p>

                <Link
                    href="/"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-[#64ffda] text-[#080808] font-display font-semibold text-sm rounded-sm hover:bg-[#64ffdacc] transition-colors duration-200"
                >
                    ← Back to Home
                </Link>
            </div>
        </section>
    </AppLayout>
</template>
