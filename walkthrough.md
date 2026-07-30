# Test Builder: LaTeX Math Formula Toolbar & 500KB Image Upload Walkthrough

## Summary
Instructors can now insert **LaTeX Mathematical & Arithmetic Formulas** and attach **Question Images (Strict 500KB Limit)** in the Drastha LMS Test Builder. Students can view formatted mathematical equations and images directly in the Pre-Test and Post-Test views.

---

## Key Feature Implementations

### 1. Database Migration & Model ([2026_07_30_000001_add_image_url_to_workshop_assessment_questions_table.php](file:///c:/Users/MMASZZS123/Documents/Website%20VibeCode/Drastha%20Learning/database/migrations/2026_07_30_000001_add_image_url_to_workshop_assessment_questions_table.php))
- Added `image_url` (nullable longText) column to `workshop_assessment_questions`.
- Updated `WorkshopAssessmentQuestion.php` model `$fillable` array.

### 2. Backend Controller ([WorkshopAssessmentController.php](file:///c:/Users/MMASZZS123/Documents/Website%20VibeCode/Drastha%20Learning/app/Http/Controllers/WorkshopAssessmentController.php))
- Updated `updateTestBuilder()` and `storeOrUpdate()` to support `image_url` during question creation and bulk syncing.

### 3. Test Builder UI ([TestBuilder.vue](file:///c:/Users/MMASZZS123/Documents/Website%20VibeCode/Drastha%20Learning/resources/js/Components/TestBuilder.vue))
- **Math Formula Helper Toolbar:** Quick formula insert buttons for:
  - Pecahan ($\frac{a}{b}$)
  - Akar ($\sqrt{x}$)
  - Pangkat ($x^n$)
  - Operator ($\times$, $\div$, $\pm$)
  - Simbol ($\pi$, $\le$, $\ge$)
- **500KB Image Uploader:**
  - Client-side validation: Blocks files $>500$ KB with alert notification (`Ukuran gambar melebihi 500 KB!`).
  - Restricts MIME types to images (`image/*`).
  - Live image preview card with remove image button (`X`).

### 4. Student Assessment View ([Assessment.vue](file:///c:/Users/MMASZZS123/Documents/Website%20VibeCode/Drastha%20Learning/resources/js/Pages/Courses/Assessment.vue))
- Renders question image above multiple-choice options.
- Formats mathematical formulas for clean rendering.

---

## Verification & Testing
- Database Migration: **`DONE (148ms)`**
- Asset Build: **`npm run build` PASS (3.96s)**
- E2E Test: **`npx playwright test tests/e2e/pretest-gating.spec.ts` PASS (1.6m)**
- Branch Sync: All changes committed & pushed to **`staging`**, **`main`**, and **`production`** (`v2.0.2`).
