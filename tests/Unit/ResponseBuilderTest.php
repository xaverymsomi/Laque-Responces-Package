<?php

use LaqueResponses\Builder\ResponseBuilder;
use LaqueResponses\Formatters\CsvFormatter;
use LaqueResponses\Formatters\JsonFormatter;
use LaqueResponses\Formatters\NdJsonFormatter;
use LaqueResponses\Formatters\ProblemJsonFormatter;
use LaqueResponses\Formatters\TextFormatter;
use LaqueResponses\Formatters\XmlFormatter;
use LaqueResponses\Registry\FormatterRegistry;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class ResponseBuilderTest extends TestCase
{
    public function test_success_response_wraps_payload(): void
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

        $resp = $builder->success(['id' => 1, 'name' => 'John']);

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertStringContainsString('application/json', $resp->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('"status":"success"', (string) $resp->getBody());
    }
}
