<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveBackupService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupToDrive extends Command
{
    protected $signature = 'backup:drive';

    protected $description = 'Backup the database and S3 storage, archive them, and upload the archive to Google Drive';

    protected string $backupDir;

    protected string $databaseDumpPath;

    protected string $s3TarPath;

    protected string $finalArchivePath;

    public function __construct(protected GoogleDriveBackupService $driveService)
    {
        parent::__construct();

        $timestamp = now()->format('Y-m-d_His');

        $this->backupDir = storage_path('app/backups');
        $this->databaseDumpPath = $this->backupDir . '/database.sql.gz';
        $this->s3TarPath = $this->backupDir . '/s3.tar';
        $this->finalArchivePath = $this->backupDir . "/hsc-stack-backup-{$timestamp}.tar.gz";
    }

    public function handle(): int
    {
        File::ensureDirectoryExists($this->backupDir);

        try {
            $this->info('Step 1/4: Dumping database…');
            $this->backupDatabase();

            $this->info('Step 2/4: Archiving S3 storage…');
            $this->backupStorage();

            $this->info('Step 3/4: Building final archive…');
            $this->createFinalArchive();

            $this->info('Step 4/4: Uploading to Google Drive…');
            $fileId = $this->driveService->upload($this->finalArchivePath);

            $this->cleanupLocalArchive();

            $this->info("Backup complete. Google Drive file ID: {$fileId}");

            Log::info('backup:drive completed successfully.', [
                'drive_file_id' => $fileId,
            ]);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());

            Log::error('backup:drive failed.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Dump MySQL database and gzip it.
     */
    protected function backupDatabase(): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (! $connection || ($connection['driver'] ?? null) !== 'mysql') {
            throw new Exception(
                'backup:drive currently supports MySQL connections only.'
            );
        }

        $plainSqlPath = $this->backupDir . '/database.sql';

        $command = [
            'mysqldump',
            '--host=' . $connection['host'],
            '--port=' . $connection['port'],
            '--user=' . $connection['username'],
            '--single-transaction',
            '--quick',
            '--result-file=' . $plainSqlPath,
            $connection['database'],
        ];

        $result = Process::env([
            'MYSQL_PWD' => $connection['password'],
        ])
            ->timeout(600)
            ->run($command);

        if ($result->failed()) {
            throw new Exception(
                'mysqldump failed: ' . $result->errorOutput()
            );
        }

        $this->gzipFile(
            $plainSqlPath,
            $this->databaseDumpPath
        );

        File::delete($plainSqlPath);
    }

    /**
     * Download S3/R2 files temporarily and create archive.
     */
    protected function backupStorage(): void
    {
        $tempStoragePath = $this->backupDir . '/s3';

        File::ensureDirectoryExists($tempStoragePath);

        $disk = Storage::disk('s3');

        foreach ($disk->allFiles() as $file) {
            $localFile = $tempStoragePath . '/' . $file;

            File::ensureDirectoryExists(
                dirname($localFile)
            );

            File::put(
                $localFile,
                $disk->get($file)
            );
        }

        $result = Process::timeout(3600)
            ->run([
                'tar',
                '-cf',
                $this->s3TarPath,
                '-C',
                $this->backupDir,
                's3',
            ]);

        if ($result->failed()) {
            throw new Exception(
                'S3 archive failed: ' . $result->errorOutput()
            );
        }

        // Remove temporary downloaded S3 files
        File::deleteDirectory($tempStoragePath);
    }

    /**
     * Create final backup archive.
     */
    protected function createFinalArchive(): void
    {
        $result = Process::path($this->backupDir)
            ->timeout(3600)
            ->run([
                'tar',
                '-czf',
                $this->finalArchivePath,
                basename($this->databaseDumpPath),
                basename($this->s3TarPath),
            ]);

        if ($result->failed()) {
            throw new Exception(
                'Final archive creation failed: ' . $result->errorOutput()
            );
        }

        File::delete([
            $this->databaseDumpPath,
            $this->s3TarPath,
        ]);
    }

    protected function cleanupLocalArchive(): void
    {
        File::deleteDirectory($this->backupDir);
    }

    protected function gzipFile(string $source, string $destination): void
    {
        $sourceHandle = fopen($source, 'rb');
        $destHandle = gzopen($destination, 'wb9');

        if ($sourceHandle === false || $destHandle === false) {
            throw new Exception(
                'Unable to open files for gzip compression.'
            );
        }

        while (! feof($sourceHandle)) {
            gzwrite(
                $destHandle,
                fread($sourceHandle, 1024 * 512)
            );
        }

        fclose($sourceHandle);
        gzclose($destHandle);
    }
}
