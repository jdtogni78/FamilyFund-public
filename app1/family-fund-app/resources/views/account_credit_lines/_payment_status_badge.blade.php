{{--
    Shared status badge for a credit-line schedule / receivable row.

    Expects:
      $status — CreditLinePayment status string

    Single source of truth for status → colour so the show-page schedule
    and the cross-account Receivables list render identically.
--}}
@if($status === 'late')
    <span class="badge bg-danger">late</span>
@elseif($status === 'partial')
    <span class="badge bg-warning text-dark">partial</span>
@elseif($status === 'paid')
    <span class="badge bg-success">paid</span>
@elseif($status === 'cancelled')
    <span class="badge text-bg-secondary text-decoration-line-through">cancelled</span>
@else
    <span class="badge text-bg-secondary">scheduled</span>
@endif
