# Reputation Info Modal — Design Spec

**Date:** 2026-05-19  
**Status:** Approved

## Summary

Add an info (`i`) icon button to the top-right of the Reputation Level card on the dashboard. Clicking it opens a modal that explains how the reputation tier system works.

## Scope

Single file change: `resources/js/pages/Dashboard.vue`

No new components. No backend changes.

## UI Change — Info Button

- Location: top-right corner of the Reputation card (`lg:col-span-4` block, lines 220–261), absolutely positioned (`absolute top-6 right-6`)
- Element: a ghost-style icon button using the `Info` icon from `lucide-vue-next`
- Style: small, subtle — `w-5 h-5 text-muted-foreground hover:text-foreground transition-colors cursor-pointer`
- `Info` must be added to the existing lucide import list

## State

One new ref in the script block:

```ts
const showReputationInfo = ref(false)
```

The button sets it to `true`; the Dialog `v-model:open` binds to it.

## Modal Content

Uses existing shadcn-vue `Dialog`, `DialogContent`, `DialogHeader`, `DialogTitle`, `DialogDescription` components (already installed).

**Header**
- Title: "How Reputation Works"
- Description: "Complete surveys to climb tiers and earn bigger point bonuses."

**Tier Table**
One row per tier (Bronze, Silver, Gold, Platinum), rendered from the existing `TIERS` array. Columns:
- Tier icon (lucide component, coloured)
- Tier name
- Survey threshold (e.g., "0+ surveys" / "5+ surveys")
- Point multiplier (e.g., "1.00×")

The row matching the user's current `reputationTier` is visually highlighted (subtle background, e.g., `bg-muted/50 rounded-lg`).

**Footer note**
A short italic line: "Your streak resets if you miss a day — keep it going for consistent bonuses."

## Imports to Add

```ts
import {
  Dialog, DialogContent, DialogHeader,
  DialogTitle, DialogDescription
} from '@/components/ui/dialog'
```

`Info` added to the lucide import line.

## What Is Not Changing

- No backend routes, controllers, or models
- No new Vue component files
- No changes to the TIERS data structure
- All other dashboard cards and features untouched
