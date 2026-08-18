<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webhook\Webhook\CarrierDeliveryParser;
use Webhook\Webhook\CarrierPickupConfirmedParser;
use Webhook\Webhook\CarrierReturnPickedUpParser;
use Webhook\Webhook\CarrierReturnReceivedParser;
use Webhook\Webhook\PaymentAuthorizedParser;
use Webhook\Webhook\PaymentFailedParser;
use Webhook\Webhook\PaymentRefundedParser;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'webhook' => [
            'routing' => [
                CarrierPickupConfirmedParser::EVENT_TYPE => [
                    'service' => CarrierPickupConfirmedParser::class,
                    'secret' => '%env(CARRIER_WEBHOOK_SECRET)%',
                ],
                CarrierDeliveryParser::EVENT_TYPE => [
                    'service' => CarrierDeliveryParser::class,
                    'secret' => '%env(CARRIER_WEBHOOK_SECRET)%',
                ],
                CarrierReturnPickedUpParser::EVENT_TYPE => [
                    'service' => CarrierReturnPickedUpParser::class,
                    'secret' => '%env(CARRIER_WEBHOOK_SECRET)%',
                ],
                CarrierReturnReceivedParser::EVENT_TYPE => [
                    'service' => CarrierReturnReceivedParser::class,
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
                PaymentRefundedParser::EVENT_TYPE => [
                    'service' => PaymentRefundedParser::class,
                    'secret' => '%env(PAYMENT_WEBHOOK_SECRET)%',
                ],
            ],
        ],
    ]);
};
