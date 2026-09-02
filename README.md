[website]: https://hscstack.site
[join]: https://hscstack.site/join

# HSCStack 📚

> A curated resource platform for HSC & SSC students of Bangladesh — built by members, for everyone.

HSCStack is a structured platform where verified members share academic resources — notes, questions, PDFs, images, and videos — organized by subject and chapter, so every SSC & HSC student can find what they need in one place. On top of that, we've got a real-time community chat, an educational blog, study progress tracking, and an AI learning assistant — all completely free and ad-free.

---

## 🌟 What is HSCStack?

HSCStack is built for students preparing for the **Higher Secondary Certificate (HSC)** and **Secondary School Certificate (SSC)** exams in Bangladesh. It brings together a trusted library of student-made resources, organized neatly by subject and chapter — no more digging through WhatsApp groups or random Facebook posts.

You can switch between HSC and SSC curricula seamlessly. Each subject has nested chapters and topics, so you can drill down to exactly what you're looking for. Resources are uploaded by verified members to keep quality high, and the community votes on the best folders to surface them to the top.

Browsing is open to everyone. But to **contribute**, you need to be an HSCStack member first — keeping the content quality high and the community trustworthy.

---

## ✨ Features

- 📂 **Dual Curriculum** — Switch between HSC and SSC. Subjects organized with custom icons, color-coded badges, and sort order.
- 🗂️ **Deep Folder Structure** — Infinite nesting: subject → chapter → topic → sub-topic. Breadcrumb navigation with caching for speed.
- 📄 **5 Resource Types** — Notes, questions, PDFs, images, and videos. Direct uploads to S3/R2 or external links.
- ⬆️ **Community Voting** — Upvote/downvote folders. Best content rises to the top.
- ✅ **Progress Tracking** — Mark resources as "Completed." See completion counts and who's been studying.
- 🎥 **Custom YouTube Player** — Watch classes without YouTube ads or distracting recommendations. Custom controls with variable speed (0.75x–2x), 5-second skip, and fullscreen.
- 💬 **Live Chat** — Real-time global chat with message replies, @mentions, emoji reactions, and a smart profanity filter. Community reporting with auto-ban for repeat offenders.
- 👤 **Public Profiles** — Vanity URLs (`/u/{username}`) with bio, institution, avatar, social links, and a unified activity stream.
- ❤️ **Peer Appreciations** — Appreciate members with milestone email notifications.
- ✍️ **Blog** — Full CMS with slug URLs, featured images, SEO tags, love reactions, and threaded comments.
- 🤖 **AI Assistant** — Dedicated interface organized by STEM subjects and chapters.
- 🔗 **URL Shortener** — Floating share bar on every page, powered by Short.io.
- 📱 **PWA** — Install it like an app. Works offline with a service worker.
- 🔔 **Notice Banner** — Site-wide announcements with images and CTA buttons.
- 🔒 **Google Sign-in** — One-click OAuth 2.0 authentication.

---

## 📚 Resource Types

| Type          | Description                                       |
| ------------- | ------------------------------------------------- |
| **Notes**     | Handwritten or typed notes by fellow students     |
| **Questions** | Exam questions and practice problems              |
| **PDFs**      | Formatted documents, guides, and reference sheets |
| **Images**    | Diagrams, charts, and visual study aids           |
| **Videos**    | Ad-free YouTube classes via custom native player  |

---

## 🚀 Getting Started

### For Students (Browsing)

1. Visit [HSCStack][website] — no account needed.
2. Pick your curriculum (HSC or SSC), then select a subject and chapter.
3. Browse, read, or download any resource freely.

### For Members (Contributing)

Only HSCStack members can upload resources. To become a member:

1. Join at 👉 **[hscstack.site/join][join]**
2. Once accepted, sign in with Google and go to the relevant subject and chapter.
3. Upload your resource — add a title, type, and content.
4. Your contribution goes live after admin review.

> **Why membership?** We keep uploads member-only to maintain quality and ensure content is relevant, accurate, and trustworthy for all students.

---

## 🛠️ Tech Stack

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
| **Backup**      | Google Drive API (automated)         |

---

## 📖 Developer Documentation

- [Google Drive Backup Setup](docs/google-drive-backup.md)
- [Storage Cleanup Command](docs/storage-cleanup.md)

---

## 🤝 Contributing

HSCStack is open-source and welcomes contributions from everyone! See [CONTRIBUTING.md](CONTRIBUTING.md) for the development setup, workflow, and code style guidelines.

Not a developer? You can still contribute academic resources (notes, PDFs, questions) by joining at **[hscstack.site/join][join]**.

---

## 📁 Project Structure

```
platform/
├── app/
│   ├── Models/               # Eloquent models (User, Subject, Node, Resource, Blog, etc.)
│   ├── Http/Controllers/     # Web + Admin + API controllers
│   ├── Mail/                 # Mailable classes
│   ├── Observers/            # Cache invalidation observers
│   ├── Services/             # Business logic services
│   └── Console/Commands/     # Artisan CLI commands (backup, sitemap, cleanup)
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
├── database/migrations/      # Database schema
├── config/                   # App configuration
└── docs/                     # Developer docs
```

---

## 🌍 Community Guidelines

HSCStack's value comes from the quality of what's shared. Please follow these rules:

- ✅ Only upload content **you created** or have permission to share.
- ✅ Keep everything **relevant to the HSC/SSC Bangladesh curriculum**.
- ✅ Add **clear titles and descriptions** so others can find your resource.
- ✅ Be respectful in live chat and comments.
- ❌ Do not upload copyrighted textbooks or board materials without permission.
- ❌ No spam, duplicate, or off-topic content.
- ❌ No profanity, harassment, or hate speech in chat.

Breaking these rules may result in content removal, chat bans, or account suspension.

---

## 📬 Contact & Support

Have a question, found a bug, or want to get involved?

- 📧 Email: `com.tajim@gmail.com`
- 🐛 Issues: [Open a GitHub Issue](../../issues)
- ❤️ Support / Donate: [hscstack.site/donate](https://hscstack.site/donate)

---

## 📄 License

This project is licensed under the [Apache 2.0 License](LICENSE).

---

<div align="center">
  Made with ❤️ by HSC students, for HSC students 🇧🇩
</div>
