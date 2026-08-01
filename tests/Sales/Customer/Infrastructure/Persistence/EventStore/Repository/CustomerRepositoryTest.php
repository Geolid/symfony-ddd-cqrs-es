<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\EventStore\Repository;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\CustomerId;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class CustomerRepositoryTest extends AbstractIntegrationTestCase
{
    private CustomerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CustomerRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsACustomerItSaved(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();

        // When
        $this->repository->save($customer);

        // Then
        $id = $customer->id();
        self::assertTrue($this->repository->has($id));
        self::assertSame('buyer@example.com', $this->repository->load($id)->email()->toString());
    }

    #[Test]
    public function itThrowsOnACustomerItNeverSaved(): void
    {
        // Given
        $id = CustomerId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
