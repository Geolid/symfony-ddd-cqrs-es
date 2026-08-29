<?php

declare(strict_types=1);

namespace Shared\Application\Command;

use Shared\Application\DrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[DrivingPort]
interface CommandBusInterface
{
    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function dispatch(CommandInterface $command): void;
}
