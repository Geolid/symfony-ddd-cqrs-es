<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Gateway\Exception;

final class GraphQlClientException extends \RuntimeException
{
    /**
     * @param array<string, scalar|array<scalar>> $variables
     */
    public static function networkFailure(string $vendor, string $query, array $variables, string $reason): self
    {
        return new self(\sprintf(
            "%s network failure: %s\nQuery: %s\nVariables: %s",
            $vendor,
            $reason,
            $query,
            json_encode($variables),
        ));
    }

    /**
     * @param array<string, scalar|array<scalar>> $variables
     */
    public static function invalidResponse(string $vendor, string $query, array $variables, string $reason): self
    {
        return new self(\sprintf(
            "%s invalid response: %s\nQuery: %s\nVariables: %s",
            $vendor,
            $reason,
            $query,
            json_encode($variables),
        ));
    }

    /**
     * @param array<string, scalar|array<scalar>> $variables
     * @param array<array{message: string, ...}>  $errors
     */
    public static function graphQlError(string $vendor, string $query, array $variables, array $errors): self
    {
        return new self(\sprintf(
            "%s GraphQL error: %s\nQuery: %s\nVariables: %s",
            $vendor,
            implode(\PHP_EOL, array_column($errors, 'message')),
            $query,
            json_encode($variables),
        ));
    }
}
