<?php

declare(strict_types=1);

namespace Webhook\Tests\Support;

use Bootstrap\Kernel;
use Support\Helpers\EventSourcingTrait;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

abstract class AbstractWebhookTestCase extends WebTestCase
{
    use EventSourcingTrait;
    use ServiceLocatorTrait;

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel('test', false, 'webhook');
    }

    protected static function sign(string $body): string
    {
        $secret = $_ENV['CARRIER_WEBHOOK_SECRET'];
        Assert::stringNotEmpty($secret);

        return 'sha256='.hash_hmac('sha256', $body, $secret);
    }
}
