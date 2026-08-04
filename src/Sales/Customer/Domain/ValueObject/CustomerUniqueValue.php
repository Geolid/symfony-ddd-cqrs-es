<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\ValueObject;

enum CustomerUniqueValue: string
{
    case EMAIL = 'sales.customer.email';
}
