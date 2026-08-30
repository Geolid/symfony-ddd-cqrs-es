<?php

declare(strict_types=1);

namespace Support;

use Support\TestCase\CqrsTrait;
use Support\TestCase\EventSourcingTrait;
use Support\TestCase\ServiceLocatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractIntegrationTestCase extends KernelTestCase
{
    use CqrsTrait;
    use EventSourcingTrait;
    use ServiceLocatorTrait;
}
