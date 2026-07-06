@props(['paginator'])

@if($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'px-3 sm:px-4 py-3 border-t border-gray-200 bg-gray-50 pagination-responsive']) }}>
        {{ $paginator->withQueryString()->links() }}
    </div>
@endif
