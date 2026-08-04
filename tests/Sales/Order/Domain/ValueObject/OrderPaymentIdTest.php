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
    public function itGenerates(): void
    {
        // When
        $id = OrderPaymentId::generate();

        // Then
        self::assertNotEmpty($id->toString());
    }

    #[Test]
    public function itDerivesTheSameIdForTheSameOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $a = OrderPaymentId::forOrder($orderId);
        $b = OrderPaymentId::forOrder($orderId);

        // Then
        self::assertTrue($a->equals($b));
    }

    #[Test]
    public function itDerivesADifferentIdForADifferentOrder(): void
    {
        // When
        $a = OrderPaymentId::forOrder(Uuid::uuid7()->toString());
        $b = OrderPaymentId::forOrder(Uuid::uuid7()->toString());

        // Then
        self::assertFalse($a->equals($b));
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
}
