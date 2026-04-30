<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacySetting extends Model
{
    protected $fillable = ['title', 'content'];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'title' => 'Privacy & Cookie Policy',
            'content' => null,
        ]);
    }
}
