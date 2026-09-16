@php
    /*
     * One pill for every status on either table. The icon is there so the
     * meaning does not rest on colour alone — a queue is scanned at speed, and
     * about one man in twelve cannot separate the red from the green.
     */
    $icons = [
        'draft' => 'ph-pencil-simple',
        'pending' => 'ph-hourglass',
        'published' => 'ph-check-circle',
        'rejected' => 'ph-x-circle',
        'expired' => 'ph-clock-countdown',
        'filled' => 'ph-seal-check',
        'hired' => 'ph-seal-check',
        'open' => 'ph-hourglass',
        'reviewing' => 'ph-eye',
        'upheld' => 'ph-shield-warning',
        'dismissed' => 'ph-shield-check',
    ];

    $group ??= 'admin.status';
@endphp

<span class="pill pill-{{ $status }}">
    <i class="ph {{ $icons[$status] ?? 'ph-circle' }}"></i>{{ __($group.'.'.$status) }}
</span>
