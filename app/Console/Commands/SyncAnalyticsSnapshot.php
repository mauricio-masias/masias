<?php

namespace App\Console\Commands;

use App\Services\Analytics\Data\Period;
use App\Services\Analytics\Exceptions\AnalyticsUnavailable;
use App\Services\Analytics\Snapshot\SnapshotWriter;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SyncAnalyticsSnapshot extends Command
{
    protected $signature = 'analytics:sync
                            {--from= : First date to sync (Y-m-d), defaults to 35 days ago}
                            {--to= : Last date to sync (Y-m-d), defaults to today}
                            {--chunk=90 : Days per request when backfilling a long range}';

    protected $description = 'Copy Google Analytics totals into the local archive';

    /**
     * The default window reaches back further than a single day on purpose.
     * GA4 keeps adjusting recent figures for a day or two after the fact, and
     * a missed run should heal itself rather than leave a permanent hole.
     */
    private const DEFAULT_LOOKBACK_DAYS = 35;

    public function handle(SnapshotWriter $writer): int
    {
        $timezone = config('analytics.timezone');

        $to = $this->option('to')
            ? CarbonImmutable::parse($this->option('to'), $timezone)
            : CarbonImmutable::now($timezone);

        $from = $this->option('from')
            ? CarbonImmutable::parse($this->option('from'), $timezone)
            : $to->subDays(self::DEFAULT_LOOKBACK_DAYS);

        if ($from->greaterThan($to)) {
            $this->error('--from must not be after --to.');

            return self::FAILURE;
        }

        $chunk = max((int) $this->option('chunk'), 1);

        $this->info("Syncing {$from->toDateString()} to {$to->toDateString()}...");

        $buckets = 0;
        $geoRows = 0;
        $reports = 0;
        $cursor = $from;

        // Long backfills are split up so one request never asks Google for
        // years of daily rows, which risks hitting the response row limit.
        while ($cursor->lessThanOrEqualTo($to)) {
            $chunkEnd = $cursor->addDays($chunk - 1);

            if ($chunkEnd->greaterThan($to)) {
                $chunkEnd = $to;
            }

            try {
                $result = $writer->sync(Period::make($cursor, $chunkEnd));
            } catch (AnalyticsUnavailable $e) {
                $this->error("Failed at {$cursor->toDateString()}: {$e->getMessage()}");

                return self::FAILURE;
            }

            $buckets += $result->buckets;
            $geoRows += $result->geoRows;
            $reports += $result->reports;

            $this->line("  {$cursor->toDateString()} to {$chunkEnd->toDateString()}: {$result->buckets} buckets, {$result->geoRows} geo rows");

            $cursor = $chunkEnd->addDay();
        }

        $this->info("Done. {$buckets} buckets, {$geoRows} geo rows, {$reports} API reports.");

        return self::SUCCESS;
    }
}
