<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Projector;

use Compliance\Tests\Erasure\Support\Builder\SubjectBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Infrastructure\Projection\SnakeCaseKeys;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{
 *     buyer_id: string,
 *     shipping_address: string|null,
 *     erasure_pending: bool,
 * }
 */
final class DbalBuyerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnBuyerRegistered(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertNull($row['shipping_address']);
        self::assertFalse((bool) $row['erasure_pending']);
    }

    #[Test]
    public function itProjectsOnBuyerPostalAddressDefined(): void
    {
        // Given
        $otherBuilder = BuyerBuilder::new()->postalAddressDefined();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = BuyerBuilder::new()->postalAddressDefined();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertNotNull($row['shipping_address']);
        self::assertSame(
            SnakeCaseKeys::from($builder['postalAddress']->toArray()),
            $this->decoded($row['shipping_address']),
        );

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotNull($otherRow['shipping_address']);
        self::assertSame(
            SnakeCaseKeys::from($otherBuilder['postalAddress']->toArray()),
            $this->decoded($otherRow['shipping_address']),
        );
    }

    #[Test]
    public function itRemovesOnBuyerErased(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erased()->create();

        // When
        $this->store($buyer);

        // Then
        self::assertFalse($this->fetchRow($buyer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->id->toString(), $otherRow['buyer_id']);
    }

    #[Test]
    public function itProjectsOnSubjectErasureRequestedIntegrationEvent(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->create();
        $subject = SubjectBuilder::new()->withId($buyer->id->toString())->create();

        // When
        $this->store($buyer, $subject);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['erasure_pending']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['erasure_pending']);
    }

    #[Test]
    public function itProjectsOnSubjectErasureCancelledIntegrationEvent(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $otherSubject = SubjectBuilder::new()->withId($other->id->toString())->create();
        $this->store($other, $otherSubject);
        $buyer = BuyerBuilder::new()->create();
        $subject = SubjectBuilder::new()->withId($buyer->id->toString())->cancelled()->create();

        // When
        $this->store($buyer, $subject);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertFalse((bool) $row['erasure_pending']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertTrue((bool) $otherRow['erasure_pending']);
    }

    #[Test]
    public function itProjectsOnSubjectErasedIntegrationEvent(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $otherSubject = SubjectBuilder::new()->withId($other->id->toString())->create();
        $this->store($other, $otherSubject);
        $buyer = BuyerBuilder::new()->create();
        $subject = SubjectBuilder::new()->withId($buyer->id->toString())->released()->create();

        // When
        $this->store($buyer, $subject);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertFalse((bool) $row['erasure_pending']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertTrue((bool) $otherRow['erasure_pending']);
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
    private function fetchRow(string $buyerId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT buyer_id, shipping_address, erasure_pending FROM %s WHERE buyer_id = :buyerId',
                DbalBuyerProjector::TABLE,
            ),
            ['buyerId' => $buyerId],
        );
    }
}
