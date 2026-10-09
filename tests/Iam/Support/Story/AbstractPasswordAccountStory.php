<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

/**
 * A Story of this family persists an identity together with the password it signs in with.
 */
abstract class AbstractPasswordAccountStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly PasswordHasherInterface $hasher,
        private readonly PasswordStrengthSpecificationInterface $passwordStrength,
    ) {
        parent::__construct($repositories);
    }

    /**
     * @param ?callable(PasswordCredentialFactory): PasswordCredentialFactory $credential
     */
    final protected function persistWithPassword(IdentityFactory $identityFactory, ?callable $credential = null): void
    {
        $identity = $identityFactory->create();
        $password = PasswordFactory::new()->create()->value;
        $passwordCredential = PasswordCredentialFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword($password)
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength);

        $this->persist($identity, (null !== $credential ? $credential($passwordCredential) : $passwordCredential)->create());
        $this->addState('account', new Account(
            $identity->id->toString(),
            $identity->email->value,
            $identity->fullName->value,
            password: $password,
        ));
    }
}
