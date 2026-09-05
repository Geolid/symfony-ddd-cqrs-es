<?php

declare(strict_types=1);

namespace Shared\Application\Finder\Exception;

use Shared\Application\Exception\ApplicationExceptionInterface;

abstract class ResultNotFoundException extends \RuntimeException implements ApplicationExceptionInterface
{
}
