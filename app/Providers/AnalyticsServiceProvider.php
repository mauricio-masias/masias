<?php

namespace App\Providers;

use App\Services\Analytics\CachedAnalyticsProvider;
use App\Services\Analytics\Contracts\AnalyticsProvider;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use App\Services\Analytics\FakeAnalyticsProvider;
use App\Services\Analytics\GoogleAnalyticsProvider;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AnalyticsProvider::class, function (): AnalyticsProvider {
            return new CachedAnalyticsProvider(
                inner: $this->baseProvider(),
                cache: $this->app->make(CacheFactory::class)->store(config('analytics.cache.store')),
                prefix: config('analytics.cache.prefix', 'analytics'),
                liveTtl: (int) config('analytics.cache.live_ttl', 900),
                historicalTtl: (int) config('analytics.cache.historical_ttl', 43200),
            );
        });
    }

    private function baseProvider(): AnalyticsProvider
    {
        if (config('analytics.driver') === 'fake') {
            return new FakeAnalyticsProvider;
        }

        $propertyId = $this->propertyId();
        $credentials = $this->credentialsPath();

        // Checked before the catch below, which cannot tell a missing class
        // from a bad key and would otherwise blame the credentials for a
        // dependency that was never installed.
        if (! class_exists(BetaAnalyticsDataClient::class)) {
            throw AnalyticsUnavailable::notConfigured(
                'the google/analytics-data package is not installed. Run "composer install" in this environment.'
            );
        }

        try {
            $client = new BetaAnalyticsDataClient(['credentials' => $credentials]);
        } catch (AnalyticsUnavailable $e) {
            throw $e;
        } catch (Throwable $e) {
            // A malformed or wrong-type key file fails inside the Google
            // client with its own exception types. Left unconverted, those
            // escape the widgets' catch and take down the whole admin panel,
            // whose landing page is the dashboard. The wording stays neutral
            // about the cause, because this catch sees more than bad keys.
            throw AnalyticsUnavailable::notConfigured(
                "the Google Analytics client could not be created using the key at {$credentials}: {$e->getMessage()}"
            );
        }

        return new GoogleAnalyticsProvider(
            client: $client,
            propertyId: $propertyId,
        );
    }

    /**
     * The numeric GA4 property id. A measurement id ("G-XXXXXXX") is a common
     * mix-up and produces an opaque permission error from the API, so it is
     * rejected here with a message that says what to do about it.
     */
    private function propertyId(): string
    {
        $propertyId = (string) config('analytics.property_id');

        if ($propertyId === '') {
            throw AnalyticsUnavailable::notConfigured('GA4_PROPERTY_ID is not set.');
        }

        if (! ctype_digit($propertyId)) {
            throw AnalyticsUnavailable::notConfigured(
                "GA4_PROPERTY_ID must be the numeric property id from GA4 Admin -> Property Settings, got \"{$propertyId}\"."
            );
        }

        return $propertyId;
    }

    private function credentialsPath(): string
    {
        $configured = (string) config('analytics.credentials_path');

        if ($configured === '') {
            throw AnalyticsUnavailable::notConfigured('GA4_CREDENTIALS_PATH is not set.');
        }

        $path = str_starts_with($configured, '/') ? $configured : base_path($configured);

        if (! is_readable($path)) {
            throw AnalyticsUnavailable::notConfigured("the service account key at {$path} is missing or unreadable.");
        }

        return $path;
    }
}
