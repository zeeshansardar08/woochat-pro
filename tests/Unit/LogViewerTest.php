<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class LogViewerTest extends TestCase
{
    /** @var string|null */
    private $tmp_file = null;

    protected function tearDown(): void
    {
        if ($this->tmp_file !== null && file_exists($this->tmp_file)) {
            unlink($this->tmp_file);
        }
        $this->tmp_file = null;
    }

    private function write_tmp(string $contents): string
    {
        $this->tmp_file = tempnam(sys_get_temp_dir(), 'wcwp-log-test-');
        file_put_contents($this->tmp_file, $contents);
        return $this->tmp_file;
    }

    public function test_parse_line_returns_tag_and_message(): void
    {
        $parsed = \wcwp_log_parse_line('[WooChat Pro] Sent message to ••••1234');
        $this->assertSame('WooChat Pro', $parsed['tag']);
        $this->assertSame('Sent message to ••••1234', $parsed['message']);
        $this->assertSame('[WooChat Pro] Sent message to ••••1234', $parsed['raw']);
    }

    public function test_parse_line_preserves_subtag(): void
    {
        $parsed = \wcwp_log_parse_line('[WooChat Pro - Cart Recovery] Attempt evt_xyz to ••••1234: Hi!');
        $this->assertSame('WooChat Pro - Cart Recovery', $parsed['tag']);
        $this->assertSame('Attempt evt_xyz to ••••1234: Hi!', $parsed['message']);
    }

    public function test_parse_line_handles_unprefixed_lines(): void
    {
        $parsed = \wcwp_log_parse_line('Stray line without prefix');
        $this->assertSame('', $parsed['tag']);
        $this->assertSame('Stray line without prefix', $parsed['message']);
    }

    public function test_parse_line_handles_empty_input(): void
    {
        $parsed = \wcwp_log_parse_line('');
        $this->assertSame('', $parsed['tag']);
        $this->assertSame('', $parsed['message']);
        $this->assertSame('', $parsed['raw']);
    }

    public function test_tail_returns_empty_for_missing_file(): void
    {
        $this->assertSame([], \wcwp_log_tail_lines('/no/such/file.log', 10));
    }

    public function test_tail_returns_empty_for_zero_max_lines(): void
    {
        $file = $this->write_tmp("a\nb\nc\n");
        $this->assertSame([], \wcwp_log_tail_lines($file, 0));
    }

    public function test_tail_returns_empty_for_empty_file(): void
    {
        $file = $this->write_tmp('');
        $this->assertSame([], \wcwp_log_tail_lines($file, 10));
    }

    public function test_tail_reads_all_lines_when_within_limit(): void
    {
        $file = $this->write_tmp("first\nsecond\nthird\n");
        $lines = \wcwp_log_tail_lines($file, 10);
        $this->assertSame(['first', 'second', 'third'], $lines);
    }

    public function test_tail_returns_only_last_n_when_file_is_larger(): void
    {
        $contents = '';
        for ($i = 1; $i <= 1000; $i++) {
            $contents .= "line {$i}\n";
        }
        $file = $this->write_tmp($contents);

        $lines = \wcwp_log_tail_lines($file, 5);

        // Lines come back oldest → newest within the window
        $this->assertSame(
            ['line 996', 'line 997', 'line 998', 'line 999', 'line 1000'],
            $lines
        );
    }

    public function test_tail_handles_file_without_trailing_newline(): void
    {
        $file = $this->write_tmp("alpha\nbravo");
        $lines = \wcwp_log_tail_lines($file, 10);
        $this->assertSame(['alpha', 'bravo'], $lines);
    }

    public function test_tail_strips_blank_lines(): void
    {
        $file = $this->write_tmp("a\n\nb\n\n\nc\n");
        $lines = \wcwp_log_tail_lines($file, 10);
        $this->assertSame(['a', 'b', 'c'], $lines);
    }

    public function test_filter_lines_applies_keyword_case_insensitively(): void
    {
        $lines = [
            '[WooChat Pro] Sent OK',
            '[WooChat Pro] Send failed: timeout',
            '[WooChat Pro - Cart Recovery] Sent reminder',
        ];
        $filtered = \wcwp_log_filter_lines($lines, 'sent');

        $this->assertCount(2, $filtered);
        $this->assertSame('Sent OK', $filtered[0]['message']);
        $this->assertSame('Sent reminder', $filtered[1]['message']);
    }

    public function test_filter_lines_applies_tag_filter(): void
    {
        $lines = [
            '[WooChat Pro] Sent OK',
            '[WooChat Pro - Cart Recovery] Sent reminder',
            '[WooChat Pro - MANUAL] Sent manual',
        ];
        $filtered = \wcwp_log_filter_lines($lines, '', 'WooChat Pro - Cart Recovery');

        $this->assertCount(1, $filtered);
        $this->assertSame('WooChat Pro - Cart Recovery', $filtered[0]['tag']);
    }

    public function test_filter_lines_combines_keyword_and_tag(): void
    {
        $lines = [
            '[WooChat Pro] Sent OK',
            '[WooChat Pro - Cart Recovery] Sent reminder',
            '[WooChat Pro - Cart Recovery] Skipped opted-out number',
        ];
        $filtered = \wcwp_log_filter_lines($lines, 'opted', 'WooChat Pro - Cart Recovery');

        $this->assertCount(1, $filtered);
        $this->assertSame('Skipped opted-out number', $filtered[0]['message']);
    }

    public function test_filter_lines_returns_all_when_filters_empty(): void
    {
        $lines = ['[WooChat Pro] One', '[WooChat Pro] Two'];
        $filtered = \wcwp_log_filter_lines($lines, '', '');

        $this->assertCount(2, $filtered);
    }

    public function test_tags_present_returns_distinct_sorted_tags(): void
    {
        $entries = [
            ['tag' => 'WooChat Pro - Cart Recovery'],
            ['tag' => 'WooChat Pro'],
            ['tag' => 'WooChat Pro - Cart Recovery'],
            ['tag' => ''],
            ['tag' => 'WooChat Pro - MANUAL'],
        ];
        $tags = \wcwp_log_tags_present($entries);

        $this->assertSame(
            ['WooChat Pro', 'WooChat Pro - Cart Recovery', 'WooChat Pro - MANUAL'],
            $tags
        );
    }

    public function test_tags_present_handles_empty_input(): void
    {
        $this->assertSame([], \wcwp_log_tags_present([]));
    }
}
