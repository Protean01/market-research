# Admin Functionality Implementation Plan

## Objective
To implement full backend and frontend functionality for the admin dashboard, including Survey Management, Targeting Configuration, Live Monitoring, and Data Export.

## Key Files & Context
- **Routes**: `routes/web.php`
- **Models**: `Survey`, `Response`, `User`, `WalletTransaction`
- **Controllers (New)**: 
  - `app/Http/Controllers/Admin/AdminSurveyController.php`
  - `app/Http/Controllers/Admin/AdminTargetingController.php`
  - `app/Http/Controllers/Admin/AdminMonitoringController.php`
  - `app/Http/Controllers/Admin/AdminExportController.php`
- **Frontend Views**: 
  - `resources/js/pages/admin/AdminSurveys.vue`
  - `resources/js/pages/admin/AdminTargeting.vue`
  - `resources/js/pages/admin/AdminMonitoring.vue`
  - `resources/js/pages/admin/AdminExport.vue`

## Implementation Steps

### 1. Update Routes
Refactor the `admin` route group in `routes/web.php` to map to the new individual controllers instead of anonymous closures rendering Inertia views directly.
Add required POST/PUT/DELETE routes for creating, updating, and exporting.

### 2. Implement Admin Controllers
- **`AdminSurveyController`**:
  - `index()`: Fetch all surveys with their metrics. Render `AdminSurveys`.
  - `store(Request $request)`: Create a new survey. Max 8 questions (MCQ & scale).
  - `update(Request $request, Survey $survey)`: Update survey details and questions.
  - `destroy(Survey $survey)`: Delete a survey.
  - `toggleStatus(Survey $survey)`: Toggle a survey's status between `active` and `closed`.

- **`AdminTargetingController`**:
  - `index()`: Fetch surveys with their targeting settings. Render `AdminTargeting`.
  - `update(Request $request, Survey $survey)`: Update targeting fields (`target_gender`, `target_age_band`, `target_location`, etc.).

- **`AdminMonitoringController`**:
  - `index()`: Fetch aggregated system metrics (total users, active surveys, total responses today). Fetch individual survey progress metrics (response count vs cap, completion rates). Render `AdminMonitoring`.

- **`AdminExportController`**:
  - `index()`: Render `AdminExport`.
  - `exportResponses()`: Generate and stream a CSV of survey responses.
  - `exportTransactions()`: Generate and stream a CSV of wallet transactions/redemptions.

### 3. Update Vue Pages
- **`AdminSurveys.vue`**: Add a list/table of existing surveys, and a modal/form for creating/editing surveys with a dynamic question builder (up to 8 questions, mcq/scale).
- **`AdminTargeting.vue`**: Add a list/table of surveys with inline editing or a modal to set demographic filters for each survey.
- **`AdminMonitoring.vue`**: Add metric cards for system-wide stats and a table/progress bars showing the status of active surveys.
- **`AdminExport.vue`**: Add buttons/forms to trigger the CSV downloads for Responses and Wallet Transactions.

## Verification & Testing
- Test all CRUD operations on surveys.
- Ensure the question limit (max 8) and types (mcq, scale) are validated on the backend.
- Verify that targeting fields can be updated and saved correctly.
- Ensure the monitoring dashboard accurately reflects the real-time data from the database.
- Download the exported CSVs and verify the columns and data integrity.
