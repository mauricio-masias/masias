import { readonly, ref } from 'vue';
import {
    acceptConsent,
    loadTagManager,
    migrateLegacyConsent,
    readConsent,
    rejectConsent,
    reloadPage,
    type ConsentValue,
} from '@/lib/consent';

/**
 * Shared cookie consent state.
 *
 * Module scope rather than per-component, so the banner and the footer link
 * that reopens it stay in step without being passed through every layer in
 * between.
 */
const isVisible = ref(false);
const choice = ref<ConsentValue | null>(null);

export function useCookieConsent() {
    /**
     * Called once by the banner. Shows itself only to a visitor who has not
     * answered yet.
     */
    function initialise(): void {
        choice.value = migrateLegacyConsent();

        if (choice.value === null) {
            isVisible.value = true;

            return;
        }

        // A returning visitor normally gets the Tag Manager snippet from the
        // server. This covers a choice only just migrated out of localStorage.
        if (choice.value === 'accepted') {
            loadTagManager();
        }
    }

    /**
     * Reopens the banner so a choice can be changed. Withdrawing consent has
     * to be as easy as giving it.
     */
    function open(): void {
        choice.value = readConsent();
        isVisible.value = true;
    }

    function accept(): void {
        acceptConsent();
        choice.value = 'accepted';
        isVisible.value = false;
    }

    function reject(): void {
        const wasAccepted = choice.value === 'accepted';

        rejectConsent();
        choice.value = 'rejected';
        isVisible.value = false;

        // Tag Manager cannot be unloaded once it is running. Reloading returns
        // the page to a state where the server never emits it at all, so
        // withdrawal genuinely stops the tracking rather than only asking it
        // to behave.
        if (wasAccepted) {
            reloadPage();
        }
    }

    return {
        isVisible: readonly(isVisible),
        choice: readonly(choice),
        initialise,
        open,
        accept,
        reject,
    };
}
