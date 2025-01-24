<div class="progress-group">
    <div class="progress-group-header">
        <i class="cil-user progress-group-icon fa-solid fa-chart-line"></i>
        <div>Expected Value:&nbsp;</div>
        <div class="ms-auto font-weight-bold">{{ number_format($goal->progress['expected']['completed_pct'], 1) }}%</div>
    </div>
    <div class="progress-group-bars">
        <div class="progress progress-thin">
            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $goal->progress['expected']['completed_pct'] }}%" 
            aria-valuenow="{{ $goal->progress['expected']['completed_pct'] }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <div class="progress-group-header">
        <i class="cil-user progress-group-icon fa-solid fa-chart-line"></i>
        <div>Current Value:&nbsp;</div>
        <div class="ms-auto font-weight-bold">{{ number_format($goal->progress['current']['completed_pct'], 1) }}%</div>
    </div>
    <div class="progress-group-bars">
        <div class="progress progress-thin">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $goal->progress['current']['completed_pct'] }}%" 
            aria-valuenow="{{ $goal->progress['current']['completed_pct'] }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>
