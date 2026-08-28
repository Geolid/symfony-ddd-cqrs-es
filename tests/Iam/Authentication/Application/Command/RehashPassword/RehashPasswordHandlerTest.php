<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RehashPassword;

use Iam\Authentication\Application\Command\RehashPassword\RehashPassword;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Infrastructure\Security\PasswordHasher;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

final class RehashPasswordHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRehashes(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher(new PasswordHasher(new NativePasswordHasher(cost: 4)))
            ->create();
        $this->store($identity, $credential);
        self::getContainer()->set(PasswordHasherInterface::class, new PasswordHasher(new NativePasswordHasher(cost: 12)));

        // When
        $this->dispatch(new RehashPassword($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityId($identity->id->toString());
        self::assertFalse($this->service(PasswordHasherInterface::class)->needsRehash($result->passwordHash));
    }

    #[Test]
    public function itIgnoresWhenRehashNotNeeded(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $hasher = $this->service(PasswordHasherInterface::class);
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPasswordStrength($this->service(PasswordStrengthInterface::class))
            ->withHasher($hasher)
            ->create();
        $this->store($credential);
        $before = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityId($identity->id->toString());

        // When
        $this->dispatch(new RehashPassword($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));

        // Then
        $after = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityId($identity->id->toString());
        self::assertSame($before->passwordHash, $after->passwordHash);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);

        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->dispatch(new RehashPassword($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));
    }
}
