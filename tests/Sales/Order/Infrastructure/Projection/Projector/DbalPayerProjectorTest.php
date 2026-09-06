<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Projection\Projector\DbalPayerProjector;
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
    public function itProjectsOnPayerPostalAddressDefined(): void
    {
        // Given
        $otherBuilder = PayerBuilder::new()->postalAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = PayerBuilder::new()->postalAddressDefined();
        $payer = $builder->create();

        // When
        $this->store($payer);

        // Then
        $row = $this->fetchRow($payer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['address']);
        self::assertSame(
            $this->postalAddress($builder['postalAddress']),
            $this->decoded($row['address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['address']);
        self::assertSame(
            $this->postalAddress($otherBuilder['postalAddress']),
            $this->decoded($otherRow['address']),
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

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function postalAddress(PostalAddress $postalAddress): array
    {
        return [
            'recipient_name' => $postalAddress->recipientName,
            'street' => $postalAddress->address->street,
            'postal_code' => $postalAddress->address->postalCode,
            'city' => $postalAddress->address->city,
            'country_code' => $postalAddress->address->countryCode->value,
        ];
    }

    /**
     * @return array{recipient_name: string, street: string, postal_code: string, city: string, country_code: string}
     */
    private function decoded(string $json): array
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
