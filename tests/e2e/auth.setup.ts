import { test as setup, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const adminFile = '.auth/admin.json';
const studentFile = '.auth/student.json';

const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@drastha.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'password';

const STUDENT_EMAIL = process.env.STUDENT_EMAIL || 'student@drastha.com';
const STUDENT_PASSWORD = process.env.STUDENT_PASSWORD || 'password';

setup('authenticate as admin', async ({ page }) => {
  if (!fs.existsSync(path.dirname(adminFile))) {
    fs.mkdirSync(path.dirname(adminFile), { recursive: true });
  }

  page.on('response', async (response) => {
    if (response.url().includes('login') && response.request().method() === 'POST') {
      try {
        const text = await response.text();
        console.log('LOGIN POST RESPONSE:', response.status(), text.substring(0, 500));
      } catch (e) {}
    }
  });

  await page.goto('/login');
  await page.fill('input[type="email"]', ADMIN_EMAIL);
  await page.fill('input[type="password"]', ADMIN_PASSWORD);
  
  const submitBtn = page.locator('button[type="submit"]');
  await expect(submitBtn).toBeEnabled();
  await submitBtn.click();
  
  // Wait for either OTP input or dashboard/course-builder
  await page.waitForURL(/.*(dashboard|course-builder|courses|login\/otp).*/, { timeout: 15000 });

  // Handle OTP
  if (await page.locator('input[type="text"]').count() > 0) {
    await page.fill('input[type="text"]', '111111');
    await page.click('button[type="submit"]');
  }

  // Tunggu sampai navigasi ke dashboard atau profil selesai
  await page.waitForURL(/.*(dashboard|course-builder).*/, { timeout: 15000 });

  await page.context().storageState({ path: adminFile });
});

setup('authenticate as student', async ({ page }) => {
  if (!fs.existsSync(path.dirname(studentFile))) {
    fs.mkdirSync(path.dirname(studentFile), { recursive: true });
  }

  await page.goto('/login');
  await page.fill('input[type="email"]', STUDENT_EMAIL);
  await page.fill('input[type="password"]', STUDENT_PASSWORD);

  const submitBtn = page.locator('button[type="submit"]');
  await expect(submitBtn).toBeEnabled();
  await submitBtn.click();

  // Wait for either OTP input or dashboard
  await page.waitForURL(/.*(dashboard|courses|login\/otp).*/, { timeout: 15000 });

  // Handle OTP
  if (await page.locator('input[type="text"]').count() > 0) {
    await page.fill('input[type="text"]', '111111');
    await page.click('button[type="submit"]');
  }

  await page.waitForURL(/.*(dashboard|courses).*/, { timeout: 15000 });

  await page.context().storageState({ path: studentFile });
});
