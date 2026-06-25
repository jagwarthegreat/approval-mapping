# Approval Mapping

Standalone Laravel package for approval mapping backend + runtime + UI.

## Features

- Approval mapping runtime (`ApprovalRequest`, mapping versions, logs)
- API endpoints for approval mapping versions
- Blade fallback UI (`/approval-mapping`)
- Optional publishable frontend assets (Vue entrypoint scaffold)
- Installer command for config, migrations, views, and optional assets

## Installation

```bash
composer require jguapin/approval-mapping
php artisan approval-mapping:install --migrate
```

Optional Vue assets:

```bash
php artisan approval-mapping:install --with-assets
```

## Published Assets

- Config: `config/approval-mapping.php`
- Migrations: `database/migrations/*approval_mapping*`
- Views: `resources/views/vendor/approval-mapping`
- Optional JS: `resources/js/vendor/approval-mapping`

## API

- `GET /api/v1/approval-mapping/versions`
- `POST /api/v1/approval-mapping/versions`
- `GET /api/v1/approval-mapping/versions/{version}`
- `PUT /api/v1/approval-mapping/versions/{version}/activate`

## Runtime Integration

Use trait on host models:

```php
use Jguapin\ApprovalMapping\Concerns\HasApprovalMapping;

class PurchaseRequest extends Model
{
    use HasApprovalMapping;

    protected string $approvalModuleCode = 'PR';

    public function approvalContext(): array
    {
        return [
            'company_id' => $this->company_id,
            'business_unit_id' => $this->business_unit_id,
            'branch_id' => $this->branch_id,
            'type' => 'direct',
        ];
    }
}
```
