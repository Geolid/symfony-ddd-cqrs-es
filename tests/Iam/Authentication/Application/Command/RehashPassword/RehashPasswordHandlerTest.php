<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RehashPassword;

use Iam\Authentication\Application\Command\RehashPassword\RehashPassword;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordHasher;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class RehashPasswordHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordStrengthInterface $passwordStrength;

    private PasswordCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = $this->service(PasswordStrengthInterface::class);
        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itRehashes(): void
    {
        // Given
        $this->replace(PasswordHasherInterface::class, new SymfonyPasswordHasher(new NativePasswordHasher(cost: 12)));

        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher(new SymfonyPasswordHasher(new NativePasswordHasher(cost: 4)));
        $credential = $factory->create();
        $this->store($credential);

        $before = $this->finder->ofIdentity($factory['identityId']);

        // When
        $this->dispatch(new RehashPassword($factory['identityId'], $factory['password']->value));

        // Then
        $after = $this->finder->ofIdentity($factory['identityId']);
        self::assertNotSame($before->passwordHash, $after->passwordHash);
    }

    #[Test]
    public function itIgnoresWhenRehashNotNeeded(): void
    {
        // Given
        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->service(PasswordHasherInterface::class));
        $credential = $factory->create();
        $this->store($credential);

        $before = $this->finder->ofIdentity($factory['identityId']);

        // When
        $this->dispatch(new RehashPassword($factory['identityId'], $factory['password']->value));

        // Then
        $after = $this->finder->ofIdentity($factory['identityId']);
        self::assertSame($before->passwordHash, $after->passwordHash);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->dispatch(new RehashPassword(
            PasswordCredentialTestFactory::sample('identityId'),
            PasswordCredentialTestFactory::sample('password')->value,
        ));
    }
}
