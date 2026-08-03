<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Security;

use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Infrastructure\Security\IdentityStatusUserChecker;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class IdentityStatusUserCheckerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itAllowsAnActiveIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // When
        $this->service(IdentityStatusUserChecker::class)->checkPreAuth(new InMemoryUser($identity->id()->toString(), null));

        // Then
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function itRefusesASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // Then
        $this->expectException(CustomUserMessageAccountStatusException::class);

        // When
        $this->service(IdentityStatusUserChecker::class)->checkPreAuth(new InMemoryUser($identity->id()->toString(), null));
    }

    #[Test]
    public function itRefusesAnUnknownIdentity(): void
    {
        // Then
        $this->expectException(CustomUserMessageAccountStatusException::class);

        // When
        $this->service(IdentityStatusUserChecker::class)->checkPreAuth(new InMemoryUser(IdentityId::generate()->toString(), null));
    }
}
