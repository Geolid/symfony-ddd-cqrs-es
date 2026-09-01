<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Tests\Authentication\Support\Doubles\FakePasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordStrength;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PatchlevelPasswordCredentialRepositoryTest extends AbstractIntegrationTestCase
{
    private PasswordCredentialRepositoryInterface $repository;
    private StubPasswordStrength $passwordStrength;
    private FakePasswordHasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(PasswordCredentialRepositoryInterface::class);
        $this->passwordStrength = new StubPasswordStrength();
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $factory->create();

        // When
        $this->repository->save($credential);
        $loaded = $this->repository->load($credential->id);

        // Then
        self::assertSame($factory['id']->toString(), $loaded->id->toString());
        self::assertSame($factory['login']->value, $loaded->login->value);
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(PasswordCredentialNotFoundException::class);

        // When
        $this->repository->load(PasswordCredentialTestFactory::sample('id'));
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->repository->save($credential);

        // When
        $exists = $this->repository->has($credential->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(PasswordCredentialTestFactory::sample('id'));

        // Then
        self::assertFalse($notExists);
    }
}
