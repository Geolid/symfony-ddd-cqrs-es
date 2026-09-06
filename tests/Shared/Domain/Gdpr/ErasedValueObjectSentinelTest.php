<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Gdpr;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\Gdpr\ErasedValueObjectSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final class ErasedValueObjectSentinelTest extends TestCase
{
    private const string SUBJECT_ID = '10000000-7000-0000-0000-000000000001';

    #[Test]
    public function itBuildsValueObjectFromFallbackArgs(): void
    {
        // Given
        $sentinel = new ErasedValueObjectSentinel(
            new ErasedFieldSentinel(['erased', '00000', 'erased', 'ZZ']),
            Address::class,
            'of',
        );

        // When
        $address = $sentinel(self::SUBJECT_ID);

        // Then
        self::assertInstanceOf(Address::class, $address);
        self::assertSame(Address::of('erased', '00000', 'erased', 'ZZ')->toArray(), $address->toArray());
    }

    #[Test]
    public function itBuildsNestedValueObjectFromComposedSentinel(): void
    {
        // Given
        $sentinel = new ErasedValueObjectSentinel(
            new ErasedFieldSentinel([
                'erased',
                new ErasedValueObjectSentinel(new ErasedFieldSentinel(['erased', '00000', 'erased', 'ZZ']), Address::class, 'of'),
            ]),
            PostalAddress::class,
            'of',
        );

        // When
        $postalAddress = $sentinel(self::SUBJECT_ID);

        // Then
        self::assertInstanceOf(PostalAddress::class, $postalAddress);
        self::assertSame(
            PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'))->toArray(),
            $postalAddress->toArray(),
        );
    }
}
