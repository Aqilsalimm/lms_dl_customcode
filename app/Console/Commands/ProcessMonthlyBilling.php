<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProcessMonthlyBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-monthly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly subscription billing engine: invoices, reminders, and suspensions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly billing engine...');

        $this->taskAGenerateInvoices();
        $this->taskBSendReminders();
        $this->taskCExecuteSuspension();

        $this->info('Monthly billing engine completed.');
    }

    /**
     * Task A: Generate Invoice (H-3 before next_billing_date)
     */
    private function taskAGenerateInvoices()
    {
        $targetDate = Carbon::today()->addDays(3);

        $subscriptions = Subscription::with(['course', 'user'])
            ->where('status', 'active')
            ->whereDate('next_billing_date', $targetDate)
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                // Prevent duplicate invoice generation
                if ($subscription->invoices()->where('status', 'unpaid')->exists()) {
                    continue;
                }

                $amount = $subscription->course->price;
                $orderId = 'INV-' . $subscription->id . '-' . time() . '-' . Str::random(5);

                $invoice = Invoice::create([
                    'subscription_id' => $subscription->id,
                    'invoice_number' => $orderId,
                    'amount' => $amount,
                    'due_date' => $subscription->next_billing_date,
                    'status' => 'unpaid',
                ]);

                // Call the existing Midtrans service to generate a Snap Token
                $params = [
                    'transaction_details' => [
                        'order_id' => $orderId,
                        'gross_amount' => $amount,
                    ],
                    'customer_details' => [
                        'first_name' => $subscription->user->name,
                        'email' => $subscription->user->email,
                    ]
                ];

                // \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
                // \Midtrans\Config::$isProduction = config('app.env') === 'production';
                // $snapToken = \Midtrans\Snap::getSnapToken($params);
                $snapToken = 'snap-token-' . Str::random(10); // Placeholder

                $invoice->update(['midtrans_snap_token' => $snapToken]);

                // Update the subscription's next_billing_date for the following month
                $subscription->update([
                    'next_billing_date' => Carbon::parse($subscription->next_billing_date)->addMonth()
                ]);

                // Dispatch InvoiceCreatedMail to the user via Brevo SMTP
                // Mail::to($subscription->user->email)->send(new InvoiceCreatedMail($invoice));

                $this->info("Generated invoice {$orderId} for subscription {$subscription->id}");
            } catch (\Exception $e) {
                $this->error("Task A Error for Subscription {$subscription->id}: " . $e->getMessage());
                // Continue to the next subscription even if this one fails
            }
        }
    }

    /**
     * Task B: Send Reminder (H-1 before due_date)
     */
    private function taskBSendReminders()
    {
        $targetDate = Carbon::today()->addDay();

        $invoices = Invoice::with('subscription.user')
            ->where('status', 'unpaid')
            ->whereDate('due_date', $targetDate)
            ->get();

        foreach ($invoices as $invoice) {
            try {
                // Dispatch PaymentReminderMail to the user
                // Mail::to($invoice->subscription->user->email)->send(new PaymentReminderMail($invoice));
                
                $this->info("Sent reminder for invoice {$invoice->invoice_number}");
            } catch (\Exception $e) {
                $this->error("Task B Error for Invoice {$invoice->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Task C: Execute Suspension (H+1 after due_date)
     */
    private function taskCExecuteSuspension()
    {
        $targetDate = Carbon::today()->subDay();

        $invoices = Invoice::with('subscription.user')
            ->where('status', 'unpaid')
            ->whereDate('due_date', $targetDate)
            ->get();

        foreach ($invoices as $invoice) {
            try {
                $invoice->update(['status' => 'expired']);

                $subscription = $invoice->subscription;
                if ($subscription && $subscription->status === 'active') {
                    $subscription->update(['status' => 'suspended']);

                    // Dispatch PaymentOverdueMail informing them of the suspension
                    // Mail::to($subscription->user->email)->send(new PaymentOverdueMail($invoice));

                    $this->info("Suspended subscription {$subscription->id} due to expired invoice {$invoice->invoice_number}");
                }
            } catch (\Exception $e) {
                $this->error("Task C Error for Invoice {$invoice->id}: " . $e->getMessage());
            }
        }
    }
}
