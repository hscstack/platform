# Google Drive Backup Setup

This project supports automatic database and **S3-compatible storage backups** to Google Drive using the Google Drive API.

The backup process includes:

- MySQL database dump
- Files stored on S3-compatible storage (such as AWS S3 or Cloudflare R2)
- Archive creation
- Upload to Google Drive

---

## Prerequisites

Install the Google API PHP Client:

```bash
composer require google/apiclient:^2.15
```

Make sure your Laravel application has a configured S3 disk:

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_ENDPOINT=
```

For Cloudflare R2, configure the S3-compatible endpoint:

```env
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
```

---

# 1. Create a Google Cloud Project

1. Go to Google Cloud Console.
2. Create a new project (for example: `hsc-stack-backups`).
3. Open **APIs & Services → Library**.
4. Search for **Google Drive API**.
5. Click **Enable**.

---

# 2. Configure the OAuth Consent Screen

1. Navigate to:

```
APIs & Services → OAuth consent screen
```

2. Choose:

```
External
```

3. Fill in the required information:

- App name
- User support email
- Developer contact email

4. Save and continue.

## Add the Drive Scope

Add:

```
https://www.googleapis.com/auth/drive.file
```

This allows the application to create and manage files created by the backup application without granting access to the entire Google Drive.

## Add Test Users

While the application is in **Testing** mode:

1. Open **OAuth consent screen → Test users**
2. Add the Google account that will store backups.

Publishing the application is not required for personal backup usage.

---

# 3. Create OAuth Credentials

1. Open:

```
APIs & Services → Credentials
```

2. Click:

```
Create Credentials → OAuth client ID
```

3. Select:

```
Application type: Desktop app
```

4. Give it a name:

Example:

```
hsc-stack-backup-cli
```

5. Create the credentials.

Copy:

- Client ID
- Client Secret

---

# 4. Configure Environment Variables

Add these values to your `.env` file:

```env
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REFRESH_TOKEN=
GOOGLE_DRIVE_FOLDER_NAME=backup
```

The configuration is loaded from:

```php
'google_drive' => [
    'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folder_name' => env('GOOGLE_DRIVE_FOLDER_NAME', 'backup'),
],
```

---

# 5. One-Time Authorization

Run:

```bash
php artisan backup:drive-authorize
```

The command will:

1. Generate a Google authorization URL.
2. Ask you to open it in your browser.
3. Sign in with the Google account added as a test user.
4. Grant access.
5. Copy the authorization code.
6. Paste the code back into the terminal.

The command will output a refresh token:

```text
GOOGLE_DRIVE_REFRESH_TOKEN=1//xxxxxxxxxxxxxxxx
```

Add this value to `.env`.

> The authorization process only needs to be completed once. The refresh token is reused automatically for future scheduled backups.

---

# 6. Run a Backup

Create and upload a backup:

```bash
php artisan backup:drive
```

The command performs these steps:

```
1. Dump MySQL database
2. Download files from S3 storage temporarily
3. Create S3 archive
4. Combine database + storage archive
5. Upload final archive to Google Drive
6. Remove temporary local backup files
```

If the configured Google Drive folder does not exist, it will be created automatically.

---

# Automation

After the refresh token is configured, backups can run automatically using Laravel Scheduler or cron.

Example scheduler:

```php
Schedule::command('backup:drive')
    ->daily();
```

Example cron entry:

```bash
0 0 * * * cd /path/to/project && php artisan schedule:run
```

---

# Backup Storage Notes

The backup command does **not** directly backup `storage/app/public`.

Instead, it backs up the configured S3 disk:

```
S3 Bucket
   |
   ├── images
   ├── documents
   ├── PDFs
   └── other uploaded files
```

This keeps the backup process aligned with the application's production storage system.

For S3-compatible providers such as Cloudflare R2, ensure the Laravel S3 filesystem configuration is correct before running backups.
