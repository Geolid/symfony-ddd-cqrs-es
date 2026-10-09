<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Builder;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Factory\BackupCodeCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use OTPHP\TOTP;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Webmozart\Assert\Assert;

final readonly class AccountBuilder
{
    private IdentityFactory $identityFactory;

    /**
     * @param \Closure(class-string): object   $service resolves a service by class-string, same contract as ServiceLocatorTrait::service()
     * @param \Closure(AggregateRoot...): void $store   persists the composed aggregates together
     */
    public function __construct(
        private \Closure $service,
        private \Closure $store,
        ?IdentityFactory $identityFactory = null,
        private ?PasswordCredentialFactory $passwordFactory = null,
        private ?TotpCredentialFactory $totpFactory = null,
        private ?BackupCodeCredentialFactory $backupCodeFactory = null,
    ) {
        $this->identityFactory = $identityFactory ?? IdentityFactory::new();
    }

    public function confirmed(): self
    {
        return clone ($this, ['identityFactory' => $this->identityFactory->confirmed()]);
    }

    public function suspended(): self
    {
        return clone ($this, ['identityFactory' => $this->identityFactory->suspended()]);
    }

    public function confirmationRequested(): self
    {
        return clone ($this, ['identityFactory' => $this->identityFactory->confirmationRequested()]);
    }

    public function erased(): self
    {
        return clone ($this, ['identityFactory' => $this->identityFactory->erasureRequested()->erased()]);
    }

    public function withPassword(): self
    {
        $hasher = ($this->service)(PasswordHasherInterface::class);
        Assert::isInstanceOf($hasher, PasswordHasherInterface::class);

        $passwordStrength = ($this->service)(PasswordStrengthSpecificationInterface::class);
        Assert::isInstanceOf($passwordStrength, PasswordStrengthSpecificationInterface::class);

        return clone ($this, ['passwordFactory' => PasswordCredentialFactory::new()
            ->withHasher($hasher)
            ->withPasswordStrength($passwordStrength)]);
    }

    public function passwordResetRequested(): self
    {
        Assert::notNull($this->passwordFactory, 'withPassword() must be called before passwordResetRequested().');

        return clone ($this, ['passwordFactory' => $this->passwordFactory->resetRequested()]);
    }

    public function withTotp(): self
    {
        $cipher = ($this->service)(TotpCipherInterface::class);
        Assert::isInstanceOf($cipher, TotpCipherInterface::class);

        return clone ($this, ['totpFactory' => TotpCredentialFactory::new()->withCipher($cipher)]);
    }

    public function withBackupCodes(): self
    {
        $backupCodeHasher = ($this->service)(BackupCodeHasherInterface::class);
        Assert::isInstanceOf($backupCodeHasher, BackupCodeHasherInterface::class);

        return clone ($this, ['backupCodeFactory' => BackupCodeCredentialFactory::new()->withBackupCodeHasher($backupCodeHasher)]);
    }

    public function create(): Account
    {
        $identity = $this->identityFactory->create();
        $aggregates = [$identity];

        $password = null;
        if (null !== $this->passwordFactory) {
            $password = PasswordFactory::new()->create()->value;
            $aggregates[] = $this->passwordFactory->withIdentityId($identity->id->toString())->withPassword($password)->create();
        }

        $totpSecret = null;
        if (null !== $this->totpFactory) {
            $totpSecret = TOTP::generate()->getSecret();
            $aggregates[] = $this->totpFactory->withIdentityId($identity->id->toString())->withSecret($totpSecret)->create();
        }

        $plainBackupCodes = null;
        if (null !== $this->backupCodeFactory) {
            $plainBackupCodes = [bin2hex(random_bytes(5)), bin2hex(random_bytes(5))];
            $aggregates[] = $this->backupCodeFactory->withIdentityId($identity->id->toString())->withPlainBackupCodes($plainBackupCodes)->create();
        }

        ($this->store)(...$aggregates);

        return new Account(
            $identity->id->toString(),
            $identity->email->value,
            $identity->fullName->value,
            $password,
            $totpSecret,
            $plainBackupCodes,
        );
    }
}
