<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Console\Command;

/**
 * Run this ONCE, locally, to obtain a refresh token for the backup service account
 * to use. It is not part of the automated backup flow — cron never calls it.
 */
class GoogleDriveAuthorize extends Command
{
    protected $signature = 'backup:drive-authorize';

    protected $description = 'One-time setup: authorize this app with Google Drive and print a refresh token for .env';

    public function handle(): int
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');

        if (! $clientId || ! $clientSecret) {
            $this->error('Set GOOGLE_DRIVE_CLIENT_ID and GOOGLE_DRIVE_CLIENT_SECRET in .env first.');

            return self::FAILURE;
        }

        $client = new GoogleClient;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri('http://127.0.0.1:8000/local/oauth2callback');
        $client->setScopes([GoogleDrive::DRIVE_FILE]);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // forces a refresh token even on repeat authorizations

        $authUrl = $client->createAuthUrl();

        $this->info('Open this URL in a browser, sign in, and approve access:');
        $this->line($authUrl);
        $this->newLine();

        $code = $this->ask('Paste the authorization code shown by Google');

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $this->error('Authorization failed: '.($token['error_description'] ?? $token['error']));

            return self::FAILURE;
        }

        if (! isset($token['refresh_token'])) {
            $this->error('No refresh token returned. Revoke app access at https://myaccount.google.com/permissions and try again.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Success. Add this line to your .env file:');
        $this->line('GOOGLE_DRIVE_REFRESH_TOKEN='.$token['refresh_token']);
        $this->newLine();
        $this->warn('This token grants upload access to your Drive — treat it like a password.');

        return self::SUCCESS;
    }
}
