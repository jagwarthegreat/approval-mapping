# Approval Mapping

Standalone Laravel package for approval mapping backend + runtime + UI.

## Features

- Approval mapping runtime (`ApprovalRequest`, mapping versions, logs)
- API endpoints for approval mapping versions
- Blade fallback UI (`/approval-mapping`)
- Optional publishable frontend assets (Vue entrypoint scaffold)
- Installer command for config, migrations, views, and optional assets
- Fully configurable organizational dimensions (company, business unit, branch, department, module)

## Installation

```bash
composer require jguapin/approval-mapping
php artisan approval-mapping:install --migrate
```

Optional Vue assets:

```bash
php artisan approval-mapping:install --with-assets
```

## Configuration

After publishing (`approval-mapping:install`), open `config/approval-mapping.php`.

### Feature Flags

Toggle each organizational dimension on or off. When disabled, the related
dropdown disappears from the UI and the lookup endpoint returns an empty array.
The nullable database columns are unaffected.

```php
'features' => [
    'company'       => true,   // set false if your app has no Company model
    'business_unit' => true,
    'branch'        => true,
    'department'    => true,   // false = rows use free-text department entry
    'module'        => true,   // set false if you don't scope by module
],
```

### Model Bindings

Bind the package roles to your actual Eloquent models.
Only `user` is truly required; all others can be `null`.

```php
'models' => [
    'user'                     => \App\Models\User::class,
    'user_assign_group'        => \App\Models\UserGroup::class,       // approver groups
    'sidebar_menu'             => \App\Models\Menu::class,            // module source
    'module'                   => null,                                // fallback module model
    'company'                  => \App\Models\Company::class,
    'business_unit'            => \App\Models\BusinessUnit::class,
    'branch'                   => \App\Models\Branch::class,
    'department'               => \App\Models\Department::class,
    'company_branch_department' => \App\Models\CompanyBranchDepartment::class, // scoped lookups
],
```

### Field Maps

If your model columns differ from the package defaults, override them here.

```php
'field_maps' => [
    'company'           => ['label' => 'company_name', 'code' => 'code'],
    'business_unit'     => ['label' => 'name', 'code' => 'unit_code'],
    'branch'            => ['label' => 'name', 'code' => 'branch_code'],
    'department'        => ['label' => 'dept_name', 'code' => 'dept_code'],
    'module'            => ['label' => 'name', 'code' => 'code', 'reference' => 'reference',
                            'status_col' => 'status', 'status_active' => 0],
    'user_assign_group' => ['label' => 'group_name'],
],
```

### Minimal Setup (no org structure)

For a simple app with just user groups and no company/BU hierarchy:

```php
'features' => [
    'company'       => false,
    'business_unit' => false,
    'branch'        => false,
    'department'    => false,
    'module'        => false,
],

'models' => [
    'user'              => \App\Models\User::class,
    'user_assign_group' => \App\Models\ApproverGroup::class,
],
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
- `GET /api/v1/approval-mapping/lookup/{type}` — `companies`, `business-units`, `branches`, `departments`, `modules`, `user-assign-groups`

## Runtime Integration

Use the trait on host models:

```php
use Jguapin\ApprovalMapping\Concerns\HasApprovalMapping;

class PurchaseRequest extends Model
{
    use HasApprovalMapping;

    protected string $approvalModuleCode = 'PR';

    public function approvalContext(): array
    {
        return [
            'company_id'      => $this->company_id,
            'business_unit_id' => $this->business_unit_id,
            'branch_id'       => $this->branch_id,
            'type'            => 'direct',
        ];
    }
}
```
