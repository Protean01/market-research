# Export Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an audit log for every CSV export, date range filtering with UI date pickers on all three export cards, nosniff headers, dead code removal, and Ziggy `route()` helpers replacing hardcoded URLs in the Vue component.

**Architecture:** A new `export_audit_logs` DB table + `ExportAuditLog` Eloquent model records every export. `AdminExportController` gains date range query params, nosniff headers on all `StreamedResponse` instances, and writes an audit record inside each closure after `fclose`. `AdminExport.vue` gains date picker refs per card and computed download URLs via Ziggy's `route()` helper.

**Tech Stack:** Laravel 12, Eloquent, StreamedResponse, Vue 3 Composition API, Ziggy

---

## File Map

| File | Action |
|---|---|
| `database/migrations/2026_05_19_000001_create_export_audit_logs_table.php` | Create |
| `app/Models/ExportAuditLog.php` | Create |
| `tests/Feature/AdminExportAuditTest.php` | Create |
| `app/Http/Controllers/Admin/AdminExportController.php` | Modify |
| `resources/js/pages/admin/AdminExport.vue` | Modify |

---

### Task 1: ExportAuditLog migration and model

**Files:**
- Create: `database/migrations/2026_05_19_000001_create_export_audit_logs_table.php`
- Create: `app/Models/ExportAuditLog.php`
- Create: `tests/Feature/AdminExportAuditTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/AdminExportAuditTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExportAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_audit_log_can_be_created_and_retrieved()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log = ExportAuditLog::create([
            'admin_id'    => $admin->id,
            'export_type' => 'responses',
            'filters'     => ['date_from' => '2026-01-01', 'date_to' => '2026-12-31'],
            'row_count'   => 42,
        ]);

        $this->assertDatabaseHas('export_audit_logs', [
            'admin_id'    => $admin->id,
            'export_type' => 'responses',
            'row_count'   => 42,
        ]);

        $this->assertEquals(
            ['date_from' => '2026-01-01', 'date_to' => '2026-12-31'],
            $log->fresh()->filters
        );
    }

    public function test_export_audit_log_admin_relation_works()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log = ExportAuditLog::create([
            'admin_id'    => $admin->id,
            'export_type' => 'transactions',
            'filters'     => [],
            'row_count'   => 0,
        ]);

        $this->assertEquals($admin->id, $log->admin->id);
    }
}
```

- [ ] **Step 2: Run test to confirm it fails**

```
php artisan test tests/Feature/AdminExportAuditTest.php
```

Expected: FAIL — `Class 'App\Models\ExportAuditLog' not found`

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_05_19_000001_create_export_audit_logs_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('export_type');
            $table->json('filters')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_audit_logs');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/ExportAuditLog.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_id', 'export_type', 'filters', 'row_count', 'created_at'];

    protected $casts = [
        'filters'    => 'array',
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
```

- [ ] **Step 5: Run migration and tests**

```
php artisan migrate
php artisan test tests/Feature/AdminExportAuditTest.php
```

Expected: PASS (2 tests)

- [ ] **Step 6: Commit**

```
git add database/migrations/2026_05_19_000001_create_export_audit_logs_table.php app/Models/ExportAuditLog.php tests/Feature/AdminExportAuditTest.php
git commit -m "feat: add ExportAuditLog migration and model"
```

---

### Task 2: Nosniff header and dead code removal

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminExportController.php`
- Modify: `tests/Feature/AdminExportAuditTest.php`

- [ ] **Step 1: Add failing tests**

Append these three tests inside the class in `tests/Feature/AdminExportAuditTest.php`:

```php
    public function test_responses_export_has_nosniff_header()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_transactions_export_has_nosniff_header()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.transactions'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_prize_entries_export_has_nosniff_header()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.prize-entries'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
```

- [ ] **Step 2: Run to confirm they fail**

```
php artisan test tests/Feature/AdminExportAuditTest.php
```

Expected: 3 new tests FAIL — `Failed asserting that response header [X-Content-Type-Options] with value [null] matches expected value [nosniff]`

- [ ] **Step 3: Add nosniff to all three $headers arrays in AdminExportController**

In `exportResponses`, replace the `$headers` assignment:
```php
$headers = [
    'Content-Type'           => 'text/csv; charset=UTF-8',
    'Content-Disposition'    => "attachment; filename=\"{$filename}\"",
    'X-Content-Type-Options' => 'nosniff',
];
```

In `exportTransactions`, replace the `$headers` assignment:
```php
$headers = [
    'Content-Type'           => 'text/csv; charset=UTF-8',
    'Content-Disposition'    => 'attachment; filename="wallet_transactions.csv"',
    'X-Content-Type-Options' => 'nosniff',
];
```

In `exportPrizeEntries`, replace the `$headers` assignment:
```php
$headers = [
    'Content-Type'           => 'text/csv; charset=UTF-8',
    'Content-Disposition'    => "attachment; filename=\"{$filename}\"",
    'X-Content-Type-Options' => 'nosniff',
];
```

- [ ] **Step 4: Remove the dead elseif in exportPrizeEntries**

Replace:
```php
$query = PrizeDrawEntry::with(['user', 'survey']);
if ($survey) {
    $query->where('survey_id', $survey->id);
} elseif ($surveyId) {
    $query->where('survey_id', $surveyId);
}
```

With:
```php
$query = PrizeDrawEntry::with(['user', 'survey']);
if ($survey) {
    $query->where('survey_id', $survey->id);
}
```

- [ ] **Step 5: Run all tests**

```
php artisan test tests/Feature/AdminExportAuditTest.php
```

Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```
git add app/Http/Controllers/Admin/AdminExportController.php tests/Feature/AdminExportAuditTest.php
git commit -m "feat: add nosniff headers to exports, remove dead elseif in prize entries"
```

---

### Task 3: Date range filtering on all three exports

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminExportController.php`
- Modify: `tests/Feature/AdminExportAuditTest.php`

- [ ] **Step 1: Add failing tests**

Append to `tests/Feature/AdminExportAuditTest.php`:

```php
    public function test_responses_export_accepts_date_range_params()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_transactions_export_accepts_date_range_params()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.transactions', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_prize_entries_export_accepts_date_range_params()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.prize-entries', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-12-31',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_responses_export_rejects_invalid_date_format()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses', ['date_from' => 'not-a-date']));

        $response->assertStatus(302);
    }
```

- [ ] **Step 2: Run to confirm the validation test fails**

```
php artisan test tests/Feature/AdminExportAuditTest.php --filter="date_range\|invalid_date"
```

Expected: date range tests pass (params accepted but not validated yet), invalid date test FAILS — gets 200 instead of 302.

- [ ] **Step 3: Replace exportResponses with date-range-aware version**

Replace the entire `exportResponses` method in `AdminExportController`:

```php
public function exportResponses(Request $request)
{
    $request->validate([
        'date_from' => ['nullable', 'date'],
        'date_to'   => ['nullable', 'date'],
    ]);

    $surveyId = $request->query('survey_id');
    $survey   = $surveyId ? Survey::find($surveyId) : null;
    $dateFrom = $request->query('date_from');
    $dateTo   = $request->query('date_to');

    $filename = $survey ? "survey_responses_{$survey->id}.csv" : 'all_survey_responses.csv';

    $headers = [
        'Content-Type'           => 'text/csv; charset=UTF-8',
        'Content-Disposition'    => "attachment; filename=\"{$filename}\"",
        'X-Content-Type-Options' => 'nosniff',
    ];

    $rowCount = 0;

    return new StreamedResponse(function () use ($survey, $surveyId, $dateFrom, $dateTo, &$rowCount) {
        $handle = fopen('php://output', 'w');

        $baseColumns = ['ID', 'User Name', 'User Email', 'Phone', 'Survey ID', 'Survey Title', 'Completed At'];

        $questionMap = [];
        if ($survey) {
            foreach ($survey->questions as $q) {
                $baseColumns[] = $q['text'];
                $questionMap[] = $q['id'];
            }
        } else {
            $baseColumns[] = 'Answers (JSON)';
        }

        fputcsv($handle, $baseColumns);

        $query = Response::with(['user', 'survey']);
        if ($survey) {
            $query->where('survey_id', $survey->id);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        try {
            $query->chunk(100, function ($responses) use ($handle, $survey, $questionMap, &$rowCount) {
                foreach ($responses as $response) {
                    $row = [
                        $response->id,
                        $response->user?->name ?? '',
                        $response->user?->email ?? '',
                        $response->user?->phone_number ?? '',
                        $response->survey_id,
                        $response->survey?->title ?? '',
                        $response->created_at->toDateTimeString(),
                    ];

                    if ($survey) {
                        foreach ($questionMap as $qId) {
                            $answer = $response->answers[$qId] ?? '';
                            $row[] = \is_array($answer) ? implode(', ', $answer) : $answer;
                        }
                    } else {
                        $row[] = json_encode($response->answers);
                    }

                    fputcsv($handle, array_map(fn ($v) => preg_match('/^[=+\-@|]/', (string) $v) ? "'{$v}" : $v, $row));
                    $rowCount++;
                }
            });
        } catch (\Throwable $e) {
            fputcsv($handle, ['ERROR', 'Export failed: ' . $e->getMessage()]);
        }

        fclose($handle);
    }, 200, $headers);
}
```

- [ ] **Step 4: Replace exportTransactions with date-range-aware version**

Replace the entire `exportTransactions` method:

```php
public function exportTransactions(Request $request)
{
    $request->validate([
        'date_from' => ['nullable', 'date'],
        'date_to'   => ['nullable', 'date'],
    ]);

    $dateFrom = $request->query('date_from');
    $dateTo   = $request->query('date_to');

    $headers = [
        'Content-Type'           => 'text/csv; charset=UTF-8',
        'Content-Disposition'    => 'attachment; filename="wallet_transactions.csv"',
        'X-Content-Type-Options' => 'nosniff',
    ];

    $rowCount = 0;

    return new StreamedResponse(function () use ($dateFrom, $dateTo, &$rowCount) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['ID', 'User Name', 'User Email', 'Type', 'Points', 'Description', 'Created At']);

        $query = WalletTransaction::with(['wallet.user']);
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        try {
            $query->chunk(100, function ($transactions) use ($handle, &$rowCount) {
                foreach ($transactions as $transaction) {
                    $desc = $transaction->type === 'earn'
                        ? ($transaction->meta['survey_title'] ?? 'Survey Earnings')
                        : ($transaction->meta['reward_type'] ?? 'Redemption');

                    $row = [
                        $transaction->id,
                        $transaction->wallet?->user?->name ?? '',
                        $transaction->wallet?->user?->email ?? '',
                        $transaction->type,
                        $transaction->points,
                        $desc,
                        $transaction->created_at->toDateTimeString(),
                    ];

                    fputcsv($handle, array_map(fn ($v) => preg_match('/^[=+\-@|]/', (string) $v) ? "'{$v}" : $v, $row));
                    $rowCount++;
                }
            });
        } catch (\Throwable $e) {
            fputcsv($handle, ['ERROR', 'Export failed: ' . $e->getMessage()]);
        }

        fclose($handle);
    }, 200, $headers);
}
```

- [ ] **Step 5: Replace exportPrizeEntries with date-range-aware version**

Replace the entire `exportPrizeEntries` method:

```php
public function exportPrizeEntries(Request $request)
{
    $request->validate([
        'date_from' => ['nullable', 'date'],
        'date_to'   => ['nullable', 'date'],
    ]);

    $surveyId = $request->query('survey_id');
    $survey   = $surveyId ? Survey::find($surveyId) : null;
    $dateFrom = $request->query('date_from');
    $dateTo   = $request->query('date_to');
    $filename = $survey ? "prize_entries_{$survey->id}.csv" : 'all_prize_entries.csv';

    $headers = [
        'Content-Type'           => 'text/csv; charset=UTF-8',
        'Content-Disposition'    => "attachment; filename=\"{$filename}\"",
        'X-Content-Type-Options' => 'nosniff',
    ];

    $rowCount = 0;

    return new StreamedResponse(function () use ($survey, $surveyId, $dateFrom, $dateTo, &$rowCount) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, [
            'ID', 'Survey ID', 'Survey Title',
            'User Name', 'User Email', 'Phone',
            'Is Winner', 'Prize Name', 'Prize Points', 'Prize Airtime Amount',
            'Won At', 'Prize Delivered', 'Delivered At', 'Entered At',
        ]);

        $query = PrizeDrawEntry::with(['user', 'survey']);
        if ($survey) {
            $query->where('survey_id', $survey->id);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        try {
            $query->chunk(100, function ($entries) use ($handle, &$rowCount) {
                foreach ($entries as $entry) {
                    $snapshot = $entry->prize_snapshot ?? [];
                    $row = [
                        $entry->id,
                        $entry->survey_id,
                        $entry->survey?->title ?? '',
                        $entry->user?->name ?? '',
                        $entry->user?->email ?? '',
                        $entry->user?->phone_number ?? '',
                        $entry->is_winner ? 'Yes' : 'No',
                        $snapshot['name'] ?? ($entry->is_winner ? 'Prize' : ''),
                        $snapshot['points'] ?? '',
                        $snapshot['amount'] ?? '',
                        $entry->won_at?->toDateTimeString() ?? '',
                        $entry->prize_delivered ? 'Yes' : 'No',
                        $entry->delivered_at?->toDateTimeString() ?? '',
                        $entry->created_at->toDateTimeString(),
                    ];
                    fputcsv($handle, array_map(fn ($v) => preg_match('/^[=+\-@|]/', (string) $v) ? "'{$v}" : $v, $row));
                    $rowCount++;
                }
            });
        } catch (\Throwable $e) {
            fputcsv($handle, ['ERROR', 'Export failed: ' . $e->getMessage()]);
        }

        fclose($handle);
    }, 200, $headers);
}
```

- [ ] **Step 6: Run all tests**

```
php artisan test tests/Feature/AdminExportAuditTest.php
```

Expected: PASS (9 tests)

- [ ] **Step 7: Commit**

```
git add app/Http/Controllers/Admin/AdminExportController.php tests/Feature/AdminExportAuditTest.php
git commit -m "feat: add date range filtering to all three export endpoints"
```

---

### Task 4: Audit log write in controller

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminExportController.php`
- Modify: `tests/Feature/AdminExportAuditTest.php`

- [ ] **Step 1: Add failing tests**

Append to `tests/Feature/AdminExportAuditTest.php`:

```php
    public function test_responses_export_writes_audit_log()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses'));

        $response->streamedContent();

        $this->assertDatabaseCount('export_audit_logs', 1);
        $log = ExportAuditLog::first();
        $this->assertEquals('responses', $log->export_type);
        $this->assertEquals($admin->id, $log->admin_id);
        $this->assertEquals(0, $log->row_count);
    }

    public function test_transactions_export_writes_audit_log()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.transactions'));

        $response->streamedContent();

        $this->assertDatabaseCount('export_audit_logs', 1);
        $this->assertEquals('transactions', ExportAuditLog::first()->export_type);
    }

    public function test_prize_entries_export_writes_audit_log()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.prize-entries'));

        $response->streamedContent();

        $this->assertDatabaseCount('export_audit_logs', 1);
        $this->assertEquals('prize_entries', ExportAuditLog::first()->export_type);
    }

    public function test_audit_log_captures_date_filters()
    {
        $admin = User::factory()->phoneVerified()->create([
            'role' => 'admin', 'is_admin' => true, 'is_password_set' => true,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin_authenticated' => true])
            ->get(route('admin.export.responses', [
                'date_from' => '2026-01-01',
                'date_to'   => '2026-06-30',
            ]));

        $response->streamedContent();

        $log = ExportAuditLog::first();
        $this->assertEquals('2026-01-01', $log->filters['date_from']);
        $this->assertEquals('2026-06-30', $log->filters['date_to']);
    }
```

- [ ] **Step 2: Run to confirm they fail**

```
php artisan test tests/Feature/AdminExportAuditTest.php --filter="writes_audit_log\|captures_date"
```

Expected: FAIL — `Expected 1 rows in [export_audit_logs], got 0`

- [ ] **Step 3: Add ExportAuditLog import to AdminExportController**

At the top of `app/Http/Controllers/Admin/AdminExportController.php`, add:

```php
use App\Models\ExportAuditLog;
```

- [ ] **Step 4: Add audit log write to exportResponses**

Inside the `exportResponses` StreamedResponse closure, after `fclose($handle);`, add:

```php
        ExportAuditLog::create([
            'admin_id'    => auth()->id(),
            'export_type' => 'responses',
            'filters'     => array_filter([
                'survey_id' => $surveyId,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]),
            'row_count' => $rowCount,
        ]);
```

- [ ] **Step 5: Add audit log write to exportTransactions**

Inside the `exportTransactions` StreamedResponse closure, after `fclose($handle);`, add:

```php
        ExportAuditLog::create([
            'admin_id'    => auth()->id(),
            'export_type' => 'transactions',
            'filters'     => array_filter([
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]),
            'row_count' => $rowCount,
        ]);
```

- [ ] **Step 6: Add audit log write to exportPrizeEntries**

Inside the `exportPrizeEntries` StreamedResponse closure, after `fclose($handle);`, add:

```php
        ExportAuditLog::create([
            'admin_id'    => auth()->id(),
            'export_type' => 'prize_entries',
            'filters'     => array_filter([
                'survey_id' => $surveyId,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ]),
            'row_count' => $rowCount,
        ]);
```

- [ ] **Step 7: Run all tests**

```
php artisan test tests/Feature/AdminExportAuditTest.php
```

Expected: PASS (13 tests)

- [ ] **Step 8: Commit**

```
git add app/Http/Controllers/Admin/AdminExportController.php tests/Feature/AdminExportAuditTest.php
git commit -m "feat: write audit log after each CSV export"
```

---

### Task 5: Frontend date pickers and Ziggy URL fixes

**Files:**
- Modify: `resources/js/pages/admin/AdminExport.vue`

No automated tests — verify manually by opening `/admin/export` in the browser after `npm run dev`.

- [ ] **Step 1: Replace the script setup block**

Replace the entire `<script setup lang="ts">` block in `resources/js/pages/admin/AdminExport.vue`:

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, Wallet, ArrowRight, Table, Trophy } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = withDefaults(defineProps<{
    surveys?: any[];
}>(), {
    surveys: () => []
});

const selectedSurveyId = ref('');
const dateFrom         = ref('');
const dateTo           = ref('');

const transDateFrom = ref('');
const transDateTo   = ref('');

const selectedPrizeSurveyId = ref('');
const prizeDateFrom         = ref('');
const prizeDateTo           = ref('');

const exportResponsesUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedSurveyId.value) params.append('survey_id', selectedSurveyId.value);
    if (dateFrom.value)         params.append('date_from', dateFrom.value);
    if (dateTo.value)           params.append('date_to', dateTo.value);
    const qs = params.toString();
    return route('admin.export.responses') + (qs ? `?${qs}` : '');
});

const exportTransactionsUrl = computed(() => {
    const params = new URLSearchParams();
    if (transDateFrom.value) params.append('date_from', transDateFrom.value);
    if (transDateTo.value)   params.append('date_to', transDateTo.value);
    const qs = params.toString();
    return route('admin.export.transactions') + (qs ? `?${qs}` : '');
});

const exportPrizeEntriesUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedPrizeSurveyId.value) params.append('survey_id', selectedPrizeSurveyId.value);
    if (prizeDateFrom.value)         params.append('date_from', prizeDateFrom.value);
    if (prizeDateTo.value)           params.append('date_to', prizeDateTo.value);
    const qs = params.toString();
    return route('admin.export.prize-entries') + (qs ? `?${qs}` : '');
});
</script>
```

- [ ] **Step 2: Add date pickers to the Survey Responses card**

In the template, inside the `<div class="space-y-4 pt-4">` of the Survey Responses card, insert this date range grid between the `</div>` that closes the survey select wrapper and the `<a>` download button:

```html
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">From</label>
                                <input type="date" v-model="dateFrom" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">To</label>
                                <input type="date" v-model="dateTo" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all" />
                            </div>
                        </div>
```

The download `<a>` tag already uses `:href="exportResponsesUrl"` — no change needed there.

- [ ] **Step 3: Add date pickers to the Wallet Transactions card**

Replace the entire `<div class="pt-10">` section (which currently wraps only the download button) with:

```html
                    <div class="space-y-4 pt-2">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">From</label>
                                <input type="date" v-model="transDateFrom" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-emerald-500/20 transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">To</label>
                                <input type="date" v-model="transDateTo" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-emerald-500/20 transition-all" />
                            </div>
                        </div>

                        <a
                            :href="exportTransactionsUrl"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-3 px-6 py-4 bg-emerald-600 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-emerald-700 shadow-xl shadow-emerald-600/20 transition-all active:scale-95"
                        >
                            Download Audit CSV
                            <ArrowRight class="w-4 h-4" />
                        </a>
                    </div>
```

- [ ] **Step 4: Add date pickers to the Prize Draw Entries card**

In the prize entries card `<div class="space-y-4 pt-2">`, insert the date grid between the `</div>` closing the survey select wrapper and the `<a>` download button:

```html
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">From</label>
                                <input type="date" v-model="prizeDateFrom" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-amber-500/20 transition-all" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-muted-foreground">To</label>
                                <input type="date" v-model="prizeDateTo" class="w-full rounded-xl border border-border bg-background p-3 font-bold text-sm focus:ring-2 focus:ring-amber-500/20 transition-all" />
                            </div>
                        </div>
```

Update the prize entries `<a>` to use the computed URL (replace the inline template literal):

```html
                        <a
                            :href="exportPrizeEntriesUrl"
                            target="_blank"
                            class="w-full inline-flex items-center justify-center gap-3 px-6 py-4 bg-amber-500 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-amber-600 shadow-xl shadow-amber-500/20 transition-all active:scale-95"
                        >
                            Download Prize Entries CSV
                            <ArrowRight class="w-4 h-4" />
                        </a>
```

- [ ] **Step 5: Run backend tests to confirm no regressions**

```
php artisan test tests/Feature/AdminExportAuditTest.php
```

Expected: PASS (13 tests)

- [ ] **Step 6: Commit**

```
git add resources/js/pages/admin/AdminExport.vue
git commit -m "feat: add date range pickers and Ziggy route() helpers to export UI"
```

---

## Final Verification

- [ ] Run the full test suite: `php artisan test`
- [ ] Confirm all tests pass with no failures or warnings
