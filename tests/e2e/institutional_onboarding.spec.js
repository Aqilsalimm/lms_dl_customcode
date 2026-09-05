import { test, expect } from '@playwright/test';

test('Institutional Student is Redirected to Onboarding when accessing Learning Page', async ({ page }) => {
    // 1. Go to Login Page
    await page.goto('http://127.0.0.1:8080/login');

    // 2. Login as Student
    await page.fill('input[type="email"]', 'student@drastha.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button:has-text("Sign In")');

    // Wait for Dashboard to load (Inertia navigate)
    await expect(page).toHaveURL(/.*dashboard/);

    // 3. Go to My Enrolled Courses
    await page.goto('http://127.0.0.1:8080/dashboard/enrolled-courses');
    
    // Wait for courses to load and click on "Mulai Belajar" or "Lanjutkan"
    // The button typically has text like "Lanjutkan Belajar" or "Mulai Belajar"
    const startLearningButton = page.locator('text=Mulai Belajar').first();
    
    if (await startLearningButton.isVisible()) {
        await startLearningButton.click();
    } else {
        const continueLearningButton = page.locator('text=Lanjutkan Belajar').first();
        if (await continueLearningButton.isVisible()) {
            await continueLearningButton.click();
        } else {
            // Fallback: Just go to courses index and click the first course
            await page.goto('http://127.0.0.1:8080/courses');
            await page.click('.grid a'); // Click first course
            await page.click('text=Mulai Belajar'); // Click start
        }
    }

    // 4. VERIFY: We should be redirected to the institutional onboarding page
    // because the student's profile is incomplete (member_number is null)
    
    // Wait a moment for any redirects
    await page.waitForTimeout(2000);
    
    const currentUrl = page.url();
    console.log('Current URL after clicking Mulai Belajar:', currentUrl);
    
    // Assert that we are on the onboarding page, not the learning page
    expect(currentUrl).toContain('/onboarding/institutional');
});
