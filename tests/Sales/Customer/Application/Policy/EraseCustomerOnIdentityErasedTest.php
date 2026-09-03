<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Policy;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Policy\EraseCustomerOnIdentityErased;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class EraseCustomerOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private EraseCustomerOnIdentityErased $policy;
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(EraseCustomerOnIdentityErased::class);
        $this->erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $customer = CustomerBuilder::new()->withId($id)->withEmail('buyer@example.com')->create();
        $this->store($customer);

        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        ($this->policy)(new IdentityErasedIntegrationEvent($id, $this->erasedAt));
        $this->service(CustomerFinderInterface::class)->ofId($id);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        ($this->policy)(new IdentityErasedIntegrationEvent(Uuid::uuid7()->toString(), $this->erasedAt));

        // Then
        self::expectNotToPerformAssertions();
    }
}
