@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row" style="min-height: 310px">
    <div class="form-group col-sm-6 col-left">
<label for="nickname">Nickname:</label>
<input type="text" name="nickname" value="{{ $account->nickname }}" >
    </div>
    <div class="form-group col-sm-6 col-right">
<label for="fund">Fund:</label>
<input type="text" name="fund" value="{{ $account->fund->name }}" >
    </div>
    <div class="form-group col-sm-6 col-left">
<label for="user">User:</label>
<input type="text" name="user" value="{{ $account->user->name }}" >
    </div>
    <div class="form-group col-sm-6 col-right">
<label for="email_cc">Email CC:</label>
<input type="text" name="email_cc" value="{{ $account->email_cc }}" >
    </div>
    @isset($api['balances'][0])
    <div class="form-group col-sm-6 col-left">
<label for="shares">Shares:</label>
<input type="number" name="shares" value="{{ $account->balances['OWN']->shares }}" >
    </div>
    <div class="form-group col-sm-6 col-right">
<label for="market_value">Market Value:</label>
        <div class="input-group">
            <div class="input-group-text a">$</div>
<input type="number" name="market_value" value="{{ $account->balances['OWN']->market_value }}" >
        </div>
    </div>
    @endisset
    <div class="form-group col-sm-6 col-left">
<label for="matching_available">Matching Available:</label>
        <div class="input-group">
            <div class="input-group-text a">$</div>
<input type="number" name="matching_available" value="{{ $api['matching_available'] }}" >
        </div>
    </div>
    <div class="form-group col-sm-6 col-left">
<label for="as_of">As Of:</label>
<input type="text" name="as_of" value="{{ $api['as_of'] }}" >
    </div>
</div>
