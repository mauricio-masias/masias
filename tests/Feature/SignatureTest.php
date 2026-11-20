<?php

namespace Tests\Feature;

use App\Models\HomeSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SignatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_layout_starts_with_the_small_pig_and_the_signature_comment(): void
    {
        HomeSetting::factory()->create();
        $pig = trim((string) file_get_contents(resource_path('js/lib/ascii-pig-small.txt')));

        $html = (string) $this->get('/')->assertOk()->getContent();

        $this->assertStringStartsWith("<!DOCTYPE html>\n<!--\n", $html);
        $this->assertStringContainsString($pig, $html);
        $this->assertMatchesRegularExpression('/Fullstack PHP Developer · React, Vue\.js, Next\.js\s*Claude AI Architecture & Agent Orchestration\s*-->\s*<html/u', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pigs(): array
    {
        return [
            'elements (small)' => ['js/lib/ascii-pig-small.txt'],
            'console' => ['js/lib/ascii-pig-console.txt'],
            'source (full)' => ['js/lib/ascii-pig.txt'],
        ];
    }

    #[DataProvider('pigs')]
    public function test_the_pig_cannot_close_the_comment_early(string $path): void
    {
        $pig = (string) file_get_contents(resource_path($path));

        $this->assertDoesNotMatchRegularExpression('/-->|<!--|--!>/', $pig);
    }

    public function test_the_elements_pig_fits_without_wrapping(): void
    {
        $lines = explode("\n", trim((string) file_get_contents(resource_path('js/lib/ascii-pig-small.txt')), "\n"));

        $this->assertLessThanOrEqual(80, max(array_map(mb_strlen(...), $lines)));
    }
}
