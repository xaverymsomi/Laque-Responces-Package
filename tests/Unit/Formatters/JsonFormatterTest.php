<?php

declare(strict_types=1);

namespace LaqueResponses\Tests\Unit\Formatters;

use LaqueResponses\Formatters\JsonFormatter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class JsonFormatterTest extends TestCase
{
    public function testContentType(): void
    {
        $formatter = new JsonFormatter();
        $this->assertSame('application/json', $formatter->contentType());
    }

    public function testFormatNull(): void
    {
        $formatter = new JsonFormatter();
        $this->assertSame('null', $formatter->format(null));
    }

    public function testFormatScalar(): void
    {
        $formatter = new JsonFormatter();
        $this->assertSame('123', $formatter->format(123));
        $this->assertSame('"hello"', $formatter->format('hello'));
        $this->assertSame('true', $formatter->format(true));
        $this->assertSame('false', $formatter->format(false));
    }

    public function testFormatArray(): void
    {
        $formatter = new JsonFormatter();
        $data = ['name' => 'John', 'age' => 30];
        $this->assertSame('{"name":"John","age":30}', $formatter->format($data));
    }

    public function testFormatObject(): void
    {
        $formatter = new JsonFormatter();
        $data = new \stdClass();
        $data->name = 'John';
        $data->age = 30;
        $this->assertSame('{"name":"John","age":30}', $formatter->format($data));
    }

    public function testPrettyPrint(): void
    {
        $formatter = new JsonFormatter(prettyPrint: true);
        $data = ['name' => 'John', 'age' => 30];
        $expected = <<<'JSON'
{
    "name": "John",
    "age": 30
}
JSON;
        $this->assertSame($expected, $formatter->format($data));
    }

    public function testFormatInvalidJson(): void
    {
        $formatter = new JsonFormatter();
        $data = ['invalid' => NAN];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON encoding failed');

        $formatter->format($data);
    }
}
