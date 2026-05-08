<?php
declare(strict_types=1);

namespace WooChatPro\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BlocksTest extends TestCase
{
    public function test_button_renders_normalized_phone_and_default_label(): void
    {
        $html = \wcwp_render_whatsapp_button_block([
            'phone' => '+1 (415) 555-0100',
        ]);

        $this->assertStringContainsString('https://wa.me/14155550100', $html);
        $this->assertStringContainsString('Chat on WhatsApp', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener nofollow"', $html);
    }

    public function test_button_appends_preset_message_as_text_query(): void
    {
        $html = \wcwp_render_whatsapp_button_block([
            'phone'   => '14155550100',
            'message' => 'Hi! I have a question.',
            'text'    => 'Talk to us',
        ]);

        $this->assertStringContainsString('Talk to us', $html);
        $this->assertStringContainsString('https://wa.me/14155550100?text=Hi%21+I+have+a+question.', $html);
    }

    public function test_button_with_blank_phone_falls_back_to_picker_url(): void
    {
        // Empty phone should still produce a clickable wa.me link rather
        // than rendering nothing — useful while authoring before the admin
        // has plugged in a number.
        $html = \wcwp_render_whatsapp_button_block(['phone' => '']);

        $this->assertStringContainsString('href="https://wa.me/"', $html);
    }

    public function test_button_carries_alignment_class(): void
    {
        $html = \wcwp_render_whatsapp_button_block([
            'phone' => '14155550100',
            'align' => 'center',
        ]);

        $this->assertStringContainsString('class="wcwp-whatsapp-button-block aligncenter"', $html);
    }
}
