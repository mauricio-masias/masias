<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const visible = ref(false);

function pushConsent(accepted: boolean) {
    (window as any).dataLayer = (window as any).dataLayer || [];
    (window as any).dataLayer.push({
        event: accepted ? 'cookie_consent_accepted' : 'cookie_consent_rejected',
    });
}

onMounted(() => {
    const stored = localStorage.getItem('cookie_consent');
    if (stored === 'accepted') {
        pushConsent(true);
    } else if (!stored) {
        visible.value = true;
    }
});

function accept() {
    localStorage.setItem('cookie_consent', 'accepted');
    pushConsent(true);
    visible.value = false;
}

function reject() {
    localStorage.setItem('cookie_consent', 'rejected');
    pushConsent(false);
    visible.value = false;
}
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="opacity-0 translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-4"
    >
        <div
            v-if="visible"
            class="fixed bottom-0 left-0 right-0 z-50 border-t border-[#1f1f1f] bg-[#0d0d0d]/95 backdrop-blur-sm"
        >
            <div class="max-w-6xl mx-auto px-6 py-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                <div>
                    <p class="text-sm text-[#888] leading-relaxed">
                        This site uses essential cookies to function and, with your consent, analytics cookies via Google Tag Manager to understand how the site is used. No personal data is stored in analytics. 
                        <Link href="/privacy" class="text-xs font-mono text-[#64ffda] hover:underline mt-1 inline-block">
                            Privacy &amp; Cookie Policy
                        </Link>
                    </p>
                    
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <button
                        class="font-mono text-xs text-[#444] hover:text-white border border-[#1f1f1f] hover:border-[#333] px-4 py-2 transition-colors duration-200 cursor-pointer"
                        @click="reject"
                    >
                        Reject
                    </button>
                    <button
                        class="font-mono text-xs text-[#080808] bg-[#64ffda] hover:bg-[#64ffdacc] px-4 py-2 transition-colors duration-200 cursor-pointer font-semibold"
                        @click="accept"
                    >
                        Accept
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
