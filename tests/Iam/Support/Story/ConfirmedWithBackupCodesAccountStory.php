<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

use function Zenstruck\Foundry\faker;

/**
 * The confirmed account with a password, plus backup codes and no TOTP. Builds on ConfirmedAccountStory.
 */
final class ConfirmedWithBackupCodesAccountStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly BackupCodeHasherInterface $backupCodeHasher,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $base = ConfirmedAccountStory::account();
        $plainBackupCodes = faker()->backupCodes();

        $this->persist(BackupCodeCredentialFactory::new()->withIdentityId($base->id)->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create());
        $this->addState('account', new Account(
            $base->id,
            $base->email,
            $base->fullName,
            password: $base->password(),
            plainBackupCodes: $plainBackupCodes,
        ));
    }
}
