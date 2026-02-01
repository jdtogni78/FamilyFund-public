<?php

namespace App\Listeners;

use App\Services\EmailLogService;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class LogSentEmail
{
    public function __construct(
        protected EmailLogService $emailLogService
    ) {}

    public function handle(MessageSent $event): void
    {
        try {
            $filepath = $this->emailLogService->log($event->message);
            Log::info('Email logged', ['file' => $filepath]);
        } catch (\Throwable $e) {
            Log::error('Failed to log email', [
                'error' => $e->getMessage(),
                'subject' => $event->message->getSubject(),
            ]);
        }
    }
}
