<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

Trait MailTrait
{

    public function sendMail($data, $to, $cc = null)
    {
        $emails = explode(",", $to);
        $mailto = Mail::to($emails);
        if (null === $cc) {
            $emailsCC = explode(",", $cc);
            $mailto->cc($emailsCC);
        }
        $mailto->send($data);

        if (Mail::failures()) {
            $msg = "Email to " . $to . ", " . $emails . " failed";
            Log::error($msg);
            return $msg;
        }
        return null;
    }
}
