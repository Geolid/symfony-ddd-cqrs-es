<?php

declare(strict_types=1);

namespace Sales\Customer\Domain\ValueObject;

enum CustomerUniqueKey: string
{
    case EMAIL = 'sales.customer.email';
}
