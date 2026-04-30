<?php

namespace App\Http\Controllers;

use App\Models\HomeSetting;
use App\Models\Work;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $settings = HomeSetting::current();
        $featured = Work::published()->active()->featured()->ordered()
            ->get(['id', 'title', 'slug', 'excerpt', 'description', 'image', 'url', 'tags']);

        return Inertia::render('Home', [
            'settings' => $settings->only([
                'hero_headline',
                'hero_tagline',
                'hero_description',
                'about_text',
                'skills',
                'cv_url',
            ]),
            'featuredWorks' => $featured,
        ]);
    }
}
