<?php

namespace App\Models;

use App\Services\Analytics\Data\Granularity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One archived Google Analytics bucket.
 *
 * @see \App\Services\Analytics\Snapshot\SnapshotWriter
 */
class AnalyticsBucket extends Model
{
    protected $fillable = [
        'granularity',
        'bucket_start',
        'visitors',
        'new_visitors',
        'sessions',
        'page_views',
        'average_session_duration',
        'engagement_rate',
    ];

    protected function casts(): array
    {
        return [
            'granularity' => Granularity::class,
            'bucket_start' => 'immutable_date',
            'visitors' => 'integer',
            'new_visitors' => 'integer',
            'sessions' => 'integer',
            'page_views' => 'integer',
            'average_session_duration' => 'float',
            'engagement_rate' => 'float',
        ];
    }

    public function scopeGranularity(Builder $query, Granularity $granularity): Builder
    {
        return $query->where('granularity', $granularity->value);
    }

    public function scopeBetween(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('bucket_start', [$start, $end]);
    }
}
