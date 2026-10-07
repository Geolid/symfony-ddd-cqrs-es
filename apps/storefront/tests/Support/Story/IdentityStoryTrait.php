<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;

trait IdentityStoryTrait
{
    use CredentialBuilderWiringTrait;

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

    protected function confirmationRequestedIdentity(): RegisteredIdentity
    {
        $identityBuilder = IdentityBuilder::new()->confirmationRequested();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new RegisteredIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }

    protected function passwordResetRequestedIdentity(): RegisteredIdentity
    {
        return $this->passwordResetRequestedFor(IdentityBuilder::new()->confirmed());
    }

    protected function passwordResetRequestedIdentityForSuspendedAccount(): RegisteredIdentity
    {
        return $this->passwordResetRequestedFor(IdentityBuilder::new()->confirmed()->suspended());
    }

    protected function confirmedIdentityWithBackupCodes(): BackupCodeEnabledCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialBuilderFor($identity->id->toString());
        $backupCodeBuilder = $this->backupCodeCredentialBuilderFor($identity->id->toString());
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
        $passwordBuilder = $this->passwordCredentialBuilderFor($identity->id->toString());
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
        $passwordBuilder = $this->passwordCredentialBuilderFor($identity->id->toString())->resetRequested();
        $this->store($identity, $passwordBuilder->create());

        return new RegisteredIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }
}
