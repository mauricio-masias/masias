<?php

namespace App\Services\Analytics\Data;

/**
 * A country or city row.
 *
 * GA4 withholds geography it cannot resolve, or that would identify too small
 * a group of users, and reports it as an empty string or the literal
 * "(not set)". Those rows are kept rather than dropped so the totals still
 * add up to the headline visitor count, but they are labelled as unknown
 * instead of being rendered as stray punctuation.
 */
final readonly class GeoRow
{
    private const UNRESOLVED = ['', '(not set)', '(other)'];

    public function __construct(
        public string $countryCode,
        public string $country,
        public ?string $city,
        public int $visitors,
        public int $sessions,
    ) {}

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return [
            'country_code' => $this->countryCode,
            'country' => $this->country,
            'city' => $this->city,
            'visitors' => $this->visitors,
            'sessions' => $this->sessions,
        ];
    }

    /**
     * @param  array<string, string|int|null>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: (string) ($data['country_code'] ?? ''),
            country: (string) ($data['country'] ?? ''),
            city: $data['city'] === null ? null : (string) $data['city'],
            visitors: (int) $data['visitors'],
            sessions: (int) $data['sessions'],
        );
    }

    public function hasKnownCountry(): bool
    {
        return ! in_array($this->country, self::UNRESOLVED, true);
    }

    public function hasKnownCity(): bool
    {
        return $this->city !== null && ! in_array($this->city, self::UNRESOLVED, true);
    }

    public function countryLabel(): string
    {
        return $this->hasKnownCountry() ? $this->country : 'Unknown';
    }

    public function cityLabel(): ?string
    {
        return $this->hasKnownCity() ? $this->city : null;
    }

    public function label(): string
    {
        $city = $this->cityLabel();

        if ($city === null) {
            return $this->countryLabel();
        }

        if (! $this->hasKnownCountry()) {
            return $city;
        }

        return $city.', '.$this->country;
    }

    /**
     * A two-letter ISO code, or null when GA4 could not resolve one. Useful
     * for flag rendering, which must not be attempted for unknown rows.
     */
    public function isoCode(): ?string
    {
        return preg_match('/^[A-Z]{2}$/', $this->countryCode) === 1
            ? $this->countryCode
            : null;
    }
}
