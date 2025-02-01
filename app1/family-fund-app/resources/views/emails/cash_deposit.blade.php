@extends('layouts.email')

@section('content')
    <p>Hello Admin,</p>
    <p>A new cash deposit has been detected. Please review the changes and update the portfolio if necessary.</p>
</x-app-layout>

@include('cash_deposits.show_ext')