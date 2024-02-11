@extends('layouts.pdf')

@section('content')
    <div class="row" style="margin-top: 30px">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Details</strong>
                </div>
                <div class="card-body">
                    @include('accounts.show_fields_pdf')
                </div>
            </div>
        </div>
    </div>
    <div class="row new-page">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Performance</strong>
                </div>
                <div class="card-body">
                    <img src="{{$files['monthly_performance.png']}}" alt="Monthly Performance"/>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Yearly Performance</strong>
                </div>
                <div class="card-body">
                    <img src="{{$files['yearly_performance.png']}}" alt="Yearly Performance"/>
                </div>
            </div>
        </div>
    </div>
    <div class="row new-page">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Shares</strong>
                </div>
                <div class="card-body">
                    <img src="{{$files['shares.png']}}" alt="Shares"/>
                </div>
            </div>
        </div>
    </div>
    <div class="row new-page">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <strong>Yearly Performance</strong>
                </div>
                <div class="card-body">
                    @php ($performance_key = 'yearly_performance')
                    @include('accounts.performance_table')
                </div>
            </div>
        </div>
    </div>
    <div class="row new-page">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <strong>Monthly Performance</strong>
                </div>
                <div class="card-body">
                    @php ($performance_key = 'monthly_performance')
                    @include('accounts.performance_table')
                </div>
            </div>
        </div>
    </div>
    <div class="row new-page">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <strong>Transactions</strong>
                </div>
                <div class="card-body">
                    @include('accounts.transactions_table')
                </div>
            </div>
        </div>
    </div>
    @if($api['matching_available'] != 0)
        <div class="row new-page">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <strong>Matching Rules</strong>
                    </div>
                    <div class="card-body">
                        @include('accounts.matching_rules_table')
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
