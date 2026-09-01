<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Infrastructure\Projection\Projector\DbalCustomerProjector;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{email: string, registered_at: string}
 */
final class DbalCustomerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCustomerRegistered(): void
    {
        // Given
        $now = Clock::get()->now();
        $customer = CustomerBuilder::new()->withEmail('buyer@example.com')->withRegisteredAt($now)->create();

        // When
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id->toString());
        self::assertNotFalse($row);
        self::assertSame('buyer@example.com', $row['email']);
        self::assertSame($now->format('Y-m-d H:i:s'), $row['registered_at']);
    }

    #[Test]
    public function itRemovesOnCustomerErased(): void
    {
        // Given
        $other = CustomerBuilder::new()->withEmail('other@example.com')->create();
        $customer = CustomerBuilder::new()->erased()->create();
        $this->store($other, $customer);

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
            \sprintf('SELECT email, registered_at FROM %s WHERE id = :id', DbalCustomerProjector::TABLE),
            ['id' => $id],
        );
    }
}
