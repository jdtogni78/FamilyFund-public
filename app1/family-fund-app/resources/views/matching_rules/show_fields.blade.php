<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $matchingRule->name }}</p>
</div>

<!-- Dollar Range Start Field -->
<div class="form-group">
    {!! Form::label('dollar_range_start', 'Dollar Range Start:') !!}
    <p>{{ $matchingRule->dollar_range_start }}</p>
</div>

<!-- Dollar Range End Field -->
<div class="form-group">
    {!! Form::label('dollar_range_end', 'Dollar Range End:') !!}
    <p>{{ $matchingRule->dollar_range_end }}</p>
</div>

<!-- Date Start Field -->
<div class="form-group">
    {!! Form::label('date_start', 'Date Start:') !!}
    <p>{{ $matchingRule->date_start }}</p>
</div>

<!-- Date End Field -->
<div class="form-group">
    {!! Form::label('date_end', 'Date End:') !!}
    <p>{{ $matchingRule->date_end }}</p>
</div>

<!-- Match Percent Field -->
<div class="form-group">
    {!! Form::label('match_percent', 'Match Percent:') !!}
    <p>{{ $matchingRule->match_percent }}</p>
</div>

