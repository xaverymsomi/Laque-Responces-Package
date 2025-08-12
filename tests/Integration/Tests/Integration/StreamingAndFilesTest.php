<?php

declare(strict_types=1);

namespace Tests\Integration;

use LaqueResponses\Builder\StreamResponseBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class StreamingAndFilesTest extends TestCase
{
    public function testStreamingResponseWritesToBodyProgressively(): void
    {
        $factory = new Psr17Factory();
        $builder = new StreamResponseBuilder($factory, $factory);

        $resp = $builder->stream(function ($stream) {
            $stream->write("Line 1\n");
            $stream->write("Line 2\n");
        }, 200, 'text/plain');

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertSame('text/plain', $resp->getHeaderLine('Content-Type'));
        $this->assertSame("Line 1\nLine 2\n", (string) $resp->getBody());
    }

    public function testFileDownloadSetsHeadersAndBody(): void
    {
        $factory = new Psr17Factory();
        $builder = new StreamResponseBuilder($factory, $factory);

        $tmp = tempnam(sys_get_temp_dir(), 'laq');
        file_put_contents($tmp, 'PDF bytes');

        $resp = $builder->file($tmp, 'report.pdf', 'application/pdf');

        $this->assertStringContainsString('attachment; filename="report.pdf"', $resp->getHeaderLine('Content-Disposition'));
        $this->assertSame('application/pdf', $resp->getHeaderLine('Content-Type'));
        $this->assertSame('PDF bytes', (string) $resp->getBody());

        @unlink($tmp);
    }
}
