<?php

declare(strict_types=1);

use Bootstrap\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$kernel = new Kernel(getenv('APP_ENV') ?: 'dev', true, 'cli');
$kernel->boot();

return new Application($kernel);
