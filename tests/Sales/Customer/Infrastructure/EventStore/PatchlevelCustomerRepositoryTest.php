<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class PatchlevelCustomerRepositoryTest extends AbstractIntegrationTestCase
{
    private CustomerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CustomerRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();

        // When
        $this->repository->save($customer);
        $loaded = $this->repository->load($customer->id);

        // Then
        self::assertSame($customer->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->repository->load(CustomerId::generate());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->repository->save($customer);

        // When
        $exists = $this->repository->has($customer->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(CustomerId::generate());

        // Then
        self::assertFalse($notExists);
    }
}
