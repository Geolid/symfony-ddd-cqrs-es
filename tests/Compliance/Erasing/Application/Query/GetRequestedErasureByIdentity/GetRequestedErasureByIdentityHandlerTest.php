<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Application\Query\GetRequestedErasureByIdentity;

use Compliance\Erasing\Application\Query\GetRequestedErasureByIdentity\GetRequestedErasureByIdentity;
use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetRequestedErasureByIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $erasure = ErasureFactory::new()->create();
        $this->store($erasure);

        // When
        $result = $this->ask(new GetRequestedErasureByIdentity($erasure->identityId));

        // Then
        self::assertNotNull($result);
        self::assertSame($erasure->id->toString(), $result->id);
        self::assertSameDateTime($erasure->requestedAt, $result->requestedAt);
        self::assertSameDateTime($erasure->effectiveAt, $result->effectiveAt);
    }
}
