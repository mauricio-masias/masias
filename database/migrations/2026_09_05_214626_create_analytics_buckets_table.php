<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A local archive of Google Analytics totals.
     *
     * GA4 discards event-level data once its retention window passes, so
     * anything not copied here is gone permanently. Each row is one bucket as
     * Google reported it at that granularity: visitor counts are de-duplicated
     * within a bucket and cannot be rebuilt by summing smaller buckets, which
     * is why weeks and months are stored rather than derived from days.
     */
    public function up(): void
    {
        Schema::create('analytics_buckets', function (Blueprint $table) {
            $table->id();
            $table->string('granularity', 16);
            $table->date('bucket_start');
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('new_visitors')->default(0);
            $table->unsignedInteger('sessions')->default(0);
            $table->unsignedInteger('page_views')->default(0);
            $table->decimal('average_session_duration', 10, 2)->default(0);
            $table->decimal('engagement_rate', 6, 5)->default(0);
            $table->timestamps();

            $table->unique(['granularity', 'bucket_start']);
            $table->index(['granularity', 'bucket_start', 'visitors']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_buckets');
    }
};
