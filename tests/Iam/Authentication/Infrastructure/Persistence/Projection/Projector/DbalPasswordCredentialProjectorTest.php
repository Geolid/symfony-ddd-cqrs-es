<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordHasher;
use Iam\Tests\Authentication\Support\Doubles\StubPasswordStrength;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{login: string, password_hash: string, defined_at: string, password_changed_at: string, identity_authenticatable: bool}
 */
final class DbalPasswordCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private PasswordStrengthInterface $passwordStrength;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = new StubPasswordStrength();
        $this->hasher = new StubPasswordHasher();
    }

    #[Test]
    public function itProjectsOnPasswordCredentialDefined(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()
            ->withLogin('ada.lovelace')
            ->withDefinedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame('ada.lovelace', $row['login']);
        self::assertSame('2026-01-01 00:00:00', $row['defined_at']);
        self::assertSame('2026-01-01 00:00:00', $row['password_changed_at']);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialChanged(): void
    {
        // Given
        $other = $this->otherCredential();
        $credential = PasswordCredentialTestFactory::new()
            ->withPassword('original-password')
            ->withDefinedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->changed('updated-password', $this->passwordStrength, $this->hasher, new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))
            ->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash('updated-password'), $row['password_hash']);
        self::assertSame('2026-01-01 00:00:00', $row['defined_at']);
        self::assertSame('2026-01-02 00:00:00', $row['password_changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotSame($this->hasher->hash('updated-password'), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialRehashed(): void
    {
        // Given
        $other = $this->otherCredential();
        $credential = PasswordCredentialTestFactory::new()
            ->withDefinedAt(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->rehashed('original-password', $this->hasher, new \DateTimeImmutable('2026-01-02T00:00:00+00:00'))
            ->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash('original-password'), $row['password_hash']);
        self::assertSame('2026-01-01 00:00:00', $row['defined_at']);
        self::assertSame('2026-01-01 00:00:00', $row['password_changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($this->hasher->hash('other-password'), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = $this->otherCredential();
        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->store();

        // When
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertFalse((bool) $row['identity_authenticatable']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertTrue((bool) $otherRow['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnIdentityReactivatedIntegrationEvent(): void
    {
        // Given
        $other = $this->otherCredential(suspended: true);
        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->store();
        $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // When
        $identity->reactivate(Reason::fromString('Appeal upheld'), new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['identity_authenticatable']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse((bool) $otherRow['identity_authenticatable']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $other = $this->otherCredential();
        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->store();

        // When
        $identity->erase(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($credential->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    private function otherCredential(bool $suspended = false, string $password = 'other-password'): PasswordCredential
    {
        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword($password)
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->store();

        if ($suspended) {
            $identity->suspend(Reason::fromString('Suspected fraudulent activity'), new \DateTimeImmutable('now +00:00'));
            $this->store($identity);
        }

        return $credential;
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT login, password_hash, defined_at, password_changed_at, identity_authenticatable FROM %s WHERE id = :id', DbalPasswordCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
