<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4 property
    |--------------------------------------------------------------------------
    |
    | The numeric property id (Admin -> Property Settings), NOT the "G-XXXX"
    | measurement id. The measurement id is kept only so the GTM container tag
    | can be verified against the property the Data API reads from.
    |
    */

    /*
    | Which implementation backs the dashboard. "google" hits the Data API.
    | "fake" returns deterministic sample data and is opt-in only -- never a
    | silent fallback, so nobody mistakes sample numbers for real traffic.
    */
    'driver' => env('ANALYTICS_DRIVER', 'google'),

    'property_id' => env('GA4_PROPERTY_ID'),

    'measurement_id' => env('GA4_MEASUREMENT_ID'),

    /*
    | Path to the service account JSON key. Relative paths are resolved from
    | the application root by AnalyticsServiceProvider.
    */
    'credentials_path' => env(
        'GA4_CREDENTIALS_PATH',
        'storage/app/private/analytics/ga4-service-account.json',
    ),

    'gtm_container_id' => env('GTM_CONTAINER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Reporting calendar
    |--------------------------------------------------------------------------
    |
    | GA4 buckets data using the property timezone. Everything this application
    | reports is anchored to the timezone below, so "today" means the same
    | thing in every widget. Keep it in sync with the GA4 property setting or
    | the daily numbers will disagree at the day boundary.
    |
    */

    'timezone' => env('ANALYTICS_TIMEZONE', 'Europe/London'),

    /*
    | 1 = Monday. UK convention; GA4's own UI defaults to Sunday.
    */
    'week_starts_on' => (int) env('ANALYTICS_WEEK_STARTS_ON', 1),

    /*
    | Lower bound for "all time" queries. GA4 rejects dates before 2015-08-14.
    */
    'earliest_date' => env('ANALYTICS_EARLIEST_DATE', '2020-01-01'),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | The Data API allows 25,000 tokens per property per day on the standard
    | tier, and a dashboard that polls will exhaust that quickly. Periods that
    | include today still move, so they expire fast; periods that have closed
    | never change and are held far longer.
    |
    */

    'cache' => [
        'store' => env('ANALYTICS_CACHE_STORE'),
        'prefix' => 'analytics',
        'live_ttl' => (int) env('ANALYTICS_LIVE_TTL', 900),
        'historical_ttl' => (int) env('ANALYTICS_HISTORICAL_TTL', 43200),
    ],

];
