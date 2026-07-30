<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webhook\Webhook\CarrierDeliveryParser;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'webhook' => [
            'routing' => [
                CarrierDeliveryParser::EVENT_TYPE => [
                    'service' => CarrierDeliveryParser::class,
                    'secret' => '%env(CARRIER_WEBHOOK_SECRET)%',
                ],
            ],
        ],
    ]);
};
