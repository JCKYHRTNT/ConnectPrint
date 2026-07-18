# ConnectPrint

ConnectPrint is a Laravel 11 academic prototype that adapts the original TheBoysMarketplace template into an artwork marketplace for Printbox handoff workflows.

The Laravel application lives at the repository root.

## Scope

ConnectPrint sells creator-defined printable artwork access. It does not process Printbox fees, generate Printbox QR codes, submit files to Printbox, or control physical printing.

Core workflows included:

- Public artwork browsing, category/search/tag filtering, and creator profiles.
- Personal artwork upload and library management.
- Public, unlisted, private, and archived visibility.
- Printable vs display-only artwork rules.
- Cart with one printable-access item per artwork.
- Simulated checkout with purchase and purchase-item snapshots.
- Purchased artwork library and controlled print-ready file access.
- Admin moderation, artwork reports, creator sales, and in-app notifications.
- Static Printbox instruction page.

## Local setup

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

If MySQL is not running locally, use SQLite for verification:

```powershell
if (!(Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite | Out-Null }
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=(Resolve-Path database\database.sqlite).Path
$env:SESSION_DRIVER='array'
$env:CACHE_STORE='array'
$env:QUEUE_CONNECTION='sync'
php artisan migrate:fresh --seed
php artisan test
```

## Demo credentials

Admin:

- Email: `jackyh@gmail.com`
- Password: `jackyh`

Users:

- Email: `john12@gmail.com`, password: `john`
- Email: `bobbyhuntrix@gmail.com`, password: `bobby`
- Email: `maya@example.com`, password: `maya`

## Main routes

- Marketplace: `/`
- Printbox handoff page: `/print-with-printbox`
- User home: `/home`
- User profile: `/account`
- Personal artwork library: `/artworks`
- Upload artwork: `/artworks/create`
- Cart: `/cart`
- Artwork detail: `/artworks/{id}`
- Purchase history: `/u/{username}/purchases`
- Purchased artwork library: `/u/{username}/purchased-artworks`
- Creator sales: `/u/{username}/sales`
- Notifications: `/u/{username}/notifications`
- Admin dashboard: `/a/{username}/admin`

## Known limitations

- Product table is intentionally reused as the artwork table to preserve the template cart/admin seams.
- Uploaded previews reuse the uploaded image; no watermark generation is implemented.
- Seeded demo artworks use placeholder/public images where available.
- Payments are simulated only.
- Printbox remains an external manual step.
