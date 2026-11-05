# Decisions log

| # | Decision | Why |
|---|---|---|
| D1 | Build on Laravel 10 / PHP 8.1.10 | Final user instruction; Laravel 11 requires PHP 8.2+ |
| D2 | Project/brand name "WheelDeal" | No name supplied; configurable |
| D3 | Enquiries/contact do not require OTP | Brief only mandates OTP for sell/buy; keeps enquiry friction low |
| D4 | `pending_verification` internal request status | Lets unverified submissions be audited without polluting the admin workflow |
| D5 | Separate `enquiries` and `contact_enquiries` tables | Clear ownership, simpler exports and admin screens |
| D6 | 360 frames stored as anterior medialibrary collection with order in `custom_properties` | Reuses WebP conversion pipeline and media tooling |
| D7 | Turnstile preferred, reCAPTCHA supported | Both are common; driver can be selected in `.env` |
| D8 | Log OTP provider instead of actual send when keys are absent | Local/demo development and tests remain deterministic |
| D9 | INR, integer prices | Indian marketplace reference; avoids float rounding |
| D10 | No auth UI for public users | Scope has no customer accounts; Sanctum infrastructure stays ready |
| D11 | Filament v3 without Shield | Only two roles with a small permission set; native policies are sufficient |
| D12 | Sitemap built from published, non-empty URLs only | Avoids submitting noindex landing pages and keeps crawl budget focused |
| D13 | `/sitemap.xml` and `/robots.txt` served dynamically, with an optional static file | Dynamic output works out of the box; the scheduled static file is faster on production web servers |
| D14 | Filtered catalogue pages canonicalise to `/cars` and emit `noindex,follow` | Prevents combinatorial filter URLs from competing with landing pages |
| D15 | Slug changes generate 301 redirects via a model observer | Preserves rankings and inbound links without manual admin work |
| D16 | Vehicle import reads CSV/XLSX directly with PhpSpreadsheet | Supports chunked reads, progress and row-level reporting without a second import abstraction |
