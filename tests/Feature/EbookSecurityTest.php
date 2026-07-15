<?php

namespace Tests\Feature;

use App\Models\Ebook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EbookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Set up local storage fake/disk for test files
        Storage::fake('local');
    }

    /**
     * Test that guest users are redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $ebook = Ebook::create([
            'title' => 'Test Ebook',
            'slug' => 'test-ebook',
            'author' => 'Test Author',
            'price' => 10000.00,
            'description' => 'Test description',
            'path_files' => 'ebooks/test.pdf',
        ]);

        $this->get(route('ebooks.view', $ebook->slug))
            ->assertRedirect();

        $this->get(route('ebooks.download', $ebook->slug))
            ->assertRedirect();
    }

    /**
     * Test that logged-in users without purchase or ownership get 403 Forbidden.
     */
    public function test_unauthorized_user_cannot_access(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $ebook = Ebook::create([
            'title' => 'Test Ebook',
            'slug' => 'test-ebook',
            'author' => 'Test Author',
            'price' => 10000.00,
            'description' => 'Test description',
            'path_files' => 'ebooks/test.pdf',
        ]);

        $this->actingAs($user);

        $this->get(route('ebooks.view', $ebook->slug))
            ->assertStatus(403);

        $this->get(route('ebooks.download', $ebook->slug))
            ->assertStatus(403);
    }

    /**
     * Test that a purchaser can view and download the local file.
     */
    public function test_purchaser_can_access_local_file(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $ebook = Ebook::create([
            'title' => 'Test Ebook',
            'slug' => 'test-ebook',
            'author' => 'Test Author',
            'price' => 10000.00,
            'description' => 'Test description',
            'path_files' => 'ebooks/test.pdf',
        ]);

        // Grant purchase access
        $user->purchasedEbooks()->attach($ebook->id);

        // Put fake file on the disk
        Storage::disk('local')->put('ebooks/test.pdf', 'PDF content here');

        $this->actingAs($user);

        // Test view (inline response)
        $responseView = $this->get(route('ebooks.view', $ebook->slug));
        $responseView->assertStatus(200);
        $responseView->assertHeader('Content-Type', 'application/pdf');

        // Test download
        $responseDownload = $this->get(route('ebooks.download', $ebook->slug));
        $responseDownload->assertStatus(200);
        $responseDownload->assertHeader('Content-Disposition', 'attachment; filename=test-ebook.pdf');
    }

    /**
     * Test that a purchaser is redirected for external URL files.
     */
    public function test_purchaser_is_redirected_to_external_url(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $ebook = Ebook::create([
            'title' => 'Test Ebook',
            'slug' => 'test-ebook',
            'author' => 'Test Author',
            'price' => 10000.00,
            'description' => 'Test description',
            'url_files' => 'https://drive.google.com/file/d/test-id/view',
        ]);

        // Grant purchase access
        $user->purchasedEbooks()->attach($ebook->id);

        $this->actingAs($user);

        $this->get(route('ebooks.view', $ebook->slug))
            ->assertRedirect('https://drive.google.com/file/d/test-id/view');

        $this->get(route('ebooks.download', $ebook->slug))
            ->assertRedirect('https://drive.google.com/file/d/test-id/view');
    }

    /**
     * Test that the author of the ebook can access it without purchasing.
     */
    public function test_author_can_access_without_purchase(): void
    {
        $author = User::factory()->create(['role' => 'instructor']);
        $ebook = Ebook::create([
            'title' => 'Test Ebook',
            'slug' => 'test-ebook',
            'author' => 'Test Author',
            'user_id' => $author->id,
            'price' => 10000.00,
            'description' => 'Test description',
            'url_files' => 'https://drive.google.com/file/d/test-id/view',
        ]);

        $this->actingAs($author);

        $this->get(route('ebooks.view', $ebook->slug))
            ->assertRedirect('https://drive.google.com/file/d/test-id/view');
    }

    /**
     * Test that the admin can access all ebooks without purchasing.
     */
    public function test_admin_can_access_all(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ebook = Ebook::create([
            'title' => 'Test Ebook',
            'slug' => 'test-ebook',
            'author' => 'Test Author',
            'price' => 10000.00,
            'description' => 'Test description',
            'url_files' => 'https://drive.google.com/file/d/test-id/view',
        ]);

        $this->actingAs($admin);

        $this->get(route('ebooks.view', $ebook->slug))
            ->assertRedirect('https://drive.google.com/file/d/test-id/view');
    }
}
