<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser\Capability;

use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait VisitsRouteTrait
{
    /**
     * @param array<string, mixed> $params
     */
    public function visitRoute(string $route, array $params = []): self
    {
        return $this->visit($this->generateRoute($route, $params));
    }

    /**
     * @param array<string, mixed> $params
     */
    public function assertRedirectedToRoute(string $route, array $params = [], int $max = \PHP_INT_MAX): self
    {
        return $this->assertRedirectedTo($this->generateRoute($route, $params), $max);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function generateRoute(string $route, array $params = []): string
    {
        $path = '';

        $this->use(static function (ContainerInterface $container) use ($route, $params, &$path): void {
            /* @phpstan-ignore symfonyContainer.privateService (test container) */
            $path = $container->get(UrlGeneratorInterface::class)->generate($route, $params);
        });

        return $path;
    }
}
