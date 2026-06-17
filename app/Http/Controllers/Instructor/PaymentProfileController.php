<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorPaymentProfile;
use Illuminate\Http\Request;

class PaymentProfileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'withdrawal_method_id' => 'required|exists:withdrawal_methods,id',
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
        ]);

        $profile = InstructorPaymentProfile::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'withdrawal_method_id' => $request->withdrawal_method_id,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
            ]
        );

        return back()->with('success', 'Payment profile saved successfully.');
    }
}
