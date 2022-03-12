<!-- Code Field -->
<div class="form-group">
    {!! Form::label('code', 'Code:') !!}
    <p>{{ $account->code }}</p>
</div>

<!-- Nickname Field -->
<div class="form-group">
    {!! Form::label('nickname', 'Nickname:') !!}
    <p>{{ $account->nickname }}</p>
</div>

<!-- Email Cc Field -->
<div class="form-group">
    {!! Form::label('email_cc', 'Email Cc:') !!}
    <p>{{ $account->email_cc }}</p>
</div>

<!-- User Id Field -->
<div class="form-group">
    {!! Form::label('user_id', 'User Id:') !!}
    <p>{{ $account->user_id }}</p>
</div>

<!-- Fund Id Field -->
<div class="form-group">
    {!! Form::label('fund_id', 'Fund Id:') !!}
    <p>{{ $account->fund_id }}</p>
</div>

