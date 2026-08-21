<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\RehashPassword;

use Iam\Authentication\Application\Command\RehashPassword\RehashPassword;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordPolicy;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RehashPasswordHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRehashes(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy(new StubPasswordPolicy())
            ->withHasher(new StubPasswordHasher())
            ->store();

        // When
        $this->dispatch(new RehashPassword($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));

        // Then
        $result = $this->service(PasswordCredentialFinderInterface::class)->ofIdentityId($identity->id->toString());
        self::assertFalse($this->service(PasswordHasherInterface::class)->needsRehash($result->passwordHash));
    }

    #[Test]
    public function itIgnoresWhenNoRehashNeeded(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $hasher = $this->service(PasswordHasherInterface::class);
        PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy(new StubPasswordPolicy())
            ->withHasher($hasher)
            ->store();
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
        $identity = IdentityTestFactory::new()->store();

        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->dispatch(new RehashPassword($identity->id->toString(), 'Xk9$mQ2vLp7&zR4w'));
    }
}
