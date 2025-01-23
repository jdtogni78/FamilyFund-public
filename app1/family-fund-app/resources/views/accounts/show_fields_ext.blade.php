@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row">
<div class="form-group col-sm-6">
    {!! Form::label('nickname', 'Nickname:') !!}
    {!! Form::text('nickname', $account->nickname, $field_props) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('fund', 'Fund:') !!}
    <div class="input-group">
        {!! Form::text('fund', $account->fund->name, $field_props) !!}
        <a href="{{ route('funds.show', [$account->fund->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
    </div>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('user', 'User:') !!}
    {!! Form::text('user', $account->user->name, $field_props) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('email_cc', 'Email CC:') !!}
    {!! Form::text('email_cc', $account->email_cc, $field_props) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('shares', 'Shares:') !!}
    <div class="input-group">
        {!! Form::number('shares', $account->balances['OWN']->shares,  $field_props) !!}
    </div>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('market_value', 'Market Value:') !!}
    <div class="input-group">
        <div class="input-group-text">$</div>
        {!! Form::number('market_value', $account->balances['OWN']->market_value,  $field_props) !!}
    </div>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('matching_available', 'Matching Available:') !!}
    <div class="input-group">
        <div class="input-group-text">$</div>
        {!! Form::number('matching_available', $api['matching_available'],  $field_props) !!}
    </div>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('as_of', 'As Of:') !!}
    {!! Form::text('as_of', $api['as_of'], $field_props) !!}
</div>
</div>
