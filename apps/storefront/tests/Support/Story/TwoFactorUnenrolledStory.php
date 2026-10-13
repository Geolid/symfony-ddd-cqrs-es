<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Storefront\Tests\Support\Persona;
use Support\Foundry\Story\AbstractAggregateStory;

use function Zenstruck\Foundry\faker;

/**
 * @method static Persona persona()
 */
final class TwoFactorUnenrolledStory extends AbstractAggregateStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly TotpCipherInterface $cipher,
        private readonly BackupCodeHasherInterface $backupCodeHasher,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $persona = ConfirmedStory::persona();

        $this->store(
            TotpCredentialFactory::new()->withIdentityId($persona->id)->withCipher($this->cipher)->unenrolled()->create(),
            BackupCodeCredentialFactory::new()->withIdentityId($persona->id)->withPlainBackupCodes(faker()->backupCodes())->withBackupCodeHasher($this->backupCodeHasher)->create(),
        );
        $this->addState('persona', $persona);
    }
}
