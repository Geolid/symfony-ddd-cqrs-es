<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Policy;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Policy\EraseCustomerOnIdentityErased;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class EraseCustomerOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private EraseCustomerOnIdentityErased $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(EraseCustomerOnIdentityErased::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $this->store(CustomerTestFactory::new()->withId($id)->withEmail('buyer@example.com')->create());

        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        ($this->policy)(new IdentityErasedIntegrationEvent($id, '2026-01-02T00:00:00+00:00'));
        $this->service(CustomerFinderInterface::class)->ofId($id);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // When
        ($this->policy)(new IdentityErasedIntegrationEvent(Uuid::uuid7()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::expectNotToPerformAssertions();
    }
}
