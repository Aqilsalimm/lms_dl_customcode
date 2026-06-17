<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WithdrawalRequestController extends Controller
{
    // Instructor Request View
    public function index()
    {
        $user = auth()->user();
        $profile = $user->paymentProfile()->with('method')->first();
        $withdrawals = Withdrawal::where('user_id', $user->id)->latest()->get();
        $methods = \App\Models\WithdrawalMethod::where('is_active', true)->get();

        return Inertia::render('Dashboard/Instructor/Withdrawals', [
            'balance' => $user->balance ?? 0, // Assuming a balance column exists, or calculation
            'profile' => $profile,
            'withdrawals' => $withdrawals,
            'methods' => $methods,
        ]);
    }

    // Instructor submits request
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = auth()->user();

        if (!$user->paymentProfile) {
            return back()->with('error', 'Please set up a payment profile first.');
        }

        try {
            DB::transaction(function () use ($user, $request) {
                // Ensure balance check within transaction locking
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                if ($lockedUser->balance < $request->amount) {
                    throw new \Exception('Insufficient balance.');
                }

                // Deduct balance
                $lockedUser->balance -= $request->amount;
                $lockedUser->save();

                // Create withdrawal record
                Withdrawal::create([
                    'user_id' => $user->id,
                    'amount' => $request->amount,
                    'status' => 'pending',
                ]);
            });

            return back()->with('success', 'Withdrawal requested successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Admin Payout View
    public function adminIndex()
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        $withdrawals = Withdrawal::with(['user.paymentProfile.method'])->latest()->get();
        return Inertia::render('Dashboard/Admin/Withdrawals', [
            'withdrawals' => $withdrawals,
        ]);
    }

    // Admin Approves Request
    public function complete(Request $request, Withdrawal $withdrawal)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        $request->validate([
            'receipt' => 'nullable|image|max:2048',
            'admin_note' => 'nullable|string'
        ]);

        $path = $withdrawal->receipt_path;
        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts', 'public');
            \App\Services\ImageOptimizer::optimize(
                storage_path('app/public/' . $path),
                $request->file('receipt')->getMimeType()
            );
        }

        $withdrawal->update([
            'status' => 'completed',
            'admin_note' => $request->admin_note,
            'receipt_path' => $path,
        ]);

        return back()->with('success', 'Withdrawal marked as completed.');
    }
    
    // Admin Rejects Request
    public function reject(Request $request, Withdrawal $withdrawal)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Unauthorized access.');

        $request->validate([
            'admin_note' => 'required|string'
        ]);

        DB::transaction(function () use ($withdrawal, $request) {
            $withdrawal->update([
                'status' => 'rejected',
                'admin_note' => $request->admin_note,
            ]);

            // Refund balance
            $lockedUser = User::where('id', $withdrawal->user_id)->lockForUpdate()->first();
            $lockedUser->balance += $withdrawal->amount;
            $lockedUser->save();
        });

        return back()->with('success', 'Withdrawal rejected and refunded.');
    }
}
