<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WithdrawalMethod;

class WithdrawalMethodSeeder extends Seeder
{
    public function run()
    {
        $methods = [
            ['type' => 'bank', 'name' => 'BCA', 'is_active' => true],
            ['type' => 'bank', 'name' => 'BNI', 'is_active' => true],
            ['type' => 'bank', 'name' => 'BRI', 'is_active' => true],
            ['type' => 'bank', 'name' => 'Mandiri', 'is_active' => true],
            ['type' => 'bank', 'name' => 'BTN', 'is_active' => true],
            ['type' => 'ewallet', 'name' => 'ShopeePay', 'is_active' => true],
            ['type' => 'ewallet', 'name' => 'GoPay', 'is_active' => true],
            ['type' => 'ewallet', 'name' => 'OVO', 'is_active' => true],
            ['type' => 'ewallet', 'name' => 'DANA', 'is_active' => true],
        ];

        foreach ($methods as $m) {
            WithdrawalMethod::firstOrCreate(['name' => $m['name']], $m);
        }
    }
}
