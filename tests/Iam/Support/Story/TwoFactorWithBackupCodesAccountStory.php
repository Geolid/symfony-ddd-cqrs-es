<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

/**
 * The two-factor account, plus its backup codes. Builds on TwoFactorAccountStory.
 */
final class TwoFactorWithBackupCodesAccountStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly BackupCodeHasherInterface $backupCodeHasher,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $base = TwoFactorAccountStory::account();
        $backupCodes = BackupCodeCredentialFactory::new()->withIdentityId($base->id)->withBackupCodeHasher($this->backupCodeHasher)->create();

        $this->persist($backupCodes);

        $this->addState('account', new Account(
            $base->id,
            $base->email,
            $base->fullName,
            password: $base->password(),
            totpSecret: $base->totpSecret(),
            plainBackupCodes: BackupCodeCredentialFactory::inputs($backupCodes)['plainBackupCodes'],
        ));
    }
}
