<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import CookieBanner from '@/Components/CookieBanner.vue';

const appEmail = (usePage().props as Record<string, unknown>).appEmail as string;

const scrolled = ref(false);
const menuOpen = ref(false);

function handleScroll() {
    scrolled.value = window.scrollY > 40;
}

onMounted(() => window.addEventListener('scroll', handleScroll));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header
            class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
            :class="scrolled ? 'bg-[#080808]/95 backdrop-blur-sm border-b border-[#1f1f1f]' : 'bg-transparent'"
        >
            <nav class="max-w-6xl mx-auto px-6 py-5 flex items-center justify-between">
                <Link href="/" class="font-display font-bold text-xl tracking-tight group flex items-center gap-2">
                    <span class="text-white">MASIAS</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#64ffda] group-hover:scale-150 transition-transform duration-300" />
                </Link>

                <!-- Desktop nav -->
                <ul class="hidden md:flex items-center gap-8 text-sm font-medium">
                    <li>
                        <Link
                            href="/"
                            class="text-[#888] hover:text-white transition-colors duration-200 hover:text-accent relative group"
                        >
                            Home
                            <span class="absolute -bottom-0.5 left-0 w-0 h-px bg-[#64ffda] group-hover:w-full transition-all duration-300" />
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="/works"
                            class="text-[#888] hover:text-white transition-colors duration-200 relative group"
                        >
                            Works
                            <span class="absolute -bottom-0.5 left-0 w-0 h-px bg-[#64ffda] group-hover:w-full transition-all duration-300" />
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="/contact"
                            class="text-[#888] hover:text-white transition-colors duration-200 relative group"
                        >
                            Contact
                            <span class="absolute -bottom-0.5 left-0 w-0 h-px bg-[#64ffda] group-hover:w-full transition-all duration-300" />
                        </Link>
                    </li>
                    <li>
                        <a
                            href="https://www.linkedin.com/in/mauriciomasias/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-[#888] hover:text-[#64ffda] transition-colors duration-200 flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 0H5C2.24 0 0 2.24 0 5v14c0 2.76 2.24 5 5 5h14c2.76 0 5-2.24 5-5V5c0-2.76-2.24-5-5-5zM8 19H5V8h3v11zM6.5 6.73c-.97 0-1.75-.79-1.75-1.76C4.75 3.99 5.53 3.2 6.5 3.2c.97 0 1.75.79 1.75 1.77 0 .97-.78 1.76-1.75 1.76zM20 19h-3v-5.6c0-3.37-4-3.12-4 0V19h-3V8h3v1.77C14.4 6.92 20 6.72 20 12.3V19z"/>
                            </svg>
                            LinkedIn
                        </a>
                    </li>
                </ul>

                <!-- Mobile hamburger -->
                <button
                    class="md:hidden text-[#888] hover:text-white transition-colors"
                    @click="menuOpen = !menuOpen"
                    aria-label="Toggle menu"
                >
                    <svg v-if="!menuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </nav>

            <!-- Mobile menu -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-4"
            >
                <div v-if="menuOpen" class="md:hidden bg-[#080808]/98 border-b border-[#1f1f1f]">
                    <ul class="max-w-6xl mx-auto px-6 py-6 flex flex-col gap-5 text-lg font-medium">
                        <li><Link href="/" @click="menuOpen = false" class="text-[#888] hover:text-white transition-colors">Home</Link></li>
                        <li><Link href="/works" @click="menuOpen = false" class="text-[#888] hover:text-white transition-colors">Works</Link></li>
                        <li><Link href="/contact" @click="menuOpen = false" class="text-[#888] hover:text-white transition-colors">Contact</Link></li>
                        <li>
                            <a href="https://www.linkedin.com/in/mauriciomasias/" target="_blank" rel="noopener noreferrer" class="text-[#888] hover:text-[#64ffda] transition-colors">
                                LinkedIn ↗
                            </a>
                        </li>
                    </ul>
                </div>
            </Transition>
        </header>

        <!-- Main content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-[#1f1f1f] py-8 px-6">
            <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-[#555]">
                <span class="flex items-center gap-2 flex-wrap justify-center sm:justify-start">
                    <span>{{ new Date().getFullYear() }} &bull; Mauricio Masias</span>
                    <span>&bull;</span>
                    <Link href="/privacy" class="hover:text-[#64ffda] transition-colors duration-200">Privacy Policy</Link>
                </span>
                <a
                    :href="`mailto:${appEmail}`"
                    class="hover:text-[#64ffda] transition-colors duration-200"
                >
                    {{ appEmail }}
                </a>
            </div>
        </footer>

        <CookieBanner />
    </div>
</template>
