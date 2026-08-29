<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Node;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'seo:sitemap';

    protected $description = 'Generate sitemap.xml with static pages, subjects, curriculum nodes, blogs, and active user profiles.';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        // 1. Static Pages
        $staticPages = [
            '/' => ['priority' => 1.0, 'freq' => 'daily'],
            '/ssc' => ['priority' => 0.9, 'freq' => 'daily'],
            '/blogs' => ['priority' => 0.8, 'freq' => 'daily'],
            '/about-us' => ['priority' => 0.7, 'freq' => 'monthly'],
            '/projects' => ['priority' => 0.7, 'freq' => 'monthly'],
            '/guide' => ['priority' => 0.7, 'freq' => 'monthly'],
            '/ai' => ['priority' => 0.7, 'freq' => 'monthly'],
            '/donate' => ['priority' => 0.6, 'freq' => 'monthly'],
            '/join' => ['priority' => 0.6, 'freq' => 'monthly'],
            '/login' => ['priority' => 0.5, 'freq' => 'monthly'],
            '/support' => ['priority' => 0.5, 'freq' => 'monthly'],
            '/privacy-policy' => ['priority' => 0.3, 'freq' => 'yearly'],
            '/terms-service' => ['priority' => 0.3, 'freq' => 'yearly'],
            '/content-policy' => ['priority' => 0.3, 'freq' => 'yearly'],
        ];

        foreach ($staticPages as $page => $meta) {
            $sitemap->add(
                Url::create(url($page))
                    ->setChangeFrequency($meta['freq'])
                    ->setPriority($meta['priority'])
            );
        }

        // 2. Subjects
        Subject::orderBy('name')->get()->each(function ($subject) use ($sitemap) {
            $sitemap->add(
                Url::create(url($subject->slug))
                    ->setLastModificationDate($subject->updated_at)
                    ->setChangeFrequency('weekly')
                    ->setPriority(0.9)
            );
        });

        // 3. Subject Nodes (Chapters, Sub-topics & Study Materials)
        $allNodes = Node::with('subject:id,slug')
            ->whereNotNull('subject_id')
            ->get(['id', 'subject_id', 'parent_id', 'slug', 'updated_at']);

        $nodesById = $allNodes->keyBy('id');

        foreach ($allNodes as $node) {
            if (! $node->subject || ! $node->slug) {
                continue;
            }

            $slugs = [$node->slug];
            $curr = $node;

            while ($curr->parent_id && isset($nodesById[$curr->parent_id])) {
                $curr = $nodesById[$curr->parent_id];
                if ($curr->slug) {
                    array_unshift($slugs, $curr->slug);
                }
            }

            $nodePath = '/'.$node->subject->slug.'/'.implode('/', $slugs);

            $sitemap->add(
                Url::create(url($nodePath))
                    ->setLastModificationDate($node->updated_at)
                    ->setChangeFrequency('weekly')
                    ->setPriority(0.8)
            );
        }

        // 4. Published Blogs
        Blog::where('is_published', true)
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at'])
            ->each(function ($blog) use ($sitemap) {
                $sitemap->add(
                    Url::create(url("/blogs/{$blog->slug}"))
                        ->setLastModificationDate($blog->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.8)
                );
            });

        // 5. Active & Contributor User Profiles (Filtering out blank/inactive accounts)
        User::whereNotNull('username')
            ->where(function ($query) {
                $query->whereHas('blogs', fn ($q) => $q->where('is_published', true))
                    ->orWhereHas('roles')
                    ->orWhereHas('resources')
                    ->orWhere(function ($sub) {
                        $sub->whereNotNull('about')
                            ->whereNotNull('institution');
                    });
            })
            ->select(['username', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get()
            ->each(function ($user) use ($sitemap) {
                $sitemap->add(
                    Url::create(url("/u/{$user->username}"))
                        ->setLastModificationDate($user->updated_at)
                        ->setChangeFrequency('weekly')
                        ->setPriority(0.5)
                );
            });

        $path = public_path('sitemap.xml');

        $sitemap->writeToFile($path);

        $this->info('Sitemap generated successfully.');
        $this->line($path);

        return self::SUCCESS;
    }
}
