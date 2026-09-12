<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Domain\Order\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;

final class OrderIdTest extends TestCase
{
    private const string CHECKOUT_SESSION_ID = '0199a1b2-3c4d-7e5f-8061-72839405a6b7';

    #[Test]
    public function itGenerates(): void
    {
        // When
        $id = OrderId::fromString(Uuid::uuid7()->toString());

        // Then
        self::assertTrue(Uuid::isValid($id->toString()));
    }

    #[Test]
    public function itDerivesKnownId(): void
    {
        // When
        $id = OrderId::forCheckoutSession(self::CHECKOUT_SESSION_ID);

        // Then
        self::assertSame('526bdab0-b284-5479-ae49-8286c465a37f', $id->toString());
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $value): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        OrderId::fromString($value);
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
    public function itEquals(): void
    {
        // Given
        $value = Uuid::uuid7()->toString();
        $a = OrderId::fromString($value);
        $b = OrderId::fromString($value);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = OrderId::fromString(Uuid::uuid7()->toString());
        $b = OrderId::fromString(Uuid::uuid7()->toString());

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
