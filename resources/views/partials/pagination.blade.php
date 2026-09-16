@php
    use App\Support\Locale;

    /*
     * This block sits outside the @if on purpose. Blade drops an @php block
     * exactly where it stands, so a `use` inside a conditional compiles to an
     * import inside an if-block — which is a PHP syntax error, not a runtime
     * one, and it only surfaces the first time a list is long enough to
     * paginate.
     *
     * The arrows point the way reading goes, so "next" is on the left in
     * Arabic. The paginator itself knows nothing about direction.
     */
    $prevIcon = Locale::isRtl() ? 'ph-caret-right' : 'ph-caret-left';
    $nextIcon = Locale::isRtl() ? 'ph-caret-left' : 'ph-caret-right';
@endphp

@if ($paginator->hasPages())
    <nav class="pager" role="navigation" aria-label="{{ __('pagination.label') }}">

        @if ($paginator->onFirstPage())
            <span class="pager-item is-disabled" aria-disabled="true">
                <i class="ph {{ $prevIcon }}"></i>
            </span>
        @else
            <a class="pager-item" href="{{ $paginator->previousPageUrl() }}" rel="prev"
               aria-label="{{ __('pagination.previous') }}">
                <i class="ph {{ $prevIcon }}"></i>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pager-gap">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pager-item is-on" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pager-item" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="pager-item" href="{{ $paginator->nextPageUrl() }}" rel="next"
               aria-label="{{ __('pagination.next') }}">
                <i class="ph {{ $nextIcon }}"></i>
            </a>
        @else
            <span class="pager-item is-disabled" aria-disabled="true">
                <i class="ph {{ $nextIcon }}"></i>
            </span>
        @endif

    </nav>
@endif
