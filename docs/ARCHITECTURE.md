# WheelDeal — Architecture

> Project name is an assumption. It is configurable through `APP_NAME`; the UI brand is
> "WheelDeal", a yellow/black 4-wheeler marketplace.

## 1. Platform decision

| Item | Decision |
|---|---|
| Framework | Laravel 10.50 (supersedes the brief's Laravel 11 line) |
| PHP | 8.1.10 (explicit final instruction; supersedes PHP 8.3) |
| Database | MySQL 8.0 |
| Cache / queue | Redis (file/sync fallbacks for local setup) |
| Public UI | Blade + Tailwind CSS + Alpine.js, server-rendered |
| Admin | Filament v3 at `/admin` |
| API | `/api/v1`, Laravel Sanctum ready (no token UI in v1) |
| Import | maatwebsite/excel, queued + chunked |
| Media | spatie/laravel-medialibrary 10, WebP conversions |
| SEO | spatie/laravel-sitemap 6, JSON-LD, canonical/noindex rules |
| Audit | spatie/laravel-activitylog + spatie/laravel-permission |

Laravel 10 was chosen over Laravel 11 because it is the last line compatible with
PHP 8.1.10. Filament v3 and medialibrary 10 both support Laravel 10/PHP 8.1.

## 2. Style of architecture

A **modular monolith** with a light repository/service layer:

```
HTTP (Blade / API / Filament)
        │  FormRequest validation, Policies, Resources
        ▼
Application services (business rules, transactions, cache, events)
        │
        ▼
Repositories (interfaces bound to Eloquent implementations)
        │
        ▼
Eloquent models + MySQL, Redis, media disk, OTP providers
```

Rules:

- Controllers are thin: validate → call service → return view/resource/redirect.
- Public and API controllers call the **same** services, so the future mobile app needs no rewrite.
- Filament resources use services for non-trivial writes (vehicle create/update, imports) and models for simple master data.
- Repositories own reusable query composition (filters, sorting, eager loading) and are bound in `RepositoryServiceProvider`.
- No N+1: listing/detail repositories eager-load relations; filter option queries are cached.
- OTP delivery is always queued; the public request row exists before OTP is sent.

## 3. Directory map

```
app/
├── Console/Commands/             GenerateSitemap, ExpireOtpVerifications
├── DTOs/                         VehicleFilterData, SeoData, OtpResult, RequestData
├── Enums/                        UserRole, VehicleStatus, RequestType, RequestStatus,
│                                 EnquiryStatus, OtpChannel, OtpDeliveryStatus, Transmission
├── Exceptions/                   Domain exceptions (OtpThrottled, InvalidOtp, etc.)
├── Filament/
│   ├── Resources/                VehicleResource, BrandResource, ModelResource,
│   │                             CategoryResource, FuelTypeResource, CityResource,
│   │                             FeatureResource, CustomerRequestResource,
│   │                             EnquiryResource, ContactEnquiryResource,
│   │                             SeoPageResource, RedirectResource,
│   │                             ImportBatchResource
│   ├── Pages/                    BulkVehicleImport, Dashboard
│   └── Widgets/                  VehicleStats, RequestStats, RecentRequests
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/               Catalog, Vehicle, Request, Otp, Enquiry, Contact
│   │   ├── Auth/                 (Sanctum endpoints reserved; not enabled in v1 UI)
│   │   └── Web/                  Home, Listing, Vehicle, SellBuy, Enquiry, Static
│   ├── Middleware/               HandleRedirects, SecurityHeaders, TrackVehicleView
│   ├── Requests/                 Web + Api FormRequests (OTP, filters, imports)
│   └── Resources/                VehicleResource, BrandResource, RequestResource…
├── Jobs/                         SendOtpJob, ProcessVehicleImport, OptimiseMedia
├── Models/                       Vehicle, Brand, VehicleModel, Category, …
├── Observers/                    SlugRedirectObserver, VehicleCacheObserver
├── Policies/                     VehiclePolicy, CustomerRequestPolicy, ImportPolicy
├── Providers/                    App, Auth, Event, Route, Repository, Filament
├── Repositories/
│   ├── Contracts/                VehicleRepositoryInterface, BrandRepositoryInterface…
│   └── Eloquent/                 EloquentVehicleRepository…
├── Rules/                        Recaptcha, ValidPhone
├── Services/
│   ├── Catalog/                  VehicleCatalogService, FilterOptionService
│   ├── Media/                    VehicleMediaService
│   ├── Otp/                      OtpService, OtpManager, Drivers/*
│   ├── Requests/                 CustomerRequestService, EnquiryService
│   ├── Import/                   VehicleImportService, VehicleImportRowValidator
│   ├── Seo/                      SeoService, SitemapService
│   └── Support/                  CacheService, SlugService
└── View/Components/              Layout, Seo, VehicleCard, Breadcrumbs, …
config/
├── marketplace.php               brand contact data, pagination, cache TTLs
├── otp.php                       provider drivers, expiry, attempts, cooldown
├── seo.php                       defaults, social handles, noindex rules
└── services.php                  MSG91 / Twilio / WhatsApp Cloud / Turnstile keys
database/
├── migrations/                   see docs/ERD.md
├── factories/                    demo data factories
└── seeders/                      DemoSeeder, MasterDataSeeder, AdminUserSeeder
docs/
├── ARCHITECTURE.md
├── ERD.md
└── DECISIONS.md
resources/
├── css/app.css                   design tokens + Tailwind layers
├── js/app.js                     Alpine, menu, enquiry form UX
├── js/vehicle-360.js             loaded only on detail pages
└── views/
    ├── components/               layout, seo, cards, filters, forms, icons
    ├── home.blade.php
    ├── vehicles/index.blade.php
    ├── vehicles/show.blade.php
    ├── requests/sell.blade.php, buy.blade.php, verify.blade.php, success.blade.php
    └── pages/about.blade.php, contact.blade.php, privacy.blade.php, terms.blade.php
routes/
├── web.php                       public pages, OTP, enquiries, sitemap
└── api.php                       /api/v1 JSON surface
```

## 4. Public URL map

| URL | Purpose |
|---|---|
| `/` | Home, featured/latest/popular, category + brand discovery |
| `/cars` | Searchable, shareable AJAX filter listing |
| `/cars/{category}` | Category landing page |
| `/cars/{category}/{brand}` | Crawlable combination landing page, optional SEO override |
| `/cars/brand/{brand}` | Brand landing page |
| `/cars/{brand}/{model}/{slug}` | Vehicle detail, gallery, 360°, enquiry |
| `/sell-vehicle` | Sell form → OTP verification |
| `/buy-vehicle` | Buy request form → OTP verification |
| `/verify-otp/{uuid}` | OTP entry / resend / verified success |
| `/contact` | Contact enquiry |
| `/about`, `/privacy`, `/terms` | Static pages |
| `/sitemap.xml`, `/robots.txt` | SEO |
| `/admin` | Filament panel |

Filter state is query-string based and pushed with `history.replaceState` so AJAX filters are
shareable. Filtered URLs emit `noindex,follow`; canonical points to the clean landing page.

## 5. Request flows

### Sell / buy

1. Validate form (`VehicleRequestRequest`), honeypot + Turnstile/reCAPTCHA, rate limits.
2. Persist `customer_requests` status `pending_verification` and optional photos.
3. Create hashed OTP (`otp_verifications`), queue `SendOtpJob` (SMS or WhatsApp).
4. Redirect to `/verify-otp/{uuid}`.
5. Verify 6 digits: hash check, expiry 5 min, max 3 attempts, 30 s resend cooldown.
6. On success set `otp_verified_at` and request status `new`; admin sees it.
7. Admin flow: `new → contacted → approved/rejected`, notes appended, activity logged.

### Enquiry / contact

Enquiries are not OTP-gated in v1 (stated assumption). They still use honeypot,
Turnstile, validation, IP/phone rate limits and a `new → contacted → closed` workflow.

## 6. 360° viewer

- Media collection `360-frames` on `Vehicle`, 24–36 ordered WebP frames.
- Admin upload enforces 24–36 images; each frame's `sort` is stored in `custom_properties`.
- Detail page outputs a JSON array of frame URLs + dimensions.
- `resources/js/vehicle-360.js` is a standalone Vite entry, loaded with `defer` only on the
  detail page. It preloads a handful of frames, shows a progress/loading state, supports
  pointer drag, touch swipe and auto-rotate, and uses `requestAnimationFrame` throttling.

## 7. Caching / performance

- Redis caches: home payload, filter options, brand grid, category list, SEO pages.
- Cache invalidation via model observers and short TTLs (10 minutes default).
- `vehicles` indexes: `status`, `brand_id`, `vehicle_model_id`, `category_id`,
  `fuel_type_id`, `city_id`, `price`, `year`, `km_driven`, `transmission`, `seats`,
  `colour`, `is_featured`, `published_at`, composite price/year indexes.
- Listing queries select only needed columns and eager-load `brand`, `vehicleModel`,
  `category`, `fuelType`, `city`, `media`.
- WebP conversion `srcset` (420/700/1280w), explicit width/height, `loading=lazy`
  except the LCP hero/detail image.
- Vite hashed assets, deferred JS, CSS preload, CDN-ready `ASSET_URL`.
- Local production-mode Lighthouse 12.8 mobile check: 96 Performance, 100 Accessibility,
  100 Best Practices, 100 SEO; LCP 2.42 s, CLS 0, TBT 67 ms with OPcache, cached
  config/routes and gzip. The web server/CDN must enable gzip or Brotli in production.

## 8. Security

- All writes: FormRequest, CSRF (web), Policies, rate limits.
- OTP hashes only, never plaintext; delivery logs exclude the OTP.
- Uploads validated by mime/size and stored through medialibrary; SVG/executables rejected.
- Recaptcha/Turnstile optional but enforced when keys are configured.
- Filament access limited to active users with `Super Admin` or `Editor` roles.
- Security headers middleware (CSP-safe defaults, HSTS in production).

## 9. Explicit assumptions

1. **Laravel 10 / PHP 8.1.10** are used, per the final instruction.
2. Brand name "WheelDeal" and demo contact details are placeholders.
3. Enquiries and contact forms use bot protection but not OTP; only sell/buy requests require OTP.
4. All prices are INR integers (no decimals). Mileage is kmpl; power is bhp.
5. OTP is 6 numeric digits, bcrypt-hashed, 5-minute expiry, 3 attempts, 30-second resend.
6. A phone number can have one active OTP per purpose; old codes are invalidated on resend.
7. Admin roles: **Super Admin** (everything) and **Editor** (catalog, requests, enquiries; no user/role/import-batch deletion).
8. Media is stored on the `public` disk locally; S3/CDN is configuration-only.
9. SEO landing copy is optional; generated fallback copy is used when no override exists.
10. Location is city-based; there is no map/radius search in v1.
