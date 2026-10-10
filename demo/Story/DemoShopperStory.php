<?php

declare(strict_types=1);

namespace Demo\Story;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Identity\Support\Factory\FullNameFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Support\Foundry\Story\AbstractAggregateStory;
use Zenstruck\Foundry\Attribute\AsFixture;

/**
 * A confirmed shopper who signs in to the demo storefront with `demo@example.test`.
 */
#[AsFixture(name: 'demo-shopper', groups: ['demo'])]
final class DemoShopperStory extends AbstractAggregateStory
{
    public const string EMAIL = 'demo@example.test';

    public const string PASSWORD = 'Demo-Shopper-2026!';

    public function __construct(
        RepositoryManager $repositories,
        private readonly PasswordHasherInterface $hasher,
        private readonly PasswordStrengthSpecificationInterface $passwordStrength,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $identity = IdentityFactory::new()->withEmail(EmailFactory::new(['value' => self::EMAIL])->create())->withFullName(FullNameFactory::new(['value' => 'Demo Shopper'])->create())->confirmed()->create();

        $this->persist(
            $identity,
            PasswordCredentialFactory::new()
                ->withIdentityId($identity->id->toString())
                ->withPassword(self::PASSWORD)
                ->withHasher($this->hasher)
                ->withPasswordStrength($this->passwordStrength)
                ->create(),
        );
    }
}
