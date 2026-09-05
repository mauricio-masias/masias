/**
 * Cookie consent, and the Google signals that depend on it.
 *
 * The choice lives in a cookie rather than localStorage so the server can read
 * it while rendering, and decide whether to emit the Tag Manager snippet at
 * all. Tag Manager is never injected before consent, so no request reaches
 * Google and no analytics cookie is written until the visitor opts in.
 */

export type ConsentValue = 'accepted' | 'rejected';

const COOKIE_NAME = 'cookie_consent';
const LEGACY_STORAGE_KEY = 'cookie_consent';

/**
 * Six months, after which the visitor is asked again. ICO guidance expects
 * consent to be refreshed rather than treated as permanent.
 */
const MAX_AGE_SECONDS = 60 * 60 * 24 * 180;

type ConsentState = 'granted' | 'denied';

declare global {
    interface Window {
        dataLayer?: unknown[];
        gtag?: (...args: unknown[]) => void;
        __gtmContainerId?: string | null;
    }
}

export function readConsent(): ConsentValue | null {
    const match = document.cookie.match(
        new RegExp('(?:^|; )' + COOKIE_NAME + '=([^;]*)'),
    );

    const value = match ? decodeURIComponent(match[1]) : null;

    return value === 'accepted' || value === 'rejected' ? value : null;
}

export function writeConsent(value: ConsentValue): void {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie =
        `${COOKIE_NAME}=${value}; Max-Age=${MAX_AGE_SECONDS}; Path=/; SameSite=Lax${secure}`;
}

/**
 * Earlier versions stored the choice in localStorage. Migrating it means
 * visitors who already answered are not asked a second time.
 */
export function migrateLegacyConsent(): ConsentValue | null {
    if (readConsent() !== null) {
        return readConsent();
    }

    let stored: string | null = null;

    try {
        stored = localStorage.getItem(LEGACY_STORAGE_KEY);
    } catch {
        // Storage can throw in private modes; treat it as no prior answer.
        return null;
    }

    if (stored !== 'accepted' && stored !== 'rejected') {
        return null;
    }

    writeConsent(stored);

    return stored;
}

function setGoogleConsent(state: ConsentState): void {
    window.gtag?.('consent', 'update', {
        ad_storage: state,
        ad_user_data: state,
        ad_personalization: state,
        analytics_storage: state,
    });
}

/**
 * Whether Tag Manager is already on the page.
 *
 * Checked by src as well as by id, because a snippet rendered by the server
 * must be recognised even if it were to arrive without the id. Missing it
 * would push a second `gtm.js` event, which re-fires every tag bound to
 * initialisation and counts the page view twice.
 */
function isTagManagerPresent(): boolean {
    return (
        document.getElementById('gtm-script') !== null ||
        document.querySelector('script[src*="googletagmanager.com/gtm.js"]') !== null
    );
}

/**
 * Injects Tag Manager. Safe to call more than once; the second call is a
 * no-op, which matters because the server already emits the snippet for
 * visitors who consented on an earlier visit.
 */
export function loadTagManager(): void {
    const containerId = window.__gtmContainerId;

    if (!containerId || isTagManagerPresent()) {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });

    const script = document.createElement('script');
    script.id = 'gtm-script';
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(containerId)}`;

    document.head.appendChild(script);
}

/**
 * Expires a cookie across the domains it might have been written on.
 *
 * Analytics cookies are set on the registrable domain rather than the exact
 * host, and a cookie can only be removed by matching the domain and path it
 * was created with, so each candidate is tried in turn.
 */
function expireCookie(name: string): void {
    const host = window.location.hostname;
    const domains = new Set<string | null>([null, host, `.${host}`]);
    const labels = host.split('.');

    for (let i = 1; i < labels.length - 1; i++) {
        domains.add(`.${labels.slice(i).join('.')}`);
    }

    domains.forEach((domain) => {
        const scope = domain ? `; Domain=${domain}` : '';
        document.cookie = `${name}=; Max-Age=0; Path=/${scope}`;
    });
}

/**
 * Removes the identifiers Google has already stored on this device.
 *
 * Withdrawing consent has to undo what consent allowed. Denying future storage
 * while leaving the existing visitor id in place would keep the visitor
 * identifiable on their next visit.
 */
export function clearAnalyticsCookies(): void {
    document.cookie
        .split(';')
        .map((cookie) => cookie.split('=')[0].trim())
        .filter((name) => /^(_ga|_gid|_gat)/.test(name))
        .forEach(expireCookie);
}

/**
 * Reloading the page, behind a function so it can be observed.
 *
 * jsdom makes window.location.reload impossible to replace, so a caller that
 * invokes it directly cannot be tested at all.
 */
export function reloadPage(): void {
    window.location.reload();
}

export function acceptConsent(): void {
    writeConsent('accepted');
    setGoogleConsent('granted');

    // Kept for any Tag Manager trigger already configured against this event.
    window.dataLayer?.push({ event: 'cookie_consent_accepted' });

    loadTagManager();
}

export function rejectConsent(): void {
    writeConsent('rejected');
    setGoogleConsent('denied');
    clearAnalyticsCookies();
    window.dataLayer?.push({ event: 'cookie_consent_rejected' });
}
