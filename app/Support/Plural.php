<?php

namespace App\Support;

/**
 * Arabic plural agreement, for the counts this board shows.
 *
 * Arabic has six plural categories where English has two, and Laravel's
 * trans_choice() speaks the English shape: a list of ranges picked by a single
 * count. Forcing Arabic through it produces "1 وظيفة" and "2 وظيفة", which read
 * as broken to every Arabic speaker who sees them.
 *
 * So the language files carry the four categories that every count on this
 * board actually falls into, and this class picks between them:
 *
 *   one   n = 1                      وظيفة واحدة مطابقة
 *   two   n = 2                      وظيفتان مطابقتان
 *   few   n % 100 between 3 and 10   :n وظائف مطابقة
 *   many  everything else, incl. 0   :n وظيفة مطابقة
 *
 * English files give the same four keys; 'one' carries the singular and the
 * other three carry the plural, so one call site serves both languages.
 *
 * (The two categories left out are 'zero' and the dual-of-hundreds case, which
 * no counter here reaches: an empty result set renders results.empty instead of
 * a count.)
 */
class Plural
{
    /**
     * @param  string  $key  A lang key resolving to an array of the four
     *                       categories, e.g. 'results.count' or 'units.days'.
     */
    public static function of(int $count, string $key, array $replace = []): string
    {
        $set = __($key);

        if (! is_array($set)) {
            // The key points at a plain string rather than a set of plural
            // categories. Still interpolate it — returning it untouched would
            // render a literal ":n" on the page, which is what happens when a
            // caller reaches for this helper on a key that never needed it.
            return is_string($set)
                ? strtr($set, self::replacements($count, $replace))
                : $key;
        }

        $category = self::category($count);
        $line = $set[$category] ?? $set['many'] ?? reset($set);

        return strtr($line, self::replacements($count, $replace));
    }

    /** one | two | few | many, for a count. */
    public static function category(int $count): string
    {
        return match (true) {
            $count === 1 => 'one',
            $count === 2 => 'two',
            $count % 100 >= 3 && $count % 100 <= 10 => 'few',
            default => 'many',
        };
    }

    /**
     * Relative time — "قبل ساعتين" / "2 hours ago" — from a minute count.
     *
     * Anything a day old or more is shown in days, because a job board's
     * listings are read in days and "قبل 72 ساعة" tells the reader nothing the
     * date would not tell them better.
     */
    public static function ago(int $minutes): string
    {
        if ($minutes < 60) {
            return self::of(max(1, (int) round($minutes)), 'units.minutes');
        }

        if ($minutes < 1440) {
            return self::of((int) round($minutes / 60), 'units.hours');
        }

        return self::of((int) round($minutes / 1440), 'units.days');
    }

    /** @return array<string, string> */
    private static function replacements(int $count, array $replace): array
    {
        $out = [':n' => number_format($count, 0, '.', ',')];

        foreach ($replace as $key => $value) {
            $out[':'.ltrim((string) $key, ':')] = (string) $value;
        }

        return $out;
    }
}
