<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Repository;

use Iam\Identity\Domain\ApiToken;
use Iam\Identity\Domain\ApiTokenId;
use Iam\Identity\Domain\Exception\ApiTokenNotFoundException;

interface ApiTokenRepositoryInterface
{
    public function has(ApiTokenId $id): bool;

    /**
     * @throws ApiTokenNotFoundException
     */
    public function load(ApiTokenId $id): ApiToken;

    public function save(ApiToken $apiToken): void;
}
