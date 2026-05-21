<?php

namespace Tests\Unit\Listeners;

use App\Listeners\LogSentEmail;
use App\Services\EmailLogService;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class LogSentEmailTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private function event(string $subject = 'Hello'): MessageSent
    {
        $email = (new Email())
            ->from('from@test.local')
            ->to('to@test.local')
            ->subject($subject)
            ->text('body');

        // $event->message is a magic accessor that returns
        // $event->sent->getOriginalMessage(). Wrap the Email through the
        // Symfony + Laravel SentMessage shells so the accessor resolves.
        $symfony = new \Symfony\Component\Mailer\SentMessage(
            $email,
            new \Symfony\Component\Mailer\Envelope(
                new \Symfony\Component\Mime\Address('from@test.local'),
                [new \Symfony\Component\Mime\Address('to@test.local')]
            )
        );
        $sent = new \Illuminate\Mail\SentMessage($symfony);
        return new MessageSent($sent, []);
    }

    public function test_handle_logs_via_email_log_service_on_success(): void
    {
        $svc = Mockery::mock(EmailLogService::class);
        $svc->shouldReceive('log')->once()->andReturn('/tmp/email-abc.eml');

        Log::shouldReceive('info')
            ->once()
            ->with('Email logged', Mockery::on(fn ($ctx) => $ctx['file'] === '/tmp/email-abc.eml'));

        (new LogSentEmail($svc))->handle($this->event());
    }

    public function test_handle_swallows_exception_and_logs_error(): void
    {
        $svc = Mockery::mock(EmailLogService::class);
        $svc->shouldReceive('log')->once()->andThrow(new \RuntimeException('disk full'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to log email', Mockery::on(function ($ctx) {
                return $ctx['error'] === 'disk full'
                    && $ctx['subject'] === 'Hello';
            }));

        // Must not propagate.
        (new LogSentEmail($svc))->handle($this->event('Hello'));
    }
}
