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
use App\Models\Course;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $courses = Course::where('status', 'published')
        ->with(['category', 'instructor'])
        ->latest()
        ->get();
        
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
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/enrolled-courses', [DashboardController::class, 'enrolledCourses'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.enrolled-courses');

Route::get('/dashboard/learning-progress', [DashboardController::class, 'learningProgress'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.learning-progress');

use App\Http\Controllers\Student\QaController;

// Placeholder routes for unimplemented dashboard features
Route::get('/dashboard/reviews', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.reviews');
Route::get('/dashboard/quiz-attempts', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.quiz-attempts');
Route::get('/dashboard/wishlist', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.wishlist');
Route::get('/dashboard/order-history', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.order-history');
Route::get('/dashboard/qa', [DiscussionController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard.qa');

// Instructor placeholder routes
Route::get('/dashboard/announcements', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.announcements');
Route::get('/dashboard/withdrawals', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.withdrawals');
Route::get('/dashboard/google-meet', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.google-meet');
Route::get('/dashboard/zoom', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.zoom');

use App\Http\Controllers\Auth\OtpController;

// OTP routes
Route::post('/otp/send', [OtpController::class, 'send'])->name('otp.send');
Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');

use App\Http\Controllers\BillingController;
Route::get('/billing/suspended', [BillingController::class, 'suspended'])->middleware(['auth'])->name('billing.suspended');

    Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Role Management (Admin only)
    Route::post('/dashboard/change-role/{user}', [DashboardController::class, 'changeRole']);
    
    // LMS Settings
    Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('/dashboard/settings', [DashboardController::class, 'updateSettings'])->name('dashboard.settings.update');
    
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
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::get('/checkout', [CartController::class, 'checkoutPage'])->name('cart.checkout-page');
    Route::get('/checkout/resume/{order}', [CartController::class, 'resumeCheckout'])->name('checkout.resume');
    Route::get('/orders/{order}/invoice', [PaymentController::class, 'downloadInvoice'])->name('orders.invoice');
    Route::get('/courses/{course:slug}/learn', [CourseController::class, 'learn'])->middleware('active.subscription')->name('courses.learn');
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

    // QnA & Discussions
    Route::get('/discussions/lesson/{lesson}', [DiscussionController::class, 'getForMaterial'])->name('discussions.lesson');
    Route::post('/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
    Route::post('/discussions/{discussion}/resolve', [DiscussionController::class, 'toggleResolved'])->name('discussions.resolve');
    Route::get('/dashboard/qna', [DiscussionController::class, 'instructorInbox'])->name('dashboard.qna');
});

// Shopping Cart Public Routes
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Midtrans webhook notifications (CSRF-exempted in bootstrap/app.php)
Route::post('/payment/notification', [PaymentController::class, 'notification'])->name('payment.notification');

Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

// Public Blog Routes
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs.index');
Route::get('/blogs/{slug}', [BlogController::class, 'show'])->name('blogs.show');

require __DIR__.'/auth.php';
