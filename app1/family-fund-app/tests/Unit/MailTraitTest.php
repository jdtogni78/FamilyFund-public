<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\MailTrait;
use App\Mail\TransactionEmail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Unit tests for MailTrait
 */
class MailTraitTest extends TestCase
{
    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traitObject = new class {
            use MailTrait;
        };
    }

    public function test_send_mail_sends_to_single_address()
    {
        Mail::fake();

        // Create a simple mailable
        $mailable = new class extends \Illuminate\Mail\Mailable {
            public function build()
            {
                return $this->view('emails.transaction')
                    ->with('api', ['test' => 'data']);
            }
        };

        $result = $this->traitObject->sendMail($mailable, 'test@example.com');

        $this->assertNull($result);
        Mail::assertSent(get_class($mailable));
    }

    public function test_send_mail_sends_to_multiple_addresses()
    {
        Mail::fake();

        $mailable = new class extends \Illuminate\Mail\Mailable {
            public function build()
            {
                return $this->view('emails.transaction')
                    ->with('api', ['test' => 'data']);
            }
        };

        $result = $this->traitObject->sendMail($mailable, 'test1@example.com,test2@example.com');

        $this->assertNull($result);
        Mail::assertSent(get_class($mailable));
    }

    public function test_send_mail_with_cc()
    {
        Mail::fake();

        $mailable = new class extends \Illuminate\Mail\Mailable {
            public function build()
            {
                return $this->view('emails.transaction')
                    ->with('api', ['test' => 'data']);
            }
        };

        $result = $this->traitObject->sendMail($mailable, 'test@example.com', 'cc@example.com');

        $this->assertNull($result);
        Mail::assertSent(get_class($mailable));
    }

    public function test_send_mail_with_empty_cc_does_not_add_cc()
    {
        Mail::fake();

        $mailable = new class extends \Illuminate\Mail\Mailable {
            public function build()
            {
                return $this->view('emails.transaction')
                    ->with('api', ['test' => 'data']);
            }
        };

        $result = $this->traitObject->sendMail($mailable, 'test@example.com', '');

        $this->assertNull($result);
        Mail::assertSent(get_class($mailable));
    }
}
