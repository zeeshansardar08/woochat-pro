<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PrivacyTest extends TestCase
{
    public function test_phone_match_suffix_returns_last_eight_digits(): void
    {
        $this->assertSame('12345678', \zignites_chat_privacy_phone_match_suffix('+1 (555) 412-345-678'));
    }

    public function test_phone_match_suffix_returns_full_string_when_short(): void
    {
        $this->assertSame('1234567', \zignites_chat_privacy_phone_match_suffix('1234567'));
    }

    public function test_phone_match_suffix_strips_non_digits(): void
    {
        $this->assertSame('23456789', \zignites_chat_privacy_phone_match_suffix('+92 (300) 123-456-789'));
    }

    public function test_phone_match_suffix_returns_empty_for_no_digits(): void
    {
        $this->assertSame('', \zignites_chat_privacy_phone_match_suffix('abc'));
        $this->assertSame('', \zignites_chat_privacy_phone_match_suffix(''));
    }

    public function test_filter_rows_by_normalized_phone_drops_non_matches(): void
    {
        $rows = [
            ['id' => 1, 'phone' => '+1 (555) 123-4567'],   // matches
            ['id' => 2, 'phone' => '+1 (999) 000-0000'],   // does not match
            ['id' => 3, 'phone' => '15551234567'],          // matches (different format, same digits)
            ['id' => 4],                                     // skipped — no phone
            ['id' => 5, 'phone' => 'abc'],                  // skipped — empty after normalisation
        ];
        $lookup = ['15551234567' => true];

        $matched = \zignites_chat_privacy_filter_rows_by_normalized_phone($rows, $lookup);

        $this->assertCount(2, $matched);
        $this->assertSame([1, 3], array_column($matched, 'id'));
    }

    public function test_filter_rows_by_normalized_phone_returns_empty_for_empty_inputs(): void
    {
        $this->assertSame([], \zignites_chat_privacy_filter_rows_by_normalized_phone([], ['x' => true]));
        $this->assertSame([], \zignites_chat_privacy_filter_rows_by_normalized_phone([['phone' => 'x']], []));
    }
}
