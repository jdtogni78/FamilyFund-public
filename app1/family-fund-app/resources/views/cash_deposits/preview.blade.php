@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('cashDeposits.index') }}">Cash Deposit</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
            @php
                $valid = [
                    'cash_deposits' => [],
                ];
                $transactions = [];
            @endphp

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Cash Deposits</strong>
                        </div>
                        <div class="card-body">
                            @php($cashDeposits = [])
                            @foreach($data['data'] as $item)
                                @php($cashDeposits[] = $cashDeposit = $item['cash_deposit'])
                                @php($valid['cash_deposits'][$cashDeposit->id] = ['unassigned' => $cashDeposit->amount, 'assigned' => 0])
                            @endforeach
                            @include('cash_deposits.table', ['cashDeposits' => $cashDeposits])
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Deposit Requests</strong>
                        </div>
                        <div class="card-body">
                            @php($deposits = [])
                            @foreach($data['data'] as $item)
                                @isset($item['deposits'])
                                    @foreach($item['deposits'] as $deposit)
                                        @php($deposits[] = $deposit['deposit'])
                                    @endforeach
                                @endisset
                            @endforeach
                            @include('deposit_requests.table', ['depositRequests' => $deposits])
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Transactions</strong>
                        </div>
                        <div class="card-body">
                            @php($transactions = [])
                            @isset($data['transactions'])
                                @foreach($data['transactions'] as $transaction_data)
                                    @php($transaction = $transaction_data['transaction'])
                                    @php($transactions[] = $transaction)
                                    @isset($transaction->depositRequest)
                                        @php($valid['cash_deposits'][$transaction->depositRequest->cashDeposit->id]['unassigned'] -= $transaction->value)
                                        @php($valid['cash_deposits'][$transaction->depositRequest->cashDeposit->id]['assigned'] += $transaction->value)
                                    @endisset
                                @endforeach
                            @endisset
                            @include('transactions.table', ['transactions' => $transactions])
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Balance Changes</strong>
                        </div>
                        <div class="card-body">
                            @php($balances = [])
                            @isset($data['transactions'])
                                @foreach($data['transactions'] as $transaction_data)
                                    @php($balances[] = $transaction_data['balance'])
                                    {{ print_r(gettype($transaction_data['balance'])) }}
                                @endforeach
                            @endisset
                            @include('account_balances.table', ['accountBalances' => $balances])
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Summary</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Cash Deposit</th>
                                        <th>Unassigned</th>
                                        <th>Assigned</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($valid['cash_deposits'] as $id => $data)
                                        <tr>
                                            <td>{{ $id }}</td>
                                            <td>${{ number_format($data['unassigned'], 2) }}</td>
                                            <td>${{ number_format($data['assigned'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            {!! Form::open(['route' => ['tradePortfolios.do_deposits', $tradePortfolio->id], 'method' => 'post']) !!}
                            {!! Form::submit('Execute Deposits', ['class' => 'btn btn-primary']) !!}
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

