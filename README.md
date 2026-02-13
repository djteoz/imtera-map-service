# Imtera — skeleton

This repository contains a minimal Laravel + Vue skeleton for the Yandex Reviews integration UI.

What I added:

- semantic colors and style‑guide (SCSS + Tailwind tokens)
- Laravel basic skeleton (routes, controllers, models)
- API endpoints for auth and settings
- Vue settings page and login placeholder
- Migrations for users, settings, reviews

Quick setup (Windows / \*nix)

1. Install PHP / Composer / Node.js
2. Clone & install

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
php artisan serve
```

Open http://127.0.0.1:8000 — settings UI is available (auth placeholder / API ready).

Notes

- Authentication implemented with API tokens (Sanctum recommended to install and configure).
- Importer is mocked (SettingsController@import) — next step is to implement scraper/worker.
