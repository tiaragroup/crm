<?php

namespace Tests\Unit;

use Tests\TestCase;
use Webkul\Core\Core;

class CurrencyFormattingTest extends TestCase
{
    public function test_currency_helpers_work_without_intl_in_both_locales(): void
    {
        if (class_exists(\NumberFormatter::class)) {
            $this->markTestSkipped('Run with intl disabled to exercise the dashboard fallback.');
        }

        // These helpers have no repository dependencies; avoid any database access.
        $core = (new \ReflectionClass(Core::class))->newInstanceWithoutConstructor();
        config(['app.currency' => 'SAR']);

        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);
            $this->assertSame('SAR', $core->currencySymbol('SAR'));
            $this->assertSame('1,234.50 SAR', $core->formatBasePrice('1234.50'));
            $this->assertSame('0.00 SAR', $core->formatBasePrice(null));
            $this->assertSame('-12.25 SAR', $core->formatBasePrice('-12.25'));
        }
    }

    public function test_fallback_respects_currency_fraction_digits(): void
    {
        if (class_exists(\NumberFormatter::class)) {
            $this->markTestSkipped('Requires intl disabled.');
        }

        $core = (new \ReflectionClass(Core::class))->newInstanceWithoutConstructor();
        config(['app.currency' => 'JPY']);
        $this->assertSame('1,234 JPY', $core->formatBasePrice('1234'));
        config(['app.currency' => 'KWD']);
        $this->assertSame('1.234 KWD', $core->formatBasePrice('1.234'));
    }
}
