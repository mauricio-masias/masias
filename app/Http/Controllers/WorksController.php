<?php

namespace App\Http\Controllers;

use App\Models\Work;
use Inertia\Inertia;
use Inertia\Response;

class WorksController extends Controller
{
    public function __invoke(): Response
    {
        $works = Work::published()->active()->ordered()
            ->get(['id', 'title', 'slug', 'excerpt', 'description', 'image', 'url', 'tags', 'is_featured']);

        return Inertia::render('Works', [
            'works' => $works,
        ]);
    }
}
