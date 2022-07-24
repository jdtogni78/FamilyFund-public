<li class="nav-item {{ Request::is('funds*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('funds.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Funds</span>
    </a>
</li>
<li class="nav-item {{ Request::is('accountBalances*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('accountBalances.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Account Balances</span>
    </a>
</li>
<li class="nav-item {{ Request::is('accountMatchingRules*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('accountMatchingRules.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Account Matching Rules</span>
    </a>
</li>
<li class="nav-item {{ Request::is('accounts*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('accounts.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Accounts</span>
    </a>
</li>
<li class="nav-item {{ Request::is('assetPrices*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('assetPrices.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Asset Prices</span>
    </a>
</li>
<li class="nav-item {{ Request::is('assets*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('assets.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Assets</span>
    </a>
</li>
<li class="nav-item {{ Request::is('matchingRules*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('matchingRules.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Matching Rules</span>
    </a>
</li>
<li class="nav-item {{ Request::is('portfolioAssets*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('portfolioAssets.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Portfolio Assets</span>
    </a>
</li>
<li class="nav-item {{ Request::is('portfolios*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('portfolios.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Portfolios</span>
    </a>
</li>
<li class="nav-item {{ Request::is('transactions*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('transactions.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Transactions</span>
    </a>
</li>
<li class="nav-item {{ Request::is('users*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('users.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Users</span>
    </a>
</li>
<li class="nav-item {{ Request::is('assetChangeLogs*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('assetChangeLogs.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Asset Change Logs</span>
    </a>
</li>
<li class="nav-item {{ Request::is('transactionMatchings*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('transactionMatchings.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Transaction Matchings</span>
    </a>
</li>
<li class="nav-item {{ Request::is('fundReports*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('fundReports.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Fund Reports</span>
    </a>
</li>
<li class="nav-item {{ Request::is('accountReports*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('accountReports.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Account Reports</span>
    </a>
</li>
<li class="nav-item {{ Request::is('changeLogs*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('changeLogs.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Change Logs</span>
    </a>
</li>
<li class="nav-item {{ Request::is('tradePortfolios*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('tradePortfolios.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Trade Portfolios</span>
    </a>
</li>
<li class="nav-item {{ Request::is('tradePortfolioItems*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('tradePortfolioItems.index') }}">
        <i class="nav-icon icon-cursor"></i>
        <span>Trade Portfolio Items</span>
    </a>
</li>
