<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Customer\Domain\Email;

final class EmailTest extends TestCase
{
    #[Test]
    public function itNormalisesTheAddress(): void
    {
        // Given
        $value = '  Buyer@Example.COM ';

        // When
        $email = Email::fromString($value);

        // Then
        self::assertSame('buyer@example.com', $email->toString());
    }

    #[Test]
    public function itFingerprintsTheNormalisedAddress(): void
    {
        // Given
        $email = Email::fromString('  Buyer@Example.COM ');

        // When
        $fingerprint = $email->fingerprint();

        // Then
        self::assertSame(hash('sha256', 'buyer@example.com'), $fingerprint);
    }

    #[Test]
    public function itRefusesAMalformedAddress(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Email::fromString('buyer@');
    }
}
