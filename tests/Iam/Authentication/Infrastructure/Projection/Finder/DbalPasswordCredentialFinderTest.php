<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Doubles\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordStrength;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DbalPasswordCredentialFinderTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialFinderInterface $finder;
    private StubPasswordStrength $passwordStrength;
    private FakePasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
        $this->passwordStrength = new StubPasswordStrength();
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itGetsByLogin(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $factory->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofLogin($factory['login']->value);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($factory['login']->value, $result->login);
        self::assertSame(
            $factory['definedAt']->format(\DateTimeImmutable::ATOM),
            $result->definedAt->format(\DateTimeImmutable::ATOM),
        );
        self::assertSame(
            $factory['definedAt']->format(\DateTimeImmutable::ATOM),
            $result->passwordChangedAt->format(\DateTimeImmutable::ATOM),
        );
        self::assertTrue($result->identityAuthenticatable);

        self::assertSame($this->hasher->hash($factory['password']->value), $result->passwordHash);
    }

    #[Test]
    public function itThrowsWhenLoginNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofLogin(PasswordCredentialTestFactory::sample('login')->value);
    }

    #[Test]
    public function itGetsByIdentity(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $factory->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofIdentity($factory['identityId']);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($factory['identityId'], $result->identityId);
    }

    #[Test]
    public function itThrowsWhenIdentityNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofIdentity(PasswordCredentialTestFactory::sample('identityId'));
    }
}
