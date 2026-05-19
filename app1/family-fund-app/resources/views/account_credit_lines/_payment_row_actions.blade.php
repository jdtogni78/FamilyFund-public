{{--
    Shared per-row payment actions for a credit-line schedule row.

    Expects:
      $line  — AccountCreditLine the row belongs to
      $row   — CreditLinePayment schedule row

    Renders, for an admin, the Register / Edit / Delete controls using the
    app-standard btn-group + btn-ghost-* icon buttons (see transactions
    table.blade.php). Only active lines accept changes (mirrors RepayService
    / show-page gating).
--}}
@php
    $openStatuses = ['scheduled', 'partial', 'late'];
    $lineActive   = $line && $line->status === 'active';
    $isOpen       = in_array($row->status, $openStatuses, true);
    $hasPayment   = !empty($row->paid_transaction_id);
@endphp
@if($lineActive && $line)
    <div class="btn-group">
        @if($isOpen)
            <a href="{{ route('credit_lines.payments.register_form', ['line' => $line->id, 'payment' => $row->id]) }}"
               class="btn btn-ghost-success" title="Register payment">
                <i class="fa fa-money-bill"></i>
            </a>
        @endif
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
