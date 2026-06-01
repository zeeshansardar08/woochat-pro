<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MediaTest extends TestCase
{
    public function test_normalize_media_type(): void
    {
        $this->assertSame('image', \zignites_chat_normalize_media_type('image/png'));
        $this->assertSame('image', \zignites_chat_normalize_media_type('image/jpeg'));
        $this->assertSame('document', \zignites_chat_normalize_media_type('application/pdf'));
        $this->assertSame('document', \zignites_chat_normalize_media_type(''));
        $this->assertSame('document', \zignites_chat_normalize_media_type('video/mp4'));
    }

    public function test_media_url_host_allowed(): void
    {
        $allowed = ['shop.example.com'];
        $this->assertTrue(\zignites_chat_media_url_host_allowed('https://shop.example.com/wp-content/uploads/a.jpg', $allowed));
        $this->assertTrue(\zignites_chat_media_url_host_allowed('http://shop.example.com/x.pdf', $allowed));
        // Case-insensitive host match.
        $this->assertTrue(\zignites_chat_media_url_host_allowed('https://SHOP.example.com/x.png', $allowed));
    }

    public function test_media_url_host_rejects_foreign_and_bad_schemes(): void
    {
        $allowed = ['shop.example.com'];
        $this->assertFalse(\zignites_chat_media_url_host_allowed('https://evil.test/x.jpg', $allowed));
        $this->assertFalse(\zignites_chat_media_url_host_allowed('ftp://shop.example.com/x.jpg', $allowed));
        $this->assertFalse(\zignites_chat_media_url_host_allowed('javascript:alert(1)', $allowed));
        $this->assertFalse(\zignites_chat_media_url_host_allowed('', $allowed));
        $this->assertFalse(\zignites_chat_media_url_host_allowed('https://shop.example.com/x.jpg', []));
    }
}
