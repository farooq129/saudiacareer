<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The same Saudi number reaches us written several ways and all of them have to
 * resolve to one account, or a returning user quietly creates a second one.
 */
class PhoneTest extends TestCase
{
    public static function equivalentNumbers(): array
    {
        return [
            'local with leading zero' => ['0551234567'],
            'international with plus' => ['+966551234567'],
            'international without plus' => ['966551234567'],
            'international with 00' => ['00966551234567'],
            'spaced' => ['+966 55 123 4567'],
            'dashed' => ['055-123-4567'],
            'arabic-indic digits' => ['٠٥٥١٢٣٤٥٦٧'],
            'bare national' => ['551234567'],
        ];
    }

    #[DataProvider('equivalentNumbers')]
    public function test_every_written_form_normalises_to_one_stored_value(string $input): void
    {
        $this->assertSame('+966551234567', Phone::normalise($input));
    }

    public function test_it_rejects_what_is_not_a_saudi_mobile(): void
    {
        $this->assertNull(Phone::normalise('0111234567'));   // Riyadh landline
        $this->assertNull(Phone::normalise('+14155550123')); // US number
        $this->assertNull(Phone::normalise('05512345'));     // too short
        $this->assertNull(Phone::normalise('05512345678'));  // too long
        $this->assertNull(Phone::normalise('not a phone'));
        $this->assertNull(Phone::normalise(null));
    }

    public function test_it_formats_for_display_and_for_whatsapp(): void
    {
        $this->assertSame('+966 55 123 4567', Phone::format('0551234567'));
        $this->assertSame('966551234567', Phone::forWhatsapp('0551234567'));
    }
}
