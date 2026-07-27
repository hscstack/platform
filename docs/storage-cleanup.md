# Storage Cleanup Command

## Purpose

The `resources:clean-unused-images` Artisan command removes orphaned files from `storage/app/public/resources`.

A file is considered unused when:
- It exists in storage
- Its path is not referenced by any `resources.file_url` database record

## Why this exists

Resources can be deleted using database cascade deletion. Since database cascades do not trigger Laravel model events, associated files may remain in storage.

This command cleans those orphan files.

## Usage

### Preview files before deleting

Always run dry-run first:

```bash
php artisan resources:clean-unused-images --dry-run
