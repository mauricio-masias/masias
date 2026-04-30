# masias.co.uk

Personal portfolio and CV website for Mauricio Masias, Full-Stack Developer. Built with Laravel, Inertia.js, and Vue 3, managed through a Filament CMS admin panel.

---

## What It Does

- Presents a portfolio of work, an about section, skills, and a contact form
- Sends two automated emails on contact form submission (notification to the owner, confirmation to the sender)
- Provides a Filament admin panel to manage all content without touching code
- Displays a cookie consent banner compliant with UK GDPR / PECR
- Tracks analytics via Google Tag Manager, loaded only after cookie consent

---

## Tech Stack

### Backend
| Package | Version | Purpose |
|---|---|---|
| PHP | 8.4 | Runtime |
| Laravel | 13 | Application framework |
| Inertia.js (Laravel adapter) | 3 | SPA bridge between Laravel and Vue |
| Filament | 5 | Admin CMS panel |
| PHPUnit | 12 | Testing |
| Laravel Pint | 1 | Code formatting |

### Frontend
| Package | Version | Purpose |
|---|---|---|
| Vue | 3 | UI framework |
| Inertia.js (Vue adapter) | 3 | SPA routing / page components |
| Tailwind CSS | 4 | Utility-first styling |
| Vite | 8 | Asset bundling |

### Infrastructure
| Service | Purpose |
|---|---|
| MariaDB | Database (container: `MASIAS_DB`) |
| PHP built-in server | Local HTTP server (container: `MASIAS_CMS`, port 8080) |
| Hostinger SMTP | Transactional email delivery |
| Google Tag Manager | Analytics tag management |
| Docker / Docker Compose | Local development environment |

---

## Site Structure

```
masias.co.uk/
├── /                   Home — hero, about, skills, featured works
├── /works              Full portfolio with tag filtering and work modals
├── /contact            Contact form
├── /privacy            Privacy & Cookie Policy (DB-managed content)
└── /admin              Filament CMS panel (authenticated)
    ├── Homepage        Edit hero, about text, skills, CV URL
    ├── Works           Create / edit / reorder portfolio works
    ├── Inbox           View contact form submissions
    └── Privacy Policy  Edit privacy & cookie policy content
```

### Key Directories

```
app/
├── Filament/Pages/         Custom CMS pages (Homepage, PrivacyPolicy)
├── Filament/Resources/     CRUD resources (Works, ContactSubmissions)
├── Http/Controllers/       Page controllers (Home, Works, Contact, Privacy)
├── Http/Middleware/        HandleInertiaRequests (shared props)
├── Http/Requests/          Form validation (StoreContactRequest)
├── Mail/                   Mailables (ContactOwnerNotification, ContactSenderConfirmation)
└── Models/                 Eloquent models

resources/
├── js/
│   ├── app.ts              Inertia app bootstrap
│   ├── Components/         Reusable Vue components (CookieBanner)
│   ├── composables/        Shared logic (useReveal)
│   ├── Layouts/            AppLayout (nav, footer, cookie banner)
│   └── Pages/              Inertia page components (Home, Works, Contact, Privacy, ErrorPage)
├── views/
│   ├── app.blade.php       Single HTML shell (GTM head/body snippets live here)
│   ├── mail/               HTML email templates (contact-owner, contact-sender)
│   └── filament/pages/     Blade views for custom Filament pages
└── css/
    └── app.css             Global styles, design tokens, animations

database/
├── migrations/             Schema migrations
├── seeders/                Data seeders (AdminUser, HomeSetting, Work, PrivacySetting)
└── factories/              Model factories for testing
```

---

## Email Flows

Two emails are sent automatically when the contact form is submitted.

### 1. Owner Notification
- **To:** `hello@masias.co.uk` (from `APP_EMAIL`)
- **From:** `noreply@masias.co.uk` (from `APP_NOREPLY_EMAIL`)
- **Reply-To:** The sender's email address
- **Content:** Sender name, email, and message; one-click Reply button
- **Template:** `resources/views/mail/contact-owner.blade.php`

### 2. Sender Confirmation
- **To:** The person who submitted the form
- **From:** `hello@masias.co.uk` (from `APP_EMAIL`)
- **Content:** Thank you message, About section, tech stack, 3 featured works
- **Template:** `resources/views/mail/contact-sender.blade.php`

Both templates are table-based HTML compatible with Outlook 2018+ and all major email clients. Email content (tech stack, featured works) is pulled live from the database at send time.

---

## Security

| Feature | Detail |
|---|---|
| CSRF protection | Laravel's `VerifyCsrfToken` middleware on all web routes |
| Rate limiting | `throttle:5,10` on `POST /contact` — 5 submissions per IP per 10 minutes |
| Honeypot | Hidden `website` field; submissions with it filled are silently discarded |
| Input validation | `StoreContactRequest` validates name, email, and message |
| Error pages | Custom themed 404, 403, 500, 503 pages via Inertia |

---

## Cookie Consent

The cookie banner (shown on first visit) provides Accept / Reject for analytics cookies.

- **Accept** — pushes `{ event: 'cookie_consent_accepted' }` to the GTM `dataLayer`. Gate GA4 (or any other tag) in GTM behind a Custom Event trigger on this event name.
- **Reject** — pushes `{ event: 'cookie_consent_rejected' }`. No analytics tags fire.
- **Returning visits** — consent state is read from `localStorage` (`cookie_consent`) and re-pushed to `dataLayer` on page load. The banner does not reappear.

Essential cookies (session, CSRF) are always active and require no consent.

---

## Environment Variables

Key variables beyond the Laravel defaults:

```env
APP_EMAIL=hello@masias.co.uk           # Used for contact emails and footer display
APP_NOREPLY_EMAIL=noreply@masias.co.uk # From address for owner notification emails

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME="${APP_EMAIL}"
MAIL_PASSWORD=...
```

---

## Local Development

### Requirements
- Docker and Docker Compose
- Node.js (for frontend assets)

### Start the stack

```bash
docker compose up -d
```

This starts:
- `MASIAS_DB` — MariaDB on port `4306`
- `MASIAS_CMS` — PHP built-in server on `http://localhost:8080`

Composer dependencies are installed automatically on first run if `vendor/` is missing.

### Frontend assets

```bash
npm install
npm run dev      # watch mode
npm run build    # production build
```

### Database setup

```bash
docker exec -w /var/www MASIAS_CMS php artisan migrate
docker exec -w /var/www MASIAS_CMS php artisan db:seed
```

### Useful commands

```bash
# Run all tests
docker exec -w /var/www MASIAS_CMS php artisan test --compact

# Format PHP code
docker exec -w /var/www MASIAS_CMS php vendor/bin/pint

# Tail application logs
docker exec -w /var/www MASIAS_CMS php artisan pail

# Preview emails in browser (local only)
http://localhost:8080/dev/mail-preview/owner
http://localhost:8080/dev/mail-preview/sender
```

### Admin panel

```
http://localhost:8080/admin
```

Default credentials are set by `AdminUserSeeder` — see `database/seeders/AdminUserSeeder.php`.

---

## Deployment

The server has PHP/Composer but no Node.js, so frontend assets are built locally and committed to the repository. `/public/build` is intentionally **not** in `.gitignore`.

**Before every commit that includes frontend changes, run:**

```bash
npm run build
```

Then commit the updated `/public/build` alongside your code. If you push without rebuilding, the server will serve stale assets.
