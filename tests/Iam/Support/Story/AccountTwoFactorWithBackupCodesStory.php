<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

use function Zenstruck\Foundry\faker;

/**
 * @method static string                 password()
 * @method static string                 totpSecret()
 * @method static list<non-empty-string> plainBackupCodes()
 */
final class AccountTwoFactorWithBackupCodesStory extends AbstractAccountStory
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

        $this->store(BackupCodeCredentialFactory::new()->withIdentityId(AccountTwoFactorStory::id())->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->create());
        $this->addState('id', AccountTwoFactorStory::id());
        $this->addState('email', AccountTwoFactorStory::email());
        $this->addState('fullName', AccountTwoFactorStory::fullName());
        $this->addState('password', AccountTwoFactorStory::password());
        $this->addState('totpSecret', AccountTwoFactorStory::totpSecret());
        $this->addState('plainBackupCodes', $plainBackupCodes);
    }
}
