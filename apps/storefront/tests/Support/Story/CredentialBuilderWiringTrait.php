<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\BackupCodeCredentialBuilder;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;

trait CredentialBuilderWiringTrait
{
    abstract protected function service(string $serviceId): object;

    private function passwordCredentialBuilderFor(string $identityId): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identityId)
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
    }

    private function backupCodeCredentialBuilderFor(string $identityId): BackupCodeCredentialBuilder
    {
        return BackupCodeCredentialBuilder::new()
            ->withIdentityId($identityId)
            ->withBackupCodeHasher($this->service(BackupCodeHasherInterface::class));
    }
}
