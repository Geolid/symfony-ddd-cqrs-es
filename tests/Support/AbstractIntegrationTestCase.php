<?php

declare(strict_types=1);

namespace Support;

use Support\Helpers\CqrsTrait;
use Support\Helpers\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractIntegrationTestCase extends KernelTestCase
{
    use CqrsTrait;
    use ServiceLocatorTrait;
}
