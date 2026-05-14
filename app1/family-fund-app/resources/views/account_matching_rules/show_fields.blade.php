@php
    $account = $api['account'] ?? $accountMatchingRule->account;
    $matchingRule = $api['mr'] ?? $accountMatchingRule->matchingRule;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Account Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user me-1"></i> Account:</label>
            <p class="mb-0">
                @if($account)
                    @include('partials.view_link', ['route' => route('accounts.show', $account->id), 'text' => $account->nickname, 'class' => 'fw-bold'])
                    @if($account->code)
                        <span class="text-body-secondary">({{ $account->code }})</span>
                    @endif
                    @if($account->fund)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-landmark me-1"></i>
                            @include('partials.view_link', ['route' => route('funds.show', $account->fund_id), 'text' => $account->fund->name])
                        </small>
                    @endif
                @else
                    <span class="text-body-secondary">N/A</span>
                @endif
            </p>
        </div>

        <!-- Matching Rule Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-link me-1"></i> Matching Rule:</label>
            <p class="mb-0">
                @if($matchingRule)
                    @include('partials.view_link', ['route' => route('matchingRules.show', $matchingRule->id), 'text' => $matchingRule->name, 'class' => 'fw-bold'])
                    <br><small class="text-body-secondary">
                        {{ number_format($matchingRule->match_percent * 100, 0) }}% match
                    </small>
                @else
                    <span class="text-body-secondary">N/A</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Effective From Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Effective From:</label>
            <p class="mb-0 fw-bold">
                @if($matchingRule)
                    {{ max($matchingRule->date_start, $accountMatchingRule->created_at?->format('Y-m-d')) }}
                @else
                    {{ $accountMatchingRule->created_at?->format('Y-m-d') ?? '-' }}
                @endif
            </p>
        </div>

        <!-- Account Matching Rule ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Account Matching Rule ID:</label>
            <p class="mb-0">#{{ $accountMatchingRule->id }}</p>
        </div>
    </div>
</div>
