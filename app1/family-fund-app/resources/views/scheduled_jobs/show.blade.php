<x-app-layout>

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('scheduledJobs.index') }}">Scheduled Job</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('layouts.flash-messages')
                 @include('coreui-templates.common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header d-flex justify-content-between align-items-center">
                                 <div>
                                     <i class="fa fa-clock me-2"></i>
                                     <strong>Details</strong>
                                 </div>
                                 <div class="d-flex flex-wrap" style="gap: 4px;">
                                     <a href="{{ route('scheduledJobs.preview', ['id' => $scheduledJob->id, 'asOf' => new Carbon\Carbon()]) }}" class="btn btn-sm btn-outline-primary" title="Run Now">
                                         <i class="fa fa-play me-1"></i> Run
                                     </a>
                                     <a href="{{ route('scheduledJobs.index') }}" class="btn btn-sm btn-primary">Back</a>
                                 </div>
                             </div>
                             <div class="card-body">
                                 @include('scheduled_jobs.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>

                 @php
                     $entities = $scheduledJob->entities();
                 @endphp

                 @if($entities->count() > 0)
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header d-flex justify-content-between align-items-center">
                                 <div>
                                     <i class="fa fa-list me-2"></i>
                                     <strong>Generated Reports</strong>
                                     <span class="badge bg-primary ms-2">{{ $entities->count() }}</span>
                                 </div>
                             </div>
                             <div class="card-body">
                                 <div class="table-responsive-sm">
                                     <table class="table table-sm table-hover">
                                         <thead>
                                             <tr class="bg-slate-50 dark:bg-slate-700">
                                                 <th>ID</th>
                                                 <th>Date</th>
                                                 <th>Created</th>
                                                 <th>Action</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                             @foreach($entities->sortByDesc(function($e) {
                                                 return $e->as_of ?? $e->timestamp ?? $e->end_date ?? $e->created_at;
                                             })->take(10) as $entity)
                                             <tr>
                                                 <td>
                                                     @if($scheduledJob->entity_descr == 'fund_report')
                                                         <a href="{{ route('fundReports.show', $entity->id) }}">#{{ $entity->id }}</a>
                                                     @elseif($scheduledJob->entity_descr == 'trade_band_report')
                                                         <a href="{{ route('tradeBandReports.show', $entity->id) }}">#{{ $entity->id }}</a>
                                                     @elseif($scheduledJob->entity_descr == 'transaction')
                                                         <a href="{{ route('transactions.show', $entity->id) }}">#{{ $entity->id }}</a>
                                                     @else
                                                         #{{ $entity->id }}
                                                     @endif
                                                 </td>
                                                 <td>
                                                     @if(isset($entity->as_of))
                                                         {{ $entity->as_of->format('M j, Y') }}
                                                     @elseif(isset($entity->timestamp))
                                                         {{ $entity->timestamp->format('M j, Y') }}
                                                     @elseif(isset($entity->end_date))
                                                         {{ $entity->end_date->format('M j, Y') }}
                                                     @endif
                                                 </td>
                                                 <td>{{ $entity->created_at->format('M j, Y g:i A') }}</td>
                                                 <td>
                                                     @if($scheduledJob->entity_descr == 'fund_report')
                                                         <a href="{{ route('fundReports.show', $entity->id) }}" class="btn btn-ghost-success" title="View">
                                                             <i class="fa fa-eye"></i>
                                                         </a>
                                                     @elseif($scheduledJob->entity_descr == 'trade_band_report')
                                                         <a href="{{ route('tradeBandReports.show', $entity->id) }}" class="btn btn-ghost-success" title="View">
                                                             <i class="fa fa-eye"></i>
                                                         </a>
                                                     @elseif($scheduledJob->entity_descr == 'transaction')
                                                         <a href="{{ route('transactions.show', $entity->id) }}" class="btn btn-ghost-success" title="View">
                                                             <i class="fa fa-eye"></i>
                                                         </a>
                                                     @endif
                                                 </td>
                                             </tr>
                                             @endforeach
                                         </tbody>
                                     </table>
                                     @if($entities->count() > 10)
                                         <small class="text-body-secondary">Showing 10 of {{ $entities->count() }} records</small>
                                     @endif
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
                 @endif
          </div>
    </div>
</x-app-layout>
