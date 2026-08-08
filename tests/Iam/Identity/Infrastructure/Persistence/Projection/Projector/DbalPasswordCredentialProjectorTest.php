<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, identity_id: string, login: string, hash: string, identity_status: string}
 */
final class DbalPasswordCredentialProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheCredentialOnPasswordCredentialSet(): void
    {
        // When
        $credential = PasswordCredentialTestFactory::new()->withLogin('buyer@example.com')->withHasher(new DummySecretHasher())->create();
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('buyer@example.com', $row['login']);
    }

    #[Test]
    public function itProjectsTheNewHashOnPasswordCredentialChanged(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()->withHasher(new DummySecretHasher())->create();
        $this->store($credential);
        $hashBeforeChange = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($hashBeforeChange);

        // When
        $reloaded = $this->service(PasswordCredentialRepositoryInterface::class)->load($credential->id());
        $reloaded->change('a new correct horse battery staple', $this->service(SecretHasherInterface::class), new \DateTimeImmutable('now +00:00'));
        $this->store($reloaded);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertNotSame($hashBeforeChange['hash'], $row['hash']);
    }

    #[Test]
    public function itProjectsTheIdentityStatusOnPasswordCredentialSet(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);

        // When
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('suspended', $row['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentitySuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);

        // When
        $identity->suspend(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('suspended', $row['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentityReactivated(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())
            ->create();
        $this->store($credential);

        // When
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('active', $row['identity_status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT id, identity_id, login, hash, identity_status FROM %s WHERE id = :id', DbalPasswordCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
