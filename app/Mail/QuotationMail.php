<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quotation;
    public $pdfContent;

    /**
     * @param object $quotation  — row dari query printQuotation (sudah include customerName, dll)
     * @param string $pdfContent — raw PDF bytes dari Pdf::loadView()->output()
     */
    public function __construct($quotation, string $pdfContent)
    {
        $this->quotation   = $quotation;
        $this->pdfContent  = $pdfContent;
    }

    public function build(): static
    {
        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Penawaran Harga - ' . $this->quotation->quotationNo . ' | Radhiyan Pet & Care')
            ->view('emails.quotation_email')
            ->with([
                'quotation' => $this->quotation,
            ])
            ->attachData(
                $this->pdfContent,
                $this->quotation->quotationNo . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
