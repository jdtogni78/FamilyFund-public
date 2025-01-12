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
        if (null !== $cc && strlen($cc) > 0) {
            $emailsCC = explode(",", $cc);
            $mailto->cc($emailsCC);
            Log::info("Adding cc " . $emailsCC);
        }
        Log::info("Sending email to " . json_encode($emails));
        $msg = "Email to " . json_encode($emails);
        Log::info($msg);
        $mailto->send($data);

        if (Mail::failures()) {
            Log::error($msg . " failed");
            return $msg . " failed";
        }
        return null;
    }
}
