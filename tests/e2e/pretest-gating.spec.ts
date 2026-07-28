import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

test.describe('E2E Flow: Pre-Test & Syllabus Unblocking', () => {

  // Setup: Login sebagai Student sebelum pengujian berjalan
  test.beforeEach(async ({ page }) => {
    // Reset assessment attempts in DB before test runs so student starts fresh
    try {
      execSync('docker exec drasthalearning-laravel.test-1 php artisan tinker --execute="eval(base64_decode(\'SWxsdW1pbmF0ZVxTdXBwb3J0XEZhY2FkZXNcU2NoZW1hOjpkaXNhYmxlRm9yZWlnbktleUNvbnN0cmFpbnRzKCk7IEFwcFxNb2RlbHNcV29ya3Nob3BBc3Nlc3NtZW50VXNlckFuc3dlcjo6dHJ1bmNhdGUoKTsgQXBwXE1vZGVsc1xXb3Jrc2hvcEFzc2Vzc21lbnRBdHRlbXB0Ojp0cnVuY2F0ZSgpOyBJbGx1bWluYXRlXFN1cHBvcnRcRmFjYWRlc1xTY2hlbWE6OmVuYWJsZUZvcmVpZ25LZXlDb25zdHJhaW50cygpOw==\'));"', { stdio: 'ignore' });
    } catch (e) {}

    // Handle native confirm dialog in Assessment.vue so it doesn't block submit
    page.on('dialog', async dialog => {
      await dialog.accept();
    });
    
    page.on('response', response => {
      if (response.status() >= 400 || response.url().includes('dashboard') || response.status() === 302) {
        console.log(`<< ${response.status()} ${response.url()}`);
      }
    });

    await page.goto('/login');
    
    // Bypass ngrok warning if it exists
    const visitSiteBtn = page.locator('button:has-text("Visit Site")');
    if (await visitSiteBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await visitSiteBtn.click();
    }
    
    // Fill credentials
    await page.fill('input[type="email"]', 'student@drastha.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for either the OTP input or a dashboard element to appear
    await Promise.race([
      page.waitForSelector('input[type="text"]', { timeout: 60000 }).then(() => 'otp'),
      page.waitForSelector('text="Dashboard"', { timeout: 60000 }).then(() => 'dashboard')
    ]).catch(() => 'timeout');

    // Handle OTP if we see the OTP input
    if (await page.locator('input[type="text"]').count() > 0) {
      await page.fill('input[type="text"]', '111111');
      await page.click('button[type="submit"]');
    }

    // Pastikan login berhasil dan masuk ke dashboard (wait for a dashboard specific element)
    await page.waitForURL('**/dashboard');
  });

  test('Mencegah akses silabus sebelum Pre-Test, dan membukanya otomatis setelah selesai', async ({ page }) => {
    test.setTimeout(180000);
    // -------------------------------------------------------------------
    // 1. KUNJUNGI HALAMAN KURSUS
    // -------------------------------------------------------------------
    await page.goto('/courses/python-class-pemrograman-dan-perkenalan-bahasa-python/learn');

    // ASSERTION: Silabus Bab 1 harus berstatus TERKUNCI
    const lockedSyllabus = page.locator('[data-testid="syllabus-locked"]').first();
    await expect(lockedSyllabus).toBeVisible({ timeout: 15000 });

    // Pastikan ada pesan peringatan/gating yang tampil
    await expect(page.locator('text=Selesaikan Pre-Test terlebih dahulu')).toBeVisible();

    // Pastikan link materi tidak dapat diinteraksi
    const lessonLink = page.locator('[data-testid="lesson-link"]').first();
    await expect(lessonLink).toHaveAttribute('aria-disabled', 'true');


    // -------------------------------------------------------------------
    // 2. AMBIL/KERJAKAN PRE-TEST
    // -------------------------------------------------------------------
    // Klik tombol Mulai Pre-Test
    await page.click('[data-testid="start-pretest-btn"]');
    
    // Verifikasi URL masuk ke halaman pengerjaan kuis Pre-Test
    await expect(page).toHaveURL(/\/courses\/.*\/assessments\/.*/, { timeout: 15000 });

    // Handle browser confirm dialog saat klik Mulai Pengerjaan Tes
    const startAttemptBtn = page.locator('button:has-text("Mulai Pengerjaan Tes")');
    if (await startAttemptBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await startAttemptBtn.click();
    }

    // Jawab Soal 1 (Pilih opsi Radio pertama)
    await page.check('input[type="radio"][value="0"]');
    
    // Klik next question
    await page.click('button:has-text("Berikutnya")');

    // Jawab Soal 2 (Pilih opsi Radio kedua)
    await page.check('input[type="radio"][value="1"]');

    // Submit Jawaban
    await page.click('[data-testid="submit-pretest-btn"]');

    // Wait for submission result card & Kembali ke Kelas link to appear
    const backBtn = page.locator('a:has-text("Kembali ke Kelas")');
    await expect(backBtn).toBeVisible({ timeout: 15000 });
    await backBtn.click();

    // -------------------------------------------------------------------
    // 3. VERIFIKASI UNBLOCKING SILABUS
    // -------------------------------------------------------------------
    // Setelah submit dan kembali ke kelas, URL harus kembali ke /learn
    await expect(page).toHaveURL(/\/courses\/.*\/learn.*/, { timeout: 15000 });

    // ASSERTION: Silabus sekarang berstatus TERBUKA (Unlocked)
    const unlockedSyllabus = page.locator('[data-testid="syllabus-unlocked"]').first();
    if (!await unlockedSyllabus.isVisible().catch(() => false)) {
      const moduleHeader = page.locator('button:has-text("Bab 1")').first();
      if (await moduleHeader.isVisible().catch(() => false)) {
        await moduleHeader.click();
      }
    }
    await expect(unlockedSyllabus).toBeVisible({ timeout: 15000 });

    // ASSERTION: Link materi Bab 1 sekarang aktif dan dapat diklik
    const lessonLinkActive = page.locator('[data-testid="lesson-link"]').first();
    await expect(lessonLinkActive).not.toHaveAttribute('aria-disabled', 'true');

    // Coba klik materi Bab 1 untuk memilih materi
    await lessonLinkActive.click();
    
    // Pastikan materi berhasil dipilih dan aktif
    await expect(lessonLinkActive).toHaveClass(/bg-\[#264790\]/);
  });

});
