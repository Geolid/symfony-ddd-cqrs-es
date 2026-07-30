<?php

declare(strict_types=1);

namespace Shared\Application\Port;

/**
 * Marks an Application port as callable directly by a Delivery Mechanism.
 * Read by the phpat rule Tools\PHPat\DeliveryMechanismTest.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsDrivingPort
{
}
