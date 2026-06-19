<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Review;
use App\Models\Wishlist;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test student can access dashboard pages.
     */
    public function test_student_can_access_dashboard_pages()
    {
        $student = User::factory()->create(['role' => 'student']);

        // Reviews page
        $response = $this->actingAs($student)
            ->get(route('dashboard.reviews'));
        $response->assertStatus(200);

        // Wishlist page
        $response = $this->actingAs($student)
            ->get(route('dashboard.wishlist'));
        $response->assertStatus(200);

        // Order history page
        $response = $this->actingAs($student)
            ->get(route('dashboard.order-history'));
        $response->assertStatus(200);
    }

    /**
     * Test wishlist toggle functionality.
     */
    public function test_student_can_toggle_wishlist()
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Test Course',
            'instructor_id' => 1,
            'course_type' => 'regular',
            'price' => 50000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        // Toggle on (Add to wishlist)
        $response = $this->actingAs($student)
            ->post(route('wishlist.toggle'), [
                'course_id' => $course->id
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $student->id,
            'course_id' => $course->id
        ]);

        // Toggle off (Remove from wishlist)
        $response = $this->actingAs($student)
            ->post(route('wishlist.toggle'), [
                'course_id' => $course->id
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $student->id,
            'course_id' => $course->id
        ]);
    }

    /**
     * Test review deletion.
     */
    public function test_student_can_delete_review()
    {
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::create([
            'title' => 'Test Course',
            'instructor_id' => 1,
            'course_type' => 'regular',
            'price' => 50000,
            'level' => 'Umum',
            'status' => 'published',
        ]);

        $review = Review::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'rating' => 5,
            'comment' => 'Excellent course!'
        ]);

        $response = $this->actingAs($student)
            ->delete(route('reviews.destroy', $review->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id
        ]);
    }
}
