<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Helpers;

use Iam\Identity\Application\Credential\ApiTokenGeneratorInterface;
use Iam\Identity\Application\Credential\GeneratedApiToken;

trait ApiTokenTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    abstract protected function service(string $serviceId): object;

    protected function generateApiKey(): GeneratedApiToken
    {
        return $this->service(ApiTokenGeneratorInterface::class)->generate();
    }

    protected function generateIdentifier(): string
    {
        return $this->generateApiKey()->identifier;
    }
}
