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
        // 1. Create Default Production Superadmin account if it does not already exist
        if (!User::where('email', 'admin@drasthabest.com')->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => 'admin@drasthabest.com',
                'password' => Hash::make('ColofulS!i31220p'),
                'role' => 'admin',
                'status' => 'active',
            ]);
        }

        // 2. Bind system's active license to the new domain "drasthalearning.com"
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
    }
}
