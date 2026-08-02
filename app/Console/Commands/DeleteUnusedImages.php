<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteUnusedImages extends Command
{
    protected $signature = 'resources:clean-unused-images {--dry-run}';

    protected $description = 'Delete unused uploaded images';

    public function handle(): void
    {
        $this->cleanDirectory(
            'resources',
            Resource::whereNotNull('file_path')
                ->pluck('file_path')
                ->toArray()
        );

        $this->cleanDirectory(
            'users',
            User::whereNotNull('image_path')
                ->pluck('image_path')
                ->toArray()
        );

        $this->cleanDirectory(
            'blogs',
            Blog::whereNotNull('featured_image_path')
                ->pluck('featured_image_path')
                ->toArray()
        );



        $this->info('Done.');
    }

    protected function cleanDirectory(string $directory, array $usedFiles): void
    {
        $files = Storage::allFiles($directory);

        foreach ($files as $file) {
            if (!in_array($file, $usedFiles, true)) {
                $this->line("Unused: {$file}");

                if (!$this->option('dry-run')) {
                    Storage::delete($file);
                }
            }
        }
    }
}
