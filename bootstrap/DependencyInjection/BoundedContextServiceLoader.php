<?php

declare(strict_types=1);

namespace Bootstrap\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;

final class BoundedContextServiceLoader
{
    public static function load(ServicesConfigurator $services, string $namespace): void
    {
        $relativePath = str_replace('\\', '/', $namespace);
        $base = '%kernel.project_dir%/src/'.$relativePath;
        $realBase = \dirname(__DIR__, 2).'/src/'.$relativePath;
        $prefix = $namespace.'\\';

        $services->load($prefix.'Domain\\', $base.'/Domain/**/{Repository,Service,Specification}/');
        $services->load($prefix.'Application\\', $base.'/Application/{Command,Query}/**/*Handler.php');

        if (is_dir($realBase.'/Application/IntegrationEvent')) {
            $services->load($prefix.'Application\\IntegrationEvent\\', $base.'/Application/IntegrationEvent/**/*Publisher.php');
        }

        $services->load($prefix.'Application\\', $base.'/Application/')
            ->exclude([
                $base.'/Application/{Command,Query,IntegrationEvent}/',
                $base.'/Application/Finder/**/*Result.php',
                $base.'/Application/**/Exception/',
            ]);
        $services->load($prefix.'Infrastructure\\', $base.'/Infrastructure/');
    }
}
