<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Infrastructure\Projection\Projector\DbalCustomerProjector;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{email: string, registered_at: string}
 */
final class DbalCustomerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCustomerRegistered(): void
    {
        // Given
        $builder = CustomerBuilder::new();
        $customer = $builder->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['email']->value, $row['email']);
        self::assertSame($builder['registeredAt']->format('Y-m-d H:i:s'), $row['registered_at']);
    }

    #[Test]
    public function itRemovesOnCustomerErased(): void
    {
        // Given
        $otherBuilder = CustomerBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);
        $customer = CustomerBuilder::new()->erased()->create();

        // When
        $this->store($customer);

        // Then
        self::assertFalse($this->fetchRow($customer->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['email']->value, $otherRow['email']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT email, registered_at FROM %s WHERE id = :id', DbalCustomerProjector::TABLE),
            ['id' => $id],
        );
    }
}
