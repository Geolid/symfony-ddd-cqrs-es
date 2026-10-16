<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\ListTrustedDevicesByIdentity;

use Iam\Authentication\Application\Query\ListTrustedDevicesByIdentity\ListTrustedDevicesByIdentity;
use Iam\Tests\Authentication\Support\Factory\TrustedDeviceFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ListTrustedDevicesByIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $first = TrustedDeviceFactory::new()->withIdentityId($identityId)->create();
        $second = TrustedDeviceFactory::new()->withIdentityId($identityId)->create();
        $revoked = TrustedDeviceFactory::new()->withIdentityId($identityId)->revoked()->create();
        $other = TrustedDeviceFactory::new()->create();
        $this->store($first, $second, $revoked, $other);

        // When
        $results = $this->ask(new ListTrustedDevicesByIdentity($identityId));

        // Then
        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing([$first->id->toString(), $second->id->toString()], [$results[0]->id, $results[1]->id]);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();

        // When
        $results = $this->ask(new ListTrustedDevicesByIdentity($identityId));

        // Then
        self::assertSame([], $results);
    }
}
