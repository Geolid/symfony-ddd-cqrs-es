<?php

declare(strict_types=1);

namespace Support\TestCase;

use Shared\Tests\Support\TestCase\CqrsTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractIntegrationTestCase extends KernelTestCase
{
    use CqrsTrait;
    use EventSourcingTrait;
    use PolicyTrait;
    use ServiceLocatorTrait;
}
