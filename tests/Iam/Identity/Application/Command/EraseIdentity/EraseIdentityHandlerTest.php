<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Command\RegisterIdentity\RegisterIdentity;
use Iam\Identity\Application\Command\SetPasswordCredential\SetPasswordCredential;
use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class EraseIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErasesTheIdentityAndReleasesTheLogin(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($id));
        $this->dispatch(new SetPasswordCredential($id, 'operator', 'S3cr3t!'));

        // When
        $this->dispatch(new EraseIdentity($id));

        // Then
        $identity = $this->service(IdentityRepositoryInterface::class)->load(IdentityId::fromString($id));
        self::assertTrue($identity->isErased());
    }

    #[Test]
    public function itFreesTheLoginForAnotherIdentity(): void
    {
        // Given
        $erased = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($erased));
        $this->dispatch(new SetPasswordCredential($erased, 'operator', 'S3cr3t!'));
        $this->dispatch(new EraseIdentity($erased));

        // When
        $newIdentity = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($newIdentity));
        $this->dispatch(new SetPasswordCredential($newIdentity, 'operator', 'Another$3cr3t'));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresASecondErasure(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($id));
        $this->dispatch(new SetPasswordCredential($id, 'operator', 'S3cr3t!'));
        $this->dispatch(new EraseIdentity($id));

        // When
        $this->dispatch(new EraseIdentity($id));

        // Then
        $identity = $this->service(IdentityRepositoryInterface::class)->load(IdentityId::fromString($id));
        self::assertTrue($identity->isErased());
    }

    #[Test]
    public function itErasesAnIdentityWithNoPasswordCredentialYet(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($id));

        // When
        $this->dispatch(new EraseIdentity($id));

        // Then
        $identity = $this->service(IdentityRepositoryInterface::class)->load(IdentityId::fromString($id));
        self::assertTrue($identity->isErased());
    }

    #[Test]
    public function itRefusesALoginAlreadyTakenAfterASecondErasure(): void
    {
        // Given
        $erased = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($erased));
        $this->dispatch(new SetPasswordCredential($erased, 'operator', 'S3cr3t!'));
        $this->dispatch(new EraseIdentity($erased));
        $taken = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($taken));
        $this->dispatch(new SetPasswordCredential($taken, 'operator', 'Another$3cr3t'));
        $this->dispatch(new EraseIdentity($erased));

        // Then
        $this->expectException(LoginAlreadyTakenException::class);

        // When
        $newIdentity = Uuid::uuid7()->toString();
        $this->dispatch(new RegisterIdentity($newIdentity));
        $this->dispatch(new SetPasswordCredential($newIdentity, 'operator', 'YetAnother$3cr3t'));
    }
}
