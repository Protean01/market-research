# User Dashboard & Prize Draw System Fix Plan

## Objective
To resolve critical visibility and logical issues preventing the Prize Draw (Slot Machine) system from functioning properly, and to enhance the User Dashboard's UX by removing hardcoded data and adding clear active draw notifications.

## Key Files & Context
- **Backend**:
  - `app/Http/Controllers/DashboardController.php` (Refactor and add "Ready to Spin" surveys)
  - `app/Http/Controllers/PrizeDrawController.php` (Fix spin conflict logic)
  - `app/Models/PrizeDrawEntry.php` (Track spin status)
- **Frontend**:
  - `resources/js/pages/Dashboard.vue` (Add active draw UI, fix stats and links)

## 1. Problem Analysis & Proposed Fixes

### A. The Slot Machine "Spin" Conflict (Backend Bug)
**The Bug:** When a user completes a survey where `reward_type = 'prize_draw'`, `SurveyController@submit` automatically creates a `PrizeDrawEntry`. However, when the user goes to spin the slot machine, `PrizeDrawController@spin` checks if *any* `PrizeDrawEntry` exists for that survey/user combination. Since it does, it immediately throws a `409 Conflict: You have already spun the slot machine!` error.
**The Fix:** 
- We will rely on the `won_at` timestamp or create a distinction in how we update the existing `PrizeDrawEntry`. Since `won_at` is `null` when an entry is created (free entry) and only updated upon winning, we can use the `points_spent` column (which is unused for survey completion entries, usually 0) or add a simple `has_spun` boolean migration. 
- A faster, migration-free fix: When a user completes a survey, `points_entered` is `0`. We can check if `won_at !== null` or if they lost (we need a way to track "spun and lost"). The cleanest approach is to check if `won_at` is set, but that doesn't distinguish between "hasn't spun yet" and "spun and lost." 
- We will add a `has_spun` boolean via a new migration to clearly separate "entered the draw" from "pulled the lever."
- `PrizeDrawController@spin` will look up the existing `PrizeDrawEntry`, calculate the win, set `has_spun = true`, and save the result (updating `won_at` if they win).

### B. Invisible Prize Draws (Frontend UX Bug)
**The Bug:** When an admin activates the Draw Phase (`draw_phase_active = true`), the survey is marked `is_active = false`. This causes the survey to disappear entirely from the user's dashboard and survey list. Users only know they can spin if they click a notification link.
**The Fix:**
- `DashboardController@index` will query for surveys where `draw_phase_active = true` AND the user has completed the survey AND the user's `PrizeDrawEntry` has `has_spun = false`.
- We will add a high-priority "🎯 Ready to Spin!" section to `Dashboard.vue` right above the "Recommended For You" surveys.

### C. Dashboard Polish (Refactoring)
- **Fix "Total Earned":** The dashboard shows "Total Earned" but not the "Current Balance." We will adjust the Hero card to show the spendable points clearly.
- **Fix "Recommended For You" Time:** Update the survey cards to use `survey.estimated_time` instead of the hardcoded `5 MIN`.
- **Controller Refactor:** Remove the anti-pattern of instantiating `SurveyController` inside `DashboardController`. We'll implement a clean Eloquent query for featured surveys.

## Implementation Steps
1. **Migration**: Create a migration to add a `has_spun` boolean (default `false`) to the `prize_draw_entries` table.
2. **Backend Updates**:
    - Update `PrizeDrawController@spin` to check `has_spun` instead of `exists()`, and update the existing entry.
    - Update `DashboardController@index` to fetch `$readyToSpin` surveys and refactor the featured survey query.
3. **Frontend Updates**:
    - Modify `Dashboard.vue` to include a new section for active prize draws.
    - Replace the hardcoded "5 MIN" in `Dashboard.vue` with `survey.estimated_time || 5`.
    - Adjust the Dashboard Hero Section to display `stats.current_points` alongside `stats.total_earned`.

## Verification & Testing
1. Create a `prize_draw` survey and complete it as a user.
2. Verify the Dashboard shows the survey in a new "Ready to Spin" section once the admin triggers the Draw Phase.
3. Click the spin link, play the slot machine, and confirm the `409` error is resolved.
4. Verify the survey disappears from "Ready to Spin" after playing.
5. Check that points are awarded correctly if the user wins.