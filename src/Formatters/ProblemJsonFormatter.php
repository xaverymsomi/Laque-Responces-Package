<?php

namespace LaqueResponses\Formatters;

use LaqueResponses\Contracts\ResponseFormatterInterface;

final class ProblemJsonFormatter implements ResponseFormatterInterface
{
    public function __construct(
        private readonly bool $prettyPrint = false
    ) {
    }

    public function contentType(): string
    {
        return 'application/problem+json';
    }

    public function format(array|object|string|int|float|bool|null $payload): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($payload, $flags) ?: '{}';
    }
}
