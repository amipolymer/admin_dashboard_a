@php
    $title = $title ?? 'JSON';
    $data = $data ?? null;
@endphp
<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong class="small mb-0">{{ $title }}</strong>
        @if ($data !== null && $data !== [])
            <button type="button" class="btn btn-outline-secondary btn-sm toggle-json" data-target="#json-{{ $id ?? md5($title) }}">Show / Hide</button>
        @endif
    </div>
    <div class="card-body p-0">
        @if ($data === null || $data === [])
            <p class="text-muted small mb-0 p-3">— No data —</p>
        @else
            <pre id="json-{{ $id ?? md5($title) }}" class="json-preview mb-0 p-3 bg-light border-0" style="max-height:480px;overflow:auto;font-size:12px;display:none;">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        @endif
    </div>
</div>
