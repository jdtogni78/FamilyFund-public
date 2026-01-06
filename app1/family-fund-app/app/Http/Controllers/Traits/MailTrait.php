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
            Log::info("Adding cc " . json_encode($emailsCC));
        }
        Log::info("Sending email to " . json_encode($emails));
        $msg = "Email to " . json_encode($emails);

        try {
            $mailto->send($data);
            Log::info($msg . " sent");
            return null;
        } catch (\Exception $e) {
            Log::error($msg . " failed: " . $e->getMessage());
            return $msg . " failed: " . $e->getMessage();
        }
    }
}

