<?php

namespace Tests\Unit;

use App\Services\Analytics\Data\GeoRow;
use Tests\TestCase;

class AnalyticsGeoRowTest extends TestCase
{
    public function test_country_row_labels_with_the_country(): void
    {
        $row = new GeoRow('GB', 'United Kingdom', null, 10, 12);

        $this->assertSame('United Kingdom', $row->label());
        $this->assertSame('GB', $row->isoCode());
    }

    public function test_city_row_labels_with_city_and_country(): void
    {
        $row = new GeoRow('FR', 'France', 'Lille', 2, 2);

        $this->assertSame('Lille, France', $row->label());
    }

    public function test_empty_geography_labels_as_unknown(): void
    {
        $row = new GeoRow('', '', '', 2, 2);

        $this->assertSame('Unknown', $row->label());
        $this->assertNull($row->isoCode());
        $this->assertNull($row->cityLabel());
    }

    public function test_not_set_city_falls_back_to_the_country(): void
    {
        $row = new GeoRow('FR', 'France', '(not set)', 2, 2);

        $this->assertSame('France', $row->label());
        $this->assertFalse($row->hasKnownCity());
        $this->assertTrue($row->hasKnownCountry());
    }

    public function test_known_city_in_an_unknown_country_labels_with_the_city_alone(): void
    {
        $row = new GeoRow('', '(not set)', 'Lille', 2, 2);

        $this->assertSame('Lille', $row->label());
    }

    public function test_other_bucket_is_treated_as_unresolved(): void
    {
        $row = new GeoRow('', '(other)', '(other)', 5, 5);

        $this->assertSame('Unknown', $row->label());
    }

    public function test_iso_code_rejects_malformed_values(): void
    {
        $this->assertNull((new GeoRow('gb', 'United Kingdom', null, 1, 1))->isoCode());
        $this->assertNull((new GeoRow('GBR', 'United Kingdom', null, 1, 1))->isoCode());
    }
}
