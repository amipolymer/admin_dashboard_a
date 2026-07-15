@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator */
    $paginator = $paginator ?? $logs;
@endphp
@if ($paginator->total() > 0)
    <div class="log-pagination-bar d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top">
        <p class="small text-muted mb-2 mb-md-0">
            Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
            of <strong>{{ $paginator->total() }}</strong>
            (page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }})
        </p>
        <div class="log-pagination-links">
            {{ $paginator->links('vendor.pagination.bootstrap-4') }}
        </div>
    </div>
@endif
