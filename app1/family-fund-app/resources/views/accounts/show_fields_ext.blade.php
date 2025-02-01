@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row">
<div class="form-group col-sm-6">
<label for="nickname">Nickname:</label>
<input type="text" name="nickname" value="{{ $account->nickname }}" >
</div>
<div class="form-group col-sm-6">
<label for="fund">Fund:</label>
    <div class="input-group">
<input type="text" name="fund" value="{{ $account->fund->name }}" >
        <a href="{{ route('funds.show', [$account->fund->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
    </div>
</div>
<div class="form-group col-sm-6">
<label for="user">User:</label>
<input type="text" name="user" value="{{ $account->user->name }}" >
</div>
<div class="form-group col-sm-6">
<label for="email_cc">Email CC:</label>
<input type="text" name="email_cc" value="{{ $account->email_cc }}" >
</div>
<div class="form-group col-sm-6">
<label for="shares">Shares:</label>
    <div class="input-group">
<input type="number" name="shares" value="{{ $account->balances['OWN']->shares }}" >
    </div>
</div>
<div class="form-group col-sm-6">
<label for="market_value">Market Value:</label>
    <div class="input-group">
        <div class="input-group-text">$</div>
<input type="number" name="market_value" value="{{ $account->balances['OWN']->market_value }}" >
    </div>
</div>
<div class="form-group col-sm-6">
<label for="matching_available">Matching Available:</label>
    <div class="input-group">
        <div class="input-group-text">$</div>
<input type="number" name="matching_available" value="{{ $api['matching_available'] }}" >
    </div>
</div>
<div class="form-group col-sm-6">
<label for="as_of">As Of:</label>
<input type="text" name="as_of" value="{{ $api['as_of'] }}" >
</div>
</div>
