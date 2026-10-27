<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'seo:sitemap';

    protected $description = 'Generate a clean, optimized sitemap.xml with static pages, subjects, curriculum nodes, published blogs, answered forum discussions, and top appreciated contributors.';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        // 1. Static Public Pages (excluding auth/login utility pages)
        $staticPages = [
            '/',
            '/tracker',
            '/peers',
            '/blogs',
            '/forum',
            '/about-us',
            '/products',
            '/guide',
            '/ai',
            '/donate',
            '/join',
            '/support',
            '/privacy-policy',
            '/terms-service',
            '/content-policy',
        ];

        foreach ($staticPages as $page) {
            $sitemap->add(
                Url::create(url($page))
            );
        }

        // 2. Published Blogs
        Blog::where('is_published', true)
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at'])
            ->each(function ($blog) use ($sitemap) {
                $sitemap->add(
                    Url::create(url("/blogs/{$blog->slug}"))
                        ->setLastModificationDate($blog->updated_at)
                );
            });

        // 5. Top 3 Most Appreciated User Profiles
        User::whereNotNull('username')
            ->withCount('appreciationsReceived')
            ->orderByDesc('appreciations_received_count')
            ->take(3)
            ->get(['username', 'updated_at'])
            ->each(function ($user) use ($sitemap) {
                $sitemap->add(
                    Url::create(url("/u/{$user->username}"))
                        ->setLastModificationDate($user->updated_at)
                );
            });

        // 6. Latest 100 Answered Forum Questions
        ForumPost::approved()
            ->where('answers_count', '>', 0)
            ->orderByDesc('updated_at')
            ->take(100)
            ->get(['slug', 'updated_at'])
            ->each(function ($post) use ($sitemap) {
                $sitemap->add(
                    Url::create(url("/forum/questions/{$post->slug}"))
                        ->setLastModificationDate($post->updated_at)
                );
            });

        $path = public_path('sitemap.xml');

        $sitemap->writeToFile($path);

        $this->info('Sitemap generated successfully.');
        $this->line($path);

        return self::SUCCESS;
    }
}
