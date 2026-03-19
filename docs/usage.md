---
title: Usage
---

# Usage

## Route

The package registers:

- `GET /logs` — Logs dashboard (named `logs`)

## Sidebar

Add a Logs nav item in your sidebar pointing to `route('logs')` (e.g. under a "System" group, icon: `document-text`).

## Publishing Views

To customize the dashboard views:

```bash
php artisan vendor:publish --tag=fluxui-loki-views
```
