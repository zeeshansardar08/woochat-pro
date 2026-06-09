<?php
declare(strict_types=1);

namespace ZignitesChat\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OptoutTest extends TestCase
{
    public function test_app_secret_notice_only_for_cloud_without_secret(): void
    {
        // Cloud provider + empty secret → notice needed.
        $this->assertTrue(\zignites_chat_meta_app_secret_notice_needed('cloud', ''));
        $this->assertTrue(\zignites_chat_meta_app_secret_notice_needed('cloud', '   '));

        // Cloud provider with a secret → no notice.
        $this->assertFalse(\zignites_chat_meta_app_secret_notice_needed('cloud', 'abc123'));

        // Non-Cloud providers never trigger it, secret or not.
        $this->assertFalse(\zignites_chat_meta_app_secret_notice_needed('twilio', ''));
        $this->assertFalse(\zignites_chat_meta_app_secret_notice_needed('', ''));
    }
}
