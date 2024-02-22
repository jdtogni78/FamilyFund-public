<!-- Start Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('start_dt', 'Start Date:') !!}
    {!! Form::date('start_dt', isset($tradePortfolio) ? $tradePortfolio->start_dt->format('Y-m-d') : new \Carbon\Carbon(), ['class' => 'form-control', 'id'=>'start_dt']) !!}
</div>

{{--@push('scripts')--}}
{{--    <script type="text/javascript">--}}
{{--        $('#start_dt').datepicker({--}}
{{--            format: 'YYYY-MM-DD',--}}
{{--            useCurrent: true,--}}
{{--            icons: {--}}
{{--                up: "icon-arrow-up-circle icons font-2xl",--}}
{{--                down: "icon-arrow-down-circle icons font-2xl"--}}
{{--            },--}}
{{--            sideBySide: true--}}
{{--        })--}}
{{--    </script>--}}
{{--@endpush--}}

<!-- End Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('end_dt', 'End Date:') !!}
    {!! Form::date('end_dt', isset($tradePortfolio) ?  $tradePortfolio->end_dt->format('Y-m-d'): '9999-12-31', ['class' => 'form-control', 'id'=>'end_dt']) !!}
</div>

{{--@push('scripts')--}}
{{--    <script type="text/javascript">--}}
{{--        $('#end_dt').datepicker({--}}
{{--            format: 'YYYY-MM-DD',--}}
{{--            useCurrent: true,--}}
{{--            icons: {--}}
{{--                up: "icon-arrow-up-circle icons font-2xl",--}}
{{--                down: "icon-arrow-down-circle icons font-2xl"--}}
{{--            },--}}
{{--            sideBySide: true--}}
{{--        })--}}
{{--    </script>--}}
{{--@endpush--}}
