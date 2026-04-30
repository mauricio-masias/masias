<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
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
    tags: string[];
}

interface Settings {
    hero_headline: string;
    hero_tagline: string;
    hero_description: string | null;
    about_text: string | null;
    skills: string[] | null;
    cv_url: string | null;
}

const props = defineProps<{
    settings: Settings;
    featuredWorks: Work[];
}>();

useReveal();

// Modal
const activeWork = ref<Work | null>(null);

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

// Parallax
const parallaxOffset = ref(0);
function handleScroll() {
    parallaxOffset.value = window.scrollY * 0.4;
}
onMounted(() => window.addEventListener('scroll', handleScroll, { passive: true }));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));

// Typewriter for tagline
const displayedTagline = ref('');
const taglines = ['Full-Stack Developer', 'PHP & TypeScript', 'Vue · React · Next.js', 'Agile Practitioner'];
let taglineIndex = 0;
let charIndex = 0;
let isDeleting = false;
let typeTimeout: ReturnType<typeof setTimeout>;

function typeWriter() {
    const current = taglines[taglineIndex];
    if (!isDeleting) {
        displayedTagline.value = current.substring(0, charIndex + 1);
        charIndex++;
        if (charIndex === current.length) {
            isDeleting = true;
            typeTimeout = setTimeout(typeWriter, 2000);
            return;
        }
    } else {
        displayedTagline.value = current.substring(0, charIndex - 1);
        charIndex--;
        if (charIndex === 0) {
            isDeleting = false;
            taglineIndex = (taglineIndex + 1) % taglines.length;
        }
    }
    typeTimeout = setTimeout(typeWriter, isDeleting ? 50 : 80);
}

onMounted(() => typeWriter());
onUnmounted(() => clearTimeout(typeTimeout));
</script>

<template>
    <Head title="Home" />
    <AppLayout>
        <!-- Hero -->
        <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
            <!-- Animated gradient orb -->
            <div
                class="absolute inset-0 pointer-events-none"
                :style="{ transform: `translateY(${parallaxOffset}px)` }"
            >
                <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-[#64ffda]/5 blur-[120px]" />
                <div class="absolute top-1/2 left-1/4 w-[400px] h-[400px] rounded-full bg-blue-500/5 blur-[100px]" />
                <!-- Subtle grid -->
                <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 80px 80px;" />
            </div>

            <div class="relative z-10 max-w-6xl mx-auto px-6 text-center">
                <!-- Status badge -->
                <div class="inline-flex items-center gap-2 border border-[#64ffda]/30 rounded-full px-4 py-1.5 text-xs font-mono text-[#64ffda] mb-8">
                    <span class="w-2 h-2 rounded-full bg-[#64ffda] animate-pulse" />
                    Available for new opportunities
                </div>

                <!-- Name -->
                <h1 class="font-display font-bold leading-none tracking-tight mb-6" style="font-size: clamp(3.5rem, 12vw, 9rem);">
                    {{ settings.hero_headline }}
                </h1>

                <!-- Typewriter tagline -->
                <p class="font-mono text-[#64ffda] text-lg md:text-2xl mb-6 h-8">
                    {{ displayedTagline }}<span class="animate-pulse">|</span>
                </p>

                <!-- Description -->
                <p v-if="settings.hero_description" class="text-[#888] text-lg max-w-xl mx-auto mb-10" v-html="settings.hero_description" />

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        href="/works"
                        class="group inline-flex items-center gap-2 bg-[#64ffda] text-[#080808] font-semibold px-8 py-4 rounded-lg hover:bg-[#64ffda]/90 transition-all duration-200 hover:-translate-y-0.5"
                    >
                        View My Work
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </Link>
                    <Link
                        href="/contact"
                        class="inline-flex items-center gap-2 border border-[#1f1f1f] text-[#888] font-medium px-8 py-4 rounded-lg hover:border-[#64ffda]/50 hover:text-white transition-all duration-200 hover:-translate-y-0.5"
                    >
                        Get in Touch
                    </Link>
                </div>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2">
                <span class="text-xs font-mono text-[#444] tracking-widest uppercase">Scroll</span>
                <div class="w-px h-12 bg-gradient-to-b from-[#444] to-transparent animate-pulse" />
            </div>
        </section>

        <!-- About -->
        <section class="py-24 px-6">
            <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-16 items-center">
                <div class="reveal" style="transition-delay: 0.1s">
                    <p class="font-mono text-[#64ffda] text-sm mb-4 tracking-widest uppercase">About</p>
                    <h2 class="font-display font-bold text-4xl md:text-5xl mb-6 leading-tight">
                        Crafting digital<br />experiences that matter.
                    </h2>
                    <div
                        v-if="settings.about_text"
                        class="text-[#888] text-lg leading-relaxed space-y-4 prose prose-invert max-w-none"
                        v-html="settings.about_text"
                    />
                    <a
                        v-if="settings.cv_url"
                        :href="settings.cv_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 mt-8 text-[#64ffda] font-mono text-sm hover:gap-3 transition-all duration-200"
                    >
                        Download CV
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                </div>

                <!-- Skills grid -->
                <div v-if="settings.skills?.length" class="reveal" style="transition-delay: 0.2s">
                    <p class="font-mono text-[#444] text-xs mb-5 tracking-widest uppercase">Tech Stack</p>
                    <div class="flex flex-wrap gap-3">
                        <span
                            v-for="skill in settings.skills"
                            :key="skill"
                            class="group relative border border-[#1f1f1f] rounded-lg px-4 py-2.5 text-sm font-mono text-[#888] hover:border-[#64ffda]/50 hover:text-white transition-all duration-200 cursor-default"
                        >
                            <span class="absolute inset-0 rounded-lg bg-[#64ffda]/5 opacity-0 group-hover:opacity-100 transition-opacity" />
                            <span class="relative">{{ skill }}</span>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Works -->
        <section v-if="featuredWorks.length" class="py-24 px-6 border-t border-[#1f1f1f]">
            <div class="max-w-6xl mx-auto">
                <div class="flex items-end justify-between mb-14 reveal">
                    <div>
                        <p class="font-mono text-[#64ffda] text-sm mb-3 tracking-widest uppercase">Selected Work</p>
                        <h2 class="font-display font-bold text-4xl md:text-5xl">Featured Projects</h2>
                    </div>
                    <Link href="/works" class="hidden md:inline-flex items-center gap-2 font-mono text-sm text-[#888] hover:text-[#64ffda] transition-colors group">
                        All Projects
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </Link>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
                    <div
                        v-for="(work, i) in featuredWorks"
                        :key="work.id"
                        class="reveal group relative bg-[#111] border border-[#1f1f1f] rounded-xl overflow-hidden hover:border-[#64ffda]/30 transition-all duration-300 hover:-translate-y-1 cursor-pointer"
                        :style="`transition-delay: ${i * 0.1}s`"
                        @click="openWork(work)"
                    >
                        <!-- Image -->
                        <div class="aspect-video bg-[#1a1a1a] relative overflow-hidden">
                            <img
                                v-if="work.image"
                                :src="`/assets/${work.image}`"
                                :alt="work.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <span class="font-mono text-3xl text-[#333]">{{ work.title.charAt(0) }}</span>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-[#111]/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                        </div>

                        <div class="p-6">
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span
                                    v-for="tag in work.tags?.slice(0, 4)"
                                    :key="tag"
                                    class="font-mono text-xs text-[#64ffda] bg-[#64ffda]/10 px-2 py-0.5 rounded"
                                >{{ tag }}</span>
                            </div>
                            <h3 class="font-display font-semibold text-lg text-white mb-2">{{ work.title }}</h3>
                            <p class="text-[#666] text-sm leading-relaxed overflow-hidden max-h-[2.75rem] group-hover:max-h-40 transition-[max-height] duration-300 ease-in-out">{{ work.excerpt }}</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-10 md:hidden reveal">
                    <Link href="/works" class="inline-flex items-center gap-2 font-mono text-sm text-[#64ffda]">
                        See all projects →
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA strip -->
        <section class="py-32 px-6">
            <div class="max-w-4xl mx-auto text-center reveal">
                <h2 class="font-display font-bold text-4xl md:text-6xl mb-6 leading-tight">
                    Let's build something<br />
                    <span class="text-[#64ffda]">remarkable</span> together.
                </h2>
                <p class="text-[#666] text-lg mb-10">Have a project in mind? I'd love to hear about it.</p>
                <Link
                    href="/contact"
                    class="group inline-flex items-center gap-3 bg-[#64ffda] text-[#080808] font-semibold text-lg px-10 py-5 rounded-xl hover:bg-[#64ffda]/90 transition-all duration-200 hover:-translate-y-0.5"
                >
                    Start a conversation
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </Link>
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
                    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeWork" />

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
                            <button
                                class="absolute top-4 right-4 z-20 w-9 h-9 flex items-center justify-center rounded-full bg-[#1f1f1f] text-[#888] hover:text-white hover:bg-[#2a2a2a] transition-colors"
                                @click="closeWork"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <div class="aspect-video bg-[#1a1a1a] relative overflow-hidden sm:rounded-t-2xl">
                                <img
                                    v-if="activeWork.image"
                                    :src="`/assets/${activeWork.image}`"
                                    :alt="activeWork.title"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f]">
                                    <span class="font-display font-bold text-6xl text-[#2a2a2a]">{{ activeWork.title.charAt(0) }}</span>
                                </div>
                            </div>

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
