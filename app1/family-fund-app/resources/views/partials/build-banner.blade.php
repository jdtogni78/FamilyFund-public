{{-- Dev/preview build banner. Renders only when launch_docker.sh injected a
     build ref (i.e. a nickname stack) and we're not in production. Inline
     styles on purpose: no Tailwind class so no asset rebuild is required. --}}
@php($ffBuildRef = config('build.ref'))
@php($ffBuildLabel = config('build.label'))
@if ($ffBuildRef && ! app()->isProduction())
    <div title="Preview build — env: {{ config('build.nickname', '?') }}@if ($ffBuildLabel) — {{ $ffBuildLabel }}@endif / {{ $ffBuildRef }}"
         style="position:fixed;bottom:10px;right:10px;z-index:2147483647;
                background:#b45309;color:#fff;font:600 11px/1.35 ui-monospace,SFMono-Regular,Menlo,monospace;
                padding:5px 10px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.35);
                opacity:.82;pointer-events:none;max-width:60vw;
                white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
        <span style="opacity:.65">env</span> {{ config('build.nickname', '?') }}
        @if ($ffBuildLabel)
            <span style="opacity:.4">&nbsp;·&nbsp;</span>
            <span style="font-weight:700">{{ $ffBuildLabel }}</span>
        @endif
        <span style="opacity:.4">&nbsp;·&nbsp;</span>
        <span style="opacity:.65">build</span> {{ $ffBuildRef }}
    </div>
@endif
