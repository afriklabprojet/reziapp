<?php

namespace Tests\Unit;

use App\Support\SensitiveData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SensitiveDataTest extends TestCase
{
    #[Test]
    public function it_masks_phone_numbers(): void
    {
        $this->assertSame('+*********7890', SensitiveData::maskPhone('+2250708097890'));
    }

    #[Test]
    public function it_masks_emails(): void
    {
        $this->assertSame('j***@e******.com', SensitiveData::maskEmail('john@example.com'));
    }

    #[Test]
    public function it_masks_ipv4_addresses(): void
    {
        $ipAddress = long2ip(3221226029);

        $this->assertSame('192.0.2.0', SensitiveData::maskIp($ipAddress));
    }

    #[Test]
    public function it_hashes_normalized_values(): void
    {
        $this->assertSame(
            SensitiveData::hash('USER@example.com '),
            SensitiveData::hash('user@example.com'),
        );
    }
}
