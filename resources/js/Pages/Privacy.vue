<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useReveal } from '@/composables/useReveal';

useReveal();

defineProps<{
    title: string;
    content: string | null;
    lastUpdated: string;
}>();

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <section class="min-h-screen pt-36 pb-24 px-6">
            <div class="max-w-3xl mx-auto">
                <p class="font-mono text-[#64ffda] text-sm mb-4 tracking-widest uppercase reveal">Legal</p>
                <h1 class="font-display font-bold text-4xl sm:text-5xl text-white mb-4 reveal" style="transition-delay:0.1s">
                    {{ title }}
                </h1>
                <p class="font-mono text-[#444] text-xs mb-14 reveal" style="transition-delay:0.15s">
                    Last updated: {{ formatDate(lastUpdated) }}
                </p>

                <div
                    class="reveal
                        [&_h2]:font-display [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-white [&_h2]:mt-10 [&_h2]:mb-3
                        [&_h3]:font-display [&_h3]:text-base [&_h3]:font-semibold [&_h3]:text-white [&_h3]:mt-6 [&_h3]:mb-2
                        [&_p]:text-[#888] [&_p]:leading-relaxed [&_p]:mb-4 [&_p:last-child]:mb-0
                        [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1 [&_ul]:mb-4 [&_ul]:text-[#888]
                        [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:space-y-1 [&_ol]:mb-4 [&_ol]:text-[#888]
                        [&_li]:text-[#888]
                        [&_strong]:text-[#bbb] [&_strong]:font-semibold
                        [&_a]:text-[#64ffda] [&_a]:no-underline hover:[&_a]:underline
                        [&_hr]:border-[#1f1f1f] [&_hr]:my-8"
                    style="transition-delay:0.2s"
                    v-html="content"
                />
            </div>
        </section>
    </AppLayout>
</template>
