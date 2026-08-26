<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Infrastructure\Persistence\Projection\Projector\DbalCustomerProjector;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{email: ?string}
 */
final class DbalCustomerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCustomerRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertSame('buyer@example.com', $row['email']);
    }

    #[Test]
    public function itRemovesOnCustomerErased(): void
    {
        // Given
        $other = CustomerTestFactory::new()->withEmail('other@example.com')->create();
        $customer = CustomerTestFactory::new()->erased()->create();
        $this->store($other);

        // When
        $this->store($customer);

        // Then
        self::assertFalse($this->fetchRow($customer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame('other@example.com', $otherRow['email']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT email FROM %s WHERE id = :id', DbalCustomerProjector::TABLE),
            ['id' => $id],
        );
    }
}
