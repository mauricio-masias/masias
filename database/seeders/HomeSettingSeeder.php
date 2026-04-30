<?php

namespace Database\Seeders;

use App\Models\HomeSetting;
use Illuminate\Database\Seeder;

class HomeSettingSeeder extends Seeder
{
    public function run(): void
    {
        HomeSetting::updateOrCreate(
            ['id' => 1],
            [
                'hero_headline' => 'Mauricio Masias',
                'hero_tagline' => 'Full-Stack Developer',
                'hero_description' => 'I build scalable web applications with PHP, TypeScript, Vue, and React — from architecture to deployment.',
                'about_text' => '<p>I\'m a Full-Stack Developer with a passion for building clean, performant, and maintainable web applications. I work across the entire stack — from database design and Laravel APIs to Vue and React frontends.</p><p>I thrive in Agile environments, value clear communication, and believe that great software is built collaboratively. Whether it\'s a greenfield product or a legacy codebase that needs love, I bring the same care and rigour to every project.</p>',
                'skills' => [
                    'PHP',
                    'Laravel',
                    'TypeScript',
                    'Vue',
                    'React',
                    'Next.js',
                    'MySQL',
                    'Docker',
                    'Git',
                    'Agile',
                    'Tailwind CSS',
                    'Node.js',
                ],
                'cv_url' => null,
            ]
        );
    }
}
