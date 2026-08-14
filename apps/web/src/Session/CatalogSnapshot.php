<?php

declare(strict_types=1);

namespace Web\Session;

use Symfony\Component\HttpFoundation\RequestStack;
use Web\Exception\MissingCatalogSnapshotException;

final readonly class CatalogSnapshot
{
    private const string SESSION_KEY = 'sales_order.place.catalog_snapshot';

    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * @param array<string, array{label: string, unitAmountInCents: int}> $currentCatalog
     */
    public function store(array $currentCatalog): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $currentCatalog);
    }

    /**
     * @param list<array{productId: string, quantity: int}> $lines
     *
     * @return list<array{productId: string, quantity: int, label: string, unitAmountInCents: int}>
     *
     * @throws MissingCatalogSnapshotException
     */
    public function resolveLines(array $lines): array
    {
        $snapshot = $this->read();

        return array_map(
            static function (array $line) use ($snapshot): array {
                $product = $snapshot[$line['productId']] ?? throw new MissingCatalogSnapshotException($line['productId']);

                return [
                    'productId' => $line['productId'],
                    'quantity' => $line['quantity'],
                    'label' => $product['label'],
                    'unitAmountInCents' => $product['unitAmountInCents'],
                ];
            },
            $lines,
        );
    }

    /**
     * @return array<string, array{label: string, unitAmountInCents: int}>
     */
    private function read(): array
    {
        /** @var array<string, array{label: string, unitAmountInCents: int}> $snapshot */
        $snapshot = $this->requestStack->getSession()->get(self::SESSION_KEY, []);

        return $snapshot;
    }
}
