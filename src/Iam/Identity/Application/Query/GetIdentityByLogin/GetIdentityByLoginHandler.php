<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetIdentityByLogin;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetIdentityByLoginHandler
{
    public function __construct(private IdentityFinderInterface $identityFinder)
    {
    }

    /**
     * @throws IdentityResultNotFoundException
     */
    public function __invoke(GetIdentityByLogin $query): IdentityResult
    {
        foreach ($this->identityFinder as $identity) {
            if ($identity->login === $query->login) {
                return $identity;
            }
        }

        throw IdentityResultNotFoundException::forLogin($query->login);
    }
}
