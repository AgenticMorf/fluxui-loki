---
title: Installation
---

# Installation

## Requirements

- PHP ^8.2
- Laravel ^11.0 or ^12.0
- Livewire ^3.0 or ^4.0
- livewire/flux ^2.0
- livewire/flux-pro (for date picker, time picker, chart)
- Loki server (e.g. via Docker Compose / Sail)

## Composer

```bash
composer require agenticmorf/fluxui-loki livewire/flux-pro
php artisan flux:publish date-picker
php artisan flux:publish time-picker
php artisan flux:publish chart
```

[Packagist](https://packagist.org/packages/agenticmorf/fluxui-loki)

## Environment

Set `LOKI_URL` in `.env` (default: `http://loki:3100` when using Sail):

```
LOKI_URL=http://loki:3100
```
