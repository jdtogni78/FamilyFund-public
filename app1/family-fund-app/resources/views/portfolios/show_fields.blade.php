<!-- Fund Id Field -->
<div class="form-group">
    {!! Form::label('fund_id', 'Fund Id:') !!}
    <p>{{ $portfolio->fund->name }}</p>
</div>

<!-- Source Field -->
<div class="form-group">
    {!! Form::label('source', 'Source:') !!}
    <p>{{ $portfolio->source }}</p>
</div>
