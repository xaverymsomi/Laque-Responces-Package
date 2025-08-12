<?php

declare(strict_types=1);

namespace LaqueResponses\Registry;

use LaqueResponses\Contracts\ResponseFormatterInterface;

/**
 * Registry for response formatters
 */
final class FormatterRegistry
{
    /** @var array<string, ResponseFormatterInterface> */
    private array $formatters = [];

    public function register(ResponseFormatterInterface $formatter): void
    {
        $key = $this->normalize($formatter->contentType());
        $this->formatters[$key] = $formatter;
    }

    public function get(string $contentType): ?ResponseFormatterInterface
    {
        $key = $this->normalize($contentType);

        return $this->formatters[$key] ?? null;
    }

    /** @return list<string> */
    public function supported(): array
    {
        return array_keys($this->formatters);
    }

    private function normalize(string $type): string
    {
        $t = strtolower(trim($type));
        $semi = strpos($t, ';');

        return $semi === false ? $t : substr($t, 0, $semi);
    }
}
