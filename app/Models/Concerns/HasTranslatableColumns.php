<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\App;

/**
 * Resolves `$model->title` to `title_ar` or `title_en` for the current locale.
 *
 * Every visitor-facing string on this board is stored in two columns rather
 * than a JSON blob, so that MySQL can index and full-text search each language
 * on its own. That storage choice would otherwise leak into every Blade as
 * `$job->{'title_'.app()->getLocale()}`; this trait keeps the views reading
 * `$job->title`.
 *
 * A model lists its translatable bases in `$translatable`:
 *
 *     protected array $translatable = ['title', 'excerpt', 'description'];
 *
 * Falling back to the other language when the requested one is empty is
 * deliberate: a half-translated listing should still render something rather
 * than a blank card. Use `rawTranslation()` where the caller needs to know a
 * translation is genuinely missing — an admin's "needs translating" report,
 * for instance.
 */
trait HasTranslatableColumns
{
    public function getAttribute($key)
    {
        if ($this->isTranslatableBase($key)) {
            return $this->translate($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Write through to the current locale's column, so `$job->title = '…'`
     * during an Arabic request fills `title_ar`.
     */
    public function setAttribute($key, $value)
    {
        if ($this->isTranslatableBase($key)) {
            return parent::setAttribute($key.'_'.$this->currentLocale(), $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * The value for a locale, falling back to the other language, then null.
     */
    public function translate(string $base, ?string $locale = null): mixed
    {
        $locale = $locale ?: $this->currentLocale();
        $other = $locale === 'ar' ? 'en' : 'ar';

        $value = parent::getAttribute($base.'_'.$locale);

        if ($value === null || $value === '' || $value === []) {
            return parent::getAttribute($base.'_'.$other);
        }

        return $value;
    }

    /**
     * The stored value for a locale with no fallback — null really means the
     * translation is missing.
     */
    public function rawTranslation(string $base, string $locale): mixed
    {
        return parent::getAttribute($base.'_'.$locale);
    }

    /**
     * True when this model translates `$base` and neither column is filled.
     */
    public function missingTranslation(string $base, string $locale): bool
    {
        $value = $this->rawTranslation($base, $locale);

        return $value === null || $value === '' || $value === [];
    }

    protected function isTranslatableBase(string $key): bool
    {
        // Only intercept the bare base name. `title_ar` must still resolve the
        // ordinary way, or the fallback in translate() would recurse.
        return in_array($key, $this->translatable ?? [], true)
            && ! array_key_exists($key, $this->attributes);
    }

    protected function currentLocale(): string
    {
        return App::getLocale() === 'en' ? 'en' : 'ar';
    }
}
