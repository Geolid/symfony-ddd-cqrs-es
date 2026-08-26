<?php

declare(strict_types=1);

namespace Tools\PHPat;

use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;

final class ArchitectureSuite
{
    /**
     * @return iterable<string, Rule>
     */
    #[TestRule]
    public function suite(): iterable
    {
        foreach ($this->testClasses() as $shortName => $class) {
            yield from $this->rulesOf($shortName, $class);
        }
    }

    /**
     * @return iterable<string, class-string>
     */
    private function testClasses(): iterable
    {
        foreach (glob(__DIR__.'/*Test.php') ?: [] as $file) {
            $shortName = basename($file, '.php');
            /** @var class-string $class */
            $class = __NAMESPACE__.'\\'.$shortName;

            yield $shortName => $class;
        }
    }

    /**
     * @param class-string $class
     *
     * @return iterable<string, Rule>
     */
    private function rulesOf(string $shortName, string $class): iterable
    {
        $instance = new $class();

        foreach (new \ReflectionClass($class)->getMethods() as $method) {
            if ([] === $method->getAttributes(TestRule::class)) {
                continue;
            }

            $result = $method->invoke($instance);
            $rules = is_iterable($result) ? $result : [$result];

            foreach ($rules as $key => $rule) {
                \assert($rule instanceof Rule);

                yield $shortName.'_'.$method->getName().(\is_string($key) ? '_'.$key : '') => $rule;
            }
        }
    }
}
