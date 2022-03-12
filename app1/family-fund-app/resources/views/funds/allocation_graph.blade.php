<div>
    <canvas id="allocationGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
const alloc_data = {
  labels: [
    'Allocated',
    'Unallocated',
  ],
  datasets: [{
    label: 'My First Dataset',
    data: [api.summary.allocated_shares_percent, api.summary.unallocated_shares_percent],
    // data: [40, 60],
    backgroundColor: [
      'green    ',
      'gray',
    ],
    hoverOffset: 3
  }]
};
const alloc_config = {
  type: 'doughnut',
  data: alloc_data,
};
  var myChart = new Chart(
    document.getElementById('allocationGraph'),
    alloc_config
  );
</script>
@endpush
