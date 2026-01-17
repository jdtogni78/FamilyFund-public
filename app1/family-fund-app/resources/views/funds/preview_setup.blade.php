<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('funds.index') !!}">Fund</a>
      </li>
      <li class="breadcrumb-item">
         <a href="{!! route('funds.createWithSetup') !!}">Create with Setup</a>
      </li>
      <li class="breadcrumb-item active">Preview</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates.common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <i class="fa fa-eye fa-lg me-2"></i>
                                <strong>Preview Fund Setup</strong>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle me-2"></i>
                                    <strong>Preview Mode:</strong> The following entities will be created when you confirm.
                                    No changes have been made to the database yet.
                                </div>

                                {{-- Fund Preview --}}
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <i class="fa fa-landmark me-2"></i> Fund
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <th style="width: 150px;">Name:</th>
                                                <td><strong>{{ $preview['fund']->name }}</strong></td>
                                            </tr>
                                            <tr>
                                                <th>Goal:</th>
                                                <td>{{ $preview['fund']->goal ?? '(none)' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                {{-- Account Preview --}}
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <i class="fa fa-wallet me-2"></i> Fund Account
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <th style="width: 150px;">Nickname:</th>
                                                <td><strong>{{ $preview['account']->nickname }}</strong></td>
                                            </tr>
                                            <tr>
                                                <th>Code:</th>
                                                <td>{{ $preview['account']->code }}</td>
                                            </tr>
                                            <tr>
                                                <th>User ID:</th>
                                                <td><span class="badge bg-secondary">null (Fund Account)</span></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                {{-- Portfolio(s) Preview --}}
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <i class="fa fa-briefcase me-2"></i> Portfolio(s) ({{ count($preview['portfolios']) }})
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Source Identifier</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($preview['portfolios'] as $index => $portfolio)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td><code>{{ $portfolio->source }}</code></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- Transaction Preview --}}
                                @if($preview['transaction'])
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <i class="fa fa-money-bill-wave me-2"></i> Initial Transaction
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-sm table-borderless">
                                                <tr>
                                                    <th style="width: 150px;">Type:</th>
                                                    <td><span class="badge bg-primary">INITIAL</span></td>
                                                </tr>
                                                <tr>
                                                    <th>Amount:</th>
                                                    <td><strong>${{ number_format($preview['transaction']->amount, 2) }}</strong></td>
                                                </tr>
                                                @if($preview['transaction']->shares)
                                                    <tr>
                                                        <th>Shares:</th>
                                                        <td><strong>{{ number_format($preview['transaction']->shares, 8) }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Share Price:</th>
                                                        <td>${{ number_format($preview['transaction']->amount / $preview['transaction']->shares, 8) }}</td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <th>Description:</th>
                                                    <td>{{ $preview['transaction']->description }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Timestamp:</th>
                                                    <td>{{ $preview['transaction']->timestamp }}</td>
                                                </tr>
                                            </table>

                                            @if($preview['accountBalance'])
                                                <hr>
                                                <h6><i class="fa fa-balance-scale me-2"></i> Account Balance</h6>
                                                <table class="table table-sm table-borderless">
                                                    <tr>
                                                        <th style="width: 150px;">Balance:</th>
                                                        <td><strong>${{ number_format($preview['accountBalance']->balance, 2) }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Shares:</th>
                                                        <td><strong>{{ number_format($preview['accountBalance']->shares, 8) }}</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Share Value:</th>
                                                        <td>${{ number_format($preview['accountBalance']->share_value, 8) }}</td>
                                                    </tr>
                                                </table>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fa fa-exclamation-triangle me-2"></i>
                                        No initial transaction will be created.
                                    </div>
                                @endif

                                {{-- Confirmation Form --}}
                                <hr class="my-4">
                                <form method="POST" action="{{ route('funds.storeWithSetup') }}">
                                    @csrf
                                    @foreach($input as $key => $value)
                                        @if($key !== 'preview')
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach

                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle me-2"></i>
                                        <strong>Ready to create?</strong> Click "Confirm & Create" to proceed with the setup.
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="preview" value="0" class="btn btn-success btn-lg">
                                            <i class="fa fa-check me-2"></i> Confirm & Create Fund
                                        </button>
                                        <a href="{{ route('funds.createWithSetup') }}" class="btn btn-secondary btn-lg">
                                            <i class="fa fa-arrow-left me-2"></i> Back to Edit
                                        </a>
                                        <a href="{{ route('funds.index') }}" class="btn btn-outline-secondary btn-lg">
                                            <i class="fa fa-times me-2"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>
