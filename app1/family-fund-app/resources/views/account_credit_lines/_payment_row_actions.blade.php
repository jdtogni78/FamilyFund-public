{{--
    Shared per-row payment actions for a loan-share schedule row.

    Expects:
      $line  — AccountCreditLine the row belongs to
      $row   — CreditLinePayment schedule row

    Renders Edit / Delete controls for an already-registered payment. New
    payments are entered via the card-level "Make payment" button (oldest-first
    cascade); manual re-allocation is reached via the per-allocation slider.
    Only active lines accept changes.
--}}
@php
    $lineActive   = $line && $line->status === 'active';
    $hasPayment   = !empty($row->paid_transaction_id);
@endphp
@if($lineActive && $line)
    <div class="btn-group">
        @if($hasPayment)
            <a href="{{ route('credit_lines.payments.edit_form', ['line' => $line->id, 'payment' => $row->id]) }}"
               class="btn btn-ghost-info"
               title="Edit this payment (txn #{{ $row->paid_transaction_id }})">
                <i class="fa fa-edit"></i>
            </a>
            <form action="{{ route('credit_lines.payments.reverse', ['line' => $line->id, 'payment' => $row->id]) }}"
                  method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-ghost-danger"
                        title="Delete — reverse the registered payment (txn #{{ $row->paid_transaction_id }})"
                        onclick="return confirm('Delete (reverse) the registered payment on row #{{ $row->id }} (txn #{{ $row->paid_transaction_id }})? This reopens the schedule row and restores outstanding.')">
                    <i class="fa fa-trash"></i>
                </button>
            </form>
        @endif
    </div>
@endif
