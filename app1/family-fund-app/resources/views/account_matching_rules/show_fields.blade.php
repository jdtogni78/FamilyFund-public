<div class="form-group">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $api['account']->id }}</p>
</div>

<div class="form-group">
    {!! Form::label('account_nickname', 'Account Nickname:') !!}
    <p>{{ $api['account']->nickname }}</p>
</div>

<div class="form-group">
    {!! Form::label('effective_from', 'Effective From:') !!}
    <p>{{ max($api['mr']->date_start, $accountMatchingRule->created_at) }}</p>
</div>


