<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

/**
 * A confirmed identity that can sign in with a password.
 */
final class ConfirmedAccountStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly PasswordHasherInterface $hasher,
        private readonly PasswordStrengthSpecificationInterface $passwordStrength,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmed()->create();
        $password = PasswordCredentialFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength)
            ->create();

        $this->persist($identity, $password);
        $this->addState('account', new Account(
            $identity->id->toString(),
            $identity->email->value,
            $identity->fullName->value,
            password: PasswordCredentialFactory::inputs($password)['password']->value,
        ));
    }
}
