@if ($paginator->total() > 0)
    <div
        class="admin-table-pagination border-top px-3 px-md-4 py-3"
        data-pagination-from="{{ $paginator->firstItem() }}"
        data-pagination-to="{{ $paginator->lastItem() }}"
        data-pagination-total="{{ $paginator->total() }}"
        data-pagination-label="{{ $label }}"
    >
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
            <p class="muted-small mb-0 text-center text-md-start">
                Showing
                <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
                to
                <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
                of
                <span class="fw-semibold">{{ $paginator->total() }}</span>
                {{ $label }}
            </p>

            @if ($paginator->hasPages())
                <div class="d-flex justify-content-center justify-content-md-end">
                    {{ $paginator->onEachSide(1)->links('admin.partials.pagination-links') }}
                </div>
            @endif
        </div>
    </div>
@endif
