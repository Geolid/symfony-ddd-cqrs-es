<?php

declare(strict_types=1);

namespace Storefront\Tests\Behat;

final class Kernel extends \Bootstrap\Kernel
{
    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug, 'storefront');
    }
}
