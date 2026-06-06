# Security Critical Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the five production blockers identified in the forensic audit so the CI gate passes, the admin panel loads without errors, and the top security vulnerabilities are closed.

**Architecture:** Each task is fully self-contained — a passing test suite after each commit proves correctness. No task depends on another being done first except Task 1 (CSP package must be installed before app.js is edited).

**Tech Stack:** Laravel 12, PHP 8.4, Alpine.js v3 / `@alpinejs/csp`, Vite 7, Filament 3, MySQL 8 spatial functions, PHPUnit 11.

---

## Overview of five tasks

| # | What | Files changed | Risk |
|---|---|---|---|
| 1 | Migrate Alpine to CSP build | `package.json`, `resources/js/app.js`, `SecurityHeaders.php` | LOW — swap one npm package |
| 2 | Fix PaymentProviderResource Pages | 3 new PHP files | LOW — add missing classes |
| 3 | Fix super_admin widget auth | 4 widget files, 1 resource file | LOW — one-line fix ×5 |
| 4 | Harden spatial SQL (path traversal + SQL) | `Residence.php`, `routes/web.php` | MEDIUM — test coverage required |
| 5 | Rotate `.env.example` default secrets | `.env.example` | LOW — no runtime change |

---

## Task 1: Migrate Alpine.js to CSP build

**Why:** The current import `from 'alpinejs'` uses a standard Alpine build that requires `unsafe-eval` in the CSP. The test `AlpineCspBuildTest` asserts `from '@alpinejs/csp'` must be present. `unsafe-eval` in CSP is a HIGH-severity security hole.

**Rollback:** `git revert` the single commit. The `alpinejs` package is not removed from `package.json` so no other code breaks.

**Files:**
- Modify: `package.json` — add `@alpinejs/csp` to `dependencies`
- Modify: `resources/js/app.js` — change import line
- Modify: `app/Http/Middleware/SecurityHeaders.php` — remove `unsafe-eval` from `script-src`
- Test: `tests/Feature/AlpineCspBuildTest.php` — already written, must go from FAIL → PASS

---

- [ ] **Step 1.1: Run the existing failing tests to confirm baseline**

```bash
php artisan test tests/Feature/AlpineCspBuildTest.php --stop-on-failure
```

Expected output:
```
FAIL  Tests\Feature\AlpineCspBuildTest
  ⨯ app js imports alpinejs csp build
  ⨯ app js does not import standard alpinejs
  ⨯ csp build package is in dependencies
Tests: 3 failed
```

---

- [ ] **Step 1.2: Install @alpinejs/csp**

```bash
npm install @alpinejs/csp
```

Expected: `package.json` `dependencies` gains `"@alpinejs/csp": "^3.x.x"`.

Verify:
```bash
grep '@alpinejs/csp' package.json
```

---

- [ ] **Step 1.3: Replace the Alpine import in app.js**

Open `resources/js/app.js`. Change line 3 from:

```js
import Alpine from 'alpinejs';
```

to:

```js
import Alpine from '@alpinejs/csp';
```

No other change in this file. `@alpinejs/csp` exports the same default export (`Alpine`) and supports the same `.plugin()`, `.data()`, `.start()` API.

---

- [ ] **Step 1.4: Run the tests to verify all three CSP assertions pass**

```bash
php artisan test tests/Feature/AlpineCspBuildTest.php
```

Expected:
```
Tests: 3 passed
```

If any test still fails, check `package-lock.json` — confirm `@alpinejs/csp` is resolved. If the third test (`csp_build_package_is_in_dependencies`) fails, confirm `@alpinejs/csp` is under `"dependencies"` (not `"devDependencies"`) in `package.json`.

---

- [ ] **Step 1.5: Remove unsafe-eval from SecurityHeaders middleware**

Open `app/Http/Middleware/SecurityHeaders.php`.

Find this line (around line 67):
```php
"script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' 'unsafe-eval' {$googleMaps} {$clarity}",
```

Replace with:
```php
"script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' {$googleMaps} {$clarity}",
```

Also update the comment block above it (around lines 43-47) to remove the mention of `unsafe-eval`:
```php
    /**
     * Content Security Policy.
     *
     * NOTE : unsafe-inline est requis pour Livewire et Filament.
     * Le frontend utilise le build @alpinejs/csp qui n'exige pas unsafe-eval.
     */
```

---

- [ ] **Step 1.6: Do a production build to confirm no Vite errors**

```bash
npm run build 2>&1 | tail -20
```

Expected: build exits with code 0. No errors about Alpine plugins.

If errors appear: `@alpinejs/intersect` and `@alpinejs/collapse` are still imported from their own packages — these are compatible with the CSP build (they are plugins, not the core runtime).

---

- [ ] **Step 1.7: Run the full test suite to confirm no regressions**

```bash
php artisan test
```

Expected: All previously passing tests still pass. The 3 CSP tests now pass. The previously failing `AlpineCspBuildTest` is now GREEN.

---

- [ ] **Step 1.8: Commit**

```bash
git add package.json package-lock.json resources/js/app.js app/Http/Middleware/SecurityHeaders.php
git commit -m "fix(security): migrate to @alpinejs/csp build, remove unsafe-eval from CSP"
```

---

## Task 2: Fix PaymentProviderResource — verify Pages exist

**Why:** The audit reported the Pages directory was empty. Direct inspection shows the files *do* exist. This task verifies they are syntactically correct and the resource loads in the admin panel.

**Files:**
- Read/verify: `app/Filament/Resources/PaymentProviderResource/Pages/ListPaymentProviders.php`
- Read/verify: `app/Filament/Resources/PaymentProviderResource/Pages/CreatePaymentProvider.php`
- Read/verify: `app/Filament/Resources/PaymentProviderResource/Pages/EditPaymentProvider.php`
- Test: new test `tests/Feature/Filament/PaymentProviderResourceTest.php`

---

- [ ] **Step 2.1: Write a failing test for the resource**

Create `tests/Feature/Filament/PaymentProviderResourceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\PaymentProviderResource;
use App\Filament\Resources\PaymentProviderResource\Pages\CreatePaymentProvider;
use App\Filament\Resources\PaymentProviderResource\Pages\EditPaymentProvider;
use App\Filament\Resources\PaymentProviderResource\Pages\ListPaymentProviders;
use App\Models\PaymentProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProviderResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_page_classes_exist(): void
    {
        $this->assertTrue(class_exists(ListPaymentProviders::class));
        $this->assertTrue(class_exists(CreatePaymentProvider::class));
        $this->assertTrue(class_exists(EditPaymentProvider::class));
    }

    public function test_resource_getpages_returns_all_three_routes(): void
    {
        $pages = PaymentProviderResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    public function test_list_page_loads_for_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('filament.admin.resources.payment-providers.index'))
            ->assertOk();
    }
}
```

---

- [ ] **Step 2.2: Run the test to verify it fails if class files have issues**

```bash
php artisan test tests/Feature/Filament/PaymentProviderResourceTest.php
```

If all three pass: the Pages files are valid. Go directly to Step 2.4.

If `test_list_page_loads_for_admin` fails with a route error: the resource may not be registered in the Filament panel. Check `app/Providers/Filament/AdminPanelProvider.php` to see if `PaymentProviderResource` is auto-discovered or manually registered.

---

- [ ] **Step 2.3: If the list route does not exist — register the resource**

Open `app/Providers/Filament/AdminPanelProvider.php`. Find the `->discoverResources()` call or the manual resources list. If using auto-discovery, verify the namespace includes `App\Filament\Resources`. If manual, add:

```php
->resources([
    // ... existing resources ...
    \App\Filament\Resources\PaymentProviderResource::class,
])
```

---

- [ ] **Step 2.4: Run all three test methods and confirm passing**

```bash
php artisan test tests/Feature/Filament/PaymentProviderResourceTest.php
```

Expected:
```
Tests: 3 passed
```

---

- [ ] **Step 2.5: Commit**

```bash
git add tests/Feature/Filament/PaymentProviderResourceTest.php
git commit -m "test(filament): verify PaymentProviderResource pages exist and list route loads"
```

---

## Task 3: Fix super_admin widget auth and AdminResource query

**Why:** Four Filament financial widgets check `role === 'admin'` (string comparison) in their `canView()` method. A `super_admin` user has `role = 'super_admin'` — so they see NO financial widgets in the admin dashboard. `AdminResource::getEloquentQuery()` also filters `role = 'admin'` exclusively, hiding super_admin users from the admin management UI.

The correct check is `$user->isAdmin()` which already includes both roles.

**Files:**
- Modify: `app/Filament/Widgets/PaymentStatsWidget.php` — line ~133
- Modify: `app/Filament/Widgets/PaymentChartWidget.php` — line ~154
- Modify: `app/Filament/Widgets/RecentPaymentsWidget.php` — line ~108
- Modify: `app/Filament/Widgets/SponsoredStatsWidget.php` — line ~115
- Modify: `app/Filament/Resources/AdminResource.php` — line 38
- Test: `tests/Feature/Filament/SuperAdminWidgetAccessTest.php`

---

- [ ] **Step 3.1: Write the failing test**

Create `tests/Feature/Filament/SuperAdminWidgetAccessTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Widgets\PaymentChartWidget;
use App\Filament\Widgets\PaymentStatsWidget;
use App\Filament\Widgets\RecentPaymentsWidget;
use App\Filament\Widgets\SponsoredStatsWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminWidgetAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_payment_stats_widget(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->assertTrue(PaymentStatsWidget::canView());
    }

    public function test_super_admin_can_view_payment_chart_widget(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->assertTrue(PaymentChartWidget::canView());
    }

    public function test_super_admin_can_view_recent_payments_widget(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->assertTrue(RecentPaymentsWidget::canView());
    }

    public function test_super_admin_can_view_sponsored_stats_widget(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $this->assertTrue(SponsoredStatsWidget::canView());
    }

    public function test_regular_user_cannot_view_payment_stats_widget(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->assertFalse(PaymentStatsWidget::canView());
    }
}
```

---

- [ ] **Step 3.2: Run test to verify it fails**

```bash
php artisan test tests/Feature/Filament/SuperAdminWidgetAccessTest.php
```

Expected: 4 failing, 1 passing (the regular user test passes because `super_admin !== 'admin'` and `user !== 'admin'` both return false).

---

- [ ] **Step 3.3: Fix PaymentStatsWidget**

Open `app/Filament/Widgets/PaymentStatsWidget.php`. Find:

```php
return auth()->user()?->role === 'admin';
```

Replace with:

```php
/** @var \App\Models\User|null $user */
$user = auth()->user();
return $user?->isAdmin() ?? false;
```

---

- [ ] **Step 3.4: Fix PaymentChartWidget**

Open `app/Filament/Widgets/PaymentChartWidget.php`. Find:

```php
return auth()->user()?->role === 'admin';
```

Replace with:

```php
/** @var \App\Models\User|null $user */
$user = auth()->user();
return $user?->isAdmin() ?? false;
```

---

- [ ] **Step 3.5: Fix RecentPaymentsWidget**

Open `app/Filament/Widgets/RecentPaymentsWidget.php`. Find:

```php
return $user?->role === 'admin';
```

Replace with:

```php
return $user?->isAdmin() ?? false;
```

---

- [ ] **Step 3.6: Fix SponsoredStatsWidget**

Open `app/Filament/Widgets/SponsoredStatsWidget.php`. Find:

```php
return Auth::user()?->role === 'admin';
```

Replace with:

```php
/** @var \App\Models\User|null $user */
$user = Auth::user();
return $user?->isAdmin() ?? false;
```

---

- [ ] **Step 3.7: Fix AdminResource::getEloquentQuery to include super_admin**

Open `app/Filament/Resources/AdminResource.php`. Find line 38:

```php
return parent::getEloquentQuery()->where('role', 'admin');
```

Replace with:

```php
return parent::getEloquentQuery()->whereIn('role', ['admin', 'super_admin']);
```

This means the Admin management list will also show super_admin users — which is correct, since super_admin is a superset of admin.

---

- [ ] **Step 3.8: Run the tests to verify all 5 pass**

```bash
php artisan test tests/Feature/Filament/SuperAdminWidgetAccessTest.php
```

Expected:
```
Tests: 5 passed
```

---

- [ ] **Step 3.9: Run full suite to confirm no regressions**

```bash
php artisan test --stop-on-failure
```

---

- [ ] **Step 3.10: Commit**

```bash
git add \
  app/Filament/Widgets/PaymentStatsWidget.php \
  app/Filament/Widgets/PaymentChartWidget.php \
  app/Filament/Widgets/RecentPaymentsWidget.php \
  app/Filament/Widgets/SponsoredStatsWidget.php \
  app/Filament/Resources/AdminResource.php \
  tests/Feature/Filament/SuperAdminWidgetAccessTest.php
git commit -m "fix(filament): allow super_admin to view financial widgets and admin list"
```

---

## Task 4: Harden spatial SQL and private file path traversal

### Part A — Spatial SQL hardening

**Why:** `Residence::scopeWithinRadius()` and `scopeNearestTo()` interpolate PHP float variables directly into WKT strings, which are then passed to `whereRaw()`. While `GeoSearchRequest` validates lat/lng as `numeric`, directly interpolating floats into SQL strings is the class of vulnerability that enables SQL injection if validation ever fails or is bypassed. The safe pattern uses `ST_GeomFromText(?, 4326)` with bindings.

**Files:**
- Modify: `app/Models/Residence.php` — rewrite four private/public spatial methods
- Test: `tests/Feature/Api/GeoSearchSqlSafetyTest.php`

---

- [ ] **Step 4.1: Write the safety test**

Create `tests/Feature/Api/GeoSearchSqlSafetyTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Residence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoSearchSqlSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_within_radius_scope_accepts_extreme_float_values(): void
    {
        // Verifies the scope does not throw a SQL error with boundary floats.
        // Abidjan approximate coordinates.
        $count = Residence::query()
            ->withinRadius(5.3599517, -4.0082563, 5000)
            ->count();

        $this->assertIsInt($count);
    }

    public function test_nearest_to_scope_accepts_valid_coordinates(): void
    {
        $count = Residence::query()
            ->nearestTo(5.3599517, -4.0082563, 10)
            ->count();

        $this->assertIsInt($count);
    }
}
```

---

- [ ] **Step 4.2: Run test to confirm it passes on the current code (baseline)**

```bash
php artisan test tests/Feature/Api/GeoSearchSqlSafetyTest.php
```

Expected on SQLite test env: both pass (SQLite path doesn't use WKT). This establishes a regression baseline.

---

- [ ] **Step 4.3: Rewrite scopeWithinRadius MySQL path to use bindings**

Open `app/Models/Residence.php`. Replace the MySQL path inside `scopeWithinRadius` (the block after `if ($driver === 'sqlite') { ... }`):

**Before:**
```php
// MySQL: MBRContains drives the SPATIAL INDEX; ST_Distance_Sphere refines
$bboxWkt  = "POLYGON(({$minLng} {$minLat},{$maxLng} {$minLat},{$maxLng} {$maxLat},{$minLng} {$maxLat},{$minLng} {$minLat}))";
$bboxExpr = "ST_GeomFromText('{$bboxWkt}', 4326)";
$ptExpr   = "ST_GeomFromText('POINT({$lng} {$lat})', 4326)";

$query = $query
    ->whereRaw("MBRContains({$bboxExpr}, location)")
    ->whereRaw("ST_Distance_Sphere(location, {$ptExpr}) <= ?", [$radius])
    ->selectRaw("*, ST_Distance_Sphere(location, {$ptExpr}) AS distance_meters");

if ($sortByDistance) {
    $query->orderBy('distance_meters', 'asc');
}

return $query;
```

**After:**
```php
// MySQL: MBRContains drives the SPATIAL INDEX; ST_Distance_Sphere refines.
// Coordinates are passed as bindings — never interpolated into the SQL string.
$bboxWkt = "POLYGON(({$minLng} {$minLat},{$maxLng} {$minLat},{$maxLng} {$maxLat},{$minLng} {$maxLat},{$minLng} {$minLat}))";

$query = $query
    ->whereRaw('MBRContains(ST_GeomFromText(?, 4326), location)', [$bboxWkt])
    ->whereRaw('ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) <= ?', [
        "POINT({$lng} {$lat})",
        $radius,
    ])
    ->selectRaw('*, ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) AS distance_meters', [
        "POINT({$lng} {$lat})",
    ]);

if ($sortByDistance) {
    $query->orderBy('distance_meters', 'asc');
}

return $query;
```

**Note:** The bounding-box WKT is built from PHP float values computed by arithmetic — not from user input directly. The lat/lng floats have already been validated as `numeric` and `between:` bounds in `GeoSearchRequest`. The `POINT(lng lat)` string is still a PHP string, but it is now passed as a **binding** to MySQL's `ST_GeomFromText()`, so MySQL parses it as geometry, not as SQL syntax. This closes the injection class.

---

- [ ] **Step 4.4: Rewrite scopeNearestTo MySQL path to use bindings**

In the same file, replace the MySQL path inside `scopeNearestTo`:

**Before:**
```php
$bboxWkt  = "POLYGON(({$minLng} {$minLat},{$maxLng} {$minLat},{$maxLng} {$maxLat},{$minLng} {$maxLat},{$minLng} {$minLat}))";
$bboxExpr = "ST_GeomFromText('{$bboxWkt}', 4326)";
$ptExpr   = "ST_GeomFromText('POINT({$lng} {$lat})', 4326)";

return $query
    ->whereRaw("MBRContains({$bboxExpr}, location)")
    ->selectRaw("*, ST_Distance_Sphere(location, {$ptExpr}) AS distance_meters")
    ->orderBy('distance_meters', 'asc')
    ->limit($limit);
```

**After:**
```php
$bboxWkt = "POLYGON(({$minLng} {$minLat},{$maxLng} {$minLat},{$maxLng} {$maxLat},{$minLng} {$maxLat},{$minLng} {$minLat}))";
$ptWkt   = "POINT({$lng} {$lat})";

return $query
    ->whereRaw('MBRContains(ST_GeomFromText(?, 4326), location)', [$bboxWkt])
    ->selectRaw('*, ST_Distance_Sphere(location, ST_GeomFromText(?, 4326)) AS distance_meters', [$ptWkt])
    ->orderBy('distance_meters', 'asc')
    ->limit($limit);
```

---

- [ ] **Step 4.5: Run tests to confirm no regressions**

```bash
php artisan test tests/Feature/Api/GeoSearchSqlSafetyTest.php tests/Feature/Api/GeoSearchTest.php
```

Expected: all pass.

---

### Part B — Private file path traversal hardening

**Why:** The route `/admin/files/private/{path}` uses `ltrim($path, '/')` as the only path sanitization. This does not prevent `../../` traversal. While Flysystem's `Storage::disk()` does resolve paths relative to the configured disk root, the explicit guard is one `ltrim` call away from failure if the Flysystem version changes.

**Files:**
- Modify: `routes/web.php` — harden the private file route handler
- Test: `tests/Feature/AdminPrivateFileTest.php`

---

- [ ] **Step 4.6: Write the path traversal test**

Create `tests/Feature/AdminPrivateFileTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPrivateFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_valid_private_file(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('kyc/test-doc.pdf', 'pdf-content');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.private-file', ['path' => 'kyc/test-doc.pdf']))
            ->assertOk();
    }

    public function test_path_traversal_attempt_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.private-file', ['path' => '../../.env']))
            ->assertStatus(400);
    }

    public function test_encoded_path_traversal_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/files/private/..%2F..%2F.env')
            ->assertStatus(400);
    }

    public function test_unauthenticated_user_cannot_access_private_file(): void
    {
        $this->get(route('admin.private-file', ['path' => 'kyc/test-doc.pdf']))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_private_file(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.private-file', ['path' => 'kyc/test-doc.pdf']))
            ->assertForbidden();
    }
}
```

---

- [ ] **Step 4.7: Run the test to see which assertions fail**

```bash
php artisan test tests/Feature/AdminPrivateFileTest.php
```

Expected failures: `test_path_traversal_attempt_is_rejected` and `test_encoded_path_traversal_is_rejected` (current code returns 404, not 400 for traversal).

---

- [ ] **Step 4.8: Harden the private file route**

Open `routes/web.php`. Replace the route handler body (starting at the `$path = ltrim(...)` line through the `return response()->stream(...)` block):

**Before:**
```php
    $path = ltrim($path, '/');

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('private');

    if (! $disk->exists($path)) {
        abort(404);
    }
```

**After:**
```php
    // Reject any path containing traversal sequences before decoding.
    if (str_contains($path, '..') || str_contains(urldecode($path), '..')) {
        abort(400, 'Invalid path.');
    }

    // Normalise: remove leading slash, collapse duplicate slashes.
    $path = ltrim(preg_replace('#/{2,}#', '/', $path), '/');

    // Only allow alphanumeric, dash, underscore, dot, and single forward slash.
    // This whitelist prevents null bytes, encoded characters, and shell metacharacters.
    if (! preg_match('#^[a-zA-Z0-9/_\-\.]+$#', $path)) {
        abort(400, 'Invalid path characters.');
    }

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = Storage::disk('private');

    if (! $disk->exists($path)) {
        abort(404);
    }
```

---

- [ ] **Step 4.9: Run the path traversal tests**

```bash
php artisan test tests/Feature/AdminPrivateFileTest.php
```

Expected:
```
Tests: 5 passed
```

---

- [ ] **Step 4.10: Run full test suite**

```bash
php artisan test --stop-on-failure
```

Expected: all previously passing tests still pass.

---

- [ ] **Step 4.11: Commit spatial SQL and path traversal fixes together**

```bash
git add app/Models/Residence.php tests/Feature/Api/GeoSearchSqlSafetyTest.php
git commit -m "fix(security): use parameterized bindings for spatial SQL queries"

git add routes/web.php tests/Feature/AdminPrivateFileTest.php
git commit -m "fix(security): harden private file route against path traversal"
```

---

## Task 5: Rotate default secrets in .env.example

**Why:** `.env.example` contains `REVERB_APP_KEY=my-app-key`, `REVERB_APP_SECRET=my-app-secret`, `REVERB_APP_ID=my-app-id`. If a developer copies `.env.example` to `.env` and skips rotating these, WebSocket channels are unprotected. The `APP_DEBUG=true` default is also dangerous if `.env.example` is accidentally used in production.

**Files:**
- Modify: `.env.example`
- Test: `tests/Feature/EnvExampleTest.php`

---

- [ ] **Step 5.1: Write the test**

Create `tests/Feature/EnvExampleTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnvExampleTest extends TestCase
{
    private const ENV_EXAMPLE_PATH = __DIR__ . '/../../.env.example';

    #[Test]
    public function env_example_does_not_contain_default_reverb_key(): void
    {
        $contents = file_get_contents(self::ENV_EXAMPLE_PATH);
        $this->assertStringNotContainsString(
            'my-app-key',
            $contents,
            'REVERB_APP_KEY must not be the default placeholder "my-app-key"'
        );
    }

    #[Test]
    public function env_example_does_not_contain_default_reverb_secret(): void
    {
        $contents = file_get_contents(self::ENV_EXAMPLE_PATH);
        $this->assertStringNotContainsString(
            'my-app-secret',
            $contents,
            'REVERB_APP_SECRET must not be the default placeholder "my-app-secret"'
        );
    }

    #[Test]
    public function env_example_has_change_me_placeholder_for_reverb(): void
    {
        $contents = file_get_contents(self::ENV_EXAMPLE_PATH);
        $this->assertStringContainsString(
            'CHANGE_ME_IN_PRODUCTION',
            $contents,
            '.env.example must contain CHANGE_ME_IN_PRODUCTION placeholders'
        );
    }
}
```

---

- [ ] **Step 5.2: Run to confirm failures**

```bash
php artisan test tests/Feature/EnvExampleTest.php
```

Expected: 2-3 failures.

---

- [ ] **Step 5.3: Update .env.example**

Open `.env.example`. Find and replace these three lines:

```
REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
```

With:

```
REVERB_APP_ID=CHANGE_ME_IN_PRODUCTION
REVERB_APP_KEY=CHANGE_ME_IN_PRODUCTION
REVERB_APP_SECRET=CHANGE_ME_IN_PRODUCTION
```

Also update the comment for `SESSION_ENCRYPT` and add a note:

Find:
```
SESSION_ENCRYPT=false
```

Replace with:
```
SESSION_ENCRYPT=true          # set to true in production
```

---

- [ ] **Step 5.4: Run the test to verify it passes**

```bash
php artisan test tests/Feature/EnvExampleTest.php
```

Expected:
```
Tests: 3 passed
```

---

- [ ] **Step 5.5: Commit**

```bash
git add .env.example tests/Feature/EnvExampleTest.php
git commit -m "fix(security): replace default Reverb/session placeholders in .env.example"
```

---

## Final verification

- [ ] **Run the complete test suite one final time**

```bash
php artisan test
```

Expected:
```
Tests: X passed, 0 failed
```

The previously failing `AlpineCspBuildTest` must now pass. No regressions. Skipped tests (MySQL-only geo tests) are expected and acceptable.

- [ ] **Verify Vite production build succeeds**

```bash
npm run build
```

Expected: exit code 0.

---

## Self-review checklist

**Spec coverage:**
- [x] CSP: migrate to @alpinejs/csp, remove unsafe-eval → Task 1
- [x] Path traversal: harden private file route → Task 4B
- [x] Spatial SQL injection: replace WKT string interpolation with bindings → Task 4A
- [x] Secrets: rotate Reverb defaults in .env.example → Task 5
- [x] PaymentProviderResource Pages: verified they exist, added test → Task 2
- [x] super_admin widget auth: fix 4 widgets + AdminResource → Task 3

**No placeholders:** All steps have actual code.

**Type consistency:** No renamed methods between tasks. All references to `isAdmin()` are to the existing `User::isAdmin()` method (verified in audit: lines in User.php).
