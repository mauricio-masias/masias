<?php

namespace App\Services\Analytics\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when analytics data cannot be retrieved.
 *
 * Widgets catch this and render an unavailable state. An outage at Google, a
 * revoked service account, or an exhausted quota must never take the whole
 * admin dashboard down with it.
 */
class AnalyticsUnavailable extends RuntimeException
{
    public static function requestFailed(string $report, Throwable $previous): self
    {
        return new self("Failed to fetch the \"{$report}\" analytics report: {$previous->getMessage()}", 0, $previous);
    }

    public static function notConfigured(string $detail): self
    {
        return new self("Analytics is not configured correctly: {$detail}");
    }
}
