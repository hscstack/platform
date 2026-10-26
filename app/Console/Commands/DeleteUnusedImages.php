<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Blog;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Resource;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteUnusedImages extends Command
{
    protected $signature = 'resources:clean-unused-images {--dry-run}';

    protected $description = 'Delete unused uploaded images from all storage directories';

    public function handle(): void
    {
        // User profile images
        $this->cleanDirectory(
            'users',
            User::whereNotNull('image_path')->pluck('image_path')->toArray()
        );

        // Blog featured images
        $this->cleanDirectory(
            'blogs',
            Blog::whereNotNull('featured_image_path')->pluck('featured_image_path')->toArray()
        );

        $rawNotice = AppSetting::get('site_notice');
        $noticeData = is_array($rawNotice) ? $rawNotice : json_decode($rawNotice ?? '', true);
        $noticeImages = [];
        if (! empty($noticeData['image']) && ! str($noticeData['image'])->startsWith(['http://', 'https://'])) {
            $noticeImages[] = $noticeData['image'];
        }
        $this->cleanDirectory('notices', $noticeImages);

        // Resource files (items stored under resources/)
        $rawItems = DB::table('resources')
            ->whereNotNull('items')
            ->pluck('items');

        $activeResourceFiles = [];
        foreach ($rawItems as $json) {
            $items = is_array($json) ? $json : (json_decode($json, true) ?: []);
            foreach ($items as $item) {
                $source = $item['source'] ?? null;
                if ($source && ! str_starts_with($source, 'http://') && ! str_starts_with($source, 'https://')) {
                    $activeResourceFiles[] = $source;
                }
            }
        }

        $this->cleanDirectory(
            'resources',
            array_values(array_unique($activeResourceFiles))
        );

        // Forum post images
        $forumPostImages = ForumPost::whereNotNull('images')
            ->pluck('images')
            ->flatten()
            ->filter(fn ($path) => is_string($path) && $path !== '' && ! str_starts_with($path, 'http'))
            ->values()
            ->all();

        $this->cleanDirectory(
            'forum/posts',
            $forumPostImages
        );

        // Forum answer images
        $this->cleanDirectory(
            'forum/answers',
            ForumAnswer::whereNotNull('image_path')->pluck('image_path')->toArray()
        );

        // Support ticket attachments
        $this->cleanDirectory(
            'tickets',
            SupportTicket::whereNotNull('attachment_path')
                ->where('attachment_path', 'not like', 'http%')
                ->pluck('attachment_path')
                ->toArray()
        );

        // emails/images is intentionally skipped — those paths are embedded
        // inline into email HTML bodies and not tracked in any model column.

        $this->info('Done.');
    }

    protected function cleanDirectory(string $directory, array $usedFiles): void
    {
        $files = Storage::allFiles($directory);

        $deleted = 0;
        $oneDayAgo = now()->subDay()->timestamp;

        foreach ($files as $file) {
            if (! in_array($file, $usedFiles, true)) {
                // Skip newly uploaded files (e.g. uploaded within last 24h) to avoid race conditions with unsubmitted forms
                try {
                    if (Storage::lastModified($file) > $oneDayAgo) {
                        continue;
                    }
                } catch (\Throwable) {
                    // ignore if storage does not support lastModified
                }

                $this->line("Unused: {$file}");

                if (! $this->option('dry-run')) {
                    Storage::delete($file);
                    $deleted++;
                }
            }
        }

        $label = $this->option('dry-run') ? 'would delete' : 'deleted';
        $count = $this->option('dry-run') ? count(array_diff($files, $usedFiles)) : $deleted;

        $this->info("[{$directory}] {$count} file(s) {$label}.");
    }
}
