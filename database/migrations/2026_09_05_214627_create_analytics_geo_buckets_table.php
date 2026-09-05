<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Country and city totals per bucket.
     *
     * Both country totals and city rows live here, told apart by "level".
     * Without that column a country-total row and a row whose city GA4
     * withheld would collide, because both carry an empty city.
     *
     * City is an empty string rather than null when unknown, so the unique key
     * stays usable: MySQL treats every null as distinct and would let
     * duplicate rows through.
     */
    public function up(): void
    {
        Schema::create('analytics_geo_buckets', function (Blueprint $table) {
            $table->id();
            $table->string('granularity', 16);
            $table->date('bucket_start');
            $table->string('level', 8);
            // Wider than an ISO code needs, because GA4 reports unresolved
            // geography as the literal string "(not set)".
            $table->string('country_code', 16)->default('');
            $table->string('country', 128)->default('');
            $table->string('city', 128)->default('');
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->timestamps();

            $table->unique(['granularity', 'bucket_start', 'level', 'country_code', 'city'], 'analytics_geo_buckets_unique');
            $table->index(['granularity', 'bucket_start', 'level', 'visitors'], 'analytics_geo_buckets_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_geo_buckets');
    }
};
