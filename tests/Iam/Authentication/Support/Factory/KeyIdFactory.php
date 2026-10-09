<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Support\Factory;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<KeyId>
 */
final class KeyIdFactory extends ObjectFactory
{
    public static function class(): string
    {
        return KeyId::class;
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('fromString'));
    }

    protected function defaults(): array
    {
        return ['value' => KeyId::PREFIX.bin2hex(random_bytes(8))];
    }
}
