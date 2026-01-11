<div class="row">
    <div class="col-md-6">
        <!-- Object Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-cube me-1"></i> Object:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $changeLog->object }}</p>
        </div>

        <!-- Change Log ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Change Log ID:</label>
            <p class="mb-0">#{{ $changeLog->id }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Content Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-file-alt me-1"></i> Content:</label>
            <div class="rounded p-2 bg-slate-100 dark:bg-slate-700">
                <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.875rem;">{{ $changeLog->content }}</pre>
            </div>
        </div>

        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Created:</label>
            <p class="mb-0">{{ $changeLog->created_at?->format('M j, Y g:i A') ?: '-' }}</p>
        </div>
    </div>
</div>
