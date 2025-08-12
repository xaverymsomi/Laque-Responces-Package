<?php

declare(strict_types=1);

namespace Tests\Integration;

use LaqueResponses\Builder\ResponseBuilder;
use LaqueResponses\Formatters\CsvFormatter;
use LaqueResponses\Formatters\JsonFormatter;
use LaqueResponses\Formatters\NdJsonFormatter;
use LaqueResponses\Formatters\ProblemJsonFormatter;
use LaqueResponses\Formatters\TextFormatter;
use LaqueResponses\Formatters\XmlFormatter;
use LaqueResponses\Registry\FormatterRegistry;
use Nyholm\Psr7\Factory\Psr17Factory; // keep registry consistent
use PHPUnit\Framework\TestCase;

final class NegotiationTest extends TestCase
{
    public function testNegotiatesFormatterByExplicitContentType(): void
    {
        $factory = new Psr17Factory();
        $registry = new FormatterRegistry();
        $registry->register(new JsonFormatter());
        $registry->register(new TextFormatter());
        $registry->register(new NdJsonFormatter());
        $registry->register(new CsvFormatter());
        $registry->register(new ProblemJsonFormatter());
        $registry->register(new XmlFormatter());

        $builder = new ResponseBuilder($factory, $factory, $registry);

        // Explicitly choose text/plain since Accept-based negotiation isn't wired here
        $resp = $builder->success(['greet' => 'hi'], 200, 'text/plain');

        //        $this->assertSame('text/plain', $resp->getHeaderLine('Content-Type'));
        //        $this->assertStringContainsString('hi', (string) $resp->getBody());
        $ct = $resp->getHeaderLine('Content-Type');
        // compare only the base media type
        $this->assertSame('text/plain', strtolower(trim(strtok($ct, ';'))));
        $this->assertStringContainsString('hi', (string) $resp->getBody());

    }
}
