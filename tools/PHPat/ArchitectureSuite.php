<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;

final class ArchitectureSuite
{
    /**
     * @return iterable<Rule>
     */
    #[TestRule]
    public function suite(): iterable
    {
        foreach (glob(__DIR__.'/*Test.php') ?: [] as $file) {
            $shortName = basename($file, '.php');
            /** @var class-string $class */
            $class = __NAMESPACE__.'\\'.$shortName;
            $instance = new $class();

            foreach (new \ReflectionClass($class)->getMethods() as $method) {
                if ([] !== $method->getAttributes(TestRule::class)) {
                    $result = $method->invoke($instance);
                    $rules = is_iterable($result) ? $result : [$result];

                    foreach ($rules as $key => $rule) {
                        \assert($rule instanceof Rule);

                        yield $shortName.'_'.$method->getName().(\is_string($key) ? '_'.$key : '') => $rule;
                    }
                }
            }
        }
    }
}
