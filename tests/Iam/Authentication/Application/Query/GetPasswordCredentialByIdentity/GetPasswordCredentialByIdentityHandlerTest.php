<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity\GetPasswordCredentialByIdentity;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetPasswordCredentialByIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordHasherInterface $hasher;
    private PasswordStrengthSpecificationInterface $passwordStrength;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
    }

    #[Test]
    public function itFinds(): void
    {
        // Given
        $credential = PasswordCredentialFactory::new()
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength)
            ->create();
        $this->store($credential);

        // When
        $result = $this->ask(new GetPasswordCredentialByIdentity($credential->identityId));

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($credential->identityId, $result->identityId);
        self::assertSameDateTime($credential->definedAt, $result->changedAt);
    }

    #[Test]
    public function itFindsNothing(): void
    {
        // Given
        $unknownIdentityId = IdentityIdFactory::new()->create()->toString();

        // When
        $result = $this->ask(new GetPasswordCredentialByIdentity($unknownIdentityId));

        // Then
        self::assertNull($result);
    }
}
