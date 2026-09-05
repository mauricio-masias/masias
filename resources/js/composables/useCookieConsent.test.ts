import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reloadPage } from '@/lib/consent';

// Only the reload is replaced; every other consent function runs for real, so
// the cookies these tests assert on are written by the production code.
vi.mock('@/lib/consent', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/lib/consent')>()),
    reloadPage: vi.fn(),
}));

type Consent = typeof import('./useCookieConsent');

/**
 * The composable keeps its state at module scope so the banner and the footer
 * link share it. Tests therefore load a fresh copy each time, or one test's
 * choice would leak into the next.
 */
async function freshModule(): Promise<Consent> {
    vi.resetModules();

    return import('./useCookieConsent');
}

function clearAllCookies(): void {
    document.cookie
        .split(';')
        .map((cookie) => cookie.split('=')[0].trim())
        .filter(Boolean)
        .forEach((name) => {
            document.cookie = `${name}=; Max-Age=0; Path=/`;
        });
}

beforeEach(() => {
    clearAllCookies();
    localStorage.clear();
    document
        .querySelectorAll('script[src*="googletagmanager.com/gtm.js"], script#gtm-script')
        .forEach((script) => script.remove());

    window.dataLayer = [];
    window.gtag = function () {
        window.dataLayer?.push(arguments);
    };
    window.__gtmContainerId = 'GTM-TESTONLY';

    vi.mocked(reloadPage).mockClear();
});

describe('initialise', () => {
    it('asks a visitor who has not answered', async () => {
        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();

        expect(consent.isVisible.value).toBe(true);
        expect(consent.choice.value).toBeNull();
    });

    it('stays out of the way for a visitor who accepted', async () => {
        document.cookie = 'cookie_consent=accepted; Path=/';

        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();

        expect(consent.isVisible.value).toBe(false);
        expect(consent.choice.value).toBe('accepted');
    });

    it('does not re-initialise Tag Manager for a returning visitor', async () => {
        // Blade already emitted the snippet for this visitor. Calling
        // loadTagManager again would double-count their page view.
        document.cookie = 'cookie_consent=accepted; Path=/';

        const serverRendered = document.createElement('script');
        serverRendered.id = 'gtm-script';
        serverRendered.src = 'https://www.googletagmanager.com/gtm.js?id=GTM-TESTONLY';
        document.head.appendChild(serverRendered);
        window.dataLayer = [{ 'gtm.start': Date.now(), event: 'gtm.js' }];

        const { useCookieConsent } = await freshModule();

        useCookieConsent().initialise();

        expect(document.querySelectorAll('script[src*="googletagmanager.com/gtm.js"]')).toHaveLength(1);
        expect(window.dataLayer?.filter((e) => (e as { event?: string }).event === 'gtm.js')).toHaveLength(1);
    });

    it('stays out of the way for a visitor who declined, and loads nothing', async () => {
        document.cookie = 'cookie_consent=rejected; Path=/';

        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();

        expect(consent.isVisible.value).toBe(false);
        expect(document.getElementById('gtm-script')).toBeNull();
    });

    it('does not re-ask someone whose answer predates the cookie', async () => {
        localStorage.setItem('cookie_consent', 'accepted');

        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();

        expect(consent.isVisible.value).toBe(false);
        expect(document.cookie).toContain('cookie_consent=accepted');
        // The server did not emit the snippet for that visitor, so the
        // migrated choice has to load it here.
        expect(document.getElementById('gtm-script')).not.toBeNull();
    });
});

describe('open', () => {
    it('reopens the banner so a choice can be changed', async () => {
        document.cookie = 'cookie_consent=accepted; Path=/';

        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();
        consent.open();

        expect(consent.isVisible.value).toBe(true);
        expect(consent.choice.value).toBe('accepted');
    });

    it('reflects a choice made since the page loaded', async () => {
        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();
        document.cookie = 'cookie_consent=rejected; Path=/';
        consent.open();

        expect(consent.choice.value).toBe('rejected');
    });
});

describe('accept', () => {
    it('records the choice, hides the banner and loads Tag Manager', async () => {
        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();
        consent.accept();

        expect(consent.isVisible.value).toBe(false);
        expect(consent.choice.value).toBe('accepted');
        expect(document.cookie).toContain('cookie_consent=accepted');
        expect(document.getElementById('gtm-script')).not.toBeNull();
        expect(reloadPage).not.toHaveBeenCalled();
    });
});

describe('reject', () => {
    it('does not reload when nothing was ever loaded', async () => {
        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();
        consent.reject();

        expect(consent.choice.value).toBe('rejected');
        expect(reloadPage).not.toHaveBeenCalled();
    });

    it('reloads when withdrawing, so Tag Manager is genuinely gone', async () => {
        document.cookie = 'cookie_consent=accepted; Path=/';

        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();
        consent.open();
        consent.reject();

        // Tag Manager cannot be unloaded once running; only a reload returns
        // the page to a state where the server never emits it.
        expect(reloadPage).toHaveBeenCalledOnce();
        expect(document.cookie).toContain('cookie_consent=rejected');
    });

    it('clears identifiers Google already stored when withdrawing', async () => {
        document.cookie = 'cookie_consent=accepted; Path=/';
        document.cookie = '_ga=GA1.1.99.99; Path=/';

        const { useCookieConsent } = await freshModule();
        const consent = useCookieConsent();

        consent.initialise();
        consent.open();
        consent.reject();

        expect(document.cookie).not.toContain('_ga=');
    });
});

describe('shared state', () => {
    it('keeps the footer link and the banner in step', async () => {
        const { useCookieConsent } = await freshModule();

        const banner = useCookieConsent();
        const footerLink = useCookieConsent();

        banner.initialise();
        expect(banner.isVisible.value).toBe(true);

        banner.accept();
        expect(footerLink.isVisible.value).toBe(false);

        // The footer button must be able to reopen the banner the visitor
        // already dismissed.
        footerLink.open();
        expect(banner.isVisible.value).toBe(true);
    });
});
