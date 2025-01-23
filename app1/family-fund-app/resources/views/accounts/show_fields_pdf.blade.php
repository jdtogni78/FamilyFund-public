@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row" style="min-height: 310px">
    <div class="form-group col-sm-6 col-left">
        {!! Form::label('nickname', 'Nickname:') !!}
        {!! Form::text('nickname', $account->nickname, $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-right">
        {!! Form::label('fund', 'Fund:') !!}
        {!! Form::text('fund', $account->fund->name, $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-left">
        {!! Form::label('user', 'User:') !!}
        {!! Form::text('user', $account->user->name, $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-right">
        {!! Form::label('email_cc', 'Email CC:') !!}
        {!! Form::text('email_cc', $account->email_cc, $field_props) !!}
    </div>
    @isset($api['balances'][0])
    <div class="form-group col-sm-6 col-left">
        {!! Form::label('shares', 'Shares:') !!}
        {!! Form::number('shares', $account->balances['OWN']->shares,  $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-right">
        {!! Form::label('market_value', 'Market Value:') !!}
        <div class="input-group">
            <div class="input-group-text a">$</div>
            {!! Form::number('market_value', $account->balances['OWN']->market_value,  $field_props) !!}
        </div>
    </div>
    @endisset
    <div class="form-group col-sm-6 col-left">
        {!! Form::label('matching_available', 'Matching Available:') !!}
        <div class="input-group">
            <div class="input-group-text a">$</div>
            {!! Form::number('matching_available', $api['matching_available'],  $field_props) !!}
        </div>
    </div>
    <div class="form-group col-sm-6 col-left">
        {!! Form::label('as_of', 'As Of:') !!}
        {!! Form::text('as_of', $api['as_of'], $field_props) !!}
    </div>
</div>
