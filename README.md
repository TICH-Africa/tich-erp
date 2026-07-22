# TICH ERP

This repository contains a multi-platform ERP starter project with separate applications for the web, mobile, and desktop experiences.

## Project structure

- web/: Laravel application for the backend and web interface
- mobile/erp_mobile/: Flutter mobile application
- desktop/: Desktop application workspace
- docs/: Project documentation

## Requirements

- PHP 8.2+ with Composer
- Node.js 18+ (for Laravel Vite assets)
- MySQL or another supported database
- Flutter SDK (for the mobile app)

## Quick start

### Web application

```bash
cd web
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

### Mobile application

```bash
cd mobile/erp_mobile
flutter pub get
flutter run
```

## Notes

- Keep local environment files out of version control.
- Run git status regularly before committing changes.
- The repository is intended to evolve into a full ERP platform across web, mobile, and desktop clients.
