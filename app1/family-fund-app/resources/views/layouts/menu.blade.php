<li class="nav-item nav-dropdown {{ Request::is('accounts*') ? 'active' : '' }}">
    <a class="nav-link nav-dropdown-toggle" href="#">
        <i class="nav-icon fa fa-money"></i>
        <span>Funds Menu</span>
    </a>
    <ul class="nav-dropdown-items">
        <li class="nav-item {{ Request::is('funds*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('funds.index') }}">
                <i class="nav-icon fa fa-money"></i>
                <span>Funds</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('portfolios*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('portfolios.index') }}">
                <i class="nav-icon fa fa-folder"></i>
                <span>Portfolios</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('portfolioAssets*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('portfolioAssets.index') }}">
                <i class="nav-icon fa fa-list"></i>
                <span>Portfolio Assets</span>
            </a>
        </li>
    </ul>
</li>
<li class="nav-item nav-dropdown {{ Request::is('accounts*') ? 'active' : '' }}">
    <a class="nav-link nav-dropdown-toggle" href="#">
        <i class="nav-icon fa fa-bank"></i>
        <span>Accounts Menu</span>
    </a>
    <ul class="nav-dropdown-items">
        <li class="nav-item {{ Request::is('accounts*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('accounts.index') }}">
                <i class="nav-icon fa fa-bank"></i>
                <span>Accounts</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('goals*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('goals.index') }}">
                <i class="nav-icon fa fa-bullseye"></i>
                <span>Goals</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('accountGoals*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('accountGoals.index') }}">
                <i class="nav-icon fa fa-bullseye"></i>
                <span>Account Goals</span>
            </a>
        </li>   
        <li class="nav-item {{ Request::is('matchingRules*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('matchingRules.index') }}">
                <i class="nav-icon fa fa-link"></i>
                <span>Matching Rules</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('accountMatchingRules*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('accountMatchingRules.index') }}">
                <i class="nav-icon fa fa-book"></i>
                <span>Account Matching Rules</span>
            </a>
        </li>
    </ul>
</li>
<li class="nav-item nav-dropdown {{ Request::is('accounts*') ? 'active' : '' }}">
    <a class="nav-link nav-dropdown-toggle" href="#">
        <i class="nav-icon fa fa-money"></i>
        <span>Transactions Menu</span>
    </a>
    <ul class="nav-dropdown-items">
        <li class="nav-item {{ Request::is('transactions*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('transactions.index') }}">
                <i class="nav-icon fa fa-money"></i>
                <span>Transactions</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('transactionMatchings*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('transactionMatchings.index') }}">
                <i class="nav-icon fa fa-link"></i>
                <span>Matchings</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('accountBalances*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('accountBalances.index') }}">
                <i class="nav-icon fa fa-balance-scale"></i>
                <span>Balances</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('depositRequests*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('depositRequests.index') }}">
                <i class="nav-icon fa fa-download"></i>
                <span>Deposit Requests</span>
            </a>
        </li>
    </ul>
</li>
<li class="nav-item nav-dropdown {{ Request::is('tradePortfolios*') ? 'active' : '' }}">
    <a class="nav-link nav-dropdown-toggle" href="#">
        <i class="nav-icon fa fa-exchange"></i>
        <span>Trading</span>
    </a>

    <ul class="nav-dropdown-items">
        <li class="nav-item {{ Request::is('tradePortfolios*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('tradePortfolios.index') }}">
                <i class="nav-icon fa fa-exchange"></i>
                <span>Trade Portfolios</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('cashDeposits*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('cashDeposits.index') }}">
                <i class="nav-icon fa fa-download"></i>
                <span>Cash Deposits</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('tradePortfolioItems*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('tradePortfolioItems.index') }}">
                <i class="nav-icon fa fa-exchange"></i>
                <span>Trade Portfolio Items</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('assetPrices*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('assetPrices.index') }}">
                <i class="nav-icon fa fa-usd"></i>
                <span>Asset Prices</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('assets*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('assets.index') }}">
                <i class="nav-icon fa fa-line-chart"></i>
                <span>Assets</span>
            </a>
        </li>
    </ul>
</li>
<!-- <li class="nav-item {{ Request::is('assetChangeLogs*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('assetChangeLogs.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Asset Change Logs</span>
    </a>
</li> -->
<li class="nav-item nav-dropdown {{ Request::is('fundReports*') ? 'active' : '' }}">
    <a class="nav-link nav-dropdown-toggle" href="#">
        <i class="nav-icon fa fa-file-text"></i>
        <span>Reports</span>
    </a>
    <ul class="nav-dropdown-items">
        <li class="nav-item {{ Request::is('fundReports*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('fundReports.index') }}">
                <i class="nav-icon fa fa-file-text-o"></i>
                <span>Fund Reports</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('accountReports*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('accountReports.index') }}">
                <i class="nav-icon fa fa-file-text-o"></i>
                <span>Account Reports</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('tradeBandReports*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('tradeBandReports.index') }}">
                <i class="nav-icon fa fa-file-text-o"></i>
                <span>Trade Band Reports</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('schedules*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('schedules.index') }}">
                <i class="nav-icon fa fa-calendar"></i>
                <span>Schedules</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('scheduledJobs*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('scheduledJobs.index') }}">
                <i class="nav-icon fa fa-clock-o"></i>
                <span>Scheduled Jobs</span>
            </a>
        </li>
    </ul>
</li>

<!-- <li class="nav-item {{ Request::is('changeLogs*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('changeLogs.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Change Logs</span>
    </a>
</li> -->
<li class="nav-item nav-dropdown {{ Request::is('people*') ? 'active' : '' }}">
    <a class="nav-link nav-dropdown-toggle" href="#">
        <i class="nav-icon fa fa-users"></i>
        <span>People & Users</span>
    </a>
    <ul class="nav-dropdown-items">
        <li class="nav-item {{ Request::is('people*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('people.index') }}">
                <i class="nav-icon fa fa-users"></i>
                <span>People</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('users*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('users.index') }}">
                <i class="nav-icon fa fa-user"></i>
                <span>Users</span>
            </a>
        </li>
        <!-- <li class="nav-item {{ Request::is('addresses*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('addresses.index') }}">
                <i class="nav-icon fa fa-map-marker"></i>
                <span>Addresses</span>
            </a>
        </li> -->
        <!-- <li class="nav-item {{ Request::is('id_documents*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('id_documents.index') }}">
                <i class="nav-icon fa fa-id-card"></i>
                <span>Id Documents</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('phones*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('phones.index') }}">
                <i class="nav-icon fa fa-phone"></i>
                <span>Phones</span>
            </a>
        </li> -->
    </ul>
</li>

@if(auth()->check() && auth()->user()->isAdmin())
<li class="nav-item {{ Request::is('operations*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('operations.index') }}">
        <i class="nav-icon fa fa-cogs"></i>
        <span>Operations</span>
    </a>
</li>
@endif
