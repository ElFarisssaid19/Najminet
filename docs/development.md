# Development notes

Day-to-day tasks and conventions that don't belong in the [README](../README.md).

- [Common Docker tasks](#common-docker-tasks)
- [Uploaded documents](#uploaded-documents)
- [Running without Docker](#running-without-docker)
- [Front-end libraries and caching](#front-end-libraries-and-caching)
- [Icons (Font Awesome subset)](#icons-font-awesome-subset)
- [Buttons](#buttons)
- [Payment flow](#payment-flow)

## Common Docker tasks

**Send a test email** (checks the `MAIL_*` settings without going through a real flow):

```bash
docker compose exec web php scripts/send-test-email.php you@example.com
```

**Change a database password.** The credentials in `.env.local` are only used when the database volume is created. To change a password later, run `ALTER USER` in MySQL, then update `.env.local`.

**Reset the database.** MySQL only runs `sql/init.sql` on an empty volume. To start over (this deletes all data, including uploaded documents):

```bash
docker compose down -v
docker compose up -d --build
```

**After changing `composer.lock`.** The web container keeps the image's `vendor/` in an anonymous volume. Rebuild and renew it:

```bash
docker compose up -d --build -V
```

## Uploaded documents

Documents are stored in the `documents` volume, mounted at `/var/www/storage/documents` (outside the web root). Only `document.php` serves them, and only to their owner.

Back them up to a tar file:

```bash
docker compose exec -T web tar cf - -C /var/www/storage/documents . > documents.tar
```

Databases older than migration `012` stored files in `uploads/documents/`, in a separate `uploads` volume. After running the migrations, copy the old files into the new volume (they keep their names). Compose names volumes `<project>_<volume>`, and `docker volume ls` lists them:

```bash
docker run --rm -v <project>_uploads:/old -v <project>_documents:/new alpine \
  sh -c 'cp -a /old/documents/. /new/ 2>/dev/null; chown -R 33:33 /new'
```

## Running without Docker

**Requirements:** PHP 8.2+ with `pdo_mysql`, `curl` and `mbstring`; Apache with `mod_rewrite`, `mod_headers` and `mod_expires`; MySQL 8.0; Composer.

```bash
composer install
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS your_db_name CHARACTER SET utf8mb4"
mysql -u root -p your_db_name < sql/init.sql
```

Create a MySQL user for the app with access to that database. Then create `.env.local` from `.env.local.example` with your local values (`DB_HOST=127.0.0.1`, the database name, user and password, …), point Apache's document root at the project folder and allow `.htaccess` overrides.

## Front-end libraries and caching

Bootstrap, Font Awesome, Chart.js and the fonts are served from the site itself (`assets/vendor/`, `assets/fonts/`; see [Third-party assets](../README.md#third-party-assets)). No page loads anything from a CDN. The only third-party scripts are PayPal's checkout SDK and buttons on `checkout.php`, which PayPal requires to be loaded from paypal.com.

`.htaccess` sets `Cache-Control`:

- one year (`immutable`) for CSS and JS linked with `?v=<file time>`, and for everything in `assets/vendor/` and `assets/fonts/`;
- `no-cache` for pages, and for CSS and JS linked without `?v=`.

Files in `assets/vendor/` and `assets/fonts/` carry their version in their name, so a changed file needs a new name.

## Icons (Font Awesome subset)

The Font Awesome font files (`*-subset*.woff2`) only contain the icons the site uses, plus the glyphs mapped to U+0020–00FF. Browsers on Linux take their hinting reference heights from those glyphs; without them, icons render a sub-pixel differently.

**When you add an icon**, regenerate both files (solid and regular) from the original 6.5.1 files with fonttools. List every icon's code point (the `content` value of its `.fa-*:before` rule in `fa.css`):

```bash
pyftsubset fa-solid-900.woff2 --flavor=woff2 --layout-features='*' \
  --unicodes=U+0020-00FF,U+f060,U+f061,... \
  --output-file=assets/vendor/fontawesome-6.5.1/webfonts/fa-solid-900-subset-3.woff2
```

Then point the `@font-face` rules in `fa.css` at the new file names. An icon missing from the subset shows as a blank space.

## Buttons

Every button and button-like link uses one system: `assets/css/buttons.css` (loaded on every page) and `assets/js/buttons.js`.

- **Classes** are prefixed `nn-` so they never clash with Bootstrap's `.btn`. Use `nn-btn` plus one variant:
  - `nn-btn--primary`: one per form or section;
  - `--secondary`;
  - `--ghost`;
  - `--danger`: destructive actions;
  - `--icon`: icon-only, always with an `aria-label`.

  Optionally add a size (`--sm`, `--lg`) and `--block`. Every size is at least 44×44px. Wrap a group in `.nn-actions`: below 640px it stacks full width with the primary first. Inside a purple `.on-brand` panel, the colours switch so text stays readable.
- **Icons** are `<span class="nn-icon nn-icon--plus" aria-hidden="true"></span>`, drawn through a CSS mask in `buttons.css`. They use Font Awesome Free 6.5.1 shapes (CC BY 4.0), so no icon font is needed. To add one, add an `.nn-icon--name` rule with the icon's SVG.
- **Behaviour** (`buttons.js`):
  - A form or button with `data-confirm="…"` asks before submitting. Every delete has one.
  - After the first submit, the form's submit buttons are disabled and the clicked one shows a loading state. They are enabled again after 15 seconds, or when the page comes back from the back/forward cache.
  - `data-toggle-password="fieldId"` shows or hides a password.
  - The shared Back button (`includes/back-button.php`) goes back in history when the visitor came from another page of the site.
- **Labels** are short verbs in sentence case. The same action has the same words everywhere ("Save invoice", "Cancel", "Delete", "Log in", "Log out", "Sign up").

## Payment flow

The Professional plan is a one-time PayPal payment for 30 days; nothing renews automatically.

1. The PayPal button calls `billing/paypal-create-order.php`. The server creates the order with the price from `classes/PayPalCheckout.php` (never from the browser), records it in `payments` as `CREATED` for the signed-in user, and returns only the order ID.
2. After the buyer approves, the button calls `billing/paypal-capture.php`. The server only accepts an order ID that has a valid format, belongs to the signed-in user and is still pending. It captures the order, then checks PayPal's answer: status `COMPLETED`, amount, currency, payee (`PAYPAL_MERCHANT_ID`) and user.
3. The subscription is activated in one database transaction that also marks the payment `COMPLETED`, so a payment can never activate twice.

Both endpoints require the CSRF token in the `X-CSRF-Token` header. To test locally, set `PAYPAL_MODE=sandbox` and pay with a PayPal sandbox personal account.
