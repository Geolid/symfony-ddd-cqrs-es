<?php

declare(strict_types=1);

namespace Tools\PHPat\Helpers;

final class BoundedContextDirs
{
    /**
     * @return list<string>
     */
    public static function all(string $root): array
    {
        return array_values(array_filter(
            glob($root.'/src/*/*', \GLOB_ONLYDIR) ?: [],
            static fn (string $dir): bool => 'Shared' !== basename(\dirname($dir)),
        ));
    }
}
