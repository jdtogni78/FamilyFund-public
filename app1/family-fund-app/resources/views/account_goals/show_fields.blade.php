@php
    $account = $accountGoal->account;
    $goal = $accountGoal->goal;
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
                    <span class="text-body-secondary">ID: {{ $accountGoal->account_id }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Goal Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-bullseye me-1"></i> Goal:</label>
            <p class="mb-0">
                @if($goal)
                    @include('partials.view_link', ['route' => route('goals.show', $goal->id), 'text' => $goal->name, 'class' => 'fw-bold'])
                    @if($goal->target_amount)
                        <br><small class="text-body-secondary">
                            Target: ${{ number_format($goal->target_amount, 2) }}
                        </small>
                    @endif
                @else
                    <span class="text-body-secondary">ID: {{ $accountGoal->goal_id }}</span>
                @endif
            </p>
        </div>

        <!-- Account Goal ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Account Goal ID:</label>
            <p class="mb-0">#{{ $accountGoal->id }}</p>
        </div>
    </div>
</div>
