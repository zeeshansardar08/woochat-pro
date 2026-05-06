<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wcwp_test_options'] = [];
    }

    public function test_normalize_phone_strips_non_digits(): void
    {
        $this->assertSame('15551234567', \wcwp_normalize_phone('+1 (555) 123-4567'));
    }

    public function test_normalize_phone_handles_no_digits(): void
    {
        $this->assertSame('', \wcwp_normalize_phone('abc'));
    }

    public function test_normalize_phone_handles_null_and_empty(): void
    {
        $this->assertSame('', \wcwp_normalize_phone(null));
        $this->assertSame('', \wcwp_normalize_phone(''));
    }

    public function test_normalize_phone_preserves_pure_digits(): void
    {
        $this->assertSame('923001234567', \wcwp_normalize_phone('923001234567'));
    }

    public function test_optout_keyword_match_default_stop(): void
    {
        $this->assertTrue(\wcwp_optout_keyword_match('STOP'));
    }

    public function test_optout_keyword_match_default_unsubscribe(): void
    {
        $this->assertTrue(\wcwp_optout_keyword_match('please unsubscribe me'));
    }

    public function test_optout_keyword_match_no_match(): void
    {
        $this->assertFalse(\wcwp_optout_keyword_match('hello there'));
    }

    public function test_optout_keyword_match_case_insensitive(): void
    {
        $this->assertTrue(\wcwp_optout_keyword_match('Stop'));
        $this->assertTrue(\wcwp_optout_keyword_match('UNSUBSCRIBE'));
    }

    public function test_optout_keyword_match_custom_keywords(): void
    {
        $GLOBALS['wcwp_test_options']['wcwp_optout_keywords'] = 'cancel, opt out';
        $this->assertTrue(\wcwp_optout_keyword_match('I want to cancel'));
        // 'stop' is no longer in the configured keyword list:
        $this->assertFalse(\wcwp_optout_keyword_match('STOP'));
    }

    public function test_optout_keyword_match_empty_input(): void
    {
        $this->assertFalse(\wcwp_optout_keyword_match(''));
    }
}
