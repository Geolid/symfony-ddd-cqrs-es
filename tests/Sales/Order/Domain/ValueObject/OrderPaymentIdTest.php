<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderPaymentId;

final class OrderPaymentIdTest extends TestCase
{
    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = OrderPaymentId::forOrder('0199a1b2-3c4d-7e5f-8061-72839405a6b7');

        // Then
        self::assertSame('35a17cdc-4f76-5c72-93c7-4ec8000e0c08', $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        OrderPaymentId::fromString($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'invalid uuid' => ['not-a-uuid'];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $a = OrderPaymentId::forOrder($orderId);
        $b = OrderPaymentId::forOrder($orderId);

        // Then
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(OrderPaymentId::forOrder(Uuid::uuid7()->toString())));
    }
}
