<!-- Name Field -->
<div class="form-group">
    <p>{!! Form::label('name', 'Name:') !!}
    {{ $fund->name }}</p>
</div>

<!-- Shares Field -->
<div class="form-group">
    <p>{!! Form::label('shares', 'Shares:') !!}
    {{ $calculated['shares'] }}</p>
</div>

<!-- Unallocated Shares Field -->
<div class="form-group">
    <p>{!! Form::label('unallocated_shares', 'Unallocated Shares:') !!}
    {{ $calculated['unallocated_shares'] }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
    <p>{!! Form::label('value', 'Value:') !!}
    {{ $calculated['value'] }}</p>
</div>

<!-- AsOf Field -->
<div class="form-group">
<p>{!! Form::label('asof', 'AsOf:') !!}
    {{ $calculated['as_of'] }}</p>
</div>

