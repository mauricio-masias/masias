<?php

namespace Database\Seeders;

use App\Models\Work;
use Illuminate\Database\Seeder;

class WorkSeeder extends Seeder
{
    public function run(): void
    {
        $works = [
            [
                'title' => 'masias.co.uk',
                'slug' => 'masias-co-uk',
                'excerpt' => 'Personal portfolio and CV site built with Laravel, Vue 3, and Inertia. SSR-powered, CMS-managed, fully responsive.',
                'description' => '<p>A complete redesign of my personal portfolio. Built with Laravel 13, Vue 3, and Inertia.js v3 for native SSR. The admin panel is powered by Filament v5 with Livewire 4.</p><p>Key features include scroll-driven animations, a parallax hero, a works grid with modal detail view, and a contact form with envelope animation. All content is managed through a bespoke CMS.</p><p>The design follows a "Signal / Noise" philosophy — dark theme, strong typography, and minimal UI chrome to let the work speak.</p>',
                'url' => 'https://masias.co.uk',
                'tags' => ['PHP', 'Laravel', 'TypeScript', 'Vue', 'Inertia', 'Tailwind'],
                'sort_order' => 1,
                'is_featured' => true,
                'published_at' => now(),
            ],
            [
                'title' => 'Enterprise CRM Platform',
                'slug' => 'enterprise-crm-platform',
                'excerpt' => 'A multi-tenant CRM built for a UK-based SaaS company, handling 50,000+ contacts with real-time updates and role-based access.',
                'description' => '<p>End-to-end design and development of a multi-tenant CRM platform for a B2B SaaS company. The platform manages customer pipelines, automated workflows, and reporting for enterprise clients.</p><p>Built with Laravel for the API layer, React for the frontend, and a PostgreSQL database with row-level security for multi-tenancy. Real-time updates are handled via Laravel Echo and Pusher.</p><p>The project involved working closely with product stakeholders in two-week Agile sprints, delivering an MVP in 12 weeks and iterating based on user feedback.</p>',
                'url' => null,
                'tags' => ['PHP', 'Laravel', 'React', 'TypeScript', 'PostgreSQL', 'Agile'],
                'sort_order' => 2,
                'is_featured' => true,
                'published_at' => now()->subMonths(3),
            ],
            [
                'title' => 'E-Commerce Headless Storefront',
                'slug' => 'ecommerce-headless-storefront',
                'excerpt' => 'A high-performance headless storefront using Next.js 14 and a custom Laravel API, achieving 98/100 Lighthouse scores.',
                'description' => '<p>A headless e-commerce storefront for a fashion retailer, replacing a legacy Magento installation. The frontend is built with Next.js 14 (App Router) and TypeScript, consuming a custom REST API built in Laravel.</p><p>The architecture separates concerns cleanly: the Laravel backend handles inventory, orders, and payments (Stripe), while the Next.js frontend focuses entirely on performance and UX. ISR (Incremental Static Regeneration) is used for product pages.</p><p>Results: page load times reduced by 60%, conversion rate improved by 18%, and the development team can now iterate on the frontend independently of the API.</p>',
                'url' => null,
                'tags' => ['Next.js', 'TypeScript', 'PHP', 'Laravel', 'Stripe', 'Docker'],
                'sort_order' => 3,
                'is_featured' => true,
                'published_at' => now()->subMonths(6),
            ],
            [
                'title' => 'Internal Tooling Dashboard',
                'slug' => 'internal-tooling-dashboard',
                'excerpt' => 'A Vue 3 + Laravel dashboard for an ops team to monitor deployments, review logs, and trigger CI/CD pipelines in real-time.',
                'description' => '<p>An internal developer experience tool built to reduce context-switching for a growing engineering team. The dashboard aggregates data from GitHub, Datadog, and AWS CloudWatch into a single view.</p><p>Frontend built with Vue 3 and Pinia for state management. The backend is a Laravel API that proxies and caches external API calls, reducing latency and rate-limit exposure.</p><p>Live deployment status updates are streamed via Server-Sent Events. The tool saved the ops team an estimated 2 hours per day in manual checks.</p>',
                'url' => null,
                'tags' => ['Vue', 'TypeScript', 'PHP', 'Laravel', 'Git', 'Docker'],
                'sort_order' => 4,
                'is_featured' => false,
                'published_at' => now()->subMonths(9),
            ],
            [
                'title' => 'Content Platform Rebuild',
                'slug' => 'content-platform-rebuild',
                'excerpt' => 'A full rebuild of a media company\'s content platform, migrating 200,000 articles from a legacy CMS to a modern Laravel + Vue stack.',
                'description' => '<p>Led the technical migration of a media company\'s publishing platform from a 10-year-old custom CMS to a modern Laravel + Vue stack. The platform publishes 50+ articles per day across multiple verticals.</p><p>The migration was done incrementally using the strangler-fig pattern — new content went to the new system while historical content was migrated in batches overnight, validated with automated comparison scripts.</p><p>The new CMS uses Laravel Nova for editorial management and a Vue 3 frontend rendered with Inertia. SEO metadata, canonical URLs, and 301 redirects were carefully managed to maintain search rankings throughout the migration.</p>',
                'url' => null,
                'tags' => ['PHP', 'Laravel', 'Vue', 'MySQL', 'Agile', 'Git'],
                'sort_order' => 5,
                'is_featured' => false,
                'published_at' => now()->subYear(),
            ],
        ];

        foreach ($works as $work) {
            Work::updateOrCreate(['slug' => $work['slug']], $work);
        }
    }
}
