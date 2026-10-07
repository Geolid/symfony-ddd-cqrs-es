<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;

trait TotpEnabledIdentityStory
{
    abstract protected function service(string $serviceId): object;

    abstract protected function store(AggregateRoot ...$aggregates): void;

    protected function confirmedIdentityWithTotp(): TotpEnabledCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $totpBuilder = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class));
        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create());

        return new TotpEnabledCredential($identityBuilder['email']->value, $passwordBuilder['password']->value, $totpBuilder['secret']);
    }

    protected function confirmedIdentityWithTotpAndBackupCodes(): TotpEnabledCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $totpBuilder = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class));
        $backupCodeBuilder = BackupCodeCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withBackupCodeHasher($this->service(BackupCodeHasherInterface::class));
        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create(), $backupCodeBuilder->create());

        return new TotpEnabledCredential($identityBuilder['email']->value, $passwordBuilder['password']->value, $totpBuilder['secret']);
    }
}
