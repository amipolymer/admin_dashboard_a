@php
    $documentItems = $documentItems ?? [];
    $docCount = count($documentItems);
@endphp

<div class="border-top pt-3 mt-3">
    <h6 class="mb-2">View documents</h6>

    @if ($docCount === 0)
        <p class="text-muted small mb-0">No uploaded documents on file yet.</p>
    @else
        <div class="row g-2 align-items-end">
            <div class="col-sm-8">
                <label for="srHrDocumentSelect" class="form-label small text-muted mb-1">Select document</label>
                <select id="srHrDocumentSelect" class="form-select form-select-sm">
                    <option value="">Choose a document…</option>
                    @foreach ($documentItems as $item)
                        <option value="{{ $item['id'] }}">
                            {{ $item['label'] }}
                            @if (!empty($item['uploaded_at']))
                                ({{ $item['uploaded_at'] }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4">
                <a id="srHrDocumentViewLink" href="#" target="_blank" rel="noopener"
                    class="btn btn-sm btn-outline-primary w-100 disabled" aria-disabled="true"
                    onclick="return srHrOpenDocument(event)">
                    View document
                </a>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2" id="srHrDocumentMeta"></p>
    @endif
</div>

@if ($docCount > 0)
<script>
var srHrDocumentItems = @json($documentItems);

function srHrSelectedDocument() {
    var select = document.getElementById('srHrDocumentSelect');
    if (!select || !select.value) return null;
    return srHrDocumentItems.find(function (item) {
        return String(item.id) === String(select.value);
    }) || null;
}

function srHrUpdateDocumentLink() {
    var link = document.getElementById('srHrDocumentViewLink');
    var meta = document.getElementById('srHrDocumentMeta');
    var item = srHrSelectedDocument();

    if (!link) return;

    if (!item) {
        link.href = '#';
        link.classList.add('disabled');
        link.setAttribute('aria-disabled', 'true');
        if (meta) meta.textContent = '';
        return;
    }

    link.href = item.view_url;
    link.classList.remove('disabled');
    link.setAttribute('aria-disabled', 'false');

    if (meta) {
        var parts = [item.file || ''];
        if (item.status) parts.push(item.status);
        meta.textContent = parts.join(' · ');
    }
}

function srHrOpenDocument(event) {
    var item = srHrSelectedDocument();
    if (!item || !item.view_url) {
        event.preventDefault();
        alert('Please select a document.');
        return false;
    }
    return true;
}

document.getElementById('srHrDocumentSelect')?.addEventListener('change', srHrUpdateDocumentLink);
srHrUpdateDocumentLink();
</script>
@endif
