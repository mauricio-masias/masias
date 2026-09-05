import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    acceptConsent,
    clearAnalyticsCookies,
    loadTagManager,
    migrateLegacyConsent,
    readConsent,
    rejectConsent,
    writeConsent,
} from './consent';

function clearAllCookies(): void {
    document.cookie
        .split(';')
        .map((cookie) => cookie.split('=')[0].trim())
        .filter(Boolean)
        .forEach((name) => {
            document.cookie = `${name}=; Max-Age=0; Path=/`;
        });
}

function consentCalls(): unknown[][] {
    return (window.dataLayer ?? []).filter(
        (entry): entry is IArguments => typeof entry === 'object' && entry !== null && 'length' in entry,
    ).map((entry) => Array.from(entry as ArrayLike<unknown>));
}

beforeEach(() => {
    clearAllCookies();
    localStorage.clear();
    document
        .querySelectorAll('script[src*="googletagmanager.com/gtm.js"], script#gtm-script')
        .forEach((script) => script.remove());

    window.dataLayer = [];
    window.gtag = function (...args: unknown[]) {
        // eslint-disable-next-line prefer-rest-params
        window.dataLayer?.push(arguments);
        void args;
    };
    window.__gtmContainerId = 'GTM-TESTONLY';
});

describe('readConsent', () => {
    it('returns null when the visitor has not answered', () => {
        expect(readConsent()).toBeNull();
    });

    it('reads a recorded choice', () => {
        document.cookie = 'cookie_consent=accepted; Path=/';

        expect(readConsent()).toBe('accepted');
    });

    it('ignores a value it does not recognise', () => {
        // A tampered or stale cookie must not be treated as consent.
        document.cookie = 'cookie_consent=yes-please; Path=/';

        expect(readConsent()).toBeNull();
    });

    it('is not confused by other cookies sharing a prefix', () => {
        document.cookie = 'other_cookie_consent=accepted; Path=/';

        expect(readConsent()).toBeNull();
    });
});

describe('writeConsent', () => {
    it('records the choice', () => {
        writeConsent('rejected');

        expect(readConsent()).toBe('rejected');
    });

    it('marks the cookie Secure over https', () => {
        const setter = vi.spyOn(document, 'cookie', 'set');

        writeConsent('accepted');

        expect(setter.mock.calls[0][0]).toContain('Secure');
        expect(setter.mock.calls[0][0]).toContain('SameSite=Lax');
    });

    it('expires so consent is asked for again rather than kept forever', () => {
        const setter = vi.spyOn(document, 'cookie', 'set');

        writeConsent('accepted');

        // Six months, per ICO guidance that consent is refreshed.
        expect(setter.mock.calls[0][0]).toContain(`Max-Age=${60 * 60 * 24 * 180}`);
    });
});

describe('migrateLegacyConsent', () => {
    it('moves an older localStorage answer into the cookie', () => {
        localStorage.setItem('cookie_consent', 'accepted');

        expect(migrateLegacyConsent()).toBe('accepted');
        expect(readConsent()).toBe('accepted');
    });

    it('prefers an existing cookie over localStorage', () => {
        document.cookie = 'cookie_consent=rejected; Path=/';
        localStorage.setItem('cookie_consent', 'accepted');

        expect(migrateLegacyConsent()).toBe('rejected');
    });

    it('returns null when nothing was ever stored', () => {
        expect(migrateLegacyConsent()).toBeNull();
        expect(readConsent()).toBeNull();
    });

    it('ignores an unrecognised localStorage value', () => {
        localStorage.setItem('cookie_consent', 'true');

        expect(migrateLegacyConsent()).toBeNull();
    });

    it('treats unreadable storage as no prior answer', () => {
        // Private browsing modes throw rather than returning null.
        vi.spyOn(localStorage, 'getItem').mockImplementation(() => {
            throw new Error('access denied');
        });

        expect(migrateLegacyConsent()).toBeNull();
    });
});

describe('loadTagManager', () => {
    it('injects the container script', () => {
        loadTagManager();

        const script = document.getElementById('gtm-script') as HTMLScriptElement | null;

        expect(script).not.toBeNull();
        expect(script?.src).toContain('googletagmanager.com/gtm.js?id=GTM-TESTONLY');
    });

    it('does nothing without a configured container', () => {
        window.__gtmContainerId = null;

        loadTagManager();

        expect(document.getElementById('gtm-script')).toBeNull();
    });

    it('does not re-initialise a container the server already rendered', () => {
        // The server-side snippet is what a returning visitor actually gets.
        // Missing it pushes a second gtm.js event, which re-fires every tag
        // bound to initialisation and counts the page view twice.
        const serverRendered = document.createElement('script');
        serverRendered.id = 'gtm-script';
        serverRendered.async = true;
        serverRendered.src = 'https://www.googletagmanager.com/gtm.js?id=GTM-TESTONLY';
        document.head.appendChild(serverRendered);
        window.dataLayer = [{ 'gtm.start': Date.now(), event: 'gtm.js' }];

        loadTagManager();

        expect(document.querySelectorAll('script[src*="googletagmanager.com/gtm.js"]')).toHaveLength(1);
        expect(window.dataLayer?.filter((e) => (e as { event?: string }).event === 'gtm.js')).toHaveLength(1);
    });

    it('recognises a container script that carries no id', () => {
        const untagged = document.createElement('script');
        untagged.src = 'https://www.googletagmanager.com/gtm.js?id=GTM-TESTONLY';
        document.head.appendChild(untagged);

        loadTagManager();

        expect(document.querySelectorAll('script[src*="googletagmanager.com/gtm.js"]')).toHaveLength(1);

        untagged.remove();
    });

    it('never injects twice', () => {
        // The server already emits the snippet for a returning visitor, so a
        // second call must not start a duplicate container.
        loadTagManager();
        loadTagManager();

        expect(document.querySelectorAll('script#gtm-script')).toHaveLength(1);
    });
});

describe('acceptConsent', () => {
    it('records the choice and loads Tag Manager', () => {
        acceptConsent();

        expect(readConsent()).toBe('accepted');
        expect(document.getElementById('gtm-script')).not.toBeNull();
    });

    it('grants every Google consent signal', () => {
        acceptConsent();

        const update = consentCalls().find((call) => call[0] === 'consent' && call[1] === 'update');

        expect(update?.[2]).toEqual({
            ad_storage: 'granted',
            ad_user_data: 'granted',
            ad_personalization: 'granted',
            analytics_storage: 'granted',
        });
    });

    it('still emits the legacy dataLayer event', () => {
        acceptConsent();

        // A Tag Manager trigger may already depend on this event.
        expect(window.dataLayer).toContainEqual({ event: 'cookie_consent_accepted' });
    });
});

describe('rejectConsent', () => {
    it('records the choice without loading Tag Manager', () => {
        rejectConsent();

        expect(readConsent()).toBe('rejected');
        expect(document.getElementById('gtm-script')).toBeNull();
    });

    it('denies every Google consent signal', () => {
        rejectConsent();

        const update = consentCalls().find((call) => call[0] === 'consent' && call[1] === 'update');

        expect(update?.[2]).toEqual({
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
        });
    });

    it('removes identifiers Google already stored', () => {
        document.cookie = '_ga=GA1.1.12345.67890; Path=/';
        document.cookie = '_ga_ABC123=GS1.1.foo; Path=/';

        rejectConsent();

        // Withdrawal has to undo what consent allowed, not merely stop more.
        expect(document.cookie).not.toContain('_ga=');
        expect(document.cookie).not.toContain('_ga_ABC123');
    });
});

describe('clearAnalyticsCookies', () => {
    it('removes Google analytics cookies', () => {
        document.cookie = '_ga=GA1.1.1.1; Path=/';
        document.cookie = '_gid=GA1.1.2.2; Path=/';
        document.cookie = '_gat_UA=1; Path=/';

        clearAnalyticsCookies();

        expect(document.cookie).not.toContain('_ga=');
        expect(document.cookie).not.toContain('_gid');
        expect(document.cookie).not.toContain('_gat_UA');
    });

    it('leaves unrelated cookies alone', () => {
        document.cookie = 'cookie_consent=rejected; Path=/';
        document.cookie = 'XSRF-TOKEN=abc; Path=/';

        clearAnalyticsCookies();

        expect(readConsent()).toBe('rejected');
        expect(document.cookie).toContain('XSRF-TOKEN');
    });
});
