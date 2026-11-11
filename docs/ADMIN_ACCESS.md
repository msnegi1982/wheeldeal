# Admin and Editor Access

Demo URL: `http://127.0.0.1:8000`

Both roles use the same Filament admin panel. The account role controls what is visible and allowed after login.

## Login route

| Route | URL | Purpose |
|---|---|---|
| `GET /admin/login` | `http://127.0.0.1:8000/admin/login` | Admin/editor login |
| `GET /admin` | `http://127.0.0.1:8000/admin` | Dashboard after login |
| `POST /admin/logout` | `http://127.0.0.1:8000/admin/logout` | Sign out |

## Demo credentials

| Role | Email | Password |
|---|---|---|
| Super Admin | `admin@wheeldeal.test` | `password` |
| Editor | `editor@wheeldeal.test` | `password` |

> These are demo credentials only. Change or remove these accounts before any shared or production deployment.

## Main admin routes

| Route | Super Admin | Editor | Purpose |
|---|:---:|:---:|---|
| `/admin` | Yes | Yes | Dashboard |
| `/admin/vehicles` | Yes | Yes | Vehicles |
| `/admin/vehicle-models` | Yes | Yes | Vehicle models |
| `/admin/vehicle-variants` | Yes | Yes | Vehicle variants |
| `/admin/brands` | Yes | Yes | Brands |
| `/admin/categories` | Yes | Yes | Categories |
| `/admin/fuel-types` | Yes | Yes | Fuel types |
| `/admin/cities` | Yes | Yes | Cities |
| `/admin/features` | Yes | Yes | Features |
| `/admin/customer-requests` | Yes | Yes | Sell/buy requests |
| `/admin/enquiries` | Yes | Yes | Vehicle enquiries |
| `/admin/contact-enquiries` | Yes | Yes | Contact messages |
| `/admin/import-batches` | Yes | Yes | Bulk CSV/XLSX imports |
| `/admin/seo-pages` | Yes | Yes | SEO landing-page overrides |
| `/admin/redirects` | Yes | Yes | 301 redirects |
| `/admin/users` | Yes | No | User management |

## Role differences

- **Super Admin:** full access, including user management and destructive actions.
- **Editor:** catalog, SEO pages, requests, enquiries and imports; cannot manage users or delete imports/redirects.

## Notes

- Replace `127.0.0.1:8000` with the deployed domain when using the site outside local development.
- Login is rate-limited by Filament/Laravel.
- Inactive users cannot access the admin panel.
