<?php

declare(strict_types=1);

namespace Storefront\Form;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class MapsError
{
    public function __construct(
        public string $exceptionClass,
        public string $translationId,
        public string $translationDomain,
    ) {
    }
}
