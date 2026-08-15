<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webhook\Webhook\CarrierDeliveryParser;
use Webhook\Webhook\PaymentAuthorizedParser;
use Webhook\Webhook\PaymentFailedParser;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'webhook' => [
            'routing' => [
                CarrierDeliveryParser::EVENT_TYPE => [
                    'service' => CarrierDeliveryParser::class,
                    'secret' => '%env(CARRIER_WEBHOOK_SECRET)%',
                ],
                PaymentAuthorizedParser::EVENT_TYPE => [
                    'service' => PaymentAuthorizedParser::class,
                    'secret' => '%env(PAYMENT_WEBHOOK_SECRET)%',
                ],
                PaymentFailedParser::EVENT_TYPE => [
                    'service' => PaymentFailedParser::class,
                    'secret' => '%env(PAYMENT_WEBHOOK_SECRET)%',
                ],
            ],
        ],
    ]);
};
