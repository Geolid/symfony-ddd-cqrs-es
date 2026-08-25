<?php

declare(strict_types=1);

namespace Shared\Application\Command;

use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Port\DrivingPort;

#[DrivingPort]
interface CommandBusInterface
{
    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function dispatch(CommandInterface $command): void;
}
