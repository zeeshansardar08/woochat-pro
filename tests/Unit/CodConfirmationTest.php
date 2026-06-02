<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CodConfirmationTest extends TestCase
{
    /* ---- is_cod_gateway ---- */

    public function test_is_cod_gateway(): void
    {
        $this->assertTrue(\zignites_chat_cod_is_cod_gateway('cod', ['cod']));
        $this->assertTrue(\zignites_chat_cod_is_cod_gateway('COD', ['cod'])); // normalized
        $this->assertTrue(\zignites_chat_cod_is_cod_gateway('cheque', ['cod', 'cheque']));
        $this->assertFalse(\zignites_chat_cod_is_cod_gateway('stripe', ['cod']));
        $this->assertFalse(\zignites_chat_cod_is_cod_gateway('', ['cod']));
        $this->assertFalse(\zignites_chat_cod_is_cod_gateway('cod', 'not-an-array'));
    }

    /* ---- classify_reply ---- */

    public function test_classify_confirm(): void
    {
        $this->assertSame('confirm', \zignites_chat_cod_classify_reply('CONFIRM', 'confirm,yes,1', 'cancel,no,2'));
        $this->assertSame('confirm', \zignites_chat_cod_classify_reply('Yes please', 'confirm,yes,1', 'cancel,no,2'));
        $this->assertSame('confirm', \zignites_chat_cod_classify_reply('1', 'confirm,yes,1', 'cancel,no,2'));
    }

    public function test_classify_cancel(): void
    {
        $this->assertSame('cancel', \zignites_chat_cod_classify_reply('Cancel', 'confirm,yes,1', 'cancel,no,2'));
        $this->assertSame('cancel', \zignites_chat_cod_classify_reply('no thanks', 'confirm,yes,1', 'cancel,no,2'));
    }

    public function test_classify_cancel_wins_ambiguous(): void
    {
        // Contains both "no" and "yes" wording — cancel must win.
        $this->assertSame('cancel', \zignites_chat_cod_classify_reply('no, not yes', 'confirm,yes,1', 'cancel,no,2'));
    }

    public function test_classify_no_match(): void
    {
        $this->assertSame('', \zignites_chat_cod_classify_reply('what is this', 'confirm,yes', 'cancel,no'));
        $this->assertSame('', \zignites_chat_cod_classify_reply('', 'confirm', 'cancel'));
        // Whole-word: "yesterday" must not match "yes".
        $this->assertSame('', \zignites_chat_cod_classify_reply('yesterday', 'yes', 'no'));
    }

    /* ---- phone matching ---- */

    public function test_phone_matches_exact_and_formatted(): void
    {
        $this->assertTrue(\zignites_chat_cod_phone_matches('+1 (415) 555-0100', '14155550100'));
        $this->assertTrue(\zignites_chat_cod_phone_matches('14155550100', '14155550100'));
    }

    public function test_phone_matches_tolerates_country_code_via_suffix(): void
    {
        // Local vs full international form, same subscriber number.
        $this->assertTrue(\zignites_chat_cod_phone_matches('03001234567', '923001234567'));
    }

    public function test_phone_matches_rejects_different_numbers(): void
    {
        $this->assertFalse(\zignites_chat_cod_phone_matches('14155550100', '14155559999'));
        $this->assertFalse(\zignites_chat_cod_phone_matches('', '14155550100'));
        // Too short to risk a suffix false-positive.
        $this->assertFalse(\zignites_chat_cod_phone_matches('123', '999123'));
    }

    /* ---- status label ---- */

    public function test_status_label(): void
    {
        $this->assertSame('Awaiting reply', \zignites_chat_cod_status_label('pending'));
        $this->assertSame('Confirmed', \zignites_chat_cod_status_label('confirmed'));
        $this->assertSame('Cancelled', \zignites_chat_cod_status_label('cancelled'));
        $this->assertSame('Send failed', \zignites_chat_cod_status_label('send_failed'));
        $this->assertSame('', \zignites_chat_cod_status_label('whatever'));
        $this->assertSame('', \zignites_chat_cod_status_label(''));
    }

    /* ---- gateways sanitizer ---- */

    public function test_sanitize_gateways_dedupes_and_cleans(): void
    {
        $this->assertSame(['cod', 'cheque'], \zignites_chat_cod_sanitize_gateways(['cod', 'COD', 'cheque', '']));
        $this->assertSame([], \zignites_chat_cod_sanitize_gateways('nope'));
    }
}
