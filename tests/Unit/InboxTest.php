<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InboxTest extends TestCase
{
    public function test_normalize_direction(): void
    {
        $this->assertSame('in', \zignites_chat_inbox_normalize_direction('in'));
        $this->assertSame('in', \zignites_chat_inbox_normalize_direction('inbound'));
        $this->assertSame('in', \zignites_chat_inbox_normalize_direction('Incoming'));
        $this->assertSame('in', \zignites_chat_inbox_normalize_direction(' received '));
        $this->assertSame('out', \zignites_chat_inbox_normalize_direction('out'));
        $this->assertSame('out', \zignites_chat_inbox_normalize_direction('outbound'));
        // Unknown / typo falls back to 'out' so it never inflates unread.
        $this->assertSame('out', \zignites_chat_inbox_normalize_direction('whatever'));
        $this->assertSame('out', \zignites_chat_inbox_normalize_direction(''));
    }

    public function test_make_excerpt_strips_and_collapses(): void
    {
        $this->assertSame('Hello there', \zignites_chat_inbox_make_excerpt("  Hello\n\t there  "));
        $this->assertSame('Bold text', \zignites_chat_inbox_make_excerpt('<b>Bold</b> text'));
        $this->assertSame('', \zignites_chat_inbox_make_excerpt(''));
    }

    public function test_make_excerpt_truncates_with_ellipsis(): void
    {
        $excerpt = \zignites_chat_inbox_make_excerpt(str_repeat('a', 200), 10);
        $this->assertSame(10, mb_strlen($excerpt));
        $this->assertStringEndsWith('…', $excerpt);
    }

    public function test_window_is_open(): void
    {
        $now = 1_000_000_000; // fixed reference timestamp.
        $just_now = gmdate('Y-m-d H:i:s', $now - 60);
        $twenty_three_h = gmdate('Y-m-d H:i:s', $now - (23 * \HOUR_IN_SECONDS));
        $twenty_five_h = gmdate('Y-m-d H:i:s', $now - (25 * \HOUR_IN_SECONDS));

        $this->assertTrue(\zignites_chat_inbox_window_is_open($just_now, $now));
        $this->assertTrue(\zignites_chat_inbox_window_is_open($twenty_three_h, $now));
        $this->assertFalse(\zignites_chat_inbox_window_is_open($twenty_five_h, $now));
        // No inbound message yet → window closed.
        $this->assertFalse(\zignites_chat_inbox_window_is_open('', $now));
        $this->assertFalse(\zignites_chat_inbox_window_is_open(null, $now));
    }

    public function test_build_message_row_shapes_and_normalizes(): void
    {
        $row = \zignites_chat_inbox_build_message_row(
            [
                'phone'      => '+1 (415) 555-0100',
                'direction'  => 'inbound',
                'body'       => '  hi there  ',
                'provider'   => 'cloud',
                'message_id' => 'wamid.ABC',
                'status'     => 'received',
            ],
            42,
            '2026-06-02 10:00:00'
        );

        $this->assertSame(42, $row['data']['conversation_id']);
        $this->assertSame('14155550100', $row['data']['phone']);
        $this->assertSame('in', $row['data']['direction']);
        $this->assertSame('hi there', $row['data']['body']);
        $this->assertSame('cloud', $row['data']['provider']);
        $this->assertSame('wamid.ABC', $row['data']['message_id']);
        $this->assertSame('received', $row['data']['status']);
        $this->assertSame('2026-06-02 10:00:00', $row['data']['created_at']);
        $this->assertSame(['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'], $row['format']);
    }

    public function test_build_thread_update_inbound_bumps_unread_and_window(): void
    {
        $existing = ['unread_count' => 2, 'customer_name' => ''];
        $update = \zignites_chat_inbox_build_thread_update(
            $existing,
            ['direction' => 'in', 'body' => 'new message', 'customer_name' => 'Jane'],
            '2026-06-02 11:00:00'
        );

        $this->assertSame(3, $update['unread_count']);
        $this->assertSame('in', $update['last_direction']);
        $this->assertSame('new message', $update['last_excerpt']);
        $this->assertSame('2026-06-02 11:00:00', $update['last_message_at']);
        $this->assertSame('2026-06-02 11:00:00', $update['last_inbound_at']);
        // Name filled in because the thread had none.
        $this->assertSame('Jane', $update['customer_name']);
    }

    public function test_build_thread_update_outbound_does_not_bump_unread_or_window(): void
    {
        $existing = ['unread_count' => 4, 'customer_name' => 'Existing Name'];
        $update = \zignites_chat_inbox_build_thread_update(
            $existing,
            ['direction' => 'out', 'body' => 'agent reply', 'customer_name' => 'Override'],
            '2026-06-02 12:00:00'
        );

        $this->assertSame(4, $update['unread_count']);
        $this->assertSame('out', $update['last_direction']);
        // Outbound must not touch the inbound window timestamp.
        $this->assertArrayNotHasKey('last_inbound_at', $update);
        // Existing name is never overwritten.
        $this->assertArrayNotHasKey('customer_name', $update);
    }

    public function test_build_thread_update_new_thread_starts_unread_at_one(): void
    {
        $update = \zignites_chat_inbox_build_thread_update(
            null,
            ['direction' => 'in', 'body' => 'first contact'],
            '2026-06-02 13:00:00'
        );
        $this->assertSame(1, $update['unread_count']);
    }

    public function test_present_thread_shapes_and_casts(): void
    {
        $present = \zignites_chat_inbox_present_thread([
            'id'              => '7',
            'phone'           => '14155550100',
            'customer_name'   => 'Jane',
            'last_excerpt'    => 'see you then',
            'last_direction'  => 'in',
            'unread_count'    => '3',
            'last_message_at' => '2026-06-02 10:00:00',
            'last_inbound_at' => '2026-06-02 09:00:00',
        ]);

        $this->assertSame(7, $present['id']);
        $this->assertSame('Jane', $present['name']);
        $this->assertSame('see you then', $present['excerpt']);
        $this->assertSame(3, $present['unread']);
        $this->assertSame('14155550100', $present['phone']);
        // Missing input → empty, well-formed shape (no notices).
        $this->assertSame([], \zignites_chat_inbox_present_thread('nope'));
    }

    public function test_present_message_shapes_and_normalizes_direction(): void
    {
        $present = \zignites_chat_inbox_present_message([
            'id'         => '12',
            'direction'  => 'inbound',
            'body'       => 'hi',
            'status'     => 'received',
            'created_at' => '2026-06-02 10:00:00',
        ]);
        $this->assertSame(12, $present['id']);
        $this->assertSame('in', $present['direction']);
        $this->assertSame('hi', $present['body']);
        // Unknown direction defaults to 'out'.
        $this->assertSame('out', \zignites_chat_inbox_present_message(['direction' => 'garbage'])['direction']);
        $this->assertSame([], \zignites_chat_inbox_present_message(null));
    }

    public function test_normalize_direction_recognizes_note(): void
    {
        $this->assertSame('note', \zignites_chat_inbox_normalize_direction('note'));
        $this->assertSame('note', \zignites_chat_inbox_normalize_direction(' NOTE '));
    }

    public function test_present_message_carries_note_and_author(): void
    {
        $present = \zignites_chat_inbox_present_message([
            'id'        => 5,
            'direction' => 'note',
            'body'      => 'Called the carrier',
            'author_id' => '12',
        ]);
        $this->assertSame('note', $present['direction']);
        $this->assertSame(12, $present['author_id']);
        // Default author_id is 0 when absent.
        $this->assertSame(0, \zignites_chat_inbox_present_message(['id' => 1])['author_id']);
    }
}
