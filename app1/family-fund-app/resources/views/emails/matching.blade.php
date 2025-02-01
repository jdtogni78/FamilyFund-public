@extends('layouts.email')

@section('content')
Dear {{$api['to']}},<br>
    The following matching was added to your account.<br>
<br>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <strong>Details</strong>
                <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-light">Back</a>
            </div>
            <div class="card-body">
                @include('account_matching_rules.show_fields')
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <strong>Matching Rule</strong>
            </div>
            <div class="card-body">
                @include('matching_rules.show_fields', ['matchingRule' => $api['mr']])
            </div>
        </div>
    </div>
</div>
</x-app-layout>
