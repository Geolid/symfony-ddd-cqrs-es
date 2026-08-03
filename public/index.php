<?php

declare(strict_types=1);

use Bootstrap\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context): Kernel {
    $appId = $context['APP_ID']
        ?? throw new \LogicException('The APP_ID environment variable must be set for web requests.');

    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG'], $appId);
};
