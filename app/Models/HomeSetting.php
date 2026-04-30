<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeSetting extends Model
{
    /** @use HasFactory<\Database\Factories\HomeSettingFactory> */
    use HasFactory;
    protected $fillable = [
        'hero_headline',
        'hero_tagline',
        'hero_description',
        'about_text',
        'skills',
        'cv_url',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'hero_headline' => 'Mauricio Masias',
            'hero_tagline' => 'Full-Stack Developer',
        ]);
    }
}
