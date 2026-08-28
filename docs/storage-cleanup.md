# Storage Cleanup Command

## Purpose

The `resources:clean-unused-images` Artisan command removes orphaned files from S3/R2 cloud storage.

It scans four storage directories and cross-references them against the database:

| Storage Directory | Database Check                          |
| ----------------- | --------------------------------------- |
| `resources/`      | `resources.file_path`                   |
| `users/`          | `users.image_path`                      |
| `blogs/`          | `blogs.featured_image_path`             |
| `notices/`        | `notices.image` (non-HTTP paths only)   |

A file is considered unused when it exists in storage but its path is not referenced by any database record.

## Why this exists

Resources, users, blogs, and notices can be deleted via database cascade or admin actions. Since database cascades do not trigger Laravel model events, associated files may remain in storage.

This command cleans those orphan files across all upload directories.

## Usage

### Preview files before deleting

Always run dry-run first:

```bash
php artisan resources:clean-unused-images --dry-run
```

### Delete unused files

```bash
php artisan resources:clean-unused-images
```
