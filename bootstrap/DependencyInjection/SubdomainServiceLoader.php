<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;

/**
 * Autowires every class under a subdomain that isn't a plain message (Command/Query/Event) or
 * an autoconfiguration attribute, following the one-class-per-file DDD/CQRS convention.
 */
final class SubdomainServiceLoader
{
    public static function load(ServicesConfigurator $services, string $subdomain): void
    {
        $base = '%kernel.project_dir%/src/'.$subdomain;

        $services->load($subdomain.'\\', $base.'/**/Domain/{Repository,Service}/');
        $services->load($subdomain.'\\', $base.'/**/Application/{Command,Query}/**/*Handler.php')
            ->exclude($base.'/**/Application/**/As*.php');
        $services->load($subdomain.'\\', $base.'/**/Application/')
            ->exclude($base.'/**/Application/{Command,Query,Event}/');
        $services->load($subdomain.'\\', $base.'/**/Infrastructure/');
    }
}
