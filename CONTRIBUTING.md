# Contributing to HSCStack

Thanks for your interest in HSCStack! This project is built by and for HSC students in Bangladesh, and we welcome help from fellow developers.

## Before You Start

HSCStack's codebase is **not open for general, unsolicited pull requests**. To contribute code, you first need to be onboarded as a core developer:

1. Apply at [hscstack.mvp.bd/join](https://hscstack.mvp.bd/join)
2. Once your application is reviewed and accepted, you'll be onboarded to the codebase.
3. After that, you're free to open issues, pick up tasks, and submit PRs following the workflow below.

If you're not yet a core developer but found a bug or have a suggestion, please [open a GitHub Issue](https://github.com/trtajim/hsc-stack/issues) instead — no membership required for that.

## Tech Stack

| Layer        | Technology            |
|--------------|------------------------|
| Backend      | Laravel (PHP)          |
| Frontend     | Vue.js via Inertia.js  |
| Storage      | Public file storage    |
| Admin Panel  | Built-in Laravel admin |

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
- Keep Vue components under `resources/js/Pages` and `resources/js/Components` organized and reusable.
- Avoid passing non-database fields into mass assignment (`create()`/`update()`); validate and filter explicitly.
- Keep PRs focused — one feature or fix per PR is easier to review.

## Project Structure

```
hscstack/
├── app/
│   ├── Models/             # Subject, Node, Resource models
│   ├── Http/Controllers/   # Web + Admin controllers
│   └── ...
├── resources/
│   ├── js/
│   │   ├── Pages/          # Inertia Vue pages
│   │   └── Components/     # Reusable Vue components
│   └── views/              # Blade layouts
├── routes/
│   ├── web.php             # Public routes
│   └── admin.php           # Admin routes
├── public/storage/         # Uploaded files
├── .env.example
└── README.md
```

## Content Contributions (Non-Code)

If you want to contribute academic resources (notes, PDFs, questions, etc.) rather than code, you don't need to touch this repository at all. Instead:

1. Become a member at [hscstack.mvp.bd/join](https://hscstack.mvp.bd/join)
2. Log in to the platform and upload your resource under the relevant subject and chapter.
3. Your submission goes live after a quick admin review.

Please only upload content you created or have permission to share, and avoid copyrighted textbooks or board materials.

## Reporting Bugs & Requesting Features

- Use [GitHub Issues](https://github.com/trtajim/hscstack/issues) for bugs and feature requests.
- Include steps to reproduce, expected vs. actual behavior, and screenshots if relevant (for UI bugs).

## Questions?

Reach out at **hello@tajimz.xyz** or open a discussion via GitHub Issues.
