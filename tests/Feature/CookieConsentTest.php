<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const CONTAINER_ID = 'GTM-TESTONLY';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('analytics.gtm_container_id', self::CONTAINER_ID);
    }

    public function test_tag_manager_is_not_loaded_without_consent(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // The container id itself is still exposed as a JS variable so the
        // banner can inject Tag Manager after the visitor accepts. What must
        // not happen is a request to Google before that.
        $response->assertDontSee('googletagmanager.com/gtm.js', escape: false);
    }

    public function test_tag_manager_is_not_loaded_when_consent_is_rejected(): void
    {
        $response = $this->withUnencryptedCookie('cookie_consent', 'rejected')->get('/');

        $response->assertOk();
        $response->assertDontSee('googletagmanager.com/gtm.js', escape: false);
    }

    public function test_tag_manager_loads_once_consent_is_granted(): void
    {
        $response = $this->withUnencryptedCookie('cookie_consent', 'accepted')->get('/');

        $response->assertOk();
        $response->assertSee('googletagmanager.com/gtm.js', escape: false);
        $response->assertSee(self::CONTAINER_ID, escape: false);
    }

    public function test_noscript_fallback_is_also_gated(): void
    {
        $this->get('/')->assertDontSee('googletagmanager.com/ns.html', escape: false);

        $this->withUnencryptedCookie('cookie_consent', 'accepted')
            ->get('/')
            ->assertSee('googletagmanager.com/ns.html', escape: false);
    }

    public function test_consent_defaults_to_denied_on_every_response(): void
    {
        // The Consent Mode defaults must be present even for a visitor who has
        // consented, because they run before any Google tag is loaded.
        foreach ([null, 'accepted', 'rejected'] as $consent) {
            $request = $consent === null
                ? $this
                : $this->withUnencryptedCookie('cookie_consent', $consent);

            $response = $request->get('/');

            $response->assertSee("gtag('consent', 'default'", escape: false);
            $response->assertSee("analytics_storage: 'denied'", escape: false);
        }
    }

    public function test_granting_consent_emits_an_update_signal(): void
    {
        $this->withUnencryptedCookie('cookie_consent', 'accepted')
            ->get('/')
            ->assertSee("gtag('consent', 'update'", escape: false);
    }

    public function test_no_update_signal_is_emitted_without_consent(): void
    {
        $this->get('/')->assertDontSee("gtag('consent', 'update'", escape: false);
    }

    public function test_nothing_is_emitted_when_no_container_is_configured(): void
    {
        config()->set('analytics.gtm_container_id', null);

        $this->withUnencryptedCookie('cookie_consent', 'accepted')
            ->get('/')
            ->assertDontSee('googletagmanager.com', escape: false);
    }

    public function test_consent_cookie_is_readable_when_set_by_the_browser(): void
    {
        // The browser writes this cookie in plain text. If it were not excluded
        // from Laravel's cookie encryption it would be discarded, and a visitor
        // who consented would silently never be tracked.
        $this->withUnencryptedCookie('cookie_consent', 'accepted')
            ->get('/')
            ->assertSee('googletagmanager.com/gtm.js', escape: false);
    }
}
