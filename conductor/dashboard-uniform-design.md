# Plan: Dashboard Uniform Design Alignment

Align the user dashboard (`Dashboard.vue`) with the project's established design language (as seen in `SurveyList.vue` and `Wallet.vue`).

## Key Changes

### 1. Color Palette & Background
- Remove `bg-slate-950` from the main container. Let it inherit from `AppLayout` or use `bg-background`.
- Use `text-foreground` and `text-muted-foreground` consistently.
- Use `bg-card` and `border-border` for all secondary sections.

### 2. Header & Welcome Message
- Align the header style with `SurveyList.vue`:
  - `text-3xl font-black tracking-tight text-foreground` for the title.
  - `text-muted-foreground mt-1 font-medium text-lg` for the subtext.
- Welcome message: "Welcome, [phone_number]".

### 3. Primary Balance Card
- Use a design similar to the `Wallet.vue` balance card or the `SurveyList.vue` push prompt:
  - `rounded-[3rem]` border radius.
  - `bg-zinc-900` or `bg-indigo-600` for prominence.
  - Large, bold points (`text-7xl font-black tracking-tighter`).

### 4. Secondary Stats & Sections
- Use `rounded-[2.5rem]` for card sections.
- For the "Next For You" (Featured Surveys) cards, use the exact same style as the `SurveyList.vue` cards:
  - `rounded-[2.5rem] border border-border bg-card p-8`.
  - Same reward and time badges.
- Use the same activity item style as `Wallet.vue`.

### 5. Quick Actions
- Keep the 4-column grid but use consistent colors and iconography.
- Use `rounded-3xl` for the action backgrounds.

## Implementation Details

### `Dashboard.vue`
- Update the `<template>` to use the new classes and structures.
- Ensure all icons and text styles match the other user pages.

## Verification & Testing
1.  **Visual Comparison:** Open the Dashboard, Survey List, and Wallet pages and ensure they look like they belong to the same application.
2.  **Mobile Fit:** Ensure the `rounded-[2.5rem]` and `p-8` values don't cause issues on smaller screens (adjust to `p-6` or `rounded-3xl` on mobile if needed, but the current project seems to favor the large radius).
3.  **Data Integrity:** Verify all stats still load correctly.
