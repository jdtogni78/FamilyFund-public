<div class="detail-list">
    <div class="detail-item">
        <span class="detail-label">Account Name</span>
        <span class="detail-value">{{ $account->nickname }}</span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Fund</span>
        <span class="detail-value">{{ $account->fund->name }}</span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Account Holder</span>
        <span class="detail-value">{{ $account->user->name }}</span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Email</span>
        <span class="detail-value">{{ $account->email_cc }}</span>
    </div>
    @isset($api['balances'][0])
        <div class="detail-item">
            <span class="detail-label">Shares</span>
            <span class="detail-value">{{ number_format($account->balances['OWN']->shares ?? 0, 2) }}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Market Value</span>
            <span class="detail-value font-bold text-primary">${{ number_format($account->balances['OWN']->market_value ?? 0, 2) }}</span>
        </div>
    @endisset
    @if($api['matching_available'] > 0)
        <div class="detail-item">
            <span class="detail-label">Matching Available</span>
            <span class="detail-value text-success">${{ number_format($api['matching_available'], 2) }}</span>
        </div>
    @endif
    <div class="detail-item">
        <span class="detail-label">Report Date</span>
        <span class="detail-value">{{ $api['as_of'] }}</span>
    </div>
</div>
