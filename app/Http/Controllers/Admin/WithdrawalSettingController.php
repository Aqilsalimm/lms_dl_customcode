<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithdrawalSettingController extends Controller
{
    public function index()
    {
        $methods = WithdrawalMethod::all();
        return Inertia::render('Dashboard/Admin/WithdrawalSettings', [
            'methods' => $methods
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:bank,ewallet',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        WithdrawalMethod::create($request->all());

        return back()->with('success', 'Withdrawal method added.');
    }

    public function update(Request $request, WithdrawalMethod $method)
    {
        $request->validate([
            'type' => 'required|in:bank,ewallet',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $method->update($request->all());

        return back()->with('success', 'Withdrawal method updated.');
    }

    public function destroy(WithdrawalMethod $method)
    {
        $method->delete();
        return back()->with('success', 'Withdrawal method deleted.');
    }
}
