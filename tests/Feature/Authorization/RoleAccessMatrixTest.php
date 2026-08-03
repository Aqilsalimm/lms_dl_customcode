<?php

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\Withdrawal;
use App\Models\Certificate;
use Illuminate\Support\Str;

class RoleAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'active', 'email_verified_at' => now()]);
        $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@test.com', 'password' => bcrypt('password'), 'role' => 'instructor', 'status' => 'active', 'email_verified_at' => now()]);
        $this->otherInstructor = User::create(['name' => 'Other', 'email' => 'other@test.com', 'password' => bcrypt('password'), 'role' => 'instructor', 'status' => 'active', 'email_verified_at' => now()]);
        $this->student = User::create(['name' => 'Student', 'email' => 'student@test.com', 'password' => bcrypt('password'), 'role' => 'student', 'status' => 'active', 'email_verified_at' => now()]);
    }

    public function test_course_builder_update_access_matrix()
    {
        $course = Course::create(['title' => 'Test', 'slug' => 'test', 'instructor_id' => $this->owner->id, 'price' => 0, 'status' => 'draft']);

        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->student)->allows('update', $course));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->otherInstructor)->allows('update', $course));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->owner)->allows('update', $course));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->admin)->allows('update', $course));
    }

    public function test_live_class_update_access_matrix()
    {
        $course = Course::create(['title' => 'Test2', 'slug' => 'test-2', 'instructor_id' => $this->owner->id, 'price' => 0, 'status' => 'draft']);
        
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->student)->allows('update', $course));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->otherInstructor)->allows('update', $course));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->owner)->allows('update', $course));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->admin)->allows('update', $course));
    }

    public function test_user_management_access_matrix()
    {
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->student)->allows('viewAny', User::class));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->otherInstructor)->allows('viewAny', User::class));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->owner)->allows('viewAny', User::class));
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($this->admin)->allows('viewAny', User::class));
    }
}
