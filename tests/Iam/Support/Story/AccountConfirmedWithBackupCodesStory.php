<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

use function Zenstruck\Foundry\faker;

/**
 * A confirmed account with a password and backup codes, without a TOTP.
 *
 * @method static string                 password()
 * @method static list<non-empty-string> plainBackupCodes()
 */
final class AccountConfirmedWithBackupCodesStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly BackupCodeHasherInterface $backupCodeHasher,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $plainBackupCodes = faker()->backupCodes();

        $this->store(BackupCodeCredentialFactory::new()->withIdentityId(AccountConfirmedStory::id())->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create());
        $this->addState('id', AccountConfirmedStory::id());
        $this->addState('email', AccountConfirmedStory::email());
        $this->addState('fullName', AccountConfirmedStory::fullName());
        $this->addState('password', AccountConfirmedStory::password());
        $this->addState('plainBackupCodes', $plainBackupCodes);
    }
}
