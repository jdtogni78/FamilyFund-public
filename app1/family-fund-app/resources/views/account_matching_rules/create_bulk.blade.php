<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('accountMatchingRules.index') !!}">Account Matching Rule</a>
      </li>
      <li class="breadcrumb-item active">Create</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates.common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Account Matching Rule</strong>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('account-matching-rules.store-bulk') }}" class="form-horizontal">
                                    @csrf
                                    <!-- Matching Rule Id Field -->
                                    <div class="form-group col-sm-6">
                                        <label class="col-sm-2 control-label" for="matching_rule_id">Matching Rule Id:</label>
                                        <div class="col-sm-10">
                                            <select name="matching_rule_id" id="matching_rule_id" class="form-control">
                                                @foreach($api['mr'] as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Multiple Account Selection Field -->
                                    <div class="form-group col-sm-6">
                                        <label class="col-sm-2 control-label" for="account_ids[]">Select Multiple Accounts:</label>
                                        <div class="col-sm-10">
                                            <select name="account_ids[]" id="account_ids[]" class="form-control" multiple size="8">
                                                @foreach($api['account'] as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple accounts</small>
                                        </div>
                                    </div>

                                    <!-- Rules Field -->
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label" for="rules">Rules:</label>
                                        <div class="col-sm-10">
                                            <textarea name="rules" id="rules" class="form-control" rows="10">{{ old('rules') }}</textarea>
                                            <p class="help-block">Enter one rule per line in the format: name|description</p>
                                        </div>
                                    </div>

                                    <!-- Submit Field -->
                                    <div class="form-group">
                                        <div class="col-sm-offset-2 col-sm-10">
                                            <button type="submit" class="btn btn-primary">Save Rules</button>
                                            <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>
