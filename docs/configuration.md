---
title: Configuration
---

# Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=fluxui-loki-config
```

Edit `config/fluxui-loki.php`:

- **url** — Loki API base URL (from `LOKI_URL`)
- **service_label** — Loki label for the service dropdown (default: `compose_service`; use `job` or `container_name` if your setup differs)
- **layout** — Livewire layout (default: `components.layouts.app.sidebar`)
- **route_path** — URL path (default: `logs`)
- **route_name** — Route name (default: `logs`)
- **middleware** — Route middleware (default: `web`, `auth`)
