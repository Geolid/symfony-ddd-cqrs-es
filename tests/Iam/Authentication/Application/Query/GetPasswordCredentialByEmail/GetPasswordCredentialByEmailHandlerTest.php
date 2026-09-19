<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Query\GetPasswordCredentialByEmail;

use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Query\GetPasswordCredentialByEmail\GetPasswordCredentialByEmail;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\Faker\SeededFaker;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetPasswordCredentialByEmailHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $credentialBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $credential = $credentialBuilder->create();
        $this->store($identity, $credential);

        // When
        $result = $this->ask(new GetPasswordCredentialByEmail($identityBuilder['email']->value));

        // Then
        self::assertSame($identity->id->toString(), $result->identityId);
        self::assertSame($identityBuilder['email']->value, $result->email);
        self::assertTrue($result->identityAuthenticatable);
        self::assertSame(
            $credentialBuilder['definedAt']->format(\DateTimeInterface::ATOM),
            $result->passwordChangedAt->format(\DateTimeInterface::ATOM),
        );
    }

    #[Test]
    public function itFailsWhenEmailNotFound(): void
    {
        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        // When
        $this->ask(new GetPasswordCredentialByEmail(SeededFaker::get()->unique()->safeEmail()));
    }

    #[Test]
    public function itFailsWhenPasswordCredentialNotFound(): void
    {
        // Given
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $this->store($identity);

        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetPasswordCredentialByEmail($identityBuilder['email']->value));
    }
}
