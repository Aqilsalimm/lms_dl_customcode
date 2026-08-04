<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\InstructorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;
use App\Jobs\SendUserInvitationEmail;
use App\Models\Otp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

class UserManageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create superadmin ID 1
        $this->superadmin = User::create([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_access_user_manage_page()
    {
        $response = $this->actingAs($this->superadmin)->get(route('dashboard.users.manage'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_user_manage_page()
    {
        $student = User::create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.users.manage'));
        $response->assertStatus(403);
    }

    public function test_admin_can_approve_pending_instructor()
    {
        $pendingInstructor = User::create([
            'name' => 'Pending Inst',
            'email' => 'pending@example.com',
            'password' => bcrypt('password'),
            'role' => 'instructor',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->superadmin)->post(route('dashboard.users.approve', $pendingInstructor->id));
        $response->assertSessionHas('success');
        
        $this->assertEquals('active', $pendingInstructor->fresh()->status);
    }

    public function test_admin_can_reject_pending_instructor()
    {
        $pendingInstructor = User::create([
            'name' => 'Pending Inst 2',
            'email' => 'pending2@example.com',
            'password' => bcrypt('password'),
            'role' => 'instructor',
            'status' => 'pending',
        ]);

        InstructorProfile::create([
            'user_id' => $pendingInstructor->id,
            'expertise_area' => 'Web Dev',
            'bio_summary' => 'Bio here'
        ]);

        $response = $this->actingAs($this->superadmin)->post(route('dashboard.users.reject', $pendingInstructor->id));
        $response->assertSessionHas('success');
        
        $freshUser = $pendingInstructor->fresh();
        $this->assertEquals('active', $freshUser->status);
        $this->assertEquals('student', $freshUser->role);
        $this->assertNull($freshUser->instructorProfile);
    }

    public function test_admin_can_update_user_role()
    {
        $user = User::create([
            'name' => 'Normal Student',
            'email' => 'normal@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superadmin)->post(route('dashboard.users.role', $user->id), [
            'role' => 'instructor'
        ]);
        
        $response->assertSessionHas('success');
        $this->assertEquals('instructor', $user->fresh()->role);
        $this->assertEquals('active', $user->fresh()->status); // Changes to active automatically by logic
    }

    public function test_superadmin_role_cannot_be_changed()
    {
        $response = $this->actingAs($this->superadmin)->post(route('dashboard.users.role', $this->superadmin->id), [
            'role' => 'student'
        ]);
        
        $response->assertSessionHas('error');
        $this->assertEquals('admin', $this->superadmin->fresh()->role);
    }

    public function test_admin_can_export_users()
    {
        Excel::fake();

        $response = $this->actingAs($this->superadmin)->get(route('dashboard.users.export'));
        
        Excel::assertDownloaded('users_drastha_lms.xlsx', function(UsersExport $export) {
            return true;
        });
    }

    public function test_admin_can_create_student_and_invitation_is_queued(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->superadmin)->post(route('dashboard.users.store'), [
            'name' => 'New Student',
            'email' => 'NEW.STUDENT@example.com ',
            'role' => 'student',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'email' => 'new.student@example.com',
            'role' => 'student',
        ]);
        Queue::assertPushed(SendUserInvitationEmail::class);
    }

    public function test_non_admin_cannot_create_managed_user(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->post(route('dashboard.users.store'), [
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'role' => 'student',
        ])->assertForbidden();
    }

    public function test_admin_can_soft_delete_non_admin_with_valid_purpose_bound_otp(): void
    {
        $target = User::factory()->create(['role' => 'student']);
        Otp::create([
            'user_id' => $this->superadmin->id,
            'email' => $this->superadmin->email,
            'otp_code' => Hash::make('123456'),
            'purpose' => Otp::PURPOSE_USER_DELETE,
            'expires_at' => now()->addMinutes(10),
            'used' => false,
        ]);

        $this->actingAs($this->superadmin)
            ->delete(route('dashboard.users.destroy', $target), ['otp_code' => '123456'])
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('otps', ['user_id' => $this->superadmin->id, 'used' => true]);
    }

    public function test_admin_cannot_delete_self_or_another_admin(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->superadmin)
            ->delete(route('dashboard.users.destroy', $this->superadmin), ['otp_code' => '123456'])
            ->assertForbidden();

        $this->actingAs($this->superadmin)
            ->delete(route('dashboard.users.destroy', $otherAdmin), ['otp_code' => '123456'])
            ->assertForbidden();
    }

    public function test_admin_can_restore_deleted_non_admin(): void
    {
        $target = User::factory()->create(['role' => 'student']);
        $target->delete();

        $this->actingAs($this->superadmin)
            ->post(route('dashboard.users.restore', $target->id))
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }
}
