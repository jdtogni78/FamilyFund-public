<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{!! route('transactions.index') !!}">Transaction</a>
        </li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-edit fa-lg"></i>
                            <strong>Edit Transaction</strong>
                        </div>
                        <div class="card-body">
<form method="patch" action="{ route('['route' => ['transactions.update', $transaction->id]') }" >
                            @include('transactions.edit_fields')
</form>
                        </div>
                    </div>
                </div>
            </div>
            @if($transaction->scheduledJobs()->count() > 0)
                @php($scheduledJobs = $transaction->scheduledJobs())
                @include('scheduled_jobs.table')
            @endif
        </div>
    </div>
</x-app-layout>
