# WheelDeal

A production-oriented used four-wheeler marketplace built with Laravel 10, Blade, Tailwind CSS, Alpine.js and Filament v3. The public site is server-rendered for SEO and speed; the admin panel covers catalog, leads, imports and SEO tools.

> **Assumption:** "WheelDeal" is a placeholder brand. Change `APP_NAME`, contact details, social links and the default SEO copy before launch.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 10.50, PHP 8.1.10 |
| Database | MySQL 8 |
| Public UI | Blade, Tailwind CSS 3.4, Alpine.js |
| Admin | Filament v3 (`/admin`) |
| Media | spatie/laravel-medialibrary 10 + Filament media plugin |
| SEO | spatie/laravel-sitemap, JSON-LD, editable SEO pages/redirects |
| Auth | Filament panel auth, spatie/laravel-permission roles; Sanctum available for APIs |
| Queue/cache | Redis in production, database/file fallbacks locally |
| Tests | PHPUnit 10 |

## Features

- Editorial yellow/black responsive storefront at 360 px, 768 px and 1280 px+.
- Home, catalogue, category landing, category + brand landing, brand landing and vehicle detail pages.
- AJAX filters with shareable URLs, crawlable base pages and `noindex,follow` on filtered views.
- Vehicle detail with gallery, specs, features, similar vehicles and a 24–36 frame 360° viewer.
- Sell/buy requests protected by a 6-digit OTP; enquiries and contact forms use honeypot + optional Turnstile/reCAPTCHA.
- Filament admin for vehicles, master data, SEO pages, redirects, users, requests, enquiries and imports.
- Queued, chunked CSV/XLSX import with row-level errors, downloadable reports and retry.
- XML sitemap, dynamic `robots.txt`, canonical URLs, Open Graph/Twitter tags and Organization, WebSite, Car/Offer and BreadcrumbList schema.
- REST API under `/api/v1` sharing the same services as the web UI.

## Requirements

- PHP 8.1.10+ with `gd` or `imagick`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`
- Composer 2
- Node.js 18+ and npm
- MySQL 8
- Redis for production (optional locally)

## Local installation

```powershell
cd C:\laragon\www\wheeldeal

composer install
copy .env.example .env
php artisan key:generate

# Set DB_DATABASE/DB_USERNAME/DB_PASSWORD in .env first, then:
php artisan migrate --seed
php artisan storage:link

npm install
npm run build

php artisan serve --host=127.0.0.1 --port=8000
```

For local development without Redis, set:

```dotenv
CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
```

Then run a worker so OTPs, imports and media conversions process:

```powershell
php artisan queue:work --tries=3
```

The demo seeder creates a queue backlog of media conversions. Clear it in local environments with `php artisan queue:clear` if you do not need the demo images converted immediately.

### Demo accounts

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@wheeldeal.test` | `password` |
| Editor | `editor@wheeldeal.test` | `password` |

These are demo-only credentials. Change or remove them before any shared deployment.

## URLs

| Path | Purpose |
|---|---|
| `/` | Home |
| `/cars` | Catalogue with AJAX filters |
| `/cars/{category}` | Category landing |
| `/cars/{category}/{brand}` | Category + brand landing |
| `/cars/brand/{brand}` | Brand landing |
| `/cars/{brand}/{model}/{slug}` | Vehicle detail |
| `/sell-vehicle`, `/buy-vehicle` | OTP-protected request forms |
| `/verify-otp/{uuid}` | OTP entry, `noindex,nofollow` |
| `/contact`, `/about`, `/privacy`, `/terms` | Static/contact pages |
| `/sitemap.xml`, `/robots.txt` | SEO endpoints |
| `/admin` | Filament panel |
| `/api/v1` | REST API |

Filtered catalogue URLs are shareable but canonicalise to the clean `/cars` page and emit `noindex,follow`. Empty category/brand landing pages also emit `noindex,follow` and are excluded from the sitemap.

Demo vehicles with real 36-frame 360° media:

- `http://127.0.0.1:8000/cars/toyota/fortuner/2022-toyota-fortuner-zx-1`
- `http://127.0.0.1:8000/cars/toyota/glanza/2021-toyota-glanza-vxi-2`

## Configuration

### Brand and contact details

Edit `config/marketplace.php` / `.env`:

- `APP_NAME`
- `CONTACT_PHONE`, `CONTACT_WHATSAPP`, `CONTACT_EMAIL`, `CONTACT_ADDRESS`
- `SOCIAL_FACEBOOK`, `SOCIAL_INSTAGRAM`, `SOCIAL_YOUTUBE`, `SOCIAL_X`
- `SEO_DEFAULT_TITLE`, `SEO_DEFAULT_DESCRIPTION`, `SEO_TWITTER_HANDLE`

### OTP

OTP rules live in `config/otp.php`: 6 digits, 5-minute expiry, 3 attempts and a 30-second resend cooldown. Codes are hashed; delivery is queued and logged.

| Channel | Drivers |
|---|---|
| SMS | `log`, `msg91`, `twilio` |
| WhatsApp | `log`, `whatsapp` (WhatsApp Cloud API) |

```dotenv
OTP_SMS_DRIVER=msg91
OTP_WHATSAPP_DRIVER=whatsapp
MSG91_AUTH_KEY=
MSG91_SENDER_ID=WHEELD
MSG91_TEMPLATE_ID=
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_FROM=
WHATSAPP_CLOUD_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_TEMPLATE_NAME=otp_verification
```

Use `log` locally. OTPs are written to the application log, never returned in HTTP responses.

### Bot protection

Set `TURNSTILE_SECRET_KEY` (preferred) or `RECAPTCHA_SECRET_KEY`. If either secret is present, the challenge is enforced. If neither is configured, validation is skipped for local/demo use. Put the matching site key in `TURNSTILE_SITE_KEY` / `RECAPTCHA_SITE_KEY` for the frontend widget.

### Media

Vehicle galleries and 360° frames are stored through medialibrary. `FILESYSTEM_DISK=public` locally; run `php artisan storage:link`. Media URLs are host-relative by default (`FILESYSTEM_PUBLIC_URL=/storage`), so the same build works on `localhost`, `127.0.0.1`, a Laragon virtual host or HTTPS. Set `FILESYSTEM_PUBLIC_URL` to an absolute CDN URL when media is served from another domain. For S3/CDN, set `FILESYSTEM_DISK=s3`, configure the AWS variables, then update the media disk configuration if you want conversions on S3. Gallery conversions produce WebP `thumb`, `card`, `large` and `og` variants; 360 frames are converted to WebP at 1200×800.

The bundled demo seed includes real 36-frame studio spins for the Fortuner and Glanza demo listings. They live in `database/seeders/assets/demo-cars`; `RealVehicleMediaSeeder` copies six frames into `gallery` and all 36 into `360-frames`. Run `php artisan queue:work --stop-when-empty` afterward so the WebP conversions are generated. These are third-party images for local development/testing only — replace them with photography you own or have licensed before publishing. See `database/seeders/assets/demo-cars/README.md` for sources.

The 360° viewer starts loading only when its tab is opened. It preloads and fully decodes every frame behind a progress overlay, then renders the decoded sequence on a canvas so rotation is flicker-free on laptops and desktops. Drag, on-screen arrow controls, keyboard and the Play/Pause auto-rotation toggle are enabled only after the whole sequence is ready. Manual rotation pauses auto-rotation so the chosen angle stays in view.

## Admin guide

### Roles

- **Super Admin** — everything, including users, imports and redirect deletion.
- **Editor** — catalog, SEO pages, requests, enquiries and imports; cannot manage users or delete imports/redirects.

Policies are auto-discovered under `app/Policies`. Permission names follow `{action}_{resource}` (for example `view_any_vehicles`, `export_requests`).

### Catalog

Manage brands, categories, models, variants, fuel types, cities and features before importing vehicles. A vehicle belongs to a brand, model, category, fuel type and city; the detail URL is `/cars/{brand}/{model}/{vehicle-slug}`.

Vehicle media collections:

- `gallery` — ordered images with WebP conversions.
- `360-frames` — 24–36 ordered frames; the 360° control appears when at least 24 exist.

### Requests and enquiries

Verified sell/buy requests move through `new → contacted → approved/rejected`. Enquiries and contact messages move through `new → contacted → closed`. Admin tables support status actions, internal notes and CSV export.

### Bulk import

Open **Admin → Imports**, download the CSV template, then upload a CSV/XLSX/XLS file. Processing is queued and chunked.

Required header columns: `brand`, `model`, `year`, `price`.

Optional columns include title, variant, category, fuel type, city, kilometres, ownership, transmission, seats, colour, registration details, engine/power/torque/mileage, description, condition, status, featured/popular flags, pipe-separated features, slug, meta fields and publish date. Common aliases such as `make`, `brand_name`, `model_name`, `body_type`, `fuel`, `city_name`, `kms`, `owners` and `feature` are accepted.

Import rules:

- Master data must already exist; unknown brands/models/features are reported as row errors.
- `status` defaults to `draft`; set `published` and a `published_at` date to publish.
- `transmission` falls back to the variant's transmission when omitted.
- Features are separated with `|`.
- Supplying `slug` matches an existing vehicle. Updates require the **update existing** option; generated slugs never overwrite existing records.
- Deleted vehicles keep their slug and must be restored or given a different slug.
- Failed rows are stored in `import_errors` and exported as a CSV report; a failed batch with zero successes can be retried.

## SEO

- Every public page has one H1, a canonical URL, meta description, Open Graph/Twitter tags and breadcrumbs.
- Landing pages can override title, description, H1 and intro copy in **Admin → SEO pages**.
- JSON-LD includes Organization, WebSite, BreadcrumbList and Car/Offer on vehicle detail pages.
- `robots.txt` is served dynamically with the absolute sitemap URL and disallows `/admin`, `/api` and `/verify-otp`.
- The sitemap includes the home page, catalogue, static pages, non-empty category/brand/category-brand landings and published vehicles. Filtered/noindex pages are excluded.

Generate or refresh the static sitemap:

```powershell
php artisan sitemap:generate --fresh
```

The command writes `public/sitemap.xml`. A daily `03:30` schedule also rebuilds it. `/sitemap.xml` renders dynamically when no static file exists, so either deployment style works. Rebuild after bulk imports if your web server serves the static file and you do not run the scheduler.

Renaming a vehicle, brand, category or model creates a 301 redirect through `SlugRedirectObserver`. Redirects can also be managed manually in **Admin → Redirects**.

## API

Base path: `/api/v1`. Endpoints share the web services and return JSON resources.

| Method | Endpoint |
|---|---|
| GET | `/api/v1/home` |
| GET | `/api/v1/filters` |
| GET | `/api/v1/vehicles` |
| GET | `/api/v1/vehicles/{uuid}` |
| POST | `/api/v1/requests` |
| GET | `/api/v1/requests/{uuid}` |
| POST | `/api/v1/requests/{uuid}/verify` |
| POST | `/api/v1/requests/{uuid}/resend` |
| POST | `/api/v1/enquiries` |
| POST | `/api/v1/contact` |

Sanctum is installed and configured but no public token-issuing UI is enabled in v1.

## Performance and caching

- Home, unfiltered listing and filter options are cached; catalog model observers invalidate the cache version.
- Listing queries eager-load card relations and select only required columns.
- Images use WebP conversions with 420/700/1280w `srcset`, explicit dimensions, `loading="lazy"` below the fold and an eager/high-priority detail hero.
- Vite hashed assets; the 360° script is a separate entry loaded only on pages that need it.
- Database indexes cover status, foreign keys, price/year/km, transmission, featured and publish date.
- Production target: Redis cache/queue/session, OPcache, CDN for `public/` assets and a supervised queue worker.

A local production-mode check (Lighthouse 12.8 mobile, PHP 8.1 OPcache, cached config/routes and gzip on HTML/CSS/JS) scored **96 Performance, 100 Accessibility, 100 Best Practices and 100 SEO**, with **LCP 2.42 s**, **CLS 0** and **TBT 67 ms** on the home page. The temporary compression harness is not shipped: configure gzip/Brotli at the web server or CDN and repeat the audit against staging before launch.

## Testing

Tests use an isolated MySQL database, configured in `phpunit.xml` as `wheeldeal_test`:

```powershell
php artisan optimize:clear
php artisan test
```

Run `optimize:clear` first if config/routes are cached; the test suite uses its own environment values from `phpunit.xml`. As a safety net, the test bootstrap refuses to run unless the active database name ends in `_test`, so a stale config cache cannot wipe the demo database.

Coverage includes catalogue filters and noindex rules, OTP persistence/verification/throttling, CSV/XLSX chunked import, admin route/policy access, sitemap/robots, 301 redirects and structured data.

## Security notes and known dependency advisories

The application applies FormRequests, CSRF, policies, rate limiting, upload validation, sensitive-field filtering and honeypots. OTP codes are hashed and never logged in plaintext.

`composer audit` currently reports advisories against:

- **laravel/framework 10.x** — Laravel 10 is past security support; the reported fixes are in Laravel 12+. The critical TokenGuard advisory is not reachable through the current UI because no API tokens are issued and Sanctum's token routes are not enabled, but this must be re-reviewed before launch.
- **spatie/laravel-medialibrary 10.x** — patched in 11.23+, which requires PHP 8.2+. Uploads are restricted by MIME type, size and Filament authorization; remote URL ingestion is not exposed.
- **spatie/browsershot 3.x** — pulled in transitively by the sitemap package. This project only renders sitemap tags directly and does not use the crawler/Browsershot execution path.

These are a direct consequence of the required PHP 8.1.10 runtime. **Before a public launch**, plan an upgrade to PHP 8.2+/Laravel 12 and medialibrary 11, then re-run `composer audit`.

## Project documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — architecture, URL map, request flows and assumptions.
- [`docs/ERD.md`](docs/ERD.md) — entity relationships.
- [`docs/DECISIONS.md`](docs/DECISIONS.md) — decision log.

## Deployment checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` and a strong `APP_KEY`.
2. Use MySQL 8 with a least-privilege database user.
3. Configure Redis for cache, queue and sessions; run `php artisan config:cache route:cache view:cache`.
4. Set `FILESYSTEM_DISK`, run `php artisan storage:link`, and point media at S3/CDN if required.
5. Run `php artisan migrate --force`.
6. Configure a supervised `php artisan queue:work` process and the scheduler (`php artisan schedule:run` every minute).
7. Run `php artisan sitemap:generate --fresh` after launch content is loaded.
8. Configure Turnstile/reCAPTCHA and a real OTP driver.
9. Remove or rotate demo accounts and test data.
10. Run `php artisan test` and `composer audit`; address or formally accept the advisories above.
11. Complete a manual responsive and Lighthouse pass at 360/768/1280 px, then submit the sitemap in Search Console.

## License

Application code is provided for the WheelDeal project. The framework and third-party packages retain their respective licenses.
