<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Query\GetPendingErasureByIdentity;

use Compliance\Erasing\Application\Query\GetPendingErasureByIdentity\GetPendingErasureByIdentity;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetPendingErasureByIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $other = ErasureFactory::new()->create();
        $erasure = ErasureFactory::new()->create();
        $this->store($other, $erasure);

        // When
        $result = $this->ask(new GetPendingErasureByIdentity($erasure->identityId));

        // Then
        self::assertNotNull($result);
        self::assertSame($erasure->id->toString(), $result->id);
        self::assertSameDateTime($erasure->requestedAt, $result->requestedAt);
        self::assertSameDateTime($erasure->requestedAt->modify('+30 days'), $result->effectiveAt);
    }

    #[Test]
    public function itGetsNothingWhenCancelled(): void
    {
        // Given
        $erasure = ErasureFactory::new()->cancelled()->create();
        $this->store($erasure);

        // When
        $result = $this->ask(new GetPendingErasureByIdentity($erasure->identityId));

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itGetsNothingWhenNotFound(): void
    {
        // Given
        $unknownIdentityId = Uuid::uuid7()->toString();

        // When
        $result = $this->ask(new GetPendingErasureByIdentity($unknownIdentityId));

        // Then
        self::assertNull($result);
    }
}
