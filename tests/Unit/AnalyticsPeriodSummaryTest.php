<?php

namespace Tests\Unit;

use App\Services\Analytics\Data\PeriodSummary;
use Tests\TestCase;

class AnalyticsPeriodSummaryTest extends TestCase
{
    public function test_percentage_change_reports_growth(): void
    {
        $this->assertSame(50.0, PeriodSummary::percentageChange(150, 100));
    }

    public function test_percentage_change_reports_decline(): void
    {
        $this->assertSame(-25.0, PeriodSummary::percentageChange(75, 100));
    }

    public function test_percentage_change_is_null_without_a_baseline(): void
    {
        $this->assertNull(PeriodSummary::percentageChange(40, 0));
    }

    public function test_returning_visitors_never_goes_negative(): void
    {
        $summary = new PeriodSummary(
            visitors: 10,
            newVisitors: 14,
            sessions: 20,
            pageViews: 40,
            averageSessionDuration: 12.0,
            engagementRate: 0.5,
        );

        $this->assertSame(0, $summary->returningVisitors());
    }

    public function test_empty_summary_is_all_zero(): void
    {
        $summary = PeriodSummary::empty();

        $this->assertSame(0, $summary->visitors);
        $this->assertSame(0, $summary->sessions);
        $this->assertSame(0.0, $summary->engagementRate);
    }
}
