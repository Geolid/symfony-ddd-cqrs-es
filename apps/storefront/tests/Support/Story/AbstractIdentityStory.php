<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Storefront\Tests\Support\Persona;
use Support\Foundry\Story\AbstractAggregateStory;

abstract class AbstractIdentityStory extends AbstractAggregateStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly UniquenessRegistryInterface $uniqueness,
        private readonly PasswordHasherInterface $hasher,
        private readonly PasswordStrengthSpecificationInterface $passwordStrength,
    ) {
        parent::__construct($repositories);
    }

    /**
     * @param (\Closure(PasswordCredentialFactory): PasswordCredentialFactory)|null $credential
     *
     * @throws UniquenessViolatedException
     */
    final protected function personaOf(Identity $identity, ?\Closure $credential = null): Persona
    {
        $id = $identity->id->toString();
        $password = PasswordFactory::new()->create()->value;
        $factory = PasswordCredentialFactory::new()
            ->withIdentityId($id)
            ->withPassword($password)
            ->withHasher($this->hasher)
            ->withPasswordStrength($this->passwordStrength);

        $this->uniqueness->claim(UniqueKey::for(IdentityUniqueKey::EMAIL), $identity->email->value, $id);
        $this->store($identity, ($credential ?? static fn (PasswordCredentialFactory $factory): PasswordCredentialFactory => $factory)($factory)->create());

        return new Persona($id, $identity->email->value, $identity->fullName->value, $password);
    }
}
