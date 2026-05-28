# Image Choice Question Type — Design Spec

**Date:** 2026-04-24

## Summary

Add a new `image_mcq` question type to the survey platform. Admins upload images per option in the builder; respondents see a 2-column image grid and tap one to select, which auto-advances the survey.

---

## Data Model

No migration required. Questions are stored as JSON in `surveys.questions`.

An `image_mcq` question:

```json
{
  "id": "abc123",
  "text": "Which packaging do you prefer?",
  "type": "image_mcq",
  "options": [
    { "label": "Classic Blue", "image_url": "/storage/survey-images/blue.jpg" },
    { "label": "Rose Pink",    "image_url": "/storage/survey-images/pink.jpg" }
  ],
  "required": true,
  "logic": {}
}
```

Options are `{label: string, image_url: string}` objects (instead of plain strings used by other types). All other question types are unaffected.

The answer recorded is the `label` string of the selected option.

---

## Image Upload

- **Endpoint:** `POST /admin/survey-images`
- **Controller:** `App\Http\Controllers\Admin\AdminSurveyImageController@upload`
- **Auth:** admin middleware (same as other admin routes)
- **Validation:** `image` mime rule, max 2 MB
- **Storage:** `public` disk → `survey-images/` → URL returned as `/storage/survey-images/{filename}`
- **Response:** `{ "url": "/storage/survey-images/abc.jpg" }`
- Upload is immediate on file pick — no "save survey first" requirement
- No image cleanup on question/survey delete (MVP simplicity)

---

## Survey Builder UI (`AdminSurveys.vue`)

- Add `"Image Choice"` option to the question type `<select>` with value `image_mcq`
- When type is `image_mcq`, replace the plain text options list with an image option builder:
  - Each row: thumbnail (click to upload) + label text input + remove button
  - Thumbnail shows the uploaded image if `image_url` set, otherwise shows a dashed upload placeholder
  - Clicking thumbnail triggers a hidden `<input type="file">`, POSTs to upload endpoint via axios, sets `option.image_url` from response
  - "Add Image Option" button appends `{ label: '', image_url: '' }`
  - Minimum 2 options enforced (remove button disabled when only 2 remain)
- Default options on type switch: `[{ label: 'Option 1', image_url: '' }, { label: 'Option 2', image_url: '' }]`
- Existing `addOption` / `removeOption` functions are replaced with `addImageOption` / `removeImageOption` for this type — existing string-based functions remain unchanged for other types

---

## Survey Taker UI

**New component:** `resources/js/components/Survey/QuestionImageMCQ.vue`

- Props: `question` (same shape as other question components)
- Emits: `answer` with `{ questionId, answer }` where `answer` is the selected `label`
- Layout: 2-column CSS grid, each cell is a tappable tile
  - Image fills the top portion of the tile
  - Label text below the image, centred
  - Selected tile: indigo border + checkmark badge (top-right of image)
  - Unselected tiles: dimmed to 50% opacity after a selection is made
- Single-select only — tapping a new tile deselects the previous one

**`SurveyActive.vue` changes:**
- Import `QuestionImageMCQ`
- Add `v-else-if="currentQuestion.type === 'image_mcq'"` branch in the question renderer
- Add `'image_mcq'` to the auto-advance type check in `handleAnswer` (alongside `'mcq'` and `'scale'`)

---

## Backend Validation

**`AdminSurveyController`** — both `store()` and `update()`:
- Add `image_mcq` to the question type enum: `'in:mcq,scale,checkbox,text,image_mcq'`
- Add `'required_if:questions.*.type,image_mcq'` to the options validation rule

**`SurveyController`** — no changes needed. The submit endpoint records answers as-is.

---

## Files Changed

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/AdminSurveyImageController.php` | New — image upload endpoint |
| `app/Http/Controllers/Admin/AdminSurveyController.php` | Add `image_mcq` to type enum + options rule |
| `routes/web.php` | Add `POST /admin/survey-images` route |
| `resources/js/pages/admin/AdminSurveys.vue` | Image option builder for `image_mcq` type |
| `resources/js/components/Survey/QuestionImageMCQ.vue` | New — taker-side image grid component |
| `resources/js/pages/SurveyActive.vue` | Import + render + auto-advance for `image_mcq` |

No migration. No changes to `Survey` model.
