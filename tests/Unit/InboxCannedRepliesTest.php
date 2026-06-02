<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InboxCannedRepliesTest extends TestCase
{
    public function test_parse_title_and_body(): void
    {
        $rows = \zignites_chat_inbox_parse_canned_replies("Shipping | Ships in 24h\nThanks | Thank you for your order!");
        $this->assertCount(2, $rows);
        $this->assertSame('Shipping', $rows[0]['title']);
        $this->assertSame('Ships in 24h', $rows[0]['body']);
        $this->assertSame('Thanks', $rows[1]['title']);
    }

    public function test_parse_body_only_derives_title(): void
    {
        $rows = \zignites_chat_inbox_parse_canned_replies('Hello there, how can we help?');
        $this->assertCount(1, $rows);
        $this->assertSame('Hello there, how can we help?', $rows[0]['body']);
        $this->assertSame('Hello there, how can we help?', $rows[0]['title']);
    }

    public function test_parse_skips_blank_lines_and_empty_bodies(): void
    {
        $rows = \zignites_chat_inbox_parse_canned_replies("\n  \nReal | Has body\nNoBody |   ");
        $this->assertCount(1, $rows);
        $this->assertSame('Real', $rows[0]['title']);
    }

    public function test_parse_only_first_pipe_splits(): void
    {
        $rows = \zignites_chat_inbox_parse_canned_replies('Title | body with | pipes');
        $this->assertSame('Title', $rows[0]['title']);
        $this->assertSame('body with | pipes', $rows[0]['body']);
    }

    public function test_parse_accepts_structured_array(): void
    {
        $rows = \zignites_chat_inbox_parse_canned_replies([
            ['title' => 'A', 'body' => 'Body A'],
            ['title' => '', 'body' => ''], // dropped (empty body)
        ]);
        $this->assertCount(1, $rows);
        $this->assertSame('A', $rows[0]['title']);
    }

    public function test_to_text_round_trips(): void
    {
        $entries = [
            ['title' => 'Shipping', 'body' => 'Ships in 24h'],
            ['title' => '', 'body' => 'Plain body'],
        ];
        $text = \zignites_chat_inbox_canned_replies_to_text($entries);
        $this->assertSame("Shipping | Ships in 24h\nPlain body", $text);
    }
}
