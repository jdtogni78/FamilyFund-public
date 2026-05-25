@php
    $menu = [
        // Items tagged 'manage' => true require FULL fund access (fund-admin /
        // financial-manager / system-admin) — every such page is fund.full-gated
        // and 403s for a readonly beneficiary, so they're hidden from that role.
        // Untagged items are beneficiary-safe (scoped read pages).
        'Funds Menu' => [
            'icon' => 'fa fa-money',
            'fundScoped' => true,
            'items' => [
                'Funds' => ['route' => 'funds.index', 'icon' => 'fa fa-money'],
                'Portfolios' => ['route' => 'portfolios.index', 'icon' => 'fa fa-folder', 'manage' => true],
                'Portfolio Assets' => ['route' => 'portfolioAssets.index', 'icon' => 'fa fa-list', 'manage' => true],
            ],
        ],
        'Accounts Menu' => [
            'icon' => 'fa fa-bank',
            'fundScoped' => true,
            'items' => [
                'Accounts' => ['route' => 'accounts.index', 'icon' => 'fa fa-bank'],
                'Goals' => ['route' => 'goals.index', 'icon' => 'fa fa-bullseye', 'manage' => true],
                'Account Goals' => ['route' => 'accountGoals.index', 'icon' => 'fa fa-bullseye', 'manage' => true],
                'Matching Rules' => ['route' => 'matchingRules.index', 'icon' => 'fa fa-link', 'manage' => true],
                'Account Matching Rules' => ['route' => 'accountMatchingRules.index', 'icon' => 'fa fa-book', 'manage' => true],
            ],
        ],
        'Transactions Menu' => [
            'icon' => 'fa fa-money',
            'fundScoped' => true,
            'items' => [
                'Transactions' => ['route' => 'transactions.index', 'icon' => 'fa fa-money'],
                'Transaction Matchings' => ['route' => 'transactionMatchings.index', 'icon' => 'fa fa-link', 'manage' => true],
                'Account Balances' => ['route' => 'accountBalances.index', 'icon' => 'fa fa-balance-scale', 'manage' => true],
                'Deposit Requests' => ['route' => 'depositRequests.index', 'icon' => 'fa fa-download', 'manage' => true],
            ],
        ],
        'Loan Shares' => [
            'icon' => 'fa fa-credit-card',
            'fundScoped' => true,
            'items' => [
                'All Loan Shares' => ['route' => 'credit_lines.global_index', 'icon' => 'fa fa-credit-card', 'manage' => true],
                'Payments' => ['route' => 'credit_lines.global_payments', 'icon' => 'fa fa-calendar-check-o', 'manage' => true],
                'Match Resolution' => ['route' => 'credit_lines.resolve_index', 'icon' => 'fa fa-link', 'manage' => true],
            ],
        ],
        'Trading Menu' => [
            'icon' => 'fa fa-exchange',
            'fundScoped' => true,
            'items' => [
                'Trade Portfolios' => ['route' => 'tradePortfolios.index', 'icon' => 'fa fa-exchange', 'manage' => true],
                'Cash Deposits' => ['route' => 'cashDeposits.index', 'icon' => 'fa fa-download', 'manage' => true],
                'Trade Portfolio Items' => ['route' => 'tradePortfolioItems.index', 'icon' => 'fa fa-exchange', 'manage' => true],
                'Asset Prices' => ['route' => 'assetPrices.index', 'icon' => 'fa fa-usd', 'manage' => true],
                'Assets' => ['route' => 'assets.index', 'icon' => 'fa fa-line-chart', 'manage' => true],
            ],
        ],
        'Reports' => [
            'icon' => 'fa fa-file-text',
            'fundScoped' => true,
            'items' => [
                'Fund Reports' => ['route' => 'fundReports.index', 'icon' => 'fa fa-file-text-o', 'manage' => true],
                'Account Reports' => ['route' => 'accountReports.index', 'icon' => 'fa fa-file-text-o', 'manage' => true],
                'Trade Band Reports' => ['route' => 'tradeBandReports.index', 'icon' => 'fa fa-file-text-o', 'manage' => true],
                'Schedules' => ['route' => 'schedules.index', 'icon' => 'fa fa-calendar', 'manage' => true],
                'Scheduled Jobs' => ['route' => 'scheduledJobs.index', 'icon' => 'fa fa-clock-o', 'manage' => true],
            ],
        ],
        'Admin' => [
            'icon' => 'fa fa-shield-alt',
            'items' => [
                'People' => ['route' => 'people.index', 'icon' => 'fa fa-users'],
                'Users' => ['route' => 'users.index', 'icon' => 'fa fa-user'],
                'Operations' => ['route' => 'operations.index', 'icon' => 'fa fa-cogs'],
                'Exchange Holidays' => ['route' => 'exchange-holidays.index', 'icon' => 'fa fa-calendar-times'],
                'Email' => ['route' => 'emails.index', 'icon' => 'fa fa-envelope'],
                'User Roles' => ['route' => 'admin.user-roles.index', 'icon' => 'fa fa-user-shield'],
            ],
            'adminOnly' => true,
        ],
    ];

    // Filter the menu by the viewer's access level so no link is shown that
    // would 403 on click (QA_BUGS 2026-05-19 #7 + beneficiary over-exposure):
    //  - adminOnly menus  -> system admins only
    //  - fundScoped menus -> users with any fund role (full OR readonly)
    //  - 'manage' items    -> dropped for readonly beneficiaries (full access only)
    //  - a menu left with no visible items is hidden entirely
    $user = auth()->user();
    $isSystemAdmin = $user && method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
    $fundAccess = $user && method_exists($user, 'getAccessibleFundIds')
        ? $user->getAccessibleFundIds()
        : ['full' => [], 'readonly' => []];
    $canManage = $isSystemAdmin || !empty($fundAccess['full']);
    $canFundUi = $canManage || !empty($fundAccess['readonly']);

    $menu = array_map(function ($group) use ($canManage) {
        if (!empty($group['items'])) {
            $group['items'] = array_filter(
                $group['items'],
                fn ($it) => empty($it['manage']) || $canManage
            );
        }
        return $group;
    }, $menu);

    $menu = array_filter($menu, function ($item) use ($isSystemAdmin, $canFundUi) {
        if (!empty($item['adminOnly']) && !$isSystemAdmin) {
            return false;
        }
        if (!empty($item['fundScoped']) && !$canFundUi) {
            return false;
        }
        // Hide a fund-scoped menu whose items were all manage-only (e.g. a
        // beneficiary's Loan Shares / Trading / Reports menus end up empty).
        if (isset($item['items']) && empty($item['items'])) {
            return false;
        }
        return true;
    });

    View::share('menu', $menu);
@endphp
