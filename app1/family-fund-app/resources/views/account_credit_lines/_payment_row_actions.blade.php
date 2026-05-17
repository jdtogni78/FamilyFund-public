{{--
    Shared per-row payment actions for a credit-line schedule row.

    Expects:
      $line  — AccountCreditLine the row belongs to
      $row   — CreditLinePayment schedule row

    Renders, for an admin, the Register / Edit / Delete controls. Only
    active lines accept changes (mirrors RepayService / show-page gating).
--}}
@php
    $openStatuses = ['scheduled', 'partial', 'late'];
    $lineActive   = $line && $line->status === 'active';
    $isOpen       = in_array($row->status, $openStatuses, true);
    $hasPayment   = !empty($row->paid_transaction_id);
@endphp
@if($lineActive && $line)
    <div class="btn-group btn-group-sm" role="group">
        @if($isOpen)
            <a class="btn btn-outline-success"
               href="{{ route('credit_lines.payments.register_form', ['line' => $line->id, 'payment' => $row->id]) }}">
                Register payment
            </a>
        @endif
        @if($hasPayment)
            <form method="POST" class="d-inline"
                  action="{{ route('credit_lines.payments.reverse', ['line' => $line->id, 'payment' => $row->id]) }}">
                @csrf
                <input type="hidden" name="then" value="edit">
                <button type="submit" class="btn btn-outline-primary"
                        onclick="return confirm('Reverse this payment (txn #{{ $row->paid_transaction_id }}) and re-register it? The schedule row will reopen and you\'ll be taken to the register form.');">
                    Edit
                </button>
            </form>
            <form method="POST" class="d-inline"
                  action="{{ route('credit_lines.payments.reverse', ['line' => $line->id, 'payment' => $row->id]) }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger"
                        onclick="return confirm('Delete (reverse) the registered payment on row #{{ $row->id }} (txn #{{ $row->paid_transaction_id }})? This reopens the schedule row and restores outstanding.');">
                    Delete
                </button>
            </form>
        @endif
    </div>
@endif
