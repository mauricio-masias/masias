<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useReveal } from '@/composables/useReveal';

useReveal();

const page = usePage();
const submitted = computed(() => page.props.flash?.success === true);
const appEmail = (page.props as Record<string, unknown>).appEmail as string;

const form = useForm({
    name: '',
    email: '',
    message: '',
    website: '', // honeypot — must remain empty
});

const sending = ref(false);
const formRef = ref<HTMLFormElement | null>(null);

function submit() {
    sending.value = true;
    setTimeout(() => {
        form.post('/contact', {
            onError: () => {
                sending.value = false;
            },
        });
    }, 1200); // let animation play first
}
</script>

<template>
    <Head title="Contact" />
    <AppLayout>
        <section class="min-h-screen pt-36 pb-24 px-6">
            <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-20 items-start">
                <!-- Left — info -->
                <div>
                    <p class="font-mono text-[#64ffda] text-sm mb-4 tracking-widest uppercase reveal">Get in touch</p>
                    <h1 class="font-display font-bold leading-tight mb-8 reveal" style="font-size: clamp(2.5rem, 6vw, 5rem); transition-delay: 0.1s">
                        Let's work<br />together.
                    </h1>
                    <p class="text-[#666] text-lg leading-relaxed mb-10 reveal" style="transition-delay: 0.15s">
                        Whether you have a project in mind, want to discuss an opportunity, or just want to say hi — my inbox is always open.
                    </p>

                    <div class="space-y-5 reveal" style="transition-delay: 0.2s">
                        <a
                            :href="`mailto:${appEmail}`"
                            class="flex items-center gap-4 group"
                        >
                            <div class="w-10 h-10 rounded-xl border border-[#1f1f1f] flex items-center justify-center text-[#555] group-hover:border-[#64ffda]/50 group-hover:text-[#64ffda] transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-mono text-[#444] mb-0.5 uppercase tracking-wider">Email</p>
                                <p class="text-[#888] group-hover:text-white transition-colors">{{ appEmail }}</p>
                            </div>
                        </a>

                        <a
                            href="https://www.linkedin.com/in/mauriciomasias/"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-4 group"
                        >
                            <div class="w-10 h-10 rounded-xl border border-[#1f1f1f] flex items-center justify-center text-[#555] group-hover:border-[#64ffda]/50 group-hover:text-[#64ffda] transition-all duration-200">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 0H5C2.24 0 0 2.24 0 5v14c0 2.76 2.24 5 5 5h14c2.76 0 5-2.24 5-5V5c0-2.76-2.24-5-5-5zM8 19H5V8h3v11zM6.5 6.73c-.97 0-1.75-.79-1.75-1.76C4.75 3.99 5.53 3.2 6.5 3.2c.97 0 1.75.79 1.75 1.77 0 .97-.78 1.76-1.75 1.76zM20 19h-3v-5.6c0-3.37-4-3.12-4 0V19h-3V8h3v1.77C14.4 6.92 20 6.72 20 12.3V19z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-mono text-[#444] mb-0.5 uppercase tracking-wider">LinkedIn</p>
                                <p class="text-[#888] group-hover:text-white transition-colors">mauriciomasias</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Right — form / success -->
                <div class="reveal" style="transition-delay: 0.25s">
                    <!-- Thank you state -->
                    <Transition
                        enter-active-class="thankyou-appear"
                        enter-from-class="opacity-0"
                    >
                        <div v-if="submitted" class="text-center py-20">
                            <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-[#64ffda]/10 border border-[#64ffda]/30 flex items-center justify-center">
                                <svg class="w-10 h-10 text-[#64ffda]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <h3 class="font-display font-bold text-2xl text-white mb-3">Message sent!</h3>
                            <p class="text-[#666]">Thanks for reaching out. I'll get back to you soon.</p>
                        </div>

                        <!-- Form state -->
                        <form
                            v-else
                            ref="formRef"
                            class="space-y-6"
                            :class="{ 'envelope-sending': sending }"
                            @submit.prevent="submit"
                        >
                            <div>
                                <label class="block text-sm font-mono text-[#555] uppercase tracking-wider mb-2">Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Your name"
                                    required
                                    class="w-full bg-[#111] border rounded-xl px-5 py-4 text-white placeholder-[#444] focus:outline-none focus:ring-1 transition-all duration-200"
                                    :class="form.errors.name ? 'border-red-500 focus:ring-red-500' : 'border-[#1f1f1f] focus:border-[#64ffda]/50 focus:ring-[#64ffda]/30'"
                                />
                                <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-400">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-mono text-[#555] uppercase tracking-wider mb-2">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="your@email.com"
                                    required
                                    class="w-full bg-[#111] border rounded-xl px-5 py-4 text-white placeholder-[#444] focus:outline-none focus:ring-1 transition-all duration-200"
                                    :class="form.errors.email ? 'border-red-500 focus:ring-red-500' : 'border-[#1f1f1f] focus:border-[#64ffda]/50 focus:ring-[#64ffda]/30'"
                                />
                                <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-400">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-mono text-[#555] uppercase tracking-wider mb-2">Message</label>
                                <textarea
                                    v-model="form.message"
                                    placeholder="Tell me about your project…"
                                    rows="6"
                                    required
                                    class="w-full bg-[#111] border rounded-xl px-5 py-4 text-white placeholder-[#444] focus:outline-none focus:ring-1 transition-all duration-200 resize-none"
                                    :class="form.errors.message ? 'border-red-500 focus:ring-red-500' : 'border-[#1f1f1f] focus:border-[#64ffda]/50 focus:ring-[#64ffda]/30'"
                                />
                                <p v-if="form.errors.message" class="mt-1.5 text-sm text-red-400">{{ form.errors.message }}</p>
                            </div>

                            <!-- Honeypot: hidden from humans, bots fill it -->
                            <div style="position:absolute;left:-9999px;" aria-hidden="true">
                                <input
                                    v-model="form.website"
                                    type="text"
                                    name="website"
                                    tabindex="-1"
                                    autocomplete="off"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing || sending"
                                class="w-full group inline-flex items-center justify-center gap-3 bg-[#64ffda] text-[#080808] font-semibold text-base px-8 py-4 rounded-xl hover:bg-[#64ffda]/90 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <template v-if="sending">
                                    <!-- Envelope SVG animating -->
                                    <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    Sending…
                                </template>
                                <template v-else>
                                    Send Message
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </template>
                            </button>
                        </form>
                    </Transition>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
