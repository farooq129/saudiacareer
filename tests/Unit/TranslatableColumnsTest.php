<?php

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every Blade on the board reads `$model->name` and trusts the trait to pick
 * the right column, so the fallback rules are worth pinning down.
 */
class TranslatableColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_current_locale_column(): void
    {
        $category = Category::create([
            'key' => 'test',
            'name_ar' => 'الاختبار',
            'name_en' => 'Testing',
        ]);

        app()->setLocale('ar');
        $this->assertSame('الاختبار', $category->name);

        app()->setLocale('en');
        $this->assertSame('Testing', $category->name);
    }

    public function test_it_falls_back_to_the_other_language_rather_than_rendering_blank(): void
    {
        $category = Category::create([
            'key' => 'half',
            'name_ar' => 'نصف مترجم',
            'name_en' => '',
        ]);

        app()->setLocale('en');

        // A half-translated row still shows something — a blank card is worse
        // than an Arabic one on an English page.
        $this->assertSame('نصف مترجم', $category->name);
    }

    public function test_raw_translation_reports_a_missing_translation_honestly(): void
    {
        $category = Category::create([
            'key' => 'half2',
            'name_ar' => 'نصف مترجم',
            'name_en' => '',
        ]);

        $this->assertSame('', $category->rawTranslation('name', 'en'));
        $this->assertTrue($category->missingTranslation('name', 'en'));
        $this->assertFalse($category->missingTranslation('name', 'ar'));
    }

    public function test_the_underlying_columns_are_still_reachable_directly(): void
    {
        $category = Category::create([
            'key' => 'direct',
            'name_ar' => 'مباشر',
            'name_en' => 'Direct',
        ]);

        $this->assertSame('مباشر', $category->name_ar);
        $this->assertSame('Direct', $category->name_en);
    }

    public function test_writing_the_bare_attribute_fills_the_current_locale_column(): void
    {
        app()->setLocale('en');

        $category = new Category(['key' => 'write']);
        $category->name = 'Written in English';
        $category->name_ar = 'بالعربية';
        $category->save();

        $this->assertSame('Written in English', $category->fresh()->name_en);
        $this->assertSame('بالعربية', $category->fresh()->name_ar);
    }
}
