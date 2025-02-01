<!-- Matching Rule Id Field -->
<div class="form-group col-sm-6">
<label for="matching_rule_id">Matching Rule Id:</label>
<input type="text" name="matching_rule_id" class="form-control">
</div>

<!-- Transaction Id Field -->
<div class="form-group col-sm-6">
<label for="transaction_id">Transaction Id:</label>
<input type="text" name="transaction_id" class="form-control">
</div>

<!-- Reference Transaction Id Field -->
<div class="form-group col-sm-6">
<label for="reference_transaction_id">Reference Transaction Id:</label>
<input type="text" name="reference_transaction_id" class="form-control">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('transactionMatchings.index') }}" class="btn btn-secondary">Cancel</a>
</div>
