@extends('layouts.email')

@section('content')
Dear {{$api['to']}},

    Find attached the PDF with the {{$api["report_name"]}}.

@endsection
