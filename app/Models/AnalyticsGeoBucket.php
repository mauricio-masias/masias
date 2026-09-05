<?php

namespace App\Models;

use App\Services\Analytics\Data\Granularity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Country and city totals for one archived bucket.
 */
class AnalyticsGeoBucket extends Model
{
    protected $fillable = [
        'granularity',
        'bucket_start',
        'country_code',
        'country',
        'city',
        'visitors',
        'sessions',
    ];

    protected function casts(): array
    {
        return [
            'granularity' => Granularity::class,
            'bucket_start' => 'immutable_date',
            'visitors' => 'integer',
            'sessions' => 'integer',
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
