<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetPasswordCredentialByIdentity;

use Iam\Authentication\Application\Query\GetPasswordCredentialByIdentity\GetPasswordCredentialByIdentity;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
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
        $credentialBuilder = PasswordCredentialBuilder::new()
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength);
        $credential = $credentialBuilder->create();
        $this->store($credential);

        // When
        $result = $this->ask(new GetPasswordCredentialByIdentity($credentialBuilder['identityId']));
        $nothing = $this->ask(new GetPasswordCredentialByIdentity(PasswordCredentialBuilder::sample('identityId')));

        // Then
        self::assertNotNull($result);
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($credentialBuilder['identityId'], $result->identityId);
        self::assertSame(
            $credentialBuilder['definedAt']->format(\DateTimeInterface::ATOM),
            $result->changedAt->format(\DateTimeInterface::ATOM),
        );

        self::assertNull($nothing);
    }
}
