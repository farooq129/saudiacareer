<?php

namespace Tests\Unit;

use App\Support\Plural;
use Tests\TestCase;

/**
 * Arabic plural agreement. Getting this wrong produces "2 وظيفة", which reads
 * as broken to every Arabic speaker on the board — and it is exactly what
 * Laravel's own trans_choice() would produce.
 */
class PluralTest extends TestCase
{
    public function test_it_picks_the_arabic_category_for_a_count(): void
    {
        $this->assertSame('one', Plural::category(1));
        $this->assertSame('two', Plural::category(2));
        $this->assertSame('few', Plural::category(3));
        $this->assertSame('few', Plural::category(10));
        $this->assertSame('many', Plural::category(11));
        $this->assertSame('many', Plural::category(100));
        // 103 % 100 = 3, so it agrees like 3 does.
        $this->assertSame('few', Plural::category(103));
        $this->assertSame('many', Plural::category(0));
    }

    public function test_it_renders_arabic_result_counts_with_the_right_agreement(): void
    {
        app()->setLocale('ar');

        $this->assertSame('وظيفة واحدة مطابقة', Plural::of(1, 'results.count'));
        $this->assertSame('وظيفتان مطابقتان', Plural::of(2, 'results.count'));
        $this->assertSame('5 وظائف مطابقة', Plural::of(5, 'results.count'));
        $this->assertSame('30 وظيفة مطابقة', Plural::of(30, 'results.count'));
    }

    public function test_english_uses_the_same_four_keys_so_one_call_site_serves_both(): void
    {
        app()->setLocale('en');

        $this->assertSame('1 matching job', Plural::of(1, 'results.count'));
        $this->assertSame('5 matching jobs', Plural::of(5, 'results.count'));
    }

    public function test_relative_time_steps_from_minutes_to_hours_to_days(): void
    {
        app()->setLocale('en');

        $this->assertSame('23 minutes ago', Plural::ago(23));
        $this->assertSame('1 hour ago', Plural::ago(60));
        $this->assertSame('3 hours ago', Plural::ago(180));
        $this->assertSame('Yesterday', Plural::ago(1440));
        $this->assertSame('3 days ago', Plural::ago(4320));
    }

    public function test_arabic_relative_time_uses_the_dual_where_arabic_does(): void
    {
        app()->setLocale('ar');

        $this->assertSame('قبل دقيقتين', Plural::ago(2));
        $this->assertSame('قبل ساعتين', Plural::ago(120));
        $this->assertSame('أمس', Plural::ago(1440));
        $this->assertSame('قبل يومين', Plural::ago(2880));
    }

    public function test_a_key_that_is_not_a_plural_set_is_still_interpolated(): void
    {
        app()->setLocale('ar');

        // home.seekerCount is a plain string with a :n in it, not a set of
        // plural categories. Reaching for this helper on such a key used to
        // render a literal ":n" on the page.
        $this->assertStringNotContainsString(':n', Plural::of(3184, 'home.seekerCount'));
        $this->assertStringContainsString('3,184', Plural::of(3184, 'home.seekerCount'));
    }

    public function test_large_counts_are_grouped_in_western_digits(): void
    {
        app()->setLocale('ar');

        // Saudi job ads quote figures in Western digits; Arabic-Indic digits in
        // a salary or a result count read as a typo to most of the audience.
        $this->assertStringContainsString('1,530', Plural::of(1530, 'results.count'));
    }
}
