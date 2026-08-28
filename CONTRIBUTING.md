# Contributing to HSCStack

Thanks for your interest in HSCStack! This project is built by and for HSC & SSC students in Bangladesh, and we welcome help from fellow developers.

## Before You Start

HSCStack's codebase is **not open for general, unsolicited pull requests**. To contribute code, you first need to be onboarded as a core developer:

1. Apply at [hscstack.site/join](https://hscstack.site/join)
2. Once your application is reviewed and accepted, you'll be onboarded to the codebase.
3. After that, you're free to open issues, pick up tasks, and submit PRs following the workflow below.

If you're not yet a core developer but found a bug or have a suggestion, please [open a GitHub Issue](https://github.com/hscstack/platform/issues) instead — no membership required for that.

## Tech Stack

| Layer           | Technology                           |
| --------------- | ------------------------------------ |
| **Backend**     | Laravel 12 (PHP)                     |
| **Frontend**    | Vue 3 + TypeScript via Inertia.js v3 |
| **Styling**     | Tailwind CSS v4                      |
| **Realtime**    | Pusher Channels + Laravel Echo       |
| **Storage**     | AWS S3 / Cloudflare R2               |
| **Auth**        | Google OAuth 2.0 (Laravel Socialite) |
| **Analytics**   | PostHog                              |
| **Permissions** | Spatie Laravel Permission            |
| **PWA**         | vite-plugin-pwa                      |

## Development Setup

```bash
# Clone the repo
git clone https://github.com/hscstack/platform
cd platform

# Install PHP dependencies
composer install

# Install JS dependencies
pnpm install

# Copy env file and configure
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start dev servers
php artisan serve
pnpm dev
```

### Additional Setup (Optional)

- **Google OAuth:** Configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env` for authentication.
- **Pusher:** Configure `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET` in `.env` for live chat.
- **S3/R2 Storage:** Configure `AWS_*` or `CLOUDFLARE_*` keys for file uploads.
- **PostHog:** Configure `POSTHOG_*` keys for analytics.
- **YouTube Data API:** Configure `YOUTUBE_API_KEY` for playlist imports.
- **Short.io:** Configure `SHORT_IO_*` keys for URL shortening.

## Workflow

1. Create a new branch off `main`:
    ```bash
    git checkout -b feature/your-feature-name
    ```
2. Make your changes, following the code style guidelines below.
3. Commit with a clear, descriptive message:
    ```bash
    git commit -m "Add: your feature"
    ```
4. Push your branch and open a Pull Request:
    ```bash
    git push origin feature/your-feature-name
    ```
5. Fill out the PR description explaining what changed and why, and link any related issue.

### Branch naming

- `feature/short-description` — new features
- `fix/short-description` — bug fixes
- `refactor/short-description` — code cleanup with no behavior change
- `chore/short-description` — tooling, config, or maintenance tasks

## Code Style & Quality

Before submitting a PR, please run:

```bash
# PHP formatting
./vendor/bin/pint

# PHP static analysis
./vendor/bin/phpstan analyse

# JS/Vue linting
pnpm lint

# Tests
php artisan test
```

General guidelines:

- Follow existing Laravel conventions (FormRequests for validation, resource controllers, etc.).
- Keep Vue components under `resources/js/pages/` and `resources/js/components/` organized and reusable.
- Avoid passing non-database fields into mass assignment (`create()`/`update()`); validate and filter explicitly.
- Keep PRs focused — one feature or fix per PR is easier to review.
- Use TypeScript types defined in `resources/js/types/` for all component props and API responses.
- Follow the existing cache invalidation pattern using Observers when adding new cacheable models.

## Project Structure

```
platform/
├── app/
│   ├── Models/               # Eloquent models (User, Subject, Node, Resource, Blog, etc.)
│   ├── Http/Controllers/     # Web + Admin + API controllers
│   ├── Mail/                 # Mailable classes (Welcome, Notifications, Broadcasts)
│   ├── Observers/            # Cache invalidation observers
│   ├── Services/             # Business logic services
│   └── Console/Commands/     # Artisan CLI commands
├── resources/
│   ├── js/
│   │   ├── pages/            # Inertia Vue pages
│   │   ├── components/       # Reusable Vue components
│   │   ├── layouts/          # App layouts
│   │   └── types/            # TypeScript type definitions
│   └── views/                # Blade templates
├── routes/
│   ├── web.php               # Public & auth routes
│   ├── admin.php             # Admin panel routes
│   └── api.php               # API routes (chat, auth, short URLs)
├── database/migrations/      # Database schema migrations
├── config/                   # App configuration
├── docs/                     # Developer documentation
└── README.md
```

## Content Contributions (Non-Code)

If you want to contribute academic resources (notes, PDFs, questions, images, videos) rather than code, you don't need to touch this repository at all. Instead:

1. Become a member at [hscstack.site/join](https://hscstack.site/join)
2. Log in with Google and navigate to the relevant subject and chapter.
3. Upload your resource with a clear title and content.
4. Your submission goes live after admin review.

Please only upload content you created or have permission to share, and avoid copyrighted textbooks or board materials.

## Reporting Bugs & Requesting Features

- Use [GitHub Issues](https://github.com/hscstack/platform/issues) for bugs and feature requests.
- Include steps to reproduce, expected vs. actual behavior, and screenshots if relevant (for UI bugs).

## Questions?

Reach out at **hello@tajimz.xyz** or open a discussion via GitHub Issues.
