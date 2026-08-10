<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Helpers;

use Iam\Identity\Application\Security\ApiKeyGeneratorInterface;
use Iam\Identity\Application\Security\GeneratedApiKey;

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

    protected function generateApiKey(): GeneratedApiKey
    {
        return $this->service(ApiKeyGeneratorInterface::class)->generate();
    }

    protected function generateIdentifier(): string
    {
        return $this->generateApiKey()->identifier;
    }
}
