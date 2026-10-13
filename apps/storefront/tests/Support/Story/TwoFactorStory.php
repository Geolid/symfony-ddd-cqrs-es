<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Storefront\Tests\Support\Persona;
use Support\Foundry\Story\AbstractAggregateStory;

use function Zenstruck\Foundry\faker;

/**
 * @method static Persona persona()
 */
final class TwoFactorStory extends AbstractAggregateStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly UniquenessRegistryInterface $uniqueness,
        private readonly TotpCipherInterface $cipher,
        private readonly BackupCodeHasherInterface $backupCodeHasher,
    ) {
        parent::__construct($repositories);
    }

    /**
     * @throws UniquenessViolatedException
     */
    public function build(): void
    {
        $persona = ConfirmedStory::persona();
        $totpSecret = faker()->totpSecret();
        $totp = TotpCredentialFactory::new()->withIdentityId($persona->id)->withSecret($totpSecret)->withCipher($this->cipher)->create();
        $plainBackupCodes = faker()->backupCodes();
        $usedBackupCode = $plainBackupCodes[0];
        $backupCodes = BackupCodeCredentialFactory::new()->withIdentityId($persona->id)->withPlainBackupCodes($plainBackupCodes)->withBackupCodeHasher($this->backupCodeHasher)->consumed($usedBackupCode)->create();

        $this->uniqueness->claim(UniqueKey::for(AuthenticationUniqueKey::TOTP_CREDENTIAL_IDENTITY), $persona->id, $totp->id->toString());
        $this->store($totp, $backupCodes);
        $this->addState('persona', $persona->withTotpSecret($totpSecret));
        $this->addToPool('backupCodes', \array_slice($plainBackupCodes, 1));
        $this->addToPool('usedBackupCodes', [$usedBackupCode]);
    }
}
