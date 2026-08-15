<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Identity\Application\Status\IdentityStatus;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\Password;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Identity\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummyPasswordPolicy;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, identity_id: string, login: string, hash: string, identity_status: string}
 */
final class DbalPasswordCredentialProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheCredentialOnPasswordCredentialDefined(): void
    {
        // When
        $credential = PasswordCredentialTestFactory::new()->withLogin('operator')->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())->store();

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('operator', $row['login']);
    }

    #[Test]
    public function itProjectsTheNewHashOnPasswordCredentialChanged(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())->store();
        $hashBeforeChange = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($hashBeforeChange);

        // When
        $reloaded = $this->service(PasswordCredentialRepositoryInterface::class)->load($credential->id());
        $reloaded->change(Password::fromString('a new correct horse battery staple'), $this->service(PasswordPolicyInterface::class), $this->service(SecretHasherInterface::class), new \DateTimeImmutable('now +00:00'));
        $this->store($reloaded);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertNotSame($hashBeforeChange['hash'], $row['hash']);
    }

    #[Test]
    public function itProjectsTheIdentityStatusOnPasswordCredentialDefined(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())
            ->store();

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentitySuspended(): void
    {
        // Given
        $other = IdentityTestFactory::new()->store();
        $otherCredential = PasswordCredentialTestFactory::new()
            ->withIdentityId($other->id()->toString())
            ->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())
            ->store();

        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())
            ->store();

        // When
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::SUSPENDED->value, $row['identity_status']);

        $otherRow = $this->fetchRow($otherCredential->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::ACTIVE->value, $otherRow['identity_status']);
    }

    #[Test]
    public function itUpdatesTheIdentityStatusOnIdentityReactivated(): void
    {
        // Given
        $other = IdentityTestFactory::new()->suspended()->store();
        $otherCredential = PasswordCredentialTestFactory::new()
            ->withIdentityId($other->id()->toString())
            ->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())
            ->store();

        $identity = IdentityTestFactory::new()->suspended()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())
            ->store();

        // When
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(IdentityStatus::ACTIVE->value, $row['identity_status']);

        $otherRow = $this->fetchRow($otherCredential->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(IdentityStatus::SUSPENDED->value, $otherRow['identity_status']);
    }

    #[Test]
    public function itProjectsTheNewHashOnPasswordCredentialRehashed(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())->store();
        $otherHashBeforeRehash = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherHashBeforeRehash);

        $credential = PasswordCredentialTestFactory::new()->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())->store();
        $hashBeforeRehash = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($hashBeforeRehash);

        // When
        $credential->rehash('a new correct horse battery staple', new DummySecretHasher(), new \DateTimeImmutable('now +00:00'));
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id()->toString());
        self::assertNotFalse($row);
        self::assertNotSame($hashBeforeRehash['hash'], $row['hash']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherHashBeforeRehash['hash'], $otherRow['hash']);
    }

    #[Test]
    public function itProjectsTheErasureOnIdentityErased(): void
    {
        // Given
        $other = IdentityTestFactory::new()->store();
        $otherCredential = PasswordCredentialTestFactory::new()
            ->withIdentityId($other->id()->toString())
            ->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())
            ->store();

        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id()->toString())
            ->withHasher(new DummySecretHasher())->withPolicy(new DummyPasswordPolicy())
            ->store();

        // When
        $identity->erase(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($credential->id()->toString()));
        self::assertNotFalse($this->fetchRow($otherCredential->id()->toString()));
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
