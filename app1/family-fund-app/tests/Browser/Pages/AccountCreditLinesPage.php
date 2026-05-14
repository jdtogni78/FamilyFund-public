<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

/**
 * Dusk page object for the per-account credit-lines index.
 *
 * Mirrors resources/views/account_credit_lines/index.blade.php.
 */
class AccountCreditLinesPage extends Page
{
    public function __construct(public int $accountId) {}

    public function url(): string
    {
        return '/accounts/' . $this->accountId . '/credit-lines';
    }

    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url())
            ->assertSee('Credit Lines');
    }

    /**
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@newButton'   => 'a.btn.btn-primary[href*="credit-lines/create"]',
            '@linesTable'  => 'table.table',
            '@emptyState'  => '.card-body p.text-muted',
        ];
    }
}
