<?php
namespace App\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailAssetRequest extends Mailable{
    use Queueable, SerializesModels;

    public $data;
    public $id_default = '00000000-0000-0000-0000-000000000000';

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder AMS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'components.mails.mail-asset', // Your Blade view for the email content
            with: ['data' => $this->data], // Pass data to the view
        );
    }
}