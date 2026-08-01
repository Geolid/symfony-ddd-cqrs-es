<?php

declare(strict_types=1);

namespace Shared\Application\Exception;

use Shared\Application\Language\PublishedLanguageInterface;

interface ApplicationExceptionInterface extends \Throwable, PublishedLanguageInterface
{
}
