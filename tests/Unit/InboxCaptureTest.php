<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class InboxCaptureTest extends TestCase
{
    /* ---- Twilio ---- */

    public function test_normalize_twilio_inbound(): void
    {
        $msg = \zignites_chat_inbox_normalize_twilio_inbound([
            'From'        => 'whatsapp:+1 (415) 555-0100',
            'Body'        => 'Hello shop',
            'MessageSid'  => 'SM123',
            'ProfileName' => 'Jane Doe',
        ]);

        $this->assertSame('14155550100', $msg['phone']);
        $this->assertSame('in', $msg['direction']);
        $this->assertSame('Hello shop', $msg['body']);
        $this->assertSame('twilio', $msg['provider']);
        $this->assertSame('SM123', $msg['message_id']);
        $this->assertSame('received', $msg['status']);
        $this->assertSame('Jane Doe', $msg['customer_name']);
    }

    public function test_normalize_twilio_inbound_rejects_empty(): void
    {
        $this->assertNull(\zignites_chat_inbox_normalize_twilio_inbound(['From' => 'whatsapp:+14155550100', 'Body' => '   ']));
        $this->assertNull(\zignites_chat_inbox_normalize_twilio_inbound(['Body' => 'orphan body']));
        $this->assertNull(\zignites_chat_inbox_normalize_twilio_inbound('not-an-array'));
    }

    /* ---- Meta body extraction ---- */

    public function test_extract_meta_message_body_types(): void
    {
        $this->assertSame('hi', \zignites_chat_inbox_extract_meta_message_body(['type' => 'text', 'text' => ['body' => 'hi']]));
        $this->assertSame('Yes', \zignites_chat_inbox_extract_meta_message_body(['type' => 'button', 'button' => ['text' => 'Yes']]));
        $this->assertSame('Confirm', \zignites_chat_inbox_extract_meta_message_body([
            'type' => 'interactive',
            'interactive' => ['button_reply' => ['title' => 'Confirm']],
        ]));
        $this->assertSame('Option A', \zignites_chat_inbox_extract_meta_message_body([
            'type' => 'interactive',
            'interactive' => ['list_reply' => ['title' => 'Option A']],
        ]));
        // Media with caption uses the caption; without, a type placeholder.
        $this->assertSame('see this', \zignites_chat_inbox_extract_meta_message_body(['type' => 'image', 'image' => ['caption' => 'see this']]));
        $this->assertSame('[image]', \zignites_chat_inbox_extract_meta_message_body(['type' => 'image', 'image' => []]));
        $this->assertSame('[location]', \zignites_chat_inbox_extract_meta_message_body(['type' => 'location']));
        $this->assertSame('[contacts]', \zignites_chat_inbox_extract_meta_message_body(['type' => 'contacts']));
    }

    /* ---- Meta payload ---- */

    public function test_normalize_meta_messages_single_text(): void
    {
        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['wa_id' => '14155550100', 'profile' => ['name' => 'Jane']]],
                        'messages' => [[
                            'from' => '14155550100',
                            'id'   => 'wamid.AAA',
                            'type' => 'text',
                            'text' => ['body' => 'hello there'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $messages = \zignites_chat_inbox_normalize_meta_messages($payload);
        $this->assertCount(1, $messages);
        $this->assertSame('14155550100', $messages[0]['phone']);
        $this->assertSame('in', $messages[0]['direction']);
        $this->assertSame('hello there', $messages[0]['body']);
        $this->assertSame('cloud', $messages[0]['provider']);
        $this->assertSame('wamid.AAA', $messages[0]['message_id']);
        $this->assertSame('Jane', $messages[0]['customer_name']);
    }

    public function test_normalize_meta_messages_multiple_and_name_matching(): void
    {
        $payload = [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [
                            ['wa_id' => '111', 'profile' => ['name' => 'Alice']],
                            ['wa_id' => '222', 'profile' => ['name' => 'Bob']],
                        ],
                        'messages' => [
                            ['from' => '111', 'id' => 'w1', 'type' => 'text', 'text' => ['body' => 'one']],
                            ['from' => '222', 'id' => 'w2', 'type' => 'text', 'text' => ['body' => 'two']],
                            ['from' => '333', 'id' => 'w3', 'type' => 'text', 'text' => ['body' => 'three']],
                        ],
                    ],
                ]],
            ]],
        ];

        $messages = \zignites_chat_inbox_normalize_meta_messages($payload);
        $this->assertCount(3, $messages);
        $this->assertSame('Alice', $messages[0]['customer_name']);
        $this->assertSame('Bob', $messages[1]['customer_name']);
        // No contact match → empty name, not a crash.
        $this->assertSame('', $messages[2]['customer_name']);
    }

    public function test_normalize_meta_messages_ignores_status_only_and_malformed(): void
    {
        // Status-only (receipt) payload — no messages[].
        $statusOnly = [
            'entry' => [[
                'changes' => [[
                    'value' => ['statuses' => [['id' => 'wamid.X', 'status' => 'read']]],
                ]],
            ]],
        ];
        $this->assertSame([], \zignites_chat_inbox_normalize_meta_messages($statusOnly));
        $this->assertSame([], \zignites_chat_inbox_normalize_meta_messages([]));
        $this->assertSame([], \zignites_chat_inbox_normalize_meta_messages(['entry' => 'nope']));
    }
}
