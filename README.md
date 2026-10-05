[website]: https://hscstack.site
[join]: https://hscstack.site/join

# HSCStack 📚

> An open source academic community and learning ecosystem for the students of Bangladesh.

HSCStack brings Bangladeshi students together into an active academic community. Beyond serving as a curated library of syllabus resources, HSCStack provides a complete social and study hub — featuring a student Q&A forum, real-time live chat, study progress tracking, peer networking, community blogging, and an AI study assistant.

---

## 🌟 What is HSCStack?

HSCStack is designed for students preparing for the **Higher Secondary Certificate (HSC)** and **Secondary School Certificate (SSC)** exams in Bangladesh.

It replaces scattered social media groups and private drives with an all-in-one open platform where students can find verified study materials, discuss academic problems, track study consistency, and connect with peers across the country.

---

## 🚀 Main Features

- 📚 **Academic Library** — Community Driven structured syllabus resources for HSC & SSC curricula, organized by subject and chapter. Free and accessible to everyone.
- 💬 **Q&A Forum** — Community discussion board to ask questions, post solutions, upvote helpful answers, and collaborate on difficult topics.
- ⏱️ **Study Tracker** — Personal study dashboard to log daily study time, track syllabus completion across subjects, and maintain study streaks.
- ⚡ **Live Chat** — Real-time global chat room for instant discussions, quick study queries, and community interaction with built-in moderation.
- 👥 **Peers & Public Profiles** — Student directory and public learner profiles (`/u/{username}`) showcasing academic background, target, and activity.
- ✍️ **Community Blog** — Educational articles, study guides, exam strategies, and subject roadmaps written by students and educators.
- 🤖 **AI Assistant** — Dedicated AI learning companion organized by subjects to help explain concepts and work through problems.

---

## 🛠️ Tech Stack

| Layer | Technology |
| --- | --- |
| **Backend** | [Laravel 12](https://laravel.com) (PHP 8.2+) |
| **Frontend** | [Vue 3](https://vuejs.org) + [Inertia.js v3](https://inertiajs.com) + TypeScript |
| **Styling** | [Tailwind CSS v4](https://tailwindcss.com) |
| **Realtime** | Pusher Channels + Laravel Echo |
| **Storage** | AWS S3 / Cloudflare R2 |
| **Authentication** | Google OAuth 2.0 (Laravel Socialite) |
| **Permissions** | Spatie Laravel Permission |
| **PWA** | `vite-plugin-pwa` |
| **Analytics** | PostHog |

---

## 🏃 Getting Started

### For Students
1. Visit **[hscstack.site][website]** — browse the academic library, forum questions, and blog posts freely.
2. Sign in with Google to ask/answer forum questions, chat in real time, track your study progress, and customize your profile.

### For Contributors
1. Apply at 👉 **[hscstack.site/join][join]** to become a verified contributor.
2. Once approved, upload verified notes, questions, and guides directly under the relevant subject and chapter.

---

## 📁 Project Structure

```
platform/
├── app/
│   ├── Http/Controllers/     # Web, Forum, Chat, Admin, & API controllers
│   ├── Models/               # Eloquent models (User, Subject, Node, Resource, Forum, etc.)
│   ├── Observers/            # Cache and model lifecycle observers
│   └── Services/             # Business logic services
├── resources/
│   ├── js/
│   │   ├── pages/            # Inertia Vue pages (Home, Forum, Tracker, Chat, Blog, AI, Peers)
│   │   ├── components/       # Reusable UI components
│   │   └── layouts/          # Platform layouts
│   └── views/                # Blade root template
├── routes/
│   ├── web.php               # Public, forum, chat, tracker, & profile routes
│   ├── admin.php             # Admin & moderation routes
│   └── api.php               # API endpoints
└── docs/                     # Developer documentation
```

---

## 📖 Developer Documentation

- [Contributing Guidelines](CONTRIBUTING.md)
- [LLM Context (llms.txt)](public/llms.txt)
- [Google Drive Backup Setup](docs/google-drive-backup.md)
- [Storage Cleanup Routine](docs/storage-cleanup.md)

---

## 🌍 Community Guidelines

- ✅ Share accurate and helpful academic content.
- ✅ Keep forum discussions and chat constructive and relevant to HSC/SSC studies.
- ✅ Be respectful to fellow students and educators.
- ❌ No spam, inappropriate content, or academic dishonesty.

---

## 📬 Contact & Support

- 📧 Email: `com.tajim@gmail.com`
- 🐛 Issues: [GitHub Issues](../../issues)
- ❤️ Donate: [hscstack.site/donate](https://hscstack.site/donate)

---

## 📄 License

This project is open-source software licensed under the [Apache 2.0 License](LICENSE).
