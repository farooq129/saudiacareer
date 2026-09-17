@php
    use App\Support\Locale;

    $brandName = config('board.brand.'.Locale::current()).__('brand.tld');
@endphp

{{-- The wordmark is a single lockup image (glyph + "Saudia career"), so the
     name is carried by the alt text rather than a text node. Compact is the
     drawer/portal sidebar size; on navy the surrounding block adds .brand
     inversion in app.css. --}}
<a href="{{ lroute('home') }}" class="brand">
    <img src="{{ asset('images/main_logo.png') }}" alt="{{ $brandName }}"
         class="brand-logo @if($compact ?? false) is-compact @endif"
         width="1965" height="438">
</a>
