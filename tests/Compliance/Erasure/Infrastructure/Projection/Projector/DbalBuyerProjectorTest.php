<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Infrastructure\Projection\Projector;

use Compliance\Erasure\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{buyer_id: string, identity_id: string}
 */
final class DbalBuyerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnBuyerRegisteredIntegrationEvent(): void
    {
        // Given
        $otherIdentityId = Uuid::uuid7()->toString();
        $other = BuyerBuilder::new()->withIdentityId($otherIdentityId)->create();
        $identityId = Uuid::uuid7()->toString();
        $buyer = BuyerBuilder::new()->withIdentityId($identityId)->create();

        // When
        $this->store($other, $buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertSame($identityId, $row['identity_id']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherIdentityId, $otherRow['identity_id']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $buyerId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT buyer_id, identity_id FROM %s WHERE buyer_id = :buyerId', DbalBuyerProjector::TABLE),
            ['buyerId' => $buyerId],
        );
    }
}
