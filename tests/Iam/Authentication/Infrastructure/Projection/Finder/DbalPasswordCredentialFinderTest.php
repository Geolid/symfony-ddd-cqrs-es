<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Finder;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Double\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Double\StubPasswordStrength;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $other = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofLogin($builder['login']->value);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['login']->value, $result->login);
        self::assertSame(
            $builder['definedAt']->format(\DateTimeInterface::ATOM),
            $result->definedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertSame(
            $builder['definedAt']->format(\DateTimeInterface::ATOM),
            $result->passwordChangedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertTrue($result->identityAuthenticatable);

        self::assertSame($this->hasher->hash($builder['password']->value), $result->passwordHash);
    }

    #[Test]
    public function itThrowsWhenLoginNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofLogin(PasswordCredentialBuilder::sample('login')->value);
    }

    #[Test]
    public function itGetsByIdentity(): void
    {
        // Given
        $other = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $builder = PasswordCredentialBuilder::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $builder->create();
        $this->store($other, $credential);

        // When
        $result = $this->finder->ofIdentity($builder['identityId']);

        // Then
        self::assertSame($credential->id->toString(), $result->id);
        self::assertSame($builder['identityId'], $result->identityId);
    }

    #[Test]
    public function itThrowsWhenIdentityNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->finder->ofIdentity(PasswordCredentialBuilder::sample('identityId'));
    }
}
