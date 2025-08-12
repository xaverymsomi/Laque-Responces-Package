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

final class ProblemDetailsTest extends TestCase
{
    public function testBuildsApplicationProblemJsonWithRequiredMembers(): void
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

        $resp = $builder->problem(
            'https://example.com/problems/out-of-stock',
            'Item Out of Stock',
            400,
            'Item #12345 is currently out of stock',
            '/orders/12345',
            ['available_at' => '2025-09-15T12:00:00Z']
        );

        $this->assertStringContainsString('application/problem+json', $resp->getHeaderLine('Content-Type'));

        $json = json_decode((string) $resp->getBody(), true);

        $this->assertEquals('https://example.com/problems/out-of-stock', $json['type']);
        $this->assertEquals('Item Out of Stock', $json['title']);
        $this->assertEquals(400, $json['status']);
        $this->assertEquals('Item #12345 is currently out of stock', $json['detail']);
        $this->assertEquals('/orders/12345', $json['instance']);
        $this->assertEquals('2025-09-15T12:00:00Z', $json['available_at']);
    }
}
