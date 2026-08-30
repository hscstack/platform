<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Http\MediaFileUpload;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleDriveBackupService
{
    protected ?GoogleClient $client = null;

    protected ?GoogleDrive $drive = null;

    /**
     * Lazily build the client on first real use — not in the constructor.
     * Laravel resolves every console command's constructor dependencies
     * when it boots the Artisan kernel, even for commands you're not
     * running, so building the Google client here would force every
     * artisan call to have valid Drive credentials already in place.
     */
    protected function client(): GoogleClient
    {
        return $this->client ??= $this->buildClient();
    }

    protected function drive(): GoogleDrive
    {
        return $this->drive ??= new GoogleDrive($this->client());
    }

    /**
     * Find (or create) the Drive folder backups go into.
     *
     * Important: the drive.file OAuth scope only lets this app see files
     * and folders IT created — not folders you made by hand in the Drive
     * web UI, even if you copy their ID into .env. Pasting a manually
     * created folder's ID here will fail with a 404 ("File not found"),
     * because the app genuinely doesn't have visibility into it.
     *
     * So instead: search for a folder (owned by this app) with the
     * configured name, and create it on first run if it doesn't exist yet.
     * Every later run reuses the same folder.
     */
    protected function resolveFolderId(): ?string
    {
        $folderName = config('services.google_drive.folder_name');

        if (! $folderName) {
            return null; // upload straight into Drive root
        }

        $drive = $this->drive();

        $escapedName = str_replace("'", "\\'", $folderName);
        $query = "mimeType='application/vnd.google-apps.folder' and name='{$escapedName}' and trashed=false";

        $results = $drive->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name)',
            'spaces' => 'drive',
            'pageSize' => 1,
        ]);

        $existing = $results->getFiles();
        if (! empty($existing)) {
            return $existing[0]->getId();
        }

        $folder = $drive->files->create(new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]), ['fields' => 'id']);

        Log::info('Created Google Drive backup folder.', ['name' => $folderName, 'id' => $folder->getId()]);

        return $folder->getId();
    }

    /**
     * Build and authenticate the Google API client using a stored refresh token.
     * No browser interaction happens here — this must run unattended (cron-safe).
     */
    protected function buildClient(): GoogleClient
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');

        if (! $clientId || ! $clientSecret || ! $refreshToken) {
            throw new RuntimeException(
                'Google Drive credentials are missing. Set GOOGLE_DRIVE_CLIENT_ID, '.
                    'GOOGLE_DRIVE_CLIENT_SECRET and GOOGLE_DRIVE_REFRESH_TOKEN in .env.'
            );
        }

        $client = new GoogleClient;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setScopes([GoogleDrive::DRIVE_FILE]);
        $client->setAccessType('offline');

        // Exchange the stored refresh token for a fresh short-lived access token.
        // Nothing here is logged or exposed — the token stays inside the client instance.
        $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($token['error'])) {
            throw new RuntimeException(
                'Failed to refresh Google Drive access token: '.($token['error_description'] ?? $token['error'])
            );
        }

        $client->setAccessToken($token);

        return $client;
    }

    /**
     * Upload a local file to Google Drive using resumable/chunked upload,
     * which is safer than a single request for large backup archives.
     *
     * @return string The uploaded file's Google Drive file ID.
     */
    public function upload(string $localPath, ?string $remoteName = null): string
    {
        if (! is_file($localPath)) {
            throw new RuntimeException("Backup file not found: {$localPath}");
        }

        $remoteName = $remoteName ?? basename($localPath);
        $chunkSizeBytes = 5 * 1024 * 1024; // 5MB chunks

        $client = $this->client();
        $drive = $this->drive();
        $folderId = $this->resolveFolderId();

        $fileMetadata = new DriveFile([
            'name' => $remoteName,
            'parents' => $folderId ? [$folderId] : [],
        ]);

        // Resumable upload: lets us stream the archive without loading it in memory.
        $client->setDefer(true);

        try {
            $request = $drive->files->create($fileMetadata, [
                'fields' => 'id, name, size',
            ]);

            $media = new MediaFileUpload(
                $client,
                $request,
                'application/gzip',
                null,
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(filesize($localPath));

            $handle = fopen($localPath, 'rb');
            if ($handle === false) {
                throw new RuntimeException("Unable to open backup file for reading: {$localPath}");
            }

            $status = false;
            while (! $status && ! feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }
            fclose($handle);
        } catch (Exception $e) {
            $reason = json_decode($e->getMessage(), true)['error']['message'] ?? $e->getMessage();
            throw new RuntimeException("Google Drive rejected the upload: {$reason}", previous: $e);
        } finally {
            $client->setDefer(false);
        }

        $uploadedFile = $status instanceof DriveFile ? $status : null;
        $fileId = $uploadedFile->id ?? null;

        if (! $fileId) {
            throw new RuntimeException('Google Drive upload finished but returned no file ID.');
        }

        Log::info('Backup uploaded to Google Drive.', [
            'file_id' => $fileId,
            'remote_name' => $remoteName,
        ]);

        return $fileId;
    }
}
