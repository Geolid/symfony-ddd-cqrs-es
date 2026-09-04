<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Projection\Projector\DbalPayerProjector;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{
 *     payer_id: string,
 *     address: string|null,
 * }
 */
final class DbalPayerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnPayerRegistered(): void
    {
        // Given
        $payer = PayerBuilder::new()->create();

        // When
        $this->store($payer);

        // Then
        $row = $this->fetchRow($payer->id->toString());
        self::assertNotFalse($row);
        self::assertNull($row['address']);
    }

    #[Test]
    public function itProjectsOnPayerAddressRegistered(): void
    {
        // Given
        $otherAddress = PostalAddress::of('John Smith', Address::of('5 rue de la République', '69001', 'Lyon', 'FR'));
        $other = PayerBuilder::new()
            ->addressRegistered($otherAddress)
            ->create();
        $this->store($other);
        $address = $this->address();
        $payer = PayerBuilder::new()
            ->addressRegistered($address)
            ->create();

        // When
        $this->store($payer);

        // Then
        $row = $this->fetchRow($payer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['address']);
        self::assertSame(
            $this->primitiveAddress($address),
            $this->postalAddress($row['address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['address']);
        self::assertSame(
            $this->primitiveAddress($otherAddress),
            $this->postalAddress($otherRow['address']),
        );
    }

    #[Test]
    public function itRemovesOnPayerErased(): void
    {
        // Given
        $other = PayerBuilder::new()->create();
        $this->store($other);
        $payer = PayerBuilder::new()->erased()->create();

        // When
        $this->store($payer);

        // Then
        self::assertFalse($this->fetchRow($payer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id->toString(), $otherRow['payer_id']);
    }

    private function address(): PostalAddress
    {
        return PostalAddress::of('Ada Lovelace', Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
    }

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function primitiveAddress(PostalAddress $address): array
    {
        return [
            'recipient_name' => $address->recipientName,
            'street' => $address->address->street,
            'postal_code' => $address->address->postalCode,
            'city' => $address->address->city,
            'country_code' => $address->address->countryCode->value,
        ];
    }

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function postalAddress(string $json): array
    {
        /** @var array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string} $decoded */
        $decoded = json_decode($json, true);

        return $decoded;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $payerId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT payer_id, address FROM %s WHERE payer_id = :payerId',
                DbalPayerProjector::TABLE,
            ),
            ['payerId' => $payerId],
        );
    }
}
