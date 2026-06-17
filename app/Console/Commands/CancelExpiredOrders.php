<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Order;

#[Signature('app:cancel-expired-orders')]
#[Description('Scan and mark pending orders older than 24 hours as failed/expired.')]
class CancelExpiredOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('No expired pending orders found.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($expiredOrders as $order) {
            $order->update(['status' => 'failed']);
            $count++;
            $this->info("Marked order #{$order->id} as failed (expired).");
        }

        $this->info("Successfully cancelled {$count} expired orders.");
        return Command::SUCCESS;
    }
}
