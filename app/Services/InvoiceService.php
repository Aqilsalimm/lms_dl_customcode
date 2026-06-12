<?php

namespace App\Services;

use App\Models\Order;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Generate Invoice PDF content
     */
    public static function generatePdf(Order $order): string
    {
        // Load relationships if not already loaded
        $order->loadMissing(['user', 'buyable', 'coupon']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'user' => $order->user,
            'buyable' => $order->buyable,
            'coupon' => $order->coupon,
        ]);

        // Set paper format
        $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Generate Invoice PDF and send to the student
     */
    public static function generateAndSend(Order $order): void
    {
        $pdfContent = self::generatePdf($order);

        Mail::to($order->user->email)->send(new InvoiceMail($order, $pdfContent));
    }
}
