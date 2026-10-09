<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Uniqueness;

use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Infrastructure\Uniqueness\ReleaseEmailOnIdentityErased;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReleaseEmailOnIdentityErasedTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itReleases(): void
    {
        // Given
        $identityId = IdentityIdFactory::new()->create()->toString();
        $email = IdentityFactory::sample('email')->value;
        $this->uniqueness->claim(UniqueKey::for(IdentityUniqueKey::EMAIL), $email, $identityId);

        // When
        $this->trigger(ReleaseEmailOnIdentityErased::class, new IdentityErased(
            IdentityId::fromString($identityId),
            Clock::get()->now(),
        ));

        // Then
        $this->uniqueness->claim(UniqueKey::for(IdentityUniqueKey::EMAIL), $email, IdentityIdFactory::new()->create()->toString());
        self::expectNotToPerformAssertions();
    }
}
