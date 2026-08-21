<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Authentication\Infrastructure\Persistence\Projection\Projector\DbalPasswordCredentialProjector;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\Reason;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{login: string, password_hash: string, identity_authenticatable: bool}
 */
final class DbalPasswordCredentialProjectorTest extends AbstractIntegrationTestCase
{
    private PasswordPolicyInterface $policy;
    private PasswordHasherInterface $hasher;
    private IdentityRepositoryInterface $identityRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(PasswordPolicyInterface::class);
        $this->hasher = $this->service(PasswordHasherInterface::class);
        $this->identityRepository = $this->service(IdentityRepositoryInterface::class);
    }

    #[Test]
    public function itProjectsOnPasswordCredentialDefined(): void
    {
        // When
        $credential = PasswordCredentialTestFactory::new()
            ->withLogin('ada.lovelace')
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->store();

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertSame('ada.lovelace', $row['login']);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itProjectsNewHashOnPasswordCredentialChanged(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()->withPolicy($this->policy)->withHasher($this->hasher)->store();

        // When
        $credential = PasswordCredentialTestFactory::new()
            ->withPassword('Xk9$mQ2vLp7&zR4w')
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->changed('Qm3&nJ8wXv5Tz1p!', $this->policy, $this->hasher)
            ->store();

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue($this->hasher->verify($row['password_hash'], 'Qm3&nJ8wXv5Tz1p!'));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertFalse($this->hasher->verify($otherRow['password_hash'], 'Qm3&nJ8wXv5Tz1p!'));
    }

    #[Test]
    public function itProjectsNewHashOnPasswordCredentialRehashed(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()
            ->withPassword('MyStr0ngP@ssw0rd123!')
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->store();

        // When
        $credential = PasswordCredentialTestFactory::new()
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->rehashed('Xk9$mQ2vLp7&zR4w', $this->hasher)
            ->store();

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue($this->hasher->verify($row['password_hash'], 'Xk9$mQ2vLp7&zR4w'));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertTrue($this->hasher->verify($otherRow['password_hash'], 'MyStr0ngP@ssw0rd123!'));
    }

    #[Test]
    public function itProjectsOnIdentitySuspendedIntegrationEvent(): void
    {
        // Given
        $other = PasswordCredentialTestFactory::new()->withPolicy($this->policy)->withHasher($this->hasher)->store();
        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
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
        $identity = IdentityTestFactory::new()->suspended()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        $row = $this->fetchRow($credential->id->toString());
        self::assertNotFalse($row);
        self::assertTrue((bool) $row['identity_authenticatable']);
    }

    #[Test]
    public function itRemovesOnIdentityErasedIntegrationEvent(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $credential = PasswordCredentialTestFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPolicy($this->policy)
            ->withHasher($this->hasher)
            ->store();

        // When
        $identity = $this->identityRepository->load($identity->id);
        $identity->erase(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // Then
        self::assertFalse($this->fetchRow($credential->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT login, password_hash, identity_authenticatable FROM %s WHERE id = :id', DbalPasswordCredentialProjector::TABLE),
            ['id' => $id],
        );
    }
}
