<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetIdentity;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetIdentityHandler
{
    public function __construct(private IdentityFinderInterface $identityFinder)
    {
    }

    /**
     * @throws IdentityResultNotFoundException
     */
    public function __invoke(GetIdentity $query): IdentityResult
    {
        return $this->identityFinder->ofId($query->id) ?? throw IdentityResultNotFoundException::forId($query->id);
    }
}
