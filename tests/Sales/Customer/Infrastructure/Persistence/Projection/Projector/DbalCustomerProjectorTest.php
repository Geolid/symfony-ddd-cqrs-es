<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Infrastructure\Persistence\Projection\Projector\DbalCustomerProjector;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{email: ?string, erased_at: ?string, identity_id: ?string}
 */
final class DbalCustomerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheCustomerOnCustomerRegistered(): void
    {
        // When
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('buyer@example.com', $row['email']);
        self::assertNull($row['erased_at']);
    }

    #[Test]
    public function itProjectsTheRedactionOnCustomerErased(): void
    {
        // When
        $customer = CustomerTestFactory::new()->erased()->create();
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id()->toString());
        self::assertNotFalse($row);
        self::assertNull($row['email']);
        self::assertNotNull($row['erased_at']);
    }

    #[Test]
    public function itProjectsTheIdentityIdOnCustomerIdentityLinked(): void
    {
        // Given
        $other = CustomerTestFactory::new()->create();
        $this->store($other);

        // When
        $customer = CustomerTestFactory::new()->linkedToIdentity('identity-1')->create();
        $this->store($customer);

        // Then
        $row = $this->fetchRow($customer->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('identity-1', $row['identity_id']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['identity_id']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT email, erased_at, identity_id FROM %s WHERE id = :id', DbalCustomerProjector::TABLE),
            ['id' => $id],
        );
    }
}
