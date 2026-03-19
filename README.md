# agenticmorf/fluxui-loki

Documentation is available on [GitHub Pages](https://agenticmorf.github.io/fluxui-loki/).

FluX UI for [Grafana Loki](https://grafana.com/oss/loki/) — Livewire dashboard to query Loki logs from Sail/Docker services.

## Requirements

- PHP ^8.2
- Laravel ^11.0|^12.0
- Livewire ^3.0|^4.0
- [livewire/flux](https://fluxui.dev) ^2.0
- [livewire/flux-pro](https://fluxui.dev/pricing) (for date picker, time picker, and chart)
- Loki server (e.g. via Docker Compose / Sail)

## Installation

```bash
composer require christhompsontldr/fluxui-loki livewire/flux-pro
php artisan flux:publish date-picker
php artisan flux:publish time-picker
php artisan flux:publish chart
```

## Configuration

Set `LOKI_URL` in `.env` (default: `http://loki:3100` when using Sail):

```env
LOKI_URL=http://loki:3100
```

Publish the config to customize:

```bash
php artisan vendor:publish --tag=fluxui-loki-config
```

Configure `config/fluxui-loki.php`:

- **url** — Loki API base URL (from `LOKI_URL`)
- **service_label** — Loki label for the service dropdown (default: `compose_service`; use `job` or `container_name` if your setup differs)
- **layout** — Livewire layout (default: `components.layouts.app.sidebar`)
- **route_path** — URL path (default: `logs`)
- **route_name** — Route name (default: `logs`)
- **middleware** — Route middleware (default: `web`, `auth`)

## Routes

The package registers:

- `GET /logs` — Logs dashboard (named `logs`)

## Sidebar

Add a Logs nav item in your sidebar pointing to `route('logs')` (e.g. under a "System" group, icon: `document-text`).

## License

MIT
