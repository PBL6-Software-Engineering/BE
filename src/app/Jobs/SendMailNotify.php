<?php

namespace App\Jobs;

use App\Mail\MailNotify;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMailNotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;

    public $message;

    public function __construct($email, $message)
    {
        $this->email = $email;
        $this->message = $message;
    }

    public function handle()
    {
        $email = $this->email;
        $message = $this->message;

        // Các bước xử lý logic liên quan đến email và token
        Mail::to($email)->send(new MailNotify($message));
        info("Email sent to $email with URL: $message");
        Log::info("Email sent to $email with URL: $message");
    }
}
