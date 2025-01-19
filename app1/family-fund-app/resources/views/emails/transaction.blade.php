@extends('layouts.email')

@section('content')
Dear {{$api['to']}},<br>
    Find the confirmation of the transaction below.<br>
<br>

@include('transactions.show_changes')
@endsection
