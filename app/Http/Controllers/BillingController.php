<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Invoice;

class BillingController extends Controller
{
    public function suspended(Request $request)
    {
        $invoiceId = $request->query('invoice_id');

        if (!$invoiceId) {
            return redirect()->route('dashboard')->with('error', 'Invoice not found.');
        }

        $invoice = Invoice::findOrFail($invoiceId);

        // Security check
        if ($invoice->subscription->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        return Inertia::render('BillingSuspended', [
            'invoice' => collect($invoice)->only(['id', 'invoice_number', 'amount', 'due_date', 'midtrans_snap_token'])
        ]);
    }
}
