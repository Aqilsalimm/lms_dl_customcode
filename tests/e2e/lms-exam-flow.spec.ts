import { test, expect, Browser } from '@playwright/test';

/**
 * Multi-Role E2E Test Suite: Live Course & Exam Report System
 * Tech Stack: Laravel 13, Vue 3, Inertia.js, Tailwind CSS
 */

const COURSE_TITLE = 'Workshop Hybrid Live Class';
const COURSE_SLUG = 'workshop-hybrid-live-class';

test.describe('Full LMS Exam & Live Course Lifecycle (Multi-Role)', () => {
  test('Executes end-to-end flow with separate Admin & Student contexts', async ({ browser }: { browser: Browser }) => {
    // -------------------------------------------------------------------------
    // BROWSER CONTEXT SETUP (Isolated Storage States for Multi-Role Simulation)
    // -------------------------------------------------------------------------
    const adminContext = await browser.newContext({
      baseURL: 'http://localhost:8080',
      viewport: { width: 1280, height: 720 },
    });

    const studentContext = await browser.newContext({
      baseURL: 'http://localhost:8080',
      viewport: { width: 1280, height: 720 },
    });

    const adminPage = await adminContext.newPage();
    const studentPage = await studentContext.newPage();

    async function loginUser(page: import('@playwright/test').Page, email: string, pass: string) {
      await page.goto('/login');
      await page.fill('input[type="email"]', email);
      await page.fill('input[type="password"]', pass);
      await page.click('button[type="submit"]');
      await page.waitForURL(/.*(dashboard|courses|course-builder|login\/otp).*/, { timeout: 15000 });
      if (await page.locator('input[type="text"]').count() > 0) {
        await page.fill('input[type="text"]', '111111');
        await page.click('button[type="submit"]');
      }
      await page.waitForURL(/.*(dashboard|courses|course-builder).*/, { timeout: 15000 });
    }

    try {
      // =======================================================================
      // STEP 0 (Login)
      // =======================================================================
      console.log('🔹 Step 0: Logging in Admin & Student...');
      await loginUser(adminPage, 'admin@drastha.com', 'password');
      await loginUser(studentPage, 'student@drastha.com', 'password');

      // =======================================================================
      // STEP A (Admin - Create Course)
      // =======================================================================
      console.log('🔹 Step A: Admin creating course...');
      await adminPage.goto('/course-builder');
      
      // Buka Modal Tambah Kelas
      await adminPage.click('button:has-text("Tambah Kelas Baru")');
      await expect(adminPage.locator('h3:has-text("Tambah Kelas Baru")')).toBeVisible();

      // Pilih Kelas Live Class
      await adminPage.click('text=Live Class / Workshop');

      // Isi Judul Kelas
      await adminPage.fill('input[placeholder*="Contoh: Pemrograman"]', COURSE_TITLE);
      
      // Pilih Mode Hybrid
      await adminPage.click('text=Hybrid Mode');

      // Fill Location
      await adminPage.fill('textarea[placeholder*="Gedung Utama"]', 'Jakarta Convention Center');
      
      // Fill Meeting Url
      await adminPage.fill('input[placeholder*="zoom.us"]', 'https://zoom.us/j/1234567890');

      // Submit Course Creation & Assert Disabled State (Anti-Spam)
      const saveCourseBtn = adminPage.locator('button:has-text("Buat Kelas")');
      await saveCourseBtn.click();
      await expect(saveCourseBtn).toBeDisabled();

      // Wait for modal to close (indicator of success)
      await expect(adminPage.locator('h3:has-text("Tambah Kelas Baru")')).toBeHidden({ timeout: 10000 });

      // Click "Builder" / "Settings" to go to syllabus builder
      const builderBtn = adminPage.locator('a:has-text("Builder")').first();
      await expect(builderBtn).toBeVisible({ timeout: 10000 });
      await builderBtn.click();

      // =======================================================================
      // STEP B (Student - Pre-Test Submission)
      // =======================================================================
      console.log('🔹 Step B: Student taking Pre-Test...');
      await studentPage.goto(`/courses/${COURSE_SLUG}`);
      
      const startPreTestBtn = studentPage.locator('button:has-text("Mulai Pre-Test"), a:has-text("Mulai Pre-Test")');
      await expect(startPreTestBtn).toBeVisible({ timeout: 10000 });
      await startPreTestBtn.click();

      // Answer questions
      const options = studentPage.locator('input[type="radio"], label:has-text("A")');
      const count = await options.count();
      for (let i = 0; i < Math.min(count, 3); i++) {
        await options.nth(i).click();
      }

      // Submit Pre-Test & Assert Disabled State (Anti-Spam)
      const submitPreTestBtn = studentPage.locator('button[type="submit"]:has-text("Submit"), button:has-text("Kirim Jawaban")');
      await submitPreTestBtn.click();
      await expect(submitPreTestBtn).toBeDisabled();

      // Auto-wait for completed state / status indicator
      await expect(studentPage.locator('text=Pre-Test Selesai, text=Berhasil, text=Submitted').first()).toBeVisible({ timeout: 10000 });

      // =======================================================================
      // STEP C (Admin - Live Monitoring Verification)
      // =======================================================================
      console.log('🔹 Step C: Admin monitoring Live Exam Report...');
      await adminPage.goto('/admin/exam-reports/live');
      await expect(adminPage).toHaveURL(/\/admin\/exam-reports\/live/);

      // Verify participant finished Pre-Test count incremented to 1
      const preTestStats = adminPage.locator('[data-testid="pretest-completed-count"], .pretest-count, text=1').first();
      await expect(preTestStats).toBeVisible({ timeout: 10000 });

      // =======================================================================
      // STEP D (Student - Access Zoom & Post-Test Submission)
      // =======================================================================
      console.log('🔹 Step D: Student accessing unlocked Zoom link & Post-Test...');
      await studentPage.goto(`/courses/${COURSE_SLUG}`);

      // Verify Zoom/Meeting link is unlocked
      const zoomLink = studentPage.locator('a:has-text("Zoom"), a:has-text("Join Meeting"), [data-testid="zoom-link"]').first();
      await expect(zoomLink).toBeVisible();
      await expect(zoomLink).not.toHaveClass(/disabled|opacity-50/);

      // Click Zoom link
      const [popupPage] = await Promise.all([
        studentPage.waitForEvent('popup').catch(() => null),
        zoomLink.click(),
      ]);
      if (popupPage) {
        await popupPage.close();
      }

      // Navigate to Post-Test
      const startPostTestBtn = studentPage.locator('button:has-text("Mulai Post-Test"), a:has-text("Post-Test")');
      await startPostTestBtn.click();

      // Fill Post-Test answers
      const postTestOptions = studentPage.locator('input[type="radio"], label:has-text("B")');
      const postCount = await postTestOptions.count();
      for (let i = 0; i < Math.min(postCount, 3); i++) {
        await postTestOptions.nth(i).click();
      }

      // Submit Post-Test & Assert Disabled State (Anti-Spam)
      const submitPostTestBtn = studentPage.locator('button[type="submit"]:has-text("Submit"), button:has-text("Kirim Post-Test")');
      await submitPostTestBtn.click();
      await expect(submitPostTestBtn).toBeDisabled();

      await expect(studentPage.locator('text=Post-Test Selesai, text=Lulus, text=Submitted').first()).toBeVisible({ timeout: 10000 });

      // =======================================================================
      // STEP E (Admin - Complete & Open Next Syllabus)
      // =======================================================================
      console.log('🔹 Step E: Admin completing & opening next syllabus...');
      await adminPage.goto('/admin/exam-reports/live');
      await adminPage.reload();

      // Verify 100% Post-Test completion recorded
      const completionStat = adminPage.locator('text=100%, [data-testid="posttest-completion-rate"]').first();
      await expect(completionStat).toBeVisible({ timeout: 10000 });

      // Click "Buka Silabus Hari Berikutnya"
      const openNextSyllabusBtn = adminPage.locator('button:has-text("Buka Silabus Hari Berikutnya"), button:has-text("Unlock Next Syllabus")');
      await expect(openNextSyllabusBtn).toBeVisible();
      await openNextSyllabusBtn.click();
      await expect(openNextSyllabusBtn).toBeDisabled();

      console.log('✅ End-to-End Multi-Role LMS Flow Test completed successfully!');

    } finally {
      await adminContext.close();
      await studentContext.close();
    }
  });
});
