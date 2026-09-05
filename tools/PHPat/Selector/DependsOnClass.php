<?php

declare(strict_types=1);

namespace Tools\PHPat\Selector;

use PHPat\Selector\SelectorInterface;
use PHPStan\Reflection\ClassReflection;

final readonly class DependsOnClass implements SelectorInterface
{
    public function __construct(private string $classname)
    {
    }

    public function getName(): string
    {
        return $this->classname;
    }

    public function matches(ClassReflection $classReflection): bool
    {
        $constructor = $classReflection->getNativeReflection()->getConstructor();

        if (!$constructor) {
            return false;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && $type->getName() === $this->classname) {
                return true;
            }
        }

        return false;
    }
}
