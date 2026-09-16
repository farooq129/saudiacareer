<?php

namespace App\Support;

/**
 * Saudi mobile numbers, normalised to one storage form.
 *
 * The same number reaches us written half a dozen ways — 0551234567,
 * +966 55 123 4567, 966551234567, ٠٥٥١٢٣٤٥٦٧ — and all of them have to resolve
 * to one account. Everything is stored as E.164 without punctuation
 * (+966551234567) and compared in that form.
 */
class Phone
{
    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    private const EXTENDED_ARABIC_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /**
     * Normalise to +9665XXXXXXXX, or null when the input is not a Saudi mobile.
     */
    public static function normalise(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Arabic-Indic digits first — a number typed on an Arabic keyboard is
        // the common case here, not the exception.
        $value = str_replace(self::ARABIC_DIGITS, range(0, 9), $input);
        $value = str_replace(self::EXTENDED_ARABIC_DIGITS, range(0, 9), $value);

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        $national = match (true) {
            str_starts_with($digits, '966') => substr($digits, 3),
            str_starts_with($digits, '00966') => substr($digits, 5),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        // Saudi mobiles are nine digits and always begin with 5.
        if (! preg_match('/^5\d{8}$/', $national)) {
            return null;
        }

        return '+966'.$national;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalise($input) !== null;
    }

    /** Grouped for display: +966 55 123 4567. */
    public static function format(?string $stored): string
    {
        $normalised = self::normalise($stored);

        if ($normalised === null) {
            return (string) $stored;
        }

        $n = substr($normalised, 4);

        return sprintf('+966 %s %s %s', substr($n, 0, 2), substr($n, 2, 3), substr($n, 5));
    }

    /** The number in the form wa.me expects — digits only, no plus. */
    public static function forWhatsapp(?string $stored): ?string
    {
        $normalised = self::normalise($stored);

        return $normalised === null ? null : ltrim($normalised, '+');
    }
}
