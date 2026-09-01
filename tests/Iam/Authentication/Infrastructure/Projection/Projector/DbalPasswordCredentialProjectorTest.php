<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Infrastructure\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Tests\Authentication\Support\Doubles\FakePasswordHasher;
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
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private PasswordStrengthInterface $passwordStrength;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordStrength = new StubPasswordStrength();
        $this->hasher = new FakePasswordHasher();
    }

    #[Test]
    public function itProjectsOnPasswordCredentialDefined(): void
    {
        // Given
        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $credential = $factory->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($factory['login']->value, $row['login']);
        self::assertSame($factory['definedAt']->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($factory['definedAt']->format(self::DATE_FORMAT), $row['password_changed_at']);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialChanged(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $newPassword = 'updated-password';
        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->changed($newPassword, $this->passwordStrength, $this->hasher);
        $credential = $factory->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash($newPassword), $row['password_hash']);
        self::assertSame($factory['definedAt']->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($factory['changedAt']->format(self::DATE_FORMAT), $row['password_changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNotSame($this->hasher->hash($newPassword), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialRehashed(): void
    {
        // Given
        $otherFactory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $other = $otherFactory->create();
        $this->store($other);

        $factory = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher);
        $factory = $factory->rehashed($factory['password']->value, $this->hasher);
        $credential = $factory->create();

        // When
        $this->store($credential);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame($this->hasher->hash($factory['password']->value), $row['password_hash']);
        self::assertSame($factory['definedAt']->format(self::DATE_FORMAT), $row['defined_at']);
        self::assertSame($factory['definedAt']->format(self::DATE_FORMAT), $row['password_changed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($this->hasher->hash($otherFactory['password']->value), $otherRow['password_hash']);
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $identity = IdentityTestFactory::new()->suspended()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        // When
        $this->store($credential, $identity);

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
        $otherIdentity = IdentityTestFactory::new()->suspended()->create();
        $other = PasswordCredentialTestFactory::new()
            ->withIdentityId($otherIdentity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        $identity = IdentityTestFactory::new()->suspended()->reactivated()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        // When
        $this->store($other, $otherIdentity, $credential, $identity);

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
        $other = PasswordCredentialTestFactory::new()
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();
        $this->store($other);

        $identity = IdentityTestFactory::new()->erased()->create();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPasswordStrength($this->passwordStrength)
            ->withHasher($this->hasher)
            ->create();

        // When
        $this->store($credential, $identity);

        // Then
        self::assertFalse($this->fetchRow($credential->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
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
