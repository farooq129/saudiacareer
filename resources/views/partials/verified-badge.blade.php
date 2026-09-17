{{-- The verified seal that follows a company name. Drawn inline rather than
     pulled from the Phosphor font: only the regular weight is imported, and the
     badge needs a solid fill, so an inline SVG costs nothing next to a second
     webfont. The scallops are twelve overlapping circles plus a centre disc —
     one fill makes them read as a single shape. Colour comes from currentColor
     so .verified-badge can retint it (white on the navy surfaces). --}}
<svg class="verified-badge" viewBox="0 0 24 24" width="16" height="16"
     role="img" aria-label="{{ __('tag.verified') }}" focusable="false">
    <g fill="currentColor">
        <circle cx="12" cy="4.4" r="2.9"/><circle cx="15.8" cy="5.42" r="2.9"/>
        <circle cx="18.58" cy="8.2" r="2.9"/><circle cx="19.6" cy="12" r="2.9"/>
        <circle cx="18.58" cy="15.8" r="2.9"/><circle cx="15.8" cy="18.58" r="2.9"/>
        <circle cx="12" cy="19.6" r="2.9"/><circle cx="8.2" cy="18.58" r="2.9"/>
        <circle cx="5.42" cy="15.8" r="2.9"/><circle cx="4.4" cy="12" r="2.9"/>
        <circle cx="5.42" cy="8.2" r="2.9"/><circle cx="8.2" cy="5.42" r="2.9"/>
        <circle cx="12" cy="12" r="8.6"/>
    </g>
    <path d="M8.1 12.2 L10.9 15 L16 9.5" fill="none" stroke="#ffffff" stroke-width="2.4"
          stroke-linecap="round" stroke-linejoin="round"/>
</svg>
