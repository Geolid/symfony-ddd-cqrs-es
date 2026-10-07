<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;

trait TotpEnabledIdentityStoryTrait
{
    use CredentialBuilderWiringTrait;

    abstract protected function service(string $serviceId): object;

    abstract protected function store(AggregateRoot ...$aggregates): void;

    protected function confirmedIdentityWithTotp(): TotpEnabledCredential
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = $this->passwordCredentialBuilderFor($identity->id->toString());
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
        $passwordBuilder = $this->passwordCredentialBuilderFor($identity->id->toString());
        $totpBuilder = TotpCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withCipher($this->service(TotpCipherInterface::class));
        $backupCodeBuilder = $this->backupCodeCredentialBuilderFor($identity->id->toString());
        $this->store($identity, $passwordBuilder->create(), $totpBuilder->create(), $backupCodeBuilder->create());

        return new TotpEnabledCredential($identityBuilder['email']->value, $passwordBuilder['password']->value, $totpBuilder['secret']);
    }
}
