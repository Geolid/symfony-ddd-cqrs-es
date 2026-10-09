<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RehashPassword;

use Iam\Authentication\Application\Command\RehashPassword\RehashPassword;
use Iam\Authentication\Application\Finder\PasswordCredential\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordHasher;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class RehashPasswordHandlerTest extends AbstractIntegrationTestCase
{
    private PasswordStrengthSpecificationInterface $passwordStrength;

    private PasswordCredentialFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = $this->service(PasswordStrengthSpecificationInterface::class);
        $this->finder = $this->service(PasswordCredentialFinderInterface::class);
    }

    #[Test]
    public function itRehashes(): void
    {
        // Given
        $this->replace(PasswordHasherInterface::class, new SymfonyPasswordHasher(new NativePasswordHasher(cost: 12)));
        $password = PasswordFactory::new()->create()->value;

        $credential = PasswordCredentialFactory::new()->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher(new SymfonyPasswordHasher(new NativePasswordHasher(cost: 4)))->create();
        $this->store($credential);

        $before = $this->finder->ofIdentityOrNull($credential->identityId);
        self::assertNotNull($before);

        // When
        $this->dispatch(new RehashPassword($credential->identityId, $password));

        // Then
        $after = $this->finder->ofIdentityOrNull($credential->identityId);
        self::assertNotNull($after);
        self::assertNotSame($before->passwordHash, $after->passwordHash);
    }

    #[Test]
    public function itIgnoresWhenRehashNotNeeded(): void
    {
        // Given
        $password = PasswordFactory::new()->create()->value;
        $credential = PasswordCredentialFactory::new()->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->service(PasswordHasherInterface::class))->create();
        $this->store($credential);

        $before = $this->finder->ofIdentityOrNull($credential->identityId);
        self::assertNotNull($before);

        // When
        $this->dispatch(new RehashPassword($credential->identityId, $password));

        // Then
        $after = $this->finder->ofIdentityOrNull($credential->identityId);
        self::assertNotNull($after);
        self::assertSame($before->passwordHash, $after->passwordHash);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->dispatch(new RehashPassword(
            Uuid::uuid7()->toString(),
            PasswordFactory::new()->create()->value,
        ));
    }
}
