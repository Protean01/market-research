# Export Improvements Design

**Date:** 2026-05-19

## Overview

Five targeted improvements to the admin data export feature:

1. Audit log — database record of every export (who, what, when, filters used)
2. Date range filtering — from/to date pickers on all three export cards
3. `nosniff` header — add `X-Content-Type-Options: nosniff` to all streamed responses
4. Dead code removal — remove unreachable `elseif` in `exportPrizeEntries`
5. Hardcoded URL fix — replace raw path strings with Ziggy `route()` helper in Vue

---

## 1. Database & Model

### Migration: `create_export_audit_logs_table`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `admin_id` | foreignId → users | nullable (handles soft-deleted admins) |
| `export_type` | varchar | `responses`, `transactions`, or `prize_entries` |
| `filters` | json | `{ survey_id?, date_from?, date_to? }` |
| `row_count` | unsignedInteger | rows written to the CSV |
| `created_at` | timestamp | `useCurrent()` — no `updated_at` |

### Model: `ExportAuditLog`

- `$timestamps = false`, `created_at` set manually via `useCurrent()` in migration
- `$fillable`: `admin_id`, `export_type`, `filters`, `row_count`, `created_at`
- `$casts`: `filters` → `array`, `created_at` → `datetime`
- Relation: `belongsTo(User::class, 'admin_id')`

Follows the same shape as the existing `SurveyNotificationLog` model.

---

## 2. Backend: `AdminExportController`

### Date range filtering

All three export methods (`exportResponses`, `exportTransactions`, `exportPrizeEntries`) accept two optional query params: `date_from` and `date_to`, validated as `nullable|date`. When present, a `whereBetween('created_at', [$from, $to])` clause is added to the chunk query. Date range stacks with `survey_id` filtering where applicable.

### Audit log write

`$rowCount = 0` is declared before the `StreamedResponse` closure. It is passed by reference (`use (&$rowCount)`) into the inner chunk callback so the count accumulates across chunks. `$request` must also be added to the `StreamedResponse` closure's `use` clause so it is accessible for reading query params at audit time.

The audit log write happens **inside the `StreamedResponse` closure**, after `fclose`, so the final row count is known:

```php
return new StreamedResponse(function () use ($survey, $request, &$rowCount) {
    $handle = fopen('php://output', 'w');
    // ... headers, chunk with use (&$rowCount) ...
    fclose($handle);

    ExportAuditLog::create([
        'admin_id'    => auth()->id(),
        'export_type' => 'responses', // or 'transactions' / 'prize_entries'
        'filters'     => array_filter([
            'survey_id' => $request->query('survey_id'),
            'date_from' => $request->query('date_from'),
            'date_to'   => $request->query('date_to'),
        ]),
        'row_count' => $rowCount,
    ]);
}, 200, $headers);
```

`array_filter` removes null/empty entries so the JSON stays clean. The write is outside the `try/catch` so a failed export does not produce an audit record.

### `nosniff` header

`'X-Content-Type-Options' => 'nosniff'` added to the `$headers` array on all three `StreamedResponse` instances.

### Dead code removal

The `elseif ($surveyId)` block in `exportPrizeEntries` is removed. It only fired when a `survey_id` was passed but the survey didn't exist — resulting in zero rows either way.

---

## 3. Frontend: `AdminExport.vue`

### Date pickers

Each of the three export cards gets a two-column "From / To" date input row inserted between the filter section and the download button. Reactive refs per card:

- Responses: `dateFrom`, `dateTo`
- Transactions: `transDateFrom`, `transDateTo`
- Prize entries: `prizeDateFrom`, `prizeDateTo`

### Computed URLs

All three download URLs become computed properties that append `&date_from=...&date_to=...` when either date ref has a value.

### Hardcoded URL fix

The two raw path strings `/admin/export/transactions` and `/admin/export/prize-entries` (currently in the template as literal `href` values) are replaced with computed properties using Ziggy's `route()` helper:

- `route('admin.export.transactions')`
- `route('admin.export.prize-entries')`

---

## Files Affected

| File | Change |
|---|---|
| `database/migrations/YYYY_MM_DD_create_export_audit_logs_table.php` | New |
| `app/Models/ExportAuditLog.php` | New |
| `app/Http/Controllers/Admin/AdminExportController.php` | Modified |
| `resources/js/pages/admin/AdminExport.vue` | Modified |

## Out of Scope

- Displaying the audit log in the admin UI (future work)
- Async/queued exports for very large datasets (future work)
