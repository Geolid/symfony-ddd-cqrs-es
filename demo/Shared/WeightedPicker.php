<?php

declare(strict_types=1);

namespace Demo\Shared;

final class WeightedPicker
{
    /**
     * @param array<string, int> $weights
     */
    public static function pick(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;

            if ($rand <= $cumulative) {
                return $key;
            }
        }

        throw new \LogicException('Invalid weights distribution.');
    }
}
