<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Query\GetIdentity;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Query\GetIdentity\GetIdentity;
use Iam\Identity\Domain\IdentityId;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAnIdentityById(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $this->store(IdentityTestFactory::new()->create());

        // When
        $result = $this->ask(new GetIdentity($identity->id()->toString()));

        // Then
        self::assertSame($identity->id()->toString(), $result->id);
        self::assertSame('active', $result->status);
    }

    #[Test]
    public function itFailsWhenTheIdentityDoesNotExist(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->ask(new GetIdentity(IdentityId::generate()->toString()));
    }
}
