<?php

declare(strict_types=1);

/**
 * @param array<mixed> $lineItems
 *
 * @return list<string>
 */
function fake_globex_line_items_currencies(array $lineItems): array
{
    return array_values(array_unique(array_map(static function (mixed $item): string {
        $item = is_array($item) ? $item : [];
        $priceData = is_array($item['price_data'] ?? null) ? $item['price_data'] : [];

        return is_string($priceData['currency'] ?? null) ? $priceData['currency'] : '';
    }, $lineItems)));
}

/**
 * @param array<mixed> $lineItems
 */
function fake_globex_line_items_amount_in_cents(array $lineItems): int
{
    return array_reduce($lineItems, static function (int $carry, mixed $item): int {
        $item = is_array($item) ? $item : [];
        $priceData = is_array($item['price_data'] ?? null) ? $item['price_data'] : [];
        $unitAmount = filter_var($priceData['unit_amount'] ?? 0, \FILTER_VALIDATE_INT) ?: 0;
        $quantity = filter_var($item['quantity'] ?? 1, \FILTER_VALIDATE_INT) ?: 1;

        return $carry + $unitAmount * $quantity;
    }, 0);
}
