<?php

namespace App\Http\Controllers;

use App\Models\PrivacySetting;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    public function __invoke(): Response
    {
        $setting = PrivacySetting::current();

        return Inertia::render('Privacy', [
            'title' => $setting->title,
            'content' => $setting->content,
            'lastUpdated' => $setting->updated_at->toDateString(),
        ]);
    }
}
