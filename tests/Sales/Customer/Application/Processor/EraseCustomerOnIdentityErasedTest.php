<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Processor;

use Iam\Identity\Application\Event\IdentityErasedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Processor\EraseCustomerOnIdentityErased;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class EraseCustomerOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private EraseCustomerOnIdentityErased $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(EraseCustomerOnIdentityErased::class);
    }

    #[Test]
    public function itErasesTheCustomerOnIdentityErased(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        CustomerTestFactory::new()->withId($id)->withEmail('buyer@example.com')->store();

        // When
        ($this->processor)(new IdentityErasedIntegrationEvent($id, '2026-01-02T00:00:00+00:00'));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofId($id);
        self::assertNull($result->email);
        self::assertNotNull($result->erasedAt);
    }

    #[Test]
    public function itDoesNothingWhenNoCustomerExistsForTheIdentity(): void
    {
        // When
        ($this->processor)(new IdentityErasedIntegrationEvent(Uuid::uuid7()->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        self::expectNotToPerformAssertions();
    }
}
