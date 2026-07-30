<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Setting;
use App\Models\Category;

class ProductionSeeder extends Seeder
{
    /**
     * Run the production-exclusive database seeds.
     */
    public function run(): void
    {
        // 1. Seed Production Superadmin account if it does not already exist
        if (!User::where('email', 'admin@drasthabest.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@drasthabest.com',
                'password' => Hash::make('ColofulS!i31220p'),
                'role' => 'admin',
                'status' => 'active',
            ]);
        }

        // 2. Bind system's active production license to "drasthalearning.com"
        $domain = 'drasthalearning.com';
        $salt = 'drastha-secure-salt-2026';
        $licenseSignature = strtoupper(md5($domain . '-' . $salt));
        $productionLicenseKey = 'DRSTHA-MASTER-LIFETIME-' . $licenseSignature;

        Setting::updateOrCreate(
            ['key' => 'license_key'],
            ['value' => $productionLicenseKey]
        );

        // 3. Seed Default System Settings for Production
        $defaultSettings = [
            'course_visibility' => 'false',
            'courses_per_page' => '12',
            'spotlight_mode' => 'true',
            'course_content_access' => 'false',
            'test_builder_enforce_prerequisites' => 'true',
            'test_builder_default_duration' => '30',
            'test_builder_pre_passing_score' => '70',
            'test_builder_post_passing_score' => '70',
            'test_builder_default_max_attempts' => '0', // Unlimited attempts for production flexibility
        ];

        foreach ($defaultSettings as $key => $val) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $val]
            );
        }

        // 4. Seed Standard Categories
        $categories = [
            ['name' => 'IT & Software', 'slug' => 'it-software', 'description' => 'Kelas pemrograman, web dev, Python, dll.'],
            ['name' => 'Finance & Accounting', 'slug' => 'finance-accounting', 'description' => 'Kelas akuntansi, finansial, dan audit.'],
            ['name' => 'Sains & Matematika', 'slug' => 'sains-matematika', 'description' => 'Kelas untuk SD, SMP, SMA.'],
            ['name' => 'Umum', 'slug' => 'umum', 'description' => 'Seminar, sertifikasi umum, dll.']
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 5. Seed Instructor Withdrawal Payout Methods
        $this->call(WithdrawalMethodSeeder::class);
    }
}
