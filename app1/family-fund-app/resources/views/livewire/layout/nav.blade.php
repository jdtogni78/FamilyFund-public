@php
    $menu = [
        'Funds Menu' => [
            'icon' => 'fa fa-money',
            'items' => [
                'Funds' => ['route' => 'funds.index', 'icon' => 'fa fa-money'],
                'Portfolios' => ['route' => 'portfolios.index', 'icon' => 'fa fa-folder'],
                'Portfolio Assets' => ['route' => 'portfolioAssets.index', 'icon' => 'fa fa-list'],
            ],
        ],
        'Accounts Menu' => [
            'icon' => 'fa fa-bank',
            'items' => [
                'Accounts' => ['route' => 'accounts.index', 'icon' => 'fa fa-bank'],
                'Goals' => ['route' => 'goals.index', 'icon' => 'fa fa-bullseye'],
                'Account Goals' => ['route' => 'accountGoals.index', 'icon' => 'fa fa-bullseye'],
                'Matching Rules' => ['route' => 'matchingRules.index', 'icon' => 'fa fa-link'],
                'Account Matching Rules' => ['route' => 'accountMatchingRules.index', 'icon' => 'fa fa-book'],
            ],
        ],
        'Transactions Menu' => [
            'icon' => 'fa fa-money',
            'items' => [
                'Transactions' => ['route' => 'transactions.index', 'icon' => 'fa fa-money'],
                'Transaction Matchings' => ['route' => 'transactionMatchings.index', 'icon' => 'fa fa-link'],
                'Account Balances' => ['route' => 'accountBalances.index', 'icon' => 'fa fa-balance-scale'],
                'Deposit Requests' => ['route' => 'depositRequests.index', 'icon' => 'fa fa-download'],
            ],
        ],
        'Trading Menu' => [
            'icon' => 'fa fa-exchange',
            'items' => [
                'Trade Portfolios' => ['route' => 'tradePortfolios.index', 'icon' => 'fa fa-exchange'],
                'Cash Deposits' => ['route' => 'cashDeposits.index', 'icon' => 'fa fa-download'],
                'Trade Portfolio Items' => ['route' => 'tradePortfolioItems.index', 'icon' => 'fa fa-exchange'],
                'Asset Prices' => ['route' => 'assetPrices.index', 'icon' => 'fa fa-usd'],
                'Assets' => ['route' => 'assets.index', 'icon' => 'fa fa-line-chart'],
            ],
        ],
        'Reports' => [
            'icon' => 'fa fa-file-text',
            'items' => [
                'Fund Reports' => ['route' => 'fundReports.index', 'icon' => 'fa fa-file-text-o'],
                'Account Reports' => ['route' => 'accountReports.index', 'icon' => 'fa fa-file-text-o'],
                'Trade Band Reports' => ['route' => 'tradeBandReports.index', 'icon' => 'fa fa-file-text-o'],
                'Schedules' => ['route' => 'schedules.index', 'icon' => 'fa fa-calendar'],
                'Scheduled Jobs' => ['route' => 'scheduledJobs.index', 'icon' => 'fa fa-clock-o'],
            ],
        ],
        'Admin' => [
            'icon' => 'fa fa-shield-alt',
            'items' => [
                'People' => ['route' => 'people.index', 'icon' => 'fa fa-users'],
                'Users' => ['route' => 'users.index', 'icon' => 'fa fa-user'],
                'Operations' => ['route' => 'operations.index', 'icon' => 'fa fa-cogs'],
                'Email' => ['route' => 'emails.index', 'icon' => 'fa fa-envelope'],
                'User Roles' => ['route' => 'admin.user-roles.index', 'icon' => 'fa fa-user-shield'],
            ],
            'adminOnly' => true,
        ],
    ];

    // Filter out admin-only menus for non-admins
    $user = auth()->user();
    $isSystemAdmin = $user && method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
    if (!$isSystemAdmin) {
        $menu = array_filter($menu, fn($item) => empty($item['adminOnly']));
    }

    View::share('menu', $menu);
@endphp
