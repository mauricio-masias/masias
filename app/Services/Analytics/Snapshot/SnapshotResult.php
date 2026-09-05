<?php

namespace App\Services\Analytics\Snapshot;

/**
 * What one snapshot run wrote, for command output and tests.
 */
final readonly class SnapshotResult
{
    public function __construct(
        public int $buckets = 0,
        public int $geoRows = 0,
        public int $reports = 0,
    ) {}

    public function add(int $buckets = 0, int $geoRows = 0, int $reports = 0): self
    {
        return new self(
            buckets: $this->buckets + $buckets,
            geoRows: $this->geoRows + $geoRows,
            reports: $this->reports + $reports,
        );
    }
}
