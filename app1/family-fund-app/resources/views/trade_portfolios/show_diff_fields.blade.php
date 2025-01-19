<div class="row">

    <!-- Account Name Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('account_name', 'Account Name:') !!}
        <p>{{ $api['old']->account_name }}
            @if(isset($api['new']) && $api['new']['account_name'] != $api['old']['account_name'])
                -> <span class="text-success">{{ $api['new']['account_name'] }}</span>
            @endif
        </p>
    </div>

    <!-- Fund Id Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('portfolio_id', 'Portfolio Id:') !!}
        <p>{{ $api['old']->portfolio_id }}
            @if(isset($api['new']) && $api['new']['portfolio_id'] != $api['old']['portfolio_id'])
                -> <span class="text-success">{{ $api['new']['portfolio_id'] }}</span>
            @endif
        </p>
    </div>

    <!-- TWS Query Id Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('tws_query_id', 'TWS Query Id:') !!}
        <p>{{ $api['old']->tws_query_id }}
            @if(isset($api['new']) && $api['new']['tws_query_id'] != $api['old']['tws_query_id'])
                -> <span class="text-success">{{ $api['new']['tws_query_id'] }}</span>
            @endif
        </p>
    </div>

    <!-- TWS Token Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('tws_token', 'TWS Token:') !!}
        <p>{{ $api['old']->tws_token }}
            @if(isset($api['new']) && $api['new']['tws_token'] != $api['old']['tws_token'])
                -> <span class="text-success">{{ $api['new']['tws_token'] }}</span>
            @endif
        </p>
    </div>

    <!-- Fund Name Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('portfolio_name', 'Portfolio Source:') !!}
        <p>{{ $api['old']->portfolio->source }}
            @if(isset($api['new']) && $api['new']['portfolio']['source'] != $api['old']['portfolio']['source'])
                -> <span class="text-success">{{ $api['new']['portfolio']['source'] }}</span>
            @endif
        </p>
    </div>


    <!-- Start Date Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('start_dt', 'Start Date:') !!}
        <p id="show_start_dt">{{ ($api['old']['start_dt'] ?? $api['old']['start_dt'])->format('Y-m-d') }}
            @if(isset($api['new']) && $api['new']['start_dt'] != $api['old']['start_dt'])
                -> <span class="text-success">{{ ($api['new']['start_dt'] ?? $api['new']['start_dt'])->format('Y-m-d') }}</span>
            @endif
        </p>
    </div>

    <!-- create end date field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('end_dt', 'End Date:') !!}
        <p id="show_end_dt">{{ ($api['old']['end_dt'] ?? $api['old']['end_dt'])->format('Y-m-d') }}
            @if(isset($api['new']) && $api['new']['end_dt'] != $api['old']['end_dt'])
                -> <span class="text-success">{{ ($api['new']['end_dt'] ?? $api['new']['end_dt'])->format('Y-m-d') }}</span>
            @endif
        </p>
    </div>

    <!-- Cash Target Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('cash_target', 'Cash Target:') !!}
        <p>{{ $api['old']->cash_target * 100 }}%
            @if(isset($api['new']) && $api['new']['cash_target'] != $api['old']['cash_target'])
                -> <span class="text-success">{{ $api['new']['cash_target'] * 100 }}%</span>
            @endif
        </p>
    </div>

    <!-- Cash Reserve Target Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('cash_reserve_target', 'Cash Reserve Target:') !!}
        <p>{{ $api['old']->cash_reserve_target * 100 }}%
            @if(isset($api['new']) && $api['new']['cash_reserve_target'] != $api['old']['cash_reserve_target'])
                -> <span class="text-success">{{ $api['new']['cash_reserve_target'] * 100 }}%</span>
            @endif
        </p>
    </div>

    <!-- Max Single Order Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('max_single_order', 'Max Single Order:') !!}
        <p>{{ $api['old']->max_single_order * 100 }}%
            @if(isset($api['new']) && $api['new']['max_single_order'] != $api['old']['max_single_order'])
                -> <span class="text-success">{{ $api['new']['max_single_order'] * 100 }}%</span>
            @endif
        </p>
    </div>

    <!-- Minimum Order Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('minimum_order', 'Minimum Order:') !!}
        <p>${{ $api['old']->minimum_order }}
            @if(isset($api['new']) && $api['new']['minimum_order'] != $api['old']['minimum_order'])
                -> <span class="text-success">{{ $api['new']['minimum_order'] }}</span>
            @endif
        </p>
    </div>

    <!-- Rebalance Period Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('rebalance_period', 'Rebalance Period:') !!}
        <p>{{ $api['old']->rebalance_period }} days
            @if(isset($api['new']) && $api['new']['rebalance_period'] != $api['old']['rebalance_period'])
                -> <span class="text-success">{{ $api['new']['rebalance_period'] }} days</span>
            @endif
        </p>
    </div>

    <!-- Total Share Field: calculate cash target plus sum of all item shares -->
    <div class="form-group col-sm-6 font-weight-bold {{ $api['old']->total_shares - 100 == 0 ? 'text-success' : 'text-danger' }}">
        {!! Form::label('total_share', 'Total Shares:') !!}
        <p>{{ $api['old']->total_shares }}%
            @if(isset($api['new']) && $api['new']['total_shares'] != $api['old']['total_shares'])
                -> <span class="text-success">{{ $api['new']['total_shares'] }}%</span>
            @endif
        </p>
    </div>
</div>

<script>
    api = {!! json_encode($api) !!};
</script>