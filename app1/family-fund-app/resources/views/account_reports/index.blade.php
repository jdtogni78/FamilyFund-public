<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Account Reports</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-file-alt me-2"></i>
                                 <strong>Account Reports</strong>
                                 <span class="badge bg-primary ms-2">{{ $accountReports->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('accountReports.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Report
                             </a>
                         </div>
                         <div class="card-body">
                             {{-- Filters --}}
                             <form method="GET" action="{{ route('accountReports.index') }}" class="row g-3 mb-3">
                                 <div class="col-auto">
                                     <select name="fund_id" class="form-select form-select-sm" style="width: 150px;">
                                         <option value="">All Funds</option>
                                         @foreach($funds as $id => $name)
                                             <option value="{{ $id }}" {{ $fundId == $id ? 'selected' : '' }}>{{ $name }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="col-auto">
                                     <select name="account_id" class="form-select form-select-sm" style="width: 200px;">
                                         <option value="">All Accounts</option>
                                         @foreach($accounts as $id => $name)
                                             <option value="{{ $id }}" {{ $accountId == $id ? 'selected' : '' }}>{{ $name }}</option>
                                         @endforeach
                                     </select>
                                 </div>
                                 <div class="col-auto">
                                     <input type="date" name="date_from" class="form-control form-control-sm"
                                            value="{{ $dateFrom }}" placeholder="From" title="From date">
                                 </div>
                                 <div class="col-auto">
                                     <input type="date" name="date_to" class="form-control form-control-sm"
                                            value="{{ $dateTo }}" placeholder="To" title="To date">
                                 </div>
                                 <div class="col-auto">
                                     <button type="submit" class="btn btn-sm btn-primary">
                                         <i class="fa fa-filter me-1"></i> Filter
                                     </button>
                                 </div>
                                 @if($fundId || $accountId || $dateFrom || $dateTo)
                                 <div class="col-auto">
                                     <a href="{{ route('accountReports.index') }}" class="btn btn-sm btn-outline-secondary">
                                         <i class="fa fa-times me-1"></i> Clear
                                     </a>
                                 </div>
                                 @endif
                             </form>

                             @include('account_reports.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

