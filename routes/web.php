<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseBuilderController;
use App\Http\Controllers\BundleBuilderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\EbookFileController;
use App\Models\Course;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/loaderio-0229882d7a9cb322b9c89ff53e4c2bb2.txt', function () {
    return 'loaderio-0229882d7a9cb322b9c89ff53e4c2bb2';
});

// Public self-help cache cleanup page (Opsi C — service worker / browser cache reset)
Route::get('/help/clear-cache', function () {
    return Inertia::render('Help/ClearCache', [
        'siteName' => config('app.name', 'Drastha Learning'),
    ]);
})->name('help.clear-cache');

// NOTE: Utility routes /clear-app-cache and /publish-all-courses were removed.
// They were unauthenticated GET endpoints that allowed anyone to flush the
// application cache (which also destroys the database-backed session store)
// or mass-publish every draft course. Use `php artisan cache:clear` on the
// server instead, and publish courses through the authenticated course builder.

// Public AJAX API Endpoint for Dynamic Course Filtering
Route::get('/api/courses/search', [CourseController::class, 'apiSearch']);

Route::get('/language/{locale}', [LocalizationController::class, 'switchLanguage'])->name('language.switch');

// Redirect old WordPress or indexed paths to homepage
Route::redirect('/admission', '/', 301);

Route::get('/', function () {
    $courses = \Illuminate\Support\Facades\Cache::remember('homepage_courses', 3600, function () {
        return Course::where('status', 'published')
            ->select('id', 'title', 'slug', 'thumbnail', 'price', 'status', 'instructor_id', 'category_id', 'created_at')
            ->with(['category:id,name,slug', 'instructor:id,name,photo', 'lessons'])
            ->latest()
            ->get()
            ->makeHidden(['description', 'meta_desc']);
    });
        
    return Inertia::render('Welcome', [
        'courses' => $courses,
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard Route pointing to DashboardController
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/dashboard/enrolled-courses', [DashboardController::class, 'enrolledCourses'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.enrolled-courses');

Route::get('/dashboard/learning-progress', [DashboardController::class, 'learningProgress'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.learning-progress');

use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;

Route::get('/dashboard/reviews', [DashboardController::class, 'reviews'])->middleware(['auth', 'verified'])->name('dashboard.reviews');
Route::get('/dashboard/quiz-attempts', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.quiz-attempts');
Route::get('/dashboard/wishlist', [DashboardController::class, 'wishlist'])->middleware(['auth', 'verified'])->name('dashboard.wishlist');
Route::get('/dashboard/order-history', [DashboardController::class, 'orderHistory'])->middleware(['auth', 'verified'])->name('dashboard.order-history');
Route::get('/dashboard/qa', [DiscussionController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard.qa');

Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->middleware(['auth', 'verified'])->name('wishlist.toggle');
Route::post('/courses/{slug}/reviews', [ReviewController::class, 'store'])->middleware(['auth', 'verified'])->name('reviews.store');
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->middleware(['auth', 'verified'])->name('reviews.destroy');

// Instructor Live Class routes
Route::get('/dashboard/live-class', [DashboardController::class, 'liveClassSchedule'])->middleware(['auth', 'verified'])->name('dashboard.live-class');
Route::post('/dashboard/live-class/{course}/update-schedule', [DashboardController::class, 'updateLiveClassSchedule'])->middleware(['auth', 'verified'])->name('dashboard.live-class.update-schedule');

// Instructor Withdrawal routes
Route::get('/dashboard/withdrawals', [\App\Http\Controllers\WithdrawalRequestController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard.withdrawals');
Route::post('/dashboard/withdrawals', [\App\Http\Controllers\WithdrawalRequestController::class, 'store'])->middleware(['auth', 'verified'])->name('dashboard.withdrawals.store');
Route::post('/dashboard/payment-profile', [\App\Http\Controllers\Instructor\PaymentProfileController::class, 'store'])->middleware(['auth', 'verified'])->name('dashboard.payment-profile.store');

use App\Http\Controllers\Auth\OtpController;

// OTP routes
Route::post('/otp/send', [OtpController::class, 'send'])->name('otp.send')->middleware('throttle:3,1');
Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify')->middleware('throttle:5,1');

use App\Http\Controllers\BillingController;
Route::get('/billing/suspended', [BillingController::class, 'suspended'])->middleware(['auth'])->name('billing.suspended');

    Route::middleware('auth')->group(function () {
    // E-Book Security & File routes
    Route::get('/ebooks/{ebook:slug}/view', [EbookFileController::class, 'view'])->name('ebooks.view');
    Route::get('/ebooks/{ebook:slug}/download', [EbookFileController::class, 'download'])->name('ebooks.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Profile Photo routes
    Route::post('/profile/photo', [\App\Http\Controllers\ProfilePhotoController::class, 'update'])->name('profile.photo.update');
    Route::delete('/profile/photo', [\App\Http\Controllers\ProfilePhotoController::class, 'destroy'])->name('profile.photo.destroy');

    // Instructor Registration Flow
    Route::get('/instructor/setup', [\App\Http\Controllers\InstructorRegistrationController::class, 'create'])->name('instructor.profile.setup');
    Route::post('/instructor/setup', [\App\Http\Controllers\InstructorRegistrationController::class, 'store'])->name('instructor.profile.store');

    // Role Management (Admin only)
    Route::post('/dashboard/change-role/{user}', [DashboardController::class, 'changeRole']);
    
    // User Management (Admin only)
    Route::get('/dashboard/users-manage', [\App\Http\Controllers\Admin\UserManageController::class, 'index'])->name('dashboard.users.manage');
    Route::post('/dashboard/users-manage/{user}/approve', [\App\Http\Controllers\Admin\UserManageController::class, 'approveInstructor'])->name('dashboard.users.approve');
    Route::post('/dashboard/users-manage/{user}/reject', [\App\Http\Controllers\Admin\UserManageController::class, 'rejectInstructor'])->name('dashboard.users.reject');
    Route::post('/dashboard/users-manage/{user}/role', [\App\Http\Controllers\Admin\UserManageController::class, 'updateRole'])->name('dashboard.users.role');
    Route::get('/dashboard/users-manage/export', [\App\Http\Controllers\Admin\UserManageController::class, 'export'])->name('dashboard.users.export');
    Route::post('/dashboard/users-manage', [\App\Http\Controllers\Admin\UserManageController::class, 'store'])->name('dashboard.users.store');

    Route::delete('/dashboard/users-manage/{user}', [\App\Http\Controllers\Admin\UserManageController::class, 'destroy'])->name('dashboard.users.destroy');
    Route::post('/dashboard/users-manage/{id}/restore', [\App\Http\Controllers\Admin\UserManageController::class, 'restore'])->name('dashboard.users.restore');
    
    // Exam & Assessment Analytics Report (Admin & Instructor)
    Route::get('/dashboard/reports/exam', [\App\Http\Controllers\ExamReportController::class, 'index'])->name('dashboard.reports.exam');

    // LMS Settings
    Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('/dashboard/settings', [DashboardController::class, 'updateSettings'])->name('dashboard.settings.update');

    // Admin Withdrawal Settings & Requests
    Route::get('/dashboard/admin/withdrawals', [\App\Http\Controllers\WithdrawalRequestController::class, 'adminIndex'])->name('dashboard.admin.withdrawals');
    Route::post('/dashboard/admin/withdrawals/{withdrawal}/complete', [\App\Http\Controllers\WithdrawalRequestController::class, 'complete'])->name('dashboard.admin.withdrawals.complete');
    Route::post('/dashboard/admin/withdrawals/{withdrawal}/reject', [\App\Http\Controllers\WithdrawalRequestController::class, 'reject'])->name('dashboard.admin.withdrawals.reject');

    Route::get('/dashboard/settings/withdrawal-methods', [\App\Http\Controllers\Admin\WithdrawalSettingController::class, 'index'])->name('dashboard.settings.withdrawal-methods');
    Route::post('/dashboard/settings/withdrawal-methods', [\App\Http\Controllers\Admin\WithdrawalSettingController::class, 'store'])->name('dashboard.settings.withdrawal-methods.store');
    Route::put('/dashboard/settings/withdrawal-methods/{method}', [\App\Http\Controllers\Admin\WithdrawalSettingController::class, 'update'])->name('dashboard.settings.withdrawal-methods.update');
    Route::delete('/dashboard/settings/withdrawal-methods/{method}', [\App\Http\Controllers\Admin\WithdrawalSettingController::class, 'destroy'])->name('dashboard.settings.withdrawal-methods.destroy');
    
    // e-Commerce Settings & Analytics (Admin only)
    Route::get('/dashboard/ecommerce/analytics', [\App\Http\Controllers\Admin\EcommerceController::class, 'analytics'])
        ->name('dashboard.ecommerce.analytics');
    Route::get('/dashboard/ecommerce/coupons', [\App\Http\Controllers\Admin\EcommerceController::class, 'coupons'])
        ->name('dashboard.ecommerce.coupons');
    Route::post('/dashboard/ecommerce/coupons', [\App\Http\Controllers\Admin\EcommerceController::class, 'storeCoupon'])
        ->name('dashboard.ecommerce.coupons.store');
    Route::put('/dashboard/ecommerce/coupons/{coupon}', [\App\Http\Controllers\Admin\EcommerceController::class, 'updateCoupon'])
        ->name('dashboard.ecommerce.coupons.update');
    Route::delete('/dashboard/ecommerce/coupons/{coupon}', [\App\Http\Controllers\Admin\EcommerceController::class, 'deleteCoupon'])
        ->name('dashboard.ecommerce.coupons.destroy');
    Route::get('/dashboard/ecommerce/settings', [\App\Http\Controllers\Admin\EcommerceController::class, 'settings'])
        ->name('dashboard.ecommerce.settings');
    Route::post('/dashboard/ecommerce/settings', [\App\Http\Controllers\Admin\EcommerceController::class, 'updateSettings'])
        ->name('dashboard.ecommerce.settings.update');

    // Coupon verification route
    Route::post('/payment/validate-coupon', [\App\Http\Controllers\PaymentController::class, 'validateCoupon'])
        ->name('payment.validate-coupon');
    Route::get('/dashboard/settings/course-builder', [DashboardController::class, 'courseBuilderSettings'])->name('dashboard.settings.course-builder');
    Route::post('/dashboard/settings/course-builder/categories', [DashboardController::class, 'storeCategory'])->name('dashboard.settings.course-builder.categories.store');
    Route::put('/dashboard/settings/course-builder/categories/{category}', [DashboardController::class, 'updateCategory'])->name('dashboard.settings.course-builder.categories.update');
    Route::delete('/dashboard/settings/course-builder/categories/{category}', [DashboardController::class, 'deleteCategory'])->name('dashboard.settings.course-builder.categories.destroy');

    Route::post('/dashboard/settings/course-builder/tags', [DashboardController::class, 'storeTag'])->name('dashboard.settings.course-builder.tags.store');
    Route::put('/dashboard/settings/course-builder/tags/{tag}', [DashboardController::class, 'updateTag'])->name('dashboard.settings.course-builder.tags.update');
    Route::delete('/dashboard/settings/course-builder/tags/{tag}', [DashboardController::class, 'deleteTag'])->name('dashboard.settings.course-builder.tags.destroy');
    Route::get('/dashboard/settings/course-builder/download-template', [DashboardController::class, 'downloadImportTemplate'])->name('dashboard.settings.course-builder.download-template');
    Route::post('/dashboard/settings/course-builder/import', [DashboardController::class, 'importCourses'])->name('dashboard.settings.course-builder.import');
    Route::get('/dashboard/settings/course-builder/courses', [DashboardController::class, 'getCoursesWithModules'])->name('dashboard.settings.course-builder.courses');
    Route::get('/dashboard/settings/course-builder/download-quiz-template', [DashboardController::class, 'downloadQuizTemplate'])->name('dashboard.settings.course-builder.download-quiz-template');
    Route::post('/dashboard/settings/course-builder/import-quiz', [DashboardController::class, 'importQuizzes'])->name('dashboard.settings.course-builder.import-quiz');
    Route::post('/dashboard/settings/course-builder/test-builder', [DashboardController::class, 'updateTestBuilderSettings'])->name('dashboard.settings.course-builder.test-builder.update');
    Route::post('/dashboard/users-manage/settings', [\App\Http\Controllers\Admin\UserManageController::class, 'updateSettings'])->name('dashboard.users.settings.update');

    // Course Builder Routes
    Route::prefix('course-builder')->name('course-builder.')->group(function () {
        Route::get('/', [CourseBuilderController::class, 'index'])->name('index');
        Route::get('/metadata', [CourseBuilderController::class, 'getMetadata'])->name('metadata');
        Route::post('/courses', [CourseBuilderController::class, 'store'])->name('store');
        Route::get('/courses/{course}', [CourseBuilderController::class, 'builder'])->name('build');
        Route::put('/courses/{course}', [CourseBuilderController::class, 'update'])->name('update');
        Route::delete('/courses/{course}', [CourseBuilderController::class, 'destroy'])->name('destroy');
        Route::post('/courses/import', [CourseBuilderController::class, 'import'])->name('import');
        
        // Modules
        Route::post('/courses/{course}/modules', [CourseBuilderController::class, 'addModule'])->name('modules.store');
        Route::put('/modules/{module}', [CourseBuilderController::class, 'updateModule'])->name('modules.update');
        Route::delete('/modules/{module}', [CourseBuilderController::class, 'deleteModule'])->name('modules.destroy');
        
        // Lessons
        Route::post('/upload-lesson-asset', [CourseBuilderController::class, 'uploadLessonAsset'])->name('upload-lesson-asset');
        Route::post('/modules/{module}/lessons', [CourseBuilderController::class, 'addLesson'])->name('lessons.store');
        Route::put('/lessons/{lesson}', [CourseBuilderController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/lessons/{lesson}', [CourseBuilderController::class, 'deleteLesson'])->name('lessons.destroy');
        
        // Quizzes
        Route::post('/modules/{module}/quizzes', [CourseBuilderController::class, 'addQuiz'])->name('quizzes.store');
        Route::put('/quizzes/{quiz}', [CourseBuilderController::class, 'updateQuiz'])->name('quizzes.update');
        Route::delete('/quizzes/{quiz}', [CourseBuilderController::class, 'deleteQuiz'])->name('quizzes.destroy');
        
        // Questions
        Route::post('/quizzes/{quiz}/questions', [CourseBuilderController::class, 'addQuestion'])->name('questions.store');
        Route::put('/questions/{question}', [CourseBuilderController::class, 'updateQuestion'])->name('questions.update');
        Route::delete('/questions/{question}', [CourseBuilderController::class, 'deleteQuestion'])->name('questions.destroy');

        // Workshop Assessments
        Route::post('/courses/{course}/assessments', [\App\Http\Controllers\WorkshopAssessmentController::class, 'storeOrUpdate'])->name('assessments.store');
        Route::post('/courses/{course}/assessments-bulk', [\App\Http\Controllers\WorkshopAssessmentController::class, 'updateTestBuilder'])->name('assessments.bulk-store');
        Route::post('/assessments/{assessment}/questions', [\App\Http\Controllers\WorkshopAssessmentController::class, 'addQuestion'])->name('assessments.questions.store');
        Route::put('/assessment-questions/{question}', [\App\Http\Controllers\WorkshopAssessmentController::class, 'updateQuestion'])->name('assessments.questions.update');
        Route::delete('/assessment-questions/{question}', [\App\Http\Controllers\WorkshopAssessmentController::class, 'deleteQuestion'])->name('assessments.questions.destroy');
    });

    // Bundle Builder Routes
    Route::prefix('bundle-builder')->name('bundle-builder.')->group(function () {
        Route::get('/', [BundleBuilderController::class, 'index'])->name('index');
        Route::post('/bundles', [BundleBuilderController::class, 'store'])->name('store');
        Route::get('/bundles/{bundle}', [BundleBuilderController::class, 'edit'])->name('edit');
        Route::put('/bundles/{bundle}', [BundleBuilderController::class, 'update'])->name('update');
        Route::delete('/bundles/{bundle}', [BundleBuilderController::class, 'destroy'])->name('destroy');
    });

    // Checkout Routes
    Route::post('/payment/checkout', [PaymentController::class, 'checkout'])->name('payment.checkout');
    Route::post('/payment/mock-complete/{order}', [PaymentController::class, 'completeMockPayment'])->name('payment.mock-complete');
    Route::post('/payment/cancel/{order}', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::get('/checkout', [CartController::class, 'checkoutPage'])->name('cart.checkout-page');
    Route::get('/checkout/resume/{order}', [CartController::class, 'resumeCheckout'])->name('checkout.resume');
    Route::get('/orders/{order}/invoice', [PaymentController::class, 'downloadInvoice'])->name('orders.invoice');
    Route::get('/courses/{course:slug}/learn', [CourseController::class, 'learn'])->middleware('active.subscription')->name('courses.learn');
    Route::get('/courses/{course:slug}/lessons/{lesson}/content', [CourseController::class, 'getLessonContent'])->middleware('active.subscription')->name('courses.lessons.content');
    Route::post('/courses/{course:slug}/lessons/{lesson}/toggle-complete', [CourseController::class, 'toggleLessonComplete'])->name('courses.lessons.complete');
    Route::post('/courses/{course:slug}/quizzes/{quiz}/toggle-complete', [CourseController::class, 'toggleQuizComplete'])->name('courses.quizzes.complete');
    Route::post('/courses/{course:slug}/lessons/{lesson}/log-progress', [CourseController::class, 'logProgress'])->name('courses.lessons.log-progress');
    Route::post('/dashboard/blogs', [BlogController::class, 'store'])->name('blogs.store');
    Route::put('/dashboard/blogs/{blog}', [BlogController::class, 'update'])->name('blogs.update');
    Route::delete('/dashboard/blogs/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');
    Route::post('/dashboard/blogs/{blog}/approve', [BlogController::class, 'approvePost'])->name('blogs.approve');
    
    Route::get('/dashboard/settings/blog', [BlogController::class, 'settings'])->name('dashboard.settings.blog');
    Route::post('/dashboard/settings/blog', [BlogController::class, 'updateSettings'])->name('dashboard.settings.blog.update');
    Route::post('/dashboard/settings/blog/approve-settings', [BlogController::class, 'approveSettingsRequest'])->name('dashboard.settings.blog.approve-settings');
    Route::post('/dashboard/settings/blog/reject-settings', [BlogController::class, 'rejectSettingsRequest'])->name('dashboard.settings.blog.reject-settings');
    Route::post('/dashboard/settings/blog/upload-image', [BlogController::class, 'uploadImage'])->name('dashboard.settings.blog.upload-image');

    // Notifications
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/test-trigger', [NotificationController::class, 'testTrigger'])->name('notifications.test-trigger');
    Route::get('/courses/{course:slug}/certificate', [CourseController::class, 'certificate'])->name('courses.certificate');
    Route::get('/courses/{course:slug}/certificates', [\App\Http\Controllers\CertificateController::class, 'index'])->name('courses.certificates.index');
    Route::post('/courses/{course:slug}/certificates/{certificate}/claim', [\App\Http\Controllers\CertificateController::class, 'claim'])->name('courses.certificates.claim');
    Route::get('/certificates/{code}', [\App\Http\Controllers\CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/modules/{module}/certificate/download', [\App\Http\Controllers\CertificateController::class, 'downloadSessionCertificate'])->name('modules.certificate.download');
    Route::post('/course-builder/courses/{course}/certificates', [\App\Http\Controllers\CertificateController::class, 'store'])->name('course-builder.certificates.store');
    Route::delete('/course-builder/certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'destroy'])->name('course-builder.certificates.destroy');

    // QnA & Discussions
    Route::get('/discussions/lesson/{lesson}', [DiscussionController::class, 'getForMaterial'])->name('discussions.lesson');
    Route::post('/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
    Route::post('/discussions/{discussion}/resolve', [DiscussionController::class, 'toggleResolved'])->name('discussions.resolve');
    Route::get('/dashboard/qna', [DiscussionController::class, 'instructorInbox'])->name('dashboard.qna');
    
    // E-Commerce & Engagement: Wishlist
    Route::post('/wishlist/toggle', [\App\Http\Controllers\WishlistController::class, 'toggle'])->name('wishlist.toggle');
    
    // E-Commerce & Engagement: Review
    Route::post('/courses/{slug}/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');

    // Admin/Instructor Course Gifting Routes
    Route::get('/dashboard/students/search', [\App\Http\Controllers\CourseGiftController::class, 'searchStudents'])->name('dashboard.students.search');
    Route::post('/dashboard/courses/{course}/gift', [\App\Http\Controllers\CourseGiftController::class, 'gift'])->name('dashboard.courses.gift');

    // Workshop Assessment Activities & Analytics (Admin/Instructor/Student)
    Route::get('/dashboard/reports/exam', [\App\Http\Controllers\ExamReportController::class, 'index'])->name('dashboard.reports.exam');
    Route::get('/dashboard/courses/{course}/assessment-analytics', [\App\Http\Controllers\WorkshopAssessmentController::class, 'analytics'])->name('assessments.analytics');
    Route::get('/courses/{course:slug}/assessments/{assessment}', [\App\Http\Controllers\WorkshopAssessmentController::class, 'showStudentAssessment'])->name('assessments.show-student');
    Route::post('/assessments/{assessment}/start', [\App\Http\Controllers\WorkshopAssessmentController::class, 'startAttempt'])->name('assessments.start');
    Route::post('/attempts/{attempt}/submit', [\App\Http\Controllers\WorkshopAssessmentController::class, 'submitAttempt'])->name('attempts.submit');
    Route::get('/courses/{course}/live-link', [\App\Http\Controllers\WorkshopAssessmentController::class, 'getLiveMeetingLink'])->name('courses.live-link');
    Route::get('/courses/{course}/live-meeting-link', [\App\Http\Controllers\WorkshopAssessmentController::class, 'getLiveMeetingLink'])->name('courses.live-meeting-link');
    
    // Live Class secure endpoint & hybrid management
    Route::get('/live-class/{course}', [\App\Http\Controllers\LiveClassController::class, 'show'])->name('live-class.show');
    Route::post('/live-classes', [\App\Http\Controllers\LiveClassController::class, 'store'])->name('live-classes.store');
    Route::put('/live-classes/{liveClass}', [\App\Http\Controllers\LiveClassController::class, 'update'])->name('live-classes.update');
    Route::delete('/live-classes/{liveClass}', [\App\Http\Controllers\LiveClassController::class, 'destroy'])->name('live-classes.destroy');
    Route::post('/live-classes/{liveClass}/attendance', [\App\Http\Controllers\LiveClassController::class, 'selectAttendance'])->middleware(['throttle:10,1'])->name('live-classes.select-attendance');

    // Web Push Subscriptions
    Route::get('/push-subscriptions/key', [\App\Http\Controllers\PushSubscriptionController::class, 'key'])->name('push.key');
    Route::post('/push-subscriptions', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.store');
    Route::delete('/push-subscriptions', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.destroy');
});

// Shopping Cart Public Routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Midtrans webhook notifications (CSRF-exempted in bootstrap/app.php)
Route::post('/payment/notification', [PaymentController::class, 'notification'])->name('payment.notification');

// Gemini AI Chat Proxy Route (Secure server-side proxy to hide Gemini API key)
Route::post('/api/gemini/chat', [\App\Http\Controllers\GeminiChatController::class, 'chat'])->name('api.gemini.chat');

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/live-classes', [CourseController::class, 'index'])->name('live-classes.index');
Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');

// Public Blog Routes
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('blogs.show');

require __DIR__.'/auth.php';

if (app()->environment('local', 'testing')) {
    require __DIR__.'/local_dev.php';
}
