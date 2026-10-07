<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Builder;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Webmozart\Assert\Assert;

final readonly class AccountBuilder
{
    private IdentityBuilder $identityBuilder;

    /**
     * @param \Closure(class-string): object   $service resolves a service by class-string, same contract as ServiceLocatorTrait::service()
     * @param \Closure(AggregateRoot...): void $store   persists the composed aggregates together
     */
    public function __construct(
        private \Closure $service,
        private \Closure $store,
        ?IdentityBuilder $identityBuilder = null,
        private ?PasswordCredentialBuilder $passwordBuilder = null,
        private ?TotpCredentialBuilder $totpBuilder = null,
        private ?BackupCodeCredentialBuilder $backupCodeBuilder = null,
    ) {
        $this->identityBuilder = $identityBuilder ?? IdentityBuilder::new();
    }

    public function confirmed(): self
    {
        return clone ($this, ['identityBuilder' => $this->identityBuilder->confirmed()]);
    }

    public function suspended(): self
    {
        return clone ($this, ['identityBuilder' => $this->identityBuilder->suspended()]);
    }

    public function confirmationRequested(): self
    {
        return clone ($this, ['identityBuilder' => $this->identityBuilder->confirmationRequested()]);
    }

    public function withPassword(): self
    {
        $hasher = ($this->service)(PasswordHasherInterface::class);
        Assert::isInstanceOf($hasher, PasswordHasherInterface::class);

        $passwordStrength = ($this->service)(PasswordStrengthSpecificationInterface::class);
        Assert::isInstanceOf($passwordStrength, PasswordStrengthSpecificationInterface::class);

        return clone ($this, ['passwordBuilder' => PasswordCredentialBuilder::new()
            ->withHasher($hasher)
            ->withPasswordStrength($passwordStrength)]);
    }

    public function passwordResetRequested(): self
    {
        Assert::notNull($this->passwordBuilder, 'withPassword() must be called before passwordResetRequested().');

        return clone ($this, ['passwordBuilder' => $this->passwordBuilder->resetRequested()]);
    }

    public function withTotp(): self
    {
        $cipher = ($this->service)(TotpCipherInterface::class);
        Assert::isInstanceOf($cipher, TotpCipherInterface::class);

        return clone ($this, ['totpBuilder' => TotpCredentialBuilder::new()->withCipher($cipher)]);
    }

    public function withBackupCodes(): self
    {
        $backupCodeHasher = ($this->service)(BackupCodeHasherInterface::class);
        Assert::isInstanceOf($backupCodeHasher, BackupCodeHasherInterface::class);

        return clone ($this, ['backupCodeBuilder' => BackupCodeCredentialBuilder::new()->withBackupCodeHasher($backupCodeHasher)]);
    }

    public function create(): Account
    {
        $identity = $this->identityBuilder->create();
        $aggregates = [$identity];

        $password = null;
        if (null !== $this->passwordBuilder) {
            $passwordBuilder = $this->passwordBuilder->withIdentityId($identity->id->toString());
            $aggregates[] = $passwordBuilder->create();
            $password = $passwordBuilder['password']->value;
        }

        $totpSecret = null;
        if (null !== $this->totpBuilder) {
            $totpBuilder = $this->totpBuilder->withIdentityId($identity->id->toString());
            $aggregates[] = $totpBuilder->create();
            $totpSecret = $totpBuilder['secret'];
        }

        $plainBackupCodes = null;
        if (null !== $this->backupCodeBuilder) {
            $backupCodeBuilder = $this->backupCodeBuilder->withIdentityId($identity->id->toString());
            $aggregates[] = $backupCodeBuilder->create();
            $plainBackupCodes = $backupCodeBuilder['plainBackupCodes'];
        }

        ($this->store)(...$aggregates);

        return new Account(
            $identity->id->toString(),
            $this->identityBuilder['email']->value,
            $this->identityBuilder['fullName']->value,
            $password,
            $totpSecret,
            $plainBackupCodes,
        );
    }
}
