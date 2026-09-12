<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Event;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\Pii\ErasedValueObjectSentinel;

#[Event('iam.authentication.password_credential.defined')]
final readonly class PasswordCredentialDefined
{
    public function __construct(
        #[DataSubjectId]
        public PasswordCredentialId $id,
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('erased-%s'), Login::class, 'fromString'))]
        public Login $login,
        public string $passwordHash,
        public \DateTimeImmutable $definedAt,
    ) {
    }
}
