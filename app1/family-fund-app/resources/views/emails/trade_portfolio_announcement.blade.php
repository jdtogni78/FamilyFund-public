@extends('layouts.email')

@section('content')
    <p>Hello Admin,</p>
    <p>A new trade portfolio has been created. Please review the changes and update the portfolio if necessary.</p>

    @include('trade_portfolios.show_diff')
@endsection
