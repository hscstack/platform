<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\ForumAnswer;
use App\Models\ForumPost;
use App\Models\Notice;
use App\Models\Resource;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Console\Command;
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

        // Notice images (skip external URLs)
        $this->cleanDirectory(
            'notices',
            Notice::whereNotNull('image')
                ->where('image', 'not like', 'http%')
                ->pluck('image')
                ->toArray()
        );

        // Resource files (notes, images, videos — all stored under resources/)
        $this->cleanDirectory(
            'resources',
            Resource::whereNotNull('file_path')->pluck('file_path')->toArray()
        );

        // Forum post images
        $this->cleanDirectory(
            'forum/posts',
            ForumPost::whereNotNull('image_path')->pluck('image_path')->toArray()
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

        foreach ($files as $file) {
            if (! in_array($file, $usedFiles, true)) {
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
