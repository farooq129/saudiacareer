@php
    use App\Support\Locale;
    use Illuminate\Support\Facades\Route;

    $locale = Locale::current();
    $brand = config('board.brand.'.$locale);
@endphp

<footer class="ftr">
    <div class="ftr-inner">

        <div>
            @include('partials.brand')

            <p class="ftr-tagline">{{ __('footer.tagline') }}</p>

            <div class="ftr-social">
                <a href="#" aria-label="X"><i class="ph ph-x-logo"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="ph ph-linkedin-logo"></i></a>
                <a href="#" aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
                <a href="#" aria-label="WhatsApp"><i class="ph ph-whatsapp-logo"></i></a>
            </div>
        </div>

        {{--
            Columns and link keys come from config/board.php, labels from
            lang/{locale}/footer.php. A link with no route yet renders as plain
            text rather than as a link that goes nowhere.
        --}}
        @foreach (config('board.footer') as $column)
            <div class="ftr-col">
                <div class="ftr-h">{{ __('footer.'.$column['key'].'.title') }}</div>

                @foreach ($column['links'] as $link)
                    @php $label = __('footer.'.$column['key'].'.'.$link['key']); @endphp

                    @if ($link['route'] && Route::has($locale.'.'.$link['route']))
                        <a href="{{ lroute($link['route']) }}" class="ftr-link">{{ $label }}</a>
                    @else
                        <span class="ftr-link" style="opacity:.62">{{ $label }}</span>
                    @endif
                @endforeach
            </div>
        @endforeach

    </div>

    <div class="ftr-bar">
        <div class="ftr-bar-inner">
            <span>{{ __('footer.copyright', ['year' => now()->year, 'brand' => $brand]) }}</span>
            <span class="ftr-legal">
                <a href="#">{{ __('footer.privacy') }}</a>
                <a href="#">{{ __('footer.terms') }}</a>
                <a href="#">{{ __('footer.cookies') }}</a>
            </span>
        </div>
    </div>
</footer>
