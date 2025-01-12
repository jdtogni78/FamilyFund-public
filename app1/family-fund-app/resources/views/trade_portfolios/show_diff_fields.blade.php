<!-- Start Date Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('start_dt', 'Start Date:') !!}
    <p id="show_start_dt">{{ ($api['old']['start_dt'] ?? $api['old']['start_dt'])->format('Y-m-d') }}
        @isset($api['new'])
            -> <span class="text-success">{{ ($api['new']['start_dt'] ?? $api['new']['start_dt'])->format('Y-m-d') }}</span>
        @endisset
    </p>
</div>

<!-- create end date field -->
<div class="form-group  col-sm-6">
    {!! Form::label('end_dt', 'End Date:') !!}
    <p id="show_end_dt">{{ ($api['old']['end_dt'] ?? $api['old']['end_dt'])->format('Y-m-d') }}
        @isset($api['new'])
            -> <span class="text-success">{{ ($api['new']['end_dt'] ?? $api['new']['end_dt'])->format('Y-m-d') }}</span>
        @endisset
    </p>
</div>
