<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Finder;

use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Payer\PayerFinderInterface;
use Sales\Order\Application\Finder\Payer\PayerResult;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalPayerFinderTest extends AbstractIntegrationTestCase
{
    private PayerFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PayerFinderInterface::class);
    }

    #[Test]
    public function itFindsById(): void
    {
        // Given
        $otherPayer = PayerBuilder::new()->create();
        $this->store($otherPayer);
        $address = $this->address();
        $payer = PayerBuilder::new()
            ->addressRegistered($address)
            ->create();
        $this->store($payer);

        // When
        $result = $this->finder->ofIdOrNull($payer->id->toString());

        // Then
        self::assertInstanceOf(PayerResult::class, $result);
        self::assertSame($payer->id->toString(), $result->payerId);
        self::assertNotNull($result->address);
        $addressResult = [
            'recipientName' => $result->address->recipientName,
            'street' => $result->address->street,
            'postalCode' => $result->address->postalCode,
            'city' => $result->address->city,
            'countryCode' => $result->address->countryCode,
        ];
        $expectedAddress = $address->toArray();
        self::assertSame($expectedAddress, $addressResult);
    }

    #[Test]
    public function itFindsWithNoAddress(): void
    {
        // Given
        $payer = PayerBuilder::new()->create();
        $this->store($payer);

        // When
        $result = $this->finder->ofIdOrNull($payer->id->toString());

        // Then
        self::assertInstanceOf(PayerResult::class, $result);
        self::assertSame($payer->id->toString(), $result->payerId);
        self::assertNull($result->address);
    }

    #[Test]
    public function itFindsNoneForUnknownPayer(): void
    {
        // When
        $result = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNull($result);
    }

    private function address(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }
}
