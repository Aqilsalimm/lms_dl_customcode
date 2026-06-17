<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Setting;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Disable Foreign Key Constraints for safe truncation
        Schema::disableForeignKeyConstraints();

        // 2. Clean slate: Truncate test users and transactional data
        User::truncate();
        DB::table('enrollments')->truncate();
        DB::table('subscriptions')->truncate();
        DB::table('invoices')->truncate();
        DB::table('student_learning_logs')->truncate();
        DB::table('orders')->truncate();
        DB::table('withdrawals')->truncate();
        DB::table('instructor_profiles')->truncate();
        DB::table('instructor_payment_profiles')->truncate();
        DB::table('discussions')->truncate();
        
        // Optional: clear sessions and password reset tokens for privacy/security
        DB::table('sessions')->truncate();
        DB::table('password_reset_tokens')->truncate();
        
        // Try to truncate personal_access_tokens if it exists
        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->truncate();
        }

        // 3. Create Default Production Superadmin account
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@drasthabest.com',
            'password' => Hash::make('ColofulS!i31220p'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // 4. Bind system's active license to the new domain "drasthalearning.com"
        // Key Format: DRSTHA-MASTER-LIFETIME-[MD5_OF_DOMAIN_WITH_SALT]
        // MD5 of 'drasthalearning.com-drastha-secure-salt-2026' is '6700D4BB1128C623EB484D70C7D8D17B'
        $domain = 'drasthalearning.com';
        $salt = 'drastha-secure-salt-2026';
        $licenseSignature = strtoupper(md5($domain . '-' . $salt));
        $productionLicenseKey = 'DRSTHA-MASTER-LIFETIME-' . $licenseSignature;

        Setting::updateOrCreate(
            ['key' => 'license_key'],
            ['value' => $productionLicenseKey]
        );

        // Re-enable Foreign Key Constraints
        Schema::enableForeignKeyConstraints();
    }
}
