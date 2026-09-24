<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetIdentityByEmail;

use Iam\Identity\Application\Finder\Identity\IdentityResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?IdentityResult>
 */
final readonly class GetIdentityByEmail implements QueryInterface
{
    public function __construct(public string $email)
    {
    }
}
