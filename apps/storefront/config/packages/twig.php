<?php

declare(strict_types=1);

use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeGeneratorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig', [
        'file_name_pattern' => '*.twig',
        'default_path' => '%kernel.project_dir%/apps/storefront/templates',
        'paths' => ['%kernel.project_dir%/ui/templates' => 'ui'],
        'form_themes' => ['@ui/form/pico_layout.html.twig'],
        'globals' => [
            'two_factor_code_pattern' => sprintf('\d{6}|\d{%d}', BackupCodeGeneratorInterface::DIGITS),
        ],
    ]);

    if ('test' === $container->env()) {
        $container->extension('twig', [
            'strict_variables' => true,
        ]);
    }
};
