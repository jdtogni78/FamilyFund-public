<div class="row">
    <div class="col-lg-12">
    <div class="card">
        <div class="card-header">
            <i class="fa fa-plus fa-lg"></i>
            <strong>Add Deposit Requests 
                <span class="text-danger" id="total-amount-error">
                    (Assigned: <span id="total-assigned">0</span>, 
                    Unassigned: <span id="total-unassigned">0</span>)</span></strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <form action="{{ route('cashDeposits.do_assign', $cashDeposit->id) }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="form-group col-sm-6">
<label for="unassigned">Unassigned Amount:</label>
<input type="number" name="unassigned" value="{ 0 }" step="0.01" class="form-control" id="unassigned">
                    </div>
                    <table class="table" id="deposit-requests-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Account</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="deposit-template" style="display:none">
                                <td><input type="text" name="_deposits[0][description]" class="form-control"></td>
                                <td><input type="number" name="_deposits[0][amount]" class="form-control" step="0.01"></td>
                                <td><select name="_deposits[0][account_id]" class="form-control">
                                    @foreach ($api['accountMap'] as $accountId => $accountName)
                                        <option value="{{ $accountId }}">{{ $accountName }}</option>
                                    @endforeach
                                </select></td>
                                <td>
                                    <button type="button" class="btn btn-danger remove-row">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @foreach ($api['depositRequests'] as $depositRequest)
                                <tr id="deposit-ids-{{ $depositRequest->id }}" class="deposit-ids">
                                    <td>{{ $depositRequest->description }}</td>
                                    <td>{{ $depositRequest->amount }}</td>
                                    <td>{{ $api['accountMap'][$depositRequest->account_id] }}</td>
                                    <td>
                                        <input type="checkbox" name="deposit_ids[]" value="{{ $depositRequest->id }}" class="form-check-input">
                                        Add
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-success" id="add-deposit-row">
                        <i class="fa fa-plus"></i> Add Deposit Request
                    </button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </form>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            var expectedAmount = {!! $cashDeposit->amount !!};
            updateTotalAmount();
            
            $('#add-deposit-row').click(function() {
                var template = $('#deposit-template').clone();
                var index = $('.deposit').length;
                template.removeAttr('id').show();
                template.addClass('deposit');
                template.attr('id', 'deposit-' + index);
                // update the index
                template.find('input, select').each(function() {
                    $(this).attr('name', $(this).attr('name').replace('_deposits[0]', 'deposits[' + index + ']'));
                });
                $('#deposit-requests-table tbody').append(template);
            });

            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
            });

            function updateTotalAmount() {
                var totalAmount = 0;
                var unassignedAmount = parseFloat($('input[name="unassigned"]').val());
                totalAmount += unassignedAmount;
                $('.deposit').each(function() {
                    totalAmount += parseFloat($(this).find('input[name$="[amount]"]').val());
                });
                console.log(totalAmount);
                $('.deposit-ids').each(function() {
                    console.log($(this).find('td:nth-child(2)'));
                    if ($(this).find('input[type="checkbox"]').is(':checked')) {
                        totalAmount += parseFloat($(this).find('td:nth-child(2)').text());
                    }
                });
                console.log(totalAmount);
                $('#total-assigned').text(totalAmount.toFixed(2));
                $('#total-unassigned').text((expectedAmount - totalAmount).toFixed(2));
                if (totalAmount != expectedAmount) {
                    $('#total-amount-error').removeClass('text-success');
                    $('#total-amount-error').addClass('text-danger');
                } else {
                    $('#total-amount-error').removeClass('text-danger');
                    $('#total-amount-error').addClass('text-success');
                }
            }

            // upon checkbox change or amount change, update the total amount
            $(document).on('change', '.deposit input[name$="[amount]"]', updateTotalAmount);
            $(document).on('change', '.deposit-ids input[type="checkbox"]', updateTotalAmount);
            $(document).on('change', '#unassigned', updateTotalAmount);
        });
    </script>
    @endpush
    </div>
</div>