<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetIdentityByEmail;

use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetIdentityByEmailHandler
{
    public function __construct(private IdentityFinderInterface $identityFinder)
    {
    }

    public function __invoke(GetIdentityByEmail $query): ?IdentityResult
    {
        return $this->identityFinder->ofEmailOrNull($query->email);
    }
}
