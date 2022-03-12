<!-- Action Field -->
<div class="form-group">
    {!! Form::label('action', 'Action:') !!}
    <p>{{ $assetChangeLog->action }}</p>
</div>

<!-- Asset Id Field -->
<div class="form-group">
    {!! Form::label('asset_id', 'Asset Id:') !!}
    <p>{{ $assetChangeLog->asset_id }}</p>
</div>

<!-- Field Field -->
<div class="form-group">
    {!! Form::label('field', 'Field:') !!}
    <p>{{ $assetChangeLog->field }}</p>
</div>

<!-- Content Field -->
<div class="form-group">
    {!! Form::label('content', 'Content:') !!}
    <p>{{ $assetChangeLog->content }}</p>
</div>

<!-- Datetime Field -->
<div class="form-group">
    {!! Form::label('datetime', 'Datetime:') !!}
    <p>{{ $assetChangeLog->datetime }}</p>
</div>

