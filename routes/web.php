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

// Placeholder routes for unimplemented dashboard features
Route::get('/dashboard/reviews', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.reviews');
Route::get('/dashboard/quiz-attempts', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.quiz-attempts');
Route::get('/dashboard/wishlist', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.wishlist');
Route::get('/dashboard/order-history', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.order-history');
Route::get('/dashboard/qa', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.qa');

// Instructor placeholder routes
Route::get('/dashboard/announcements', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.announcements');
Route::get('/dashboard/withdrawals', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.withdrawals');
Route::get('/dashboard/google-meet', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.google-meet');
Route::get('/dashboard/zoom', [DashboardController::class, 'placeholder'])->middleware(['auth', 'verified'])->name('dashboard.zoom');

// Auth routes group
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Role Management (Admin only)
    Route::post('/dashboard/change-role/{user}', [DashboardController::class, 'changeRole']);
    
    // LMS Settings
    Route::get('/dashboard/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');
    Route::post('/dashboard/settings', [DashboardController::class, 'updateSettings'])->name('dashboard.settings.update');

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
    Route::get('/courses/{course:slug}/learn', [CourseController::class, 'learn'])->name('courses.learn');
    Route::post('/courses/{course:slug}/lessons/{lesson}/toggle-complete', [CourseController::class, 'toggleLessonComplete'])->name('courses.lessons.complete');
    Route::post('/dashboard/blogs', [BlogController::class, 'store'])->name('blogs.store');
    Route::delete('/dashboard/blogs/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');

    // Notifications
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/test-trigger', [NotificationController::class, 'testTrigger'])->name('notifications.test-trigger');
    Route::get('/courses/{course:slug}/certificate', [CourseController::class, 'certificate'])->name('courses.certificate');
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
