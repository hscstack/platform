<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Subject;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'seo:sitemap';

    protected $description = 'Generate sitemap.xml';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        // Static pages
        foreach (
            [
                '/',
                '/ssc',
                '/blogs',
                '/about-us',
                '/privacy-policy',
                '/terms-service',
                '/content-policy',
                '/support',
                '/join',
                '/guide',
            ] as $page
        ) {
            $sitemap->add(
                Url::create(url($page))
            );
        }

        // Subjects
        Subject::orderBy('name')->get()->each(function ($subject) use ($sitemap) {
            $sitemap->add(
                Url::create(url($subject->slug))
                    ->setLastModificationDate($subject->updated_at)
            );
        });

        // Published blogs
        Blog::where('is_published', true)
            ->orderByDesc('updated_at')
            ->get()
            ->each(function ($blog) use ($sitemap) {
                $sitemap->add(
                    Url::create(url("/blogs/{$blog->slug}"))
                        ->setLastModificationDate($blog->updated_at)
                );
            });

        $path = public_path('sitemap.xml');

        $sitemap->writeToFile($path);

        $this->info('Sitemap generated successfully.');
        $this->line($path);

        return self::SUCCESS;
    }
}
