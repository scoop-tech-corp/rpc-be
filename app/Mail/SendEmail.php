<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;


    private $data = [];
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        // return $this->from('value@example.org', 'RPC Petshop')
        //     ->subject($this->data['subject'])
        //     ->view('emails.email')
        //     ->with('data', $this->data);
        
        return $this->from('value@example.org', 'RPC Petshop')
            ->subject($this->data['subject'])
            ->view('emails.email', [
                'data' => $this->data,
            ]);
    }
}
