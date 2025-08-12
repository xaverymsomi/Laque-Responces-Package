<?php

declare(strict_types=1);

namespace Tests\Unit;

use LaqueResponses\Contracts\ResponseFormatterInterface;
use LaqueResponses\Registry\FormatterRegistry;
use PHPUnit\Framework\TestCase;

final class XmlFormatterStub implements ResponseFormatterInterface
{
    public function contentType(): string
    {
        return 'application/xml';
    }

    public function format(array|object|string|int|float|bool|null $payload): string
    {
        return '<root><value>ok</value></root>';
    }
}

final class XmlFormatterTest extends TestCase
{
    public function testCustomFormatterCanBeRegisteredAndDiscoveredByContentNegotiation(): void
    {
        $registry = new FormatterRegistry();
        $registry->register(new XmlFormatterStub());

        $formatter = $registry->get('application/xml');

        $this->assertSame('application/xml', $formatter->contentType());
        $this->assertStringContainsString('<root>', $formatter->format(['x' => 1]));
    }
}
