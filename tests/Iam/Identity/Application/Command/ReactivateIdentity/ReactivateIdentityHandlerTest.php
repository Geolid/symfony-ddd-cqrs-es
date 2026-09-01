<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Application\Command\ReactivateIdentity\ReactivateIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ReactivateIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReactivates(): void
    {
        // Given
        $reason = IdentityTestFactory::sample('reason')->value;
        $now = Clock::get()->now();

        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity($identity->id->toString(), $reason));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
        self::assertSame($reason, $result->reason);
        self::assertSame(
            $now->format(\DateTimeImmutable::ATOM),
            $result->reactivatedAt?->format(\DateTimeImmutable::ATOM),
        );
    }

    #[Test]
    public function itIgnoresWhenAlreadyActive(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $this->dispatch(new ReactivateIdentity(
            $identity->id->toString(),
            IdentityTestFactory::sample('reason')->value,
        ));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ReactivateIdentity(
            IdentityTestFactory::sample('id')->toString(),
            IdentityTestFactory::sample('reason')->value,
        ));
    }

    #[Test]
    public function itFailsWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->erased()->create();
        $this->store($identity);

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new ReactivateIdentity(
            $identity->id->toString(),
            IdentityTestFactory::sample('reason')->value,
        ));
    }
}
