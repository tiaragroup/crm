<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use Webkul\Quote\Models\Quote;

class QuoteEventDateTest extends TestCase
{
    public function test_legacy_zero_event_date_is_treated_as_missing(): void
    {
        $quote = new Quote;
        $quote->setRawAttributes(['event_at' => '0000-00-00 00:00:00']);

        $this->assertNull($quote->event_at);
    }

    public function test_valid_event_date_is_still_cast_to_carbon(): void
    {
        $quote = new Quote;
        $quote->setRawAttributes(['event_at' => '2026-09-26 11:54:00']);

        $this->assertInstanceOf(Carbon::class, $quote->event_at);
        $this->assertSame('2026-09-26 11:54:00', $quote->event_at->format('Y-m-d H:i:s'));
    }
}
