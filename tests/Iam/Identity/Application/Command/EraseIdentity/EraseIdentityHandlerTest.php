<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class EraseIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itErases(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));

        // Then
        $this->expectException(IdentityResultNotFoundException::class);

        $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
    }

    #[Test]
    public function itDropsCipherKey(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->create();
        $this->store($identity);
        $identityId = $identity->id->toString();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $identityId,
            key: 'fake-key',
            method: 'aes256',
            createdAt: Clock::get()->now(),
        ));

        // When
        $this->dispatch(new EraseIdentity($identityId));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($identityId);
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $identity = IdentityBuilder::new()->erasureRequested()->erased()->create();
        $this->store($identity);

        // When
        $this->dispatch(new EraseIdentity($identity->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new EraseIdentity(Uuid::uuid7()->toString()));
    }
}
