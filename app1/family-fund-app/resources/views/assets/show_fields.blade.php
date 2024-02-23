<!-- Source Field -->
<div class="form-group">
    {!! Form::label('source', 'Source:') !!}
    <p>{{ $asset->source }}</p>
</div>

<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $asset->name }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $asset->type }}</p>
</div>

<!-- Display Group Field -->
<div class="form-group">
    {!! Form::label('display_group', 'Display Group:') !!}
    <p>{{ $asset->display_group }}</p>
</div>

