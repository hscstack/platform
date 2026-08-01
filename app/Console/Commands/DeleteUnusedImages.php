<?php

namespace App\Console\Commands;

use App\Models\Resource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteUnusedImages extends Command
{
    protected $signature = 'resources:clean-unused-images {--dry-run}';

    protected $description = 'Delete images in storage/resources that no resource points to';

    public function handle(): void
    {
        // 1. Get all image files currently on disk
        $files = Storage::disk('public')->allFiles('resources');

        // 2. Get all file_url values saved in the database
        $used = Resource::whereNotNull('file_path')
            ->pluck('file_path')
            ->toArray();

        // 3. Compare and delete whatever is not used
        foreach ($files as $file) {
            if (!in_array($file, $used)) {
                $this->line("Unused: {$file}");

                if (!$this->option('dry-run')) {
                    Storage::disk('public')->delete($file);
                }
            }
        }

        $this->info('Done.');
    }
}
