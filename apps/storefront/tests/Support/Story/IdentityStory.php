<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Symfony\Component\Clock\Clock;

trait IdentityStory
{
    abstract protected function service(string $serviceId): object;

    abstract protected function store(AggregateRoot ...$aggregates): void;

    protected function confirmedIdentity(): Credential
    {
        return $this->identityWith(IdentityBuilder::new()->confirmed());
    }

    protected function suspendedIdentity(): Credential
    {
        return $this->identityWith(IdentityBuilder::new()->confirmed()->suspended());
    }

    protected function identityEligibleForEmailChange(): Credential
    {
        return $this->identityWith(
            IdentityBuilder::new()->withRegisteredAt(Clock::get()->now()->modify('-1 hour'))->confirmed(),
        );
    }

    protected function unconfirmedIdentity(): RegisteredIdentity
    {
        $identityBuilder = IdentityBuilder::new();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new RegisteredIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }

    protected function confirmedIdentityWithoutCredential(): RegisteredIdentity
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new RegisteredIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }

    protected function confirmationRequested(): RegisteredIdentity
    {
        $identityBuilder = IdentityBuilder::new()->confirmationRequested();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new RegisteredIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }

    protected function passwordResetRequested(): RegisteredIdentity
    {
        return $this->passwordResetRequestedFor(IdentityBuilder::new()->confirmed());
    }

    protected function passwordResetRequestedForSuspendedAccount(): RegisteredIdentity
    {
        return $this->passwordResetRequestedFor(IdentityBuilder::new()->confirmed()->suspended());
    }

    protected function confirmedIdentityWithBackupCodes(): BackupCodeEnabledCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $backupCodeBuilder = BackupCodeCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withBackupCodeHasher($this->service(BackupCodeHasherInterface::class));
        $this->store($identity, $passwordBuilder->create(), $backupCodeBuilder->create());

        return new BackupCodeEnabledCredential(
            $identityBuilder['email']->value,
            $passwordBuilder['password']->value,
            $backupCodeBuilder['plainBackupCodes'],
        );
    }

    private function identityWith(IdentityBuilder $identityBuilder): Credential
    {
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $this->store($identity, $passwordBuilder->create());

        return new Credential(
            $identity->id->toString(),
            $identityBuilder['email']->value,
            $passwordBuilder['password']->value,
            $identityBuilder['fullName']->value,
        );
    }

    private function passwordResetRequestedFor(IdentityBuilder $identityBuilder): RegisteredIdentity
    {
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class))
            ->resetRequested();
        $this->store($identity, $passwordBuilder->create());

        return new RegisteredIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }
}
