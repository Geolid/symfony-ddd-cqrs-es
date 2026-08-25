<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;

final class SubdomainServiceLoader
{
    public static function load(ServicesConfigurator $services, string $subdomain): void
    {
        $base = '%kernel.project_dir%/src/'.$subdomain;

        $services->load($subdomain.'\\', $base.'/**/Domain/**/{Repository,Service}/');
        $services->load($subdomain.'\\', $base.'/**/Application/{Command,Query}/**/*Handler.php');
        $services->load($subdomain.'\\', $base.'/**/Application/')
            ->exclude($base.'/**/Application/{Command,Query}/');
        $services->load($subdomain.'\\', $base.'/**/Infrastructure/');
    }
}
