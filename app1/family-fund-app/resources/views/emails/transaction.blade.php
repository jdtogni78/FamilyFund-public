@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    @if(null !== $api['transaction'])
        @php($transaction = $api['transaction'])
        @php($balance = $transaction->balance)
        @php($isDebit = $transaction->value < 0)

        <!-- Header Card -->
        <table width="100%" cellpadding="0" cellspacing="0" style="border-left: 4px solid {{ $isDebit ? '#dc3545' : '#28a745' }}; margin-bottom: 16px;">
            <tr>
                <td style="padding: 16px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="color: #333; font-size: 24px; font-weight: bold;">Transaction Confirmation</td>
                            <td style="text-align: right;">
                                <span style="background-color: {{ $isDebit ? '#dc3545' : '#28a745' }}; color: white; padding: 8px 16px; font-size: 14px; border-radius: 4px; display: inline-block;">
                                    {{ $isDebit ? 'Withdrawal' : 'Deposit' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <p style="color: #666; margin: 12px 0 0 0;">Dear {{ $api['to'] }},</p>
                </td>
            </tr>
        </table>

        <!-- Transaction Details Card -->
        <div class="card mb-3">
            <div class="card-header" style="background-color: #f8f9fa; padding: 12px 16px;">
                <strong>Transaction Details</strong>
            </div>
            <div class="card-body" style="padding: 16px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Account</td>
                        <td style="padding: 8px 0; text-align: right; font-weight: bold;">{{ $transaction->account->nickname }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Date</td>
                        <td style="padding: 8px 0; text-align: right;">{{ $transaction->timestamp->format('F j, Y') }}</td>
                    </tr>
                    @if($transaction->descr)
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Memo</td>
                        <td style="padding: 8px 0; text-align: right;">{{ $transaction->descr }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Share Balance Card -->
        @if($balance)
        <div class="card mb-3">
            <div class="card-header" style="background-color: #f8f9fa; padding: 12px 16px;">
                <strong>Changes</strong>
            </div>
            <div class="card-body" style="padding: 16px;">
                @php($delta = $balance->shares - ($balance->previousBalance?->shares ?? 0))
                @php($prevShares = $balance->previousBalance?->shares ?? 0)
                @php($shareValue = $api['shareValue'])
                @php($prevValue = $prevShares * $shareValue)
                @php($matchingSharesTotal = isset($api['matches']) ? collect($api['matches'])->sum('shares') : 0)
                @php($afterShares = $balance->shares + $matchingSharesTotal)
                @php($newValue = $afterShares * $shareValue)
                @php($valueDelta = $newValue - $prevValue)

                <div style="text-align: center; padding: 16px 0;">
                    <!-- Before/After Flow -->
                    <div style="display: inline-block; text-align: center; padding: 0 24px;">
                        <div style="color: #999; font-size: 12px; text-transform: uppercase;">Before</div>
                        <div style="font-size: 24px; color: #999;">${{ number_format($prevValue, 2) }}</div>
                        <div style="font-size: 14px; color: #999;">{{ number_format($prevShares, 4) }} shares</div>
                    </div>
                    <span style="font-size: 24px; color: {{ $delta >= 0 ? '#28a745' : '#dc3545' }};">&#8594;</span>
                    <div style="display: inline-block; text-align: center; padding: 0 24px;">
                        <div style="color: #999; font-size: 12px; text-transform: uppercase;">After</div>
                        <div style="font-size: 24px; font-weight: bold; color: {{ $delta >= 0 ? '#28a745' : '#dc3545' }};">${{ number_format($newValue, 2) }}</div>
                        <div style="font-size: 14px; font-weight: bold; color: {{ $delta >= 0 ? '#28a745' : '#dc3545' }};">{{ number_format($afterShares, 4) }} shares</div>
                    </div>

                    <!-- Delta Badges -->
                    @php($hasMatching = isset($api['matches']) && count($api['matches']) > 0)
                    @php($matchingShares = $hasMatching ? collect($api['matches'])->sum('shares') : 0)
                    @php($matchingValue = $hasMatching ? collect($api['matches'])->sum('value') : 0)
                    @php($depositValue = $transaction->value)
                    <div style="margin-top: 12px;">
                        @if($hasMatching)
                        <!-- Dollar badges (big, on top) -->
                        <span style="background-color: #28a745; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold; margin-right: 16px;">
                            +${{ number_format($depositValue, 2) }}
                        </span>
                        <span style="background-color: #9333ea; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold;">
                            +${{ number_format($matchingValue, 2) }}
                        </span>
                        <!-- Shares badges (smaller, below) -->
                        <div style="margin-top: 10px;">
                            <span style="background-color: #28a745; color: white; padding: 4px 14px; border-radius: 16px; font-size: 13px; margin-right: 16px;">
                                +{{ number_format($delta, 4) }} shares
                            </span>
                            <span style="background-color: #9333ea; color: white; padding: 4px 14px; border-radius: 16px; font-size: 13px;">
                                +{{ number_format($matchingShares, 4) }} shares
                            </span>
                        </div>
                        @else
                        <span style="background-color: {{ $valueDelta >= 0 ? '#28a745' : '#dc3545' }}; color: white; padding: 8px 20px; border-radius: 20px; font-size: 16px; font-weight: bold; margin-right: 8px;">
                            {{ $valueDelta >= 0 ? '+' : '-' }}${{ number_format(abs($valueDelta), 2) }}
                        </span>
                        <div style="margin-top: 10px;">
                            <span style="background-color: {{ $delta >= 0 ? '#28a745' : '#dc3545' }}; color: white; padding: 4px 14px; border-radius: 16px; font-size: 13px;">
                                {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 4) }} shares
                            </span>
                        </div>
                        @endif
                    </div>

                    <!-- Share Price -->
                    <div style="margin-top: 12px; color: #666; font-size: 13px;">
                        @ ${{ number_format($shareValue, 2) }}/share
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- Pending Transaction Notice -->
        <div class="card mb-3">
            <div class="card-header" style="background-color: #fef3c7; padding: 12px 16px; border: 1px solid #f59e0b;">
                <strong style="color: #92400e;">Pending Transaction</strong>
            </div>
            <div class="card-body" style="padding: 16px; background-color: #fffbeb;">
                <p style="color: #92400e; margin: 0;">
                    This transaction is scheduled and will be processed on the execution date.
                    Balance changes will be reflected once the transaction is cleared.
                </p>
                <div style="margin-top: 12px; text-align: center;">
                    <span style="background-color: {{ $transaction->value >= 0 ? '#28a745' : '#dc3545' }}; color: white; padding: 6px 16px; border-radius: 20px; font-size: 14px;">
                        {{ $transaction->value >= 0 ? '+' : '' }}${{ number_format($transaction->value, 2) }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        <!-- Current Account Value Card -->
        @isset($api['shares_today'])
        <div class="card mb-3">
            <div class="card-header" style="background-color: #f8f9fa; padding: 12px 16px;">
                <strong>Current Account Value</strong>
                <span style="color: #999; font-size: 12px; margin-left: 8px;">as of {{ $api['today']->format('M j, Y') }}</span>
            </div>
            <div class="card-body" style="padding: 16px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Total Shares</td>
                        <td style="padding: 8px 0; text-align: right;">{{ number_format($api['shares_today'], 4) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #666;">Share Value</td>
                        <td style="padding: 8px 0; text-align: right;">${{ number_format($api['share_value_today'], 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #eee;">
                        <td style="padding: 12px 0; font-weight: bold;">Total Value</td>
                        <td style="padding: 12px 0; text-align: right; font-weight: bold; font-size: 20px; color: #333;">
                            ${{ number_format($api['value_today'], 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        @endisset

        <!-- Matching Contributions -->
        @if(isset($api['matches']) && count($api['matches']) > 0)
        <div class="card mb-3" style="border-left: 4px solid #9333ea;">
            <div class="card-header" style="background-color: #9333ea; color: white; padding: 12px 16px;">
                <strong>Matching Contributions</strong>
            </div>
            <div class="card-body" style="padding: 16px;">
                @foreach($api['matches'] as $matchTrans)
                <div style="{{ !$loop->last ? 'border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px;' : '' }}">
                    <div style="font-weight: bold;">{{ $matchTrans->descr }}</div>
                    <div style="color: #9333ea; font-size: 18px;">+${{ number_format($matchTrans->value, 2) }}</div>
                </div>
                @endforeach

            </div>
        </div>
        @endif

        <!-- Available Matching Section -->
        @if(isset($api['availableMatching']) && count($api['availableMatching']) > 0)
        <div class="card mb-3" style="border-left: 4px solid #9333ea;">
            <div class="card-header" style="background-color: #f8f9fa; padding: 12px 16px;">
                <strong style="color: #9333ea;">Available Matching</strong>
            </div>
            <div class="card-body" style="padding: 16px;">
                <div style="color: #666; font-size: 13px; margin-bottom: 12px;">
                    Matching still available for future deposits:
                </div>
                @foreach($api['availableMatching'] as $available)
                @php($rule = $available['rule'])
                @php($remaining = $available['remaining'] ?? 0)
                @php($total = $rule->dollar_range_end - $rule->dollar_range_start)
                <div style="padding: 6px 0; {{ !$loop->last ? 'border-bottom: 1px solid #eee;' : '' }}">
                    <span style="background-color: #9333ea; color: white; padding: 6px 14px; border-radius: 16px; font-size: 14px;">
                        ${{ number_format($remaining, 0) }} of ${{ number_format($total, 0) }} at {{ number_format($rule->match_percent, 0) }}%
                    </span>
                    <span style="color: #666; font-size: 13px; margin-left: 12px;">
                        Expires {{ $rule->date_end->format('M j, Y') }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div style="text-align: center; color: #999; font-size: 12px; padding: 24px 0;">
            This is an automated confirmation from Family Fund.
        </div>

    @else
        <div style="padding: 24px; background-color: #fff3cd; border-radius: 4px; color: #856404;">
            No transaction data available.
        </div>
    @endif

</div>
@endsection
