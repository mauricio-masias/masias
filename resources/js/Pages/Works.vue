<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useReveal } from '@/composables/useReveal';

interface Work {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    description: string;
    image: string | null;
    url: string | null;
    tags: string[] | null;
    is_featured: boolean;
}

const props = defineProps<{ works: Work[] }>();

useReveal();

const activeWork = ref<Work | null>(null);
const selectedTag = ref<string | null>(null);

const allTags = computed(() => {
    const tags = new Set<string>();
    props.works.forEach((w) => w.tags?.forEach((t) => tags.add(t)));
    return Array.from(tags).sort();
});

const filtered = computed(() =>
    selectedTag.value
        ? props.works.filter((w) => w.tags?.includes(selectedTag.value!))
        : props.works,
);

function openWork(work: Work) {
    activeWork.value = work;
    document.body.style.overflow = 'hidden';
}

function closeWork() {
    activeWork.value = null;
    document.body.style.overflow = '';
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') closeWork();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Works" />
    <AppLayout>
        <!-- Hero -->
        <section class="pt-40 pb-20 px-6">
            <div class="max-w-6xl mx-auto">
                <p class="font-mono text-[#64ffda] text-sm mb-4 tracking-widest uppercase reveal">Portfolio</p>
                <h1 class="font-display font-bold leading-tight reveal" style="font-size: clamp(2.5rem, 8vw, 6rem); transition-delay: 0.1s">
                    Selected<br /><span class="text-[#444]">Works.</span>
                </h1>
                <p class="text-[#666] text-lg mt-6 max-w-xl reveal" style="transition-delay: 0.2s">
                    A collection of projects built across different stacks and industries.
                </p>
            </div>
        </section>

        <!-- Tag filter -->
        <section v-if="allTags.length" class="px-6 pb-12">
            <div class="max-w-6xl mx-auto tag-filter sm:flex sm:flex-wrap gap-3">
                <button
                    class="font-mono text-sm px-4 py-2 rounded-full border transition-all duration-200"
                    :class="selectedTag === null
                        ? 'border-[#64ffda] text-[#64ffda] bg-[#64ffda]/10'
                        : 'border-[#666] text-[#777] hover:border-[#64ffda]/50 hover:text-white'"
                    @click="selectedTag = null"
                >
                    All
                </button>
                <button
                    v-for="tag in allTags"
                    :key="tag"
                    class="font-mono text-sm px-4 py-2 rounded-full border transition-all duration-200"
                    :class="selectedTag === tag
                        ? 'border-[#64ffda] text-[#64ffda] bg-[#64ffda]/10'
                        : 'border-[#666] text-[#777] hover:border-[#64ffda]/50 hover:text-white'"
                    @click="selectedTag = tag"
                >
                    {{ tag }}
                </button>
            </div>
        </section>

        <!-- Works grid -->
        <section class="px-6 pb-32">
            <div class="max-w-6xl mx-auto">
                <TransitionGroup
                    name="grid"
                    tag="div"
                    class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <button
                        v-for="(work, i) in filtered"
                        :key="work.id"
                        class="group text-left bg-[#111] border border-[#1f1f1f] rounded-xl overflow-hidden hover:border-[#64ffda]/30 transition-all duration-300 hover:-translate-y-2 hover:shadow-[0_20px_60px_rgba(0,0,0,0.5)] cursor-pointer"
                        :style="`transition-delay: ${(i % 6) * 0.08}s`"
                        @click="openWork(work)"
                    >
                        <!-- Image -->
                        <div class="aspect-video bg-[#1a1a1a] relative overflow-hidden">
                            <img
                                v-if="work.image"
                                :src="`/assets/${work.image}`"
                                :alt="work.title"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                loading="lazy"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f]">
                                <span class="font-display font-bold text-4xl text-[#2a2a2a]">{{ work.title.charAt(0) }}</span>
                            </div>

                            <!-- Overlay on hover -->
                            <div class="absolute inset-0 bg-[#080808]/70 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <span class="font-mono text-sm text-[#64ffda] border border-[#64ffda]/50 rounded-full px-5 py-2">
                                    View Details
                                </span>
                            </div>

                            <!-- Featured badge -->
                            <div v-if="work.is_featured" class="absolute top-3 right-3 bg-[#64ffda] text-[#080808] font-mono text-xs font-bold px-2 py-1 rounded">
                                Featured
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span
                                    v-for="tag in work.tags?.slice(0, 4)"
                                    :key="tag"
                                    class="font-mono text-xs text-[#64ffda] bg-[#64ffda]/10 px-2 py-0.5 rounded"
                                >{{ tag }}</span>
                            </div>
                            <h3 class="font-display font-semibold text-white text-lg mb-2 group-hover:text-[#64ffda] transition-colors">{{ work.title }}</h3>
                            <p class="text-[#555] text-sm leading-relaxed line-clamp-2">{{ work.excerpt }}</p>
                        </div>
                    </button>
                </TransitionGroup>

                <div v-if="!filtered.length" class="text-center py-24 text-[#444] font-mono">
                    No projects found for "{{ selectedTag }}".
                </div>
            </div>
        </section>

        <!-- Work Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="activeWork"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-6"
                    @click.self="closeWork"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeWork" />

                    <!-- Modal panel -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-8 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-200"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="activeWork"
                            class="relative z-10 w-full sm:max-w-3xl max-h-[90vh] sm:max-h-[85vh] bg-[#111] sm:rounded-2xl border border-[#1f1f1f] overflow-y-auto"
                        >
                            <!-- Close -->
                            <button
                                class="absolute top-4 right-4 z-20 w-9 h-9 flex items-center justify-center rounded-full bg-[#1f1f1f] text-[#888] hover:text-white hover:bg-[#2a2a2a] transition-colors"
                                @click="closeWork"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <!-- Image -->
                            <div class="aspect-video bg-[#1a1a1a] relative overflow-hidden sm:rounded-t-2xl">
                                <img
                                    v-if="activeWork.image"
                                    :src="`/storage/${activeWork.image}`"
                                    :alt="activeWork.title"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f]">
                                    <span class="font-display font-bold text-6xl text-[#2a2a2a]">{{ activeWork.title.charAt(0) }}</span>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-8">
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <span
                                        v-for="tag in activeWork.tags"
                                        :key="tag"
                                        class="font-mono text-xs text-[#64ffda] bg-[#64ffda]/10 px-2.5 py-1 rounded"
                                    >{{ tag }}</span>
                                </div>

                                <h2 class="font-display font-bold text-3xl text-white mb-4">{{ activeWork.title }}</h2>

                                <div
                                    class="text-[#888] leading-relaxed prose prose-invert max-w-none prose-p:text-[#888] prose-a:text-[#64ffda] [&_p]:mb-4 [&_p:last-child]:mb-0"
                                    v-html="activeWork.description"
                                />

                                <div v-if="activeWork.url" class="mt-8">
                                    <a
                                        :href="activeWork.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 bg-[#64ffda] text-[#080808] font-semibold px-6 py-3 rounded-lg hover:bg-[#64ffda]/90 transition-colors"
                                    >
                                        View Live Site
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.grid-move,
.grid-enter-active,
.grid-leave-active {
    transition: all 0.3s ease;
}
.grid-enter-from,
.grid-leave-to {
    opacity: 0;
    transform: scale(0.9);
}

@media (max-width: 639px) {
    .tag-filter {
        display: grid;
        grid-template-rows: repeat(2, auto);
        grid-auto-flow: column;
        overflow-x: auto;
        padding-bottom: 8px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .tag-filter::-webkit-scrollbar {
        display: none;
    }
}
</style>
