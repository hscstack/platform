[website]: https://hscstack.mvp.bd
[join]: https://hscstack.mvp.bd/join

# HSCStack 📚

> A curated resource platform for HSC students of Bangladesh — built by members, for everyone.

HSCStack is a lightweight, structured platform where verified members share academic resources — practicals, notes, PDFs, videos, and questions — organized by subject and chapter, so every HSC student can find what they need in one place.

---

## 🌟 What is HSCStack?

HSCStack is built for students preparing for the **Higher Secondary Certificate (HSC)** exams in Bangladesh. It brings together a trusted library of student-made resources, organized neatly by subject and chapter — no more digging through WhatsApp groups or random Facebook posts.

Browsing is open to everyone. But to **contribute**, you need to be an HSCStack member first — keeping the content quality high and the community trustworthy.

---

## ✨ Features

- 📂 **Subject Archive** — Resources organized under subjects with visual themes and icons.
- 🗂️ **Chapter-based Structure** — Each subject has nested chapters (nodes) so you can find exactly what you need.
- 📄 **Multiple Resource Types** — Notes, PDFs, images, videos, and exam questions — all in one place.
- 🔒 **Members-Only Uploads** — Only verified HSCStack members can contribute content.
- 🛠️ **Admin Panel** — Full control over subjects, chapters, and resources via a clean admin area.
- ☁️ **File Upload Support** — Resources are stored and served from public storage.
- 🆓 **Free to Browse** — No account needed to read or download resources.

---

## 📚 Resource Types

| Type | Description |
|------|-------------|
| **Notes** | Handwritten or typed notes by fellow students |
| **PDFs** | Formatted documents, guides, and reference sheets |
| **Images** | Diagrams, charts, and visual study aids |
| **Videos** | Recorded explanations and walkthroughs |
| **Questions** | Practice questions, past papers, and MCQs |

---

## 🚀 Getting Started

### For Students (Browsing)

1. Visit [HSCStack][website] — no account needed.
2. Pick your subject and chapter.
3. Browse, read, or download any resource freely.

### For Members (Contributing)

Only HSCStack members can upload resources. To become a member:

1. Join at 👉 **[hscstack.mvp.bd/join][join]**
2. Once accepted, log in and go to the relevant subject and chapter.
3. Upload your resource — add a title, type, and short description.
4. Your contribution goes live after a quick admin review.

> **Why membership?** We keep uploads member-only to maintain quality and ensure content is relevant, accurate, and trustworthy for all HSC students.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel (PHP) |
| **Frontend** | Vue.js via Inertia.js |
| **Storage** | Public file storage |
| **Admin Panel** | Built-in Laravel admin area |

---

## 🤝 Contributing to the Codebase

HSCStack is not open for general pull requests. To contribute code, you must be a **core developer** on the team.

To apply, join via 👉 **[hscstack.mvp.bd/join][join]** — once your application is reviewed and accepted, we'll onboard you to the codebase.

Once you're a core developer:

1. Clone the repository
2. Create a new branch: `git checkout -b feature/your-feature-name`
3. Commit your changes: `git commit -m "Add: your feature"`
4. Push to your branch: `git push origin feature/your-feature-name`
5. Open a Pull Request for review

---

## 📁 Project Structure

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

---

## 🌍 Community Guidelines

HSCStack's value comes from the quality of what's shared. Please follow these rules:

- ✅ Only upload content **you created** or have permission to share.
- ✅ Keep everything **relevant to the HSC Bangladesh curriculum**.
- ✅ Add **clear titles and descriptions** so others can find your resource.
- ❌ Do not upload copyrighted textbooks or board materials without permission.
- ❌ No spam, duplicate, or off-topic content.

Breaking these rules may result in content removal or membership revocation.

---

## 📬 Contact & Support

Have a question, found a bug, or want to get involved?

- 📧 Email: `hello@tajimz.xyz`
- 🐛 Issues: [Open a GitHub Issue](../../issues)

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

<div align="center">
  Made with ❤️ by HSC students, for HSC students 🇧🇩
</div>
