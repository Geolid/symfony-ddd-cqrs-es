<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Identity\Infrastructure\Security\SecretHasher;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, identity_id: string, login: string, hash: string}
 */
final class DbalPasswordCredentialProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheCredentialOnPasswordCredentialSet(): void
    {
        // When
        $credential = PasswordCredentialTestFactory::new()->withLogin('buyer@example.com')->create();
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
        $credential = PasswordCredentialTestFactory::new()->create();
        $this->store($credential);
        $hashBeforeChange = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($hashBeforeChange);

        // When
        $reloaded = $this->service(PasswordCredentialRepositoryInterface::class)->load($credential->id());
        $reloaded->change('a new correct horse battery staple', new SecretHasher(), new \DateTimeImmutable('now +00:00'));
        $this->store($reloaded);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertNotSame($hashBeforeChange['hash'], $row['hash']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT id, identity_id, login, hash FROM %s WHERE id = :id', DbalPasswordCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
