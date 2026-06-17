<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- STARTING QA TEST: REVENUE SHARING SYSTEM ---\n";

// 1. Setup Data
$instructor = User::where('role', 'instructor')->first();
if (!$instructor) {
    // Create dummy instructor if not exists
    $instructor = User::create([
        'name' => 'QA Tester Instructor',
        'email' => 'qa_instructor_' . time() . '@test.com',
        'password' => bcrypt('password'),
        'role' => 'instructor',
        'balance' => 0
    ]);
}

$course = Course::where('instructor_id', $instructor->id)->first();
if (!$course) {
    $course = Course::create([
        'instructor_id' => $instructor->id,
        'title' => 'QA Test Course',
        'price' => 100000,
        'status' => 'published',
        'category_id' => 1 // Assuming category 1 exists
    ]);
}

$initialBalance = (float) $instructor->balance;
$orderAmount = (float) $course->price;
$sharePercentage = (float) (Setting::where('key', 'sharing_percentage_instructor')->value('value') ?? 70);
$expectedShare = ($orderAmount * $sharePercentage) / 100;

echo "Instructor: {$instructor->name} (ID: {$instructor->id})\n";
echo "Initial Balance: " . number_format($initialBalance, 2) . "\n";
echo "Course Price: " . number_format($orderAmount, 2) . "\n";
echo "Share Percentage: {$sharePercentage}%\n";
echo "Expected Increase: " . number_format($expectedShare, 2) . "\n";

// 2. Perform Transaction (Simulate Payment Completion)
echo "\n[ACTION] Simulating Order Completion...\n";

$order = Order::create([
    'user_id' => 1, // Any student ID
    'buyable_type' => Course::class,
    'buyable_id' => $course->id,
    'amount' => $orderAmount,
    'status' => 'pending',
    'payment_type' => 'test'
]);

// Trigger the logic by calling the controller method mock logic or directly updating status 
// Since I added the logic in PaymentController, I'll simulate the update like the controller does.
// Note: In my previous edit, I added allocateRevenueShare call in PaymentController.
// For the test, I will call the logic exactly as it's implemented.

$paymentController = app()->make(\App\Http\Controllers\PaymentController::class);
$reflection = new \ReflectionClass($paymentController);
$method = $reflection->getMethod('allocateRevenueShare');
$method->setAccessible(true);
$method->invoke($paymentController, $order);

// 3. Verify Results
$instructor->refresh();
$finalBalance = (float) $instructor->balance;
$actualIncrease = $finalBalance - $initialBalance;

echo "\n--- TEST RESULTS ---\n";
echo "Final Balance: " . number_format($finalBalance, 2) . "\n";
echo "Actual Increase: " . number_format($actualIncrease, 2) . "\n";

if (abs($actualIncrease - $expectedShare) < 0.01) {
    echo "[SUCCESS] Revenue sharing allocated correctly!\n";
} else {
    echo "[FAILED] Revenue sharing mismatch. Actual: {$actualIncrease}, Expected: {$expectedShare}\n";
}

// 4. Cleanup
$order->delete();
echo "\n--- TEST COMPLETED ---\n";
