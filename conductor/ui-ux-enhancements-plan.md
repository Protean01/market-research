# Plan: UI/UX Enhancements for Admin and User Portals

This plan outlines the implementation of a more professional, engaging, and mobile-responsive UI for both administrators and users of the Market Research Platform.

## 1. Admin Dashboard Enhancements (`AdminMonitoring.vue`)
- **Objective**: Provide a data-driven, visual "Mission Control" for administrators.
- **Changes**:
    -   **Metric Cards**: Refactor to include comparative icons and better typography.
    -   **Progress Visualization**: Enhance the progress bars with better colors and labels for completion percentage.
    -   **Activity Status**: Add "High", "Medium", or "Low" activity badges for active surveys.
    -   **Responsive Layout**: Ensure cards stack gracefully on smaller screens.

## 2. Admin Survey Builder Refinement (`AdminSurveys.vue`)
- **Objective**: Simplify the survey creation process and reduce friction.
- **Changes**:
    -   **Form Grouping**: Split the form into logical sections (General Info, Rewards & Caps, Question Builder).
    -   **Improved MCQ UI**: Better styling for adding/removing options, with clearer visual hierarchy.
    -   **Validation Indicators**: Use consistent styling for errors to make them more noticeable.
    -   **Survey Management Table**: Add better badges and hover effects for quick actions.

## 3. User Survey List Improvements (`SurveyList.vue`)
- **Objective**: Make survey discovery more engaging and informative.
- **Changes**:
    -   **Survey Cards**: Add a "Time to Complete" estimate (e.g., "5 mins") and "New" badges.
    -   **Empty State**: Replace the simple text with a more encouraging "No surveys available" graphic/icon.
    -   **Refresh Animation**: Improve the refresh button's visual feedback.
    -   **Responsive Cards**: Optimize card width and spacing for mobile-first usage.

## 4. Enhanced Wallet Interface (`Wallet.vue`)
- **Objective**: Create a high-end "FinTech" feel for reward management.
- **Changes**:
    -   **Card Display**: Update the main balance card with a cleaner, more modern gradient and typography.
    -   **Transaction History**: Use more descriptive icons (e.g., specific icons for earning vs. redeeming) and consistent spacing.
    -   **Redemption UX**: Clearer "Next Step" messaging when balance is low and better success/error toast integration.
    -   **Haptic Feedback**: Integrate `useHaptics` for all major button interactions.

## Verification
-   **Visual Consistency**: Ensure both Admin and User portals share a cohesive design language.
-   **Responsive Check**: Test all major pages on mobile and desktop viewports.
-   **Interaction Quality**: Verify that all buttons and transitions feel smooth and responsive.
