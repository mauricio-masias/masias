# masias.co.uk

Personal portfolio and CV website for Mauricio Masias, Full-Stack Developer. Built with Laravel, Inertia.js, and Vue 3, managed through a Filament CMS admin panel.

---

## What It Does

- Presents a portfolio of work, an about section, skills, and a contact form
- Sends two automated emails on contact form submission (notification to the owner, confirmation to the sender)
- Provides a Filament admin panel to manage all content without touching code
- Displays a cookie consent banner compliant with UK GDPR / PECR
- Tracks analytics via Google Tag Manager, which is not loaded at all until consent is given
- Shows a Google Analytics dashboard in the admin panel (visitors, trends, countries, cities)
- Archives analytics totals to the local database, so history outlives GA4's retention window

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
| google/analytics-data | 0.26 | GA4 Data API client |

### Frontend
| Package | Version | Purpose |
|---|---|---|
| Vue | 3 | UI framework |
| Inertia.js (Vue adapter) | 3 | SPA routing / page components |
| Tailwind CSS | 4 | Utility-first styling |
| Vite | 8 | Asset bundling |
| Vitest | 4 | Frontend unit tests (jsdom) |

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
    ├── Dashboard       Google Analytics: visitors, trend chart, countries, cities
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
├── Console/Commands/       analytics:sync (archives GA4 totals locally)
├── Filament/Widgets/       Dashboard analytics widgets (stats, charts, cities table)
├── Services/Analytics/     GA4 Data API adapter, caching, DTOs, local snapshot
└── Models/                 Eloquent models

resources/
├── js/
│   ├── app.ts              Inertia app bootstrap
│   ├── Components/         Reusable Vue components (CookieBanner)
│   ├── composables/        Shared logic (useReveal, useCookieConsent)
│   ├── lib/                Framework-free helpers (consent.ts)
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

Google Tag Manager is **not injected at all** until analytics cookies are accepted. Before consent no request reaches Google and none of its cookies exist. Google Consent Mode v2 defaults (everything denied) are emitted on every response, before any Google tag.

- **Accept** — records consent, sends the Consent Mode `update` signal, and injects GTM. Also pushes `{ event: 'cookie_consent_accepted' }` for any existing GTM trigger.
- **Reject** — records the choice. Nothing is loaded.
- **Withdraw** — the **Cookie Settings** link in the footer reopens the banner. Withdrawing deletes the `_ga*` cookies already stored, then reloads so the server stops emitting GTM entirely.
- **Returning visits** — the choice lives in a `cookie_consent` cookie (6 months), so the server knows before rendering whether to emit the snippet. A pre-existing `localStorage` answer is migrated automatically.

Essential cookies (`masias-session`, `XSRF-TOKEN`) are always active and require no consent.

Because the browser writes `cookie_consent` in plain text, it is excluded from Laravel's cookie encryption in `bootstrap/app.php`. Without that, Laravel discards it as tampered and a consenting visitor is never tracked.

The consent logic lives in `resources/js/lib/consent.ts` and is covered by `npm test`.

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

# Analytics — required for the dashboard and for GTM to load at all
GA4_PROPERTY_ID=535450240                      # Numeric property id, NOT the G-XXXX measurement id
GA4_MEASUREMENT_ID=G-10P3CX72KQ                # Reference only; used to verify the GTM tag
GA4_CREDENTIALS_PATH=storage/app/private/analytics/ga4-service-account.json
GTM_CONTAINER_ID=GTM-5MCJTBPW                  # Without this, GTM never loads and analytics stop silently

# Optional analytics overrides
ANALYTICS_DRIVER=google                        # "fake" serves deterministic sample data (opt-in only)
ANALYTICS_TIMEZONE=Europe/London               # Keep in sync with the GA4 property timezone
ANALYTICS_EARLIEST_DATE=2020-01-01             # Lower bound for "all time"
```

`GA4_PROPERTY_ID` must be the **numeric** id from GA4 Admin → Property Settings. A `G-` measurement id is rejected at boot with an explanatory error, because Google otherwise returns an opaque permission failure.

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

# Run frontend tests (consent logic)
npm test

# Archive GA4 totals locally (see Server Setup)
docker exec -w /var/www MASIAS_CMS php artisan analytics:sync

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

The server has PHP/Composer but no Node.js, so frontend assets are built locally and committed to the repository. `public_html/build` is intentionally **not** in `.gitignore`.

**Before every commit that includes frontend changes, run:**

```bash
npm run build
```

Then commit the updated `public_html/build` alongside your code. If you push without rebuilding, the server serves stale assets with no error.

### What git does not carry

These never arrive via `git pull` and must be handled on the server:

| Not in git | Action |
|---|---|
| `vendor/` | `composer install` on any dependency change |
| `.env` | Maintained by hand on the server |
| `storage/app/private/analytics/*.json` | Uploaded by hand (contains a private key) |
| `public_html/storage`, `public_html/assets/works` | Symlinks created once on the server |

---

## Server Setup

Full runbook for standing the analytics stack up on a fresh server, or rebuilding it. Substitute your own SSH user, host and port (Hostinger shows them under **Advanced → SSH Access**), and the application root — the directory **containing** `public_html`, e.g. `~/domains/masias.co.uk`.

### 1. Environment variables

Add to the server's `.env`:

```env
GA4_PROPERTY_ID=535450240
GA4_MEASUREMENT_ID=G-10P3CX72KQ
GA4_CREDENTIALS_PATH=storage/app/private/analytics/ga4-service-account.json
GTM_CONTAINER_ID=GTM-5MCJTBPW
```

Without `GTM_CONTAINER_ID` the tag never loads and analytics stop collecting, with no error anywhere.

### 2. Upload the service account key

The key is gitignored, so it is never deployed by a pull. `scp` will not create the remote directory, so make it first:

```bash
ssh -p <port> <user>@<host> 'mkdir -p <app-root>/storage/app/private/analytics'

scp -P <port> storage/app/private/analytics/ga4-service-account.json \
  <user>@<host>:<app-root>/storage/app/private/analytics/

ssh -p <port> <user>@<host> 'chmod 600 <app-root>/storage/app/private/analytics/ga4-service-account.json'
```

`chmod 600` matters: this is a live private key and shared hosting defaults can be group-readable.

### 3. Install dependencies and migrate

```bash
cd <app-root>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
```

`composer install` is required on any deploy that changes `composer.json`. Skipping it produces
`Class "Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient" not found`.

`migrate` creates `analytics_buckets` and `analytics_geo_buckets`, which the archive writes to.

### 4. Apply the privacy policy

```bash
php artisan db:seed --class=PrivacyPolicySeeder --force
```

`--force` is required in production. **This overwrites whatever the admin panel currently holds** — if the policy has been edited there, copy it first. The alternative is pasting the content into Admin → Privacy Policy by hand; the source of truth is `database/seeders/PrivacyPolicySeeder.php`.

### 5. Test before automating

Never debug this through cron, which fails silently:

```bash
php -v                                        # must be 8.3+
php artisan analytics:sync --from=2026-09-01
```

Success prints bucket and geo row counts per chunk. This one command exercises the key, the `.env` values, the migrations and outbound access to Google.

### 6. Schedule the nightly sync

Without this, nothing is archived and data is lost permanently once GA4's retention window passes.

On Hostinger: **Advanced → Cron Jobs**, once a day (04:00 if the hour is selectable).

Custom command type, which allows a log so failures are visible:

```
/usr/bin/php <app-root>/artisan analytics:sync >> <app-root>/storage/logs/analytics-sync.log 2>&1
```

Or PHP job type, entering only the path and arguments:

```
<app-root-relative-to-home>/artisan analytics:sync
```

Notes:
- No `cd` is needed. Laravel derives its base path from the `artisan` file's location, so the relative `GA4_CREDENTIALS_PATH` still resolves.
- Confirm `/usr/bin/php` is 8.3+. Hostinger's default binary is sometimes older than the version configured for the site; if so, use the versioned path.
- Do not run hourly. Each run costs GA4 API quota and there is nothing new within a day.
- `routes/console.php` also registers this task with Laravel's scheduler. That path needs `* * * * * php artisan schedule:run` instead. Use one approach or the other — with the direct daily cron, the scheduler entry never fires and is harmless.

### 7. Confirm GA4 data retention

**GA4 Admin → Data Retention → set to 14 months.** The default is 2 months.

This is the single most time-critical step. The local archive only protects data captured from the moment the sync starts running; anything Google has already discarded is unrecoverable. Do this before anything else if the property is new.

### 8. Verify the next day

```bash
php artisan tinker --execute 'echo App\Models\AnalyticsBucket::max("bucket_start");'
```

Yesterday's date means cron is working. The date of the manual backfill means it is not — check `storage/logs/analytics-sync.log`.

### Reference

| Step | Command |
|---|---|
| Backfill a range | `php artisan analytics:sync --from=2026-04-01 --chunk=60` |
| Re-sync recent days | `php artisan analytics:sync` (rolling 35-day window) |
| Archive coverage | `php artisan tinker --execute 'echo App\Models\AnalyticsBucket::count();'` |

The sync is idempotent — re-running over the same dates refreshes rather than duplicating, and a missed night heals itself on the next run because each run re-syncs a rolling 35-day window.
