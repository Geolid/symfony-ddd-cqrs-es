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
 * A confirmed account that signs in with a password.
 *
 * @method static string password()
 */
final class AccountConfirmedStory extends AbstractAccountStory
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
        $password = PasswordFactory::new()->create()->value;
        $identity = IdentityFactory::new()->confirmed()->create();
        $credential = PasswordCredentialFactory::new()
            ->withIdentityId($identity->id->toString())
            ->withPassword($password)
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength)
            ->create();

        $this->store($identity, $credential);
        $this->addIdentityStates($identity);
        $this->addState('password', $password);
    }
}
