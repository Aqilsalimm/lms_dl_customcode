<?php

namespace Tests\Feature\Payment;

use App\Models\Order;
use App\Models\User;
use App\Models\Course;
use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockPaymentGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\LicenseMiddleware::class]);
    }

    public function test_it_completes_pending_mock_payment_for_owner()
    {
        $user = User::factory()->create();
        $course = Course::forceCreate([
            'title' => 'Test', 'slug' => 'test-' . uniqid(), 'price' => 100, 'instructor_id' => $user->id,
        ]);
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'pending',
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 100,
        ]);

        $response = $this->actingAs($user)->postJson("/payment/mock-complete/{$order->id}");
        
        $response->assertStatus(200);
        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_it_is_idempotent_and_does_not_increment_coupon_twice()
    {
        $user = User::factory()->create();
        $course = Course::forceCreate([
            'title' => 'Test', 'slug' => 'test-' . uniqid(), 'price' => 100, 'instructor_id' => $user->id,
        ]);
        $coupon = Coupon::forceCreate([
            'code' => 'DISC-' . uniqid(), 'type' => 'percentage', 'value' => 10, 'uses' => 0, 'max_uses' => 10,
        ]);
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'pending',
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 100,
            'coupon_id' => $coupon->id,
        ]);

        // First call
        $this->actingAs($user)->postJson("/payment/mock-complete/{$order->id}")->assertStatus(200);
        $this->assertEquals(1, $coupon->fresh()->uses);
        $this->assertEquals('completed', $order->fresh()->status);

        // Second call
        $this->actingAs($user)->postJson("/payment/mock-complete/{$order->id}")->assertStatus(200);
        $this->assertEquals(1, $coupon->fresh()->uses); // should not be 2
    }

    public function test_it_does_not_mutate_failed_orders()
    {
        $user = User::factory()->create();
        $course = Course::forceCreate([
            'title' => 'Test', 'slug' => 'test-' . uniqid(), 'price' => 100, 'instructor_id' => $user->id,
        ]);
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'failed',
            'buyable_type' => Course::class,
            'buyable_id' => $course->id,
            'amount' => 100,
        ]);

        $this->actingAs($user)->postJson("/payment/mock-complete/{$order->id}")->assertStatus(200);
        $this->assertEquals('failed', $order->fresh()->status);
    }

    public function test_it_aborts_in_production_environment()
    {
        $user = User::factory()->create();
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'pending',
            'amount' => 100,
            'buyable_type' => Course::class,
            'buyable_id' => 1,
        ]);

        app()->detectEnvironment(fn() => 'production');
        $this->withoutMiddleware();

        $this->actingAs($user)->postJson("/payment/mock-complete/{$order->id}")->assertStatus(403);
    }

    public function test_it_aborts_for_non_owner()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::forceCreate([
            'user_id' => $user->id,
            'status' => 'pending',
            'amount' => 100,
            'buyable_type' => Course::class,
            'buyable_id' => 1,
        ]);

        $response = $this->actingAs($otherUser)->postJson("/payment/mock-complete/{$order->id}");
        $response->assertStatus(403);
    }
}
